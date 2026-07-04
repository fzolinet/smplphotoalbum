<?php
/*
 * This file is part of PHP-FFmpeg.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FFMpeg\Media;

use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use FFMpeg\Exception\InvalidArgumentException;
use FFMpeg\Exception\RuntimeException;
use FFMpeg\Filters\Audio\SimpleFilter;
use FFMpeg\Filters\FilterInterface;
use FFMpeg\Format\AudioInterface;
use FFMpeg\Format\FormatInterface;
use FFMpeg\Format\ProgressableInterface;
use Spatie\TemporaryDirectory\TemporaryDirectory;

abstract class AbstractAudio extends Audio
{
    private $defaultSettings = null;
    /**
     * FileSystem Manager instance.
     *
     * @var Manager
     */
    protected $fs;

    /**
     * FileSystem Manager ID.
     *
     * @var int
     */
    protected $fsId;        

    /**
     * {@inheritDoc}
     *
     * @return VideoFilters
     */
    public function filters()
    {
        return new VideoFilters($this);
    }

    /**
     * {@inheritDoc}
     *
     * @return Video
     */
    public function addFilter(FilterInterface $filter)
    {
        $this->filters->add($filter);

        return $this;
    }

    /**
     * Exports the video in the desired format, applies registered filters.
     *
     * @param string $outputPathfile
     *
     * @return Video
     *
     * @throws RuntimeException
     */
    public function save(FormatInterface $format, $outputPathfile)
    {
        $passes = $this->buildCommand($format, $outputPathfile);

        $failure = null;
        $totalPasses = $format->getPasses();

        foreach ($passes as $pass => $passCommands) {
            try {
                /** add listeners here */
                $listeners = null;

                if ($format instanceof ProgressableInterface) {
                    $filters = clone $this->filters;
                    $duration = 0;

                    // check the filters of the video, and if the video has the ClipFilter then
                    // take the new video duration and send to the
                    // FFMpeg\Format\ProgressListener\AbstractProgressListener class
                    foreach ($filters as $filter) {
                        if ($filter instanceof ClipFilter) {
                            if (null === $filter->getDuration()) {
                                continue;
                            }

                            $duration = $filter->getDuration()->toSeconds();
                            break;
                        }
                    }
                    $listeners = $format->createProgressListener($this, $this->ffprobe, $pass + 1, $totalPasses, $duration);
                }

                $this->driver->command($passCommands, false, $listeners);
            } catch (ExecutionFailureException $e) {
                $failure = $e;
                break;
            }
        }

        $this->fs->delete();

        if (null !== $failure) {
            throw new RuntimeException('Encoding failed', $failure->getCode(), $failure);
        }

        return $this;
    }

    /**
     * NOTE: This method is different to the Audio's one, because Video is using passes.
     * {@inheritDoc}
     */
    public function getFinalCommand(FormatInterface $format, $outputPathfile)
    {
        $finalCommands = [];

        foreach ($this->buildCommand($format, $outputPathfile) as $pass => $passCommands) {
            $finalCommands[] = implode(' ', $passCommands);
        }

        $this->fs->delete();

        return $finalCommands;
    }

    /**
     * **NOTE:** This creates passes instead of a single command!
     *
     * {@inheritDoc}
     *
     * @return string[][]
     */
    protected function buildCommand(FormatInterface $format, $outputPathfile)
    {
        $commands = $this->basePartOfCommand($format);

        $filters = clone $this->filters;
        $filters->add(new SimpleFilter($format->getExtraParams(), 10));

        if ($this->driver->getConfiguration()->has('ffmpeg.threads')) {
            $filters->add(new SimpleFilter(['-threads', $this->driver->getConfiguration()->get('ffmpeg.threads')]));
        }
        if ($format instanceof VideoInterface) {
            if (null !== $format->getVideoCodec()) {
                $filters->add(new SimpleFilter(['-vcodec', $format->getVideoCodec()]));
            }
        }
        if ($format instanceof AudioInterface) {
            if (null !== $format->getAudioCodec()) {
                $filters->add(new SimpleFilter(['-acodec', $format->getAudioCodec()]));
            }
        }

        foreach ($filters as $filter) {
            $commands = array_merge($commands, $filter->apply($this, $format));
        }

        if ($format instanceof VideoInterface) {
            if (0 !== $format->getKiloBitrate()) {
                $commands[] = '-b:v';
                $commands[] = $format->getKiloBitrate().'k';
            }

        // changed by FZ to allow custom config file for video encoding, if not set use default settings
            $def = $this->defaultSettings($this->defaultSettings);
            $commands = array_merge($commands, $def);
        // changed by FZ to allow custom config file for video encoding, if not set use default settings
        }
        

        if ($format instanceof AudioInterface) {
            if (null !== $format->getAudioKiloBitrate()) {
                $commands[] = '-b:a';
                $commands[] = $format->getAudioKiloBitrate().'k';
            }
            if (null !== $format->getAudioChannels()) {
                $commands[] = '-ac';
                $commands[] = $format->getAudioChannels();
            }
        }

        // If the user passed some additional parameters
        if ($format instanceof VideoInterface) {
            if (null !== $format->getAdditionalParameters()) {
                foreach ($format->getAdditionalParameters() as $additionalParameter) {
                    $commands[] = $additionalParameter;
                }
            }
        }

        // Merge Filters into one command
        $videoFilterVars = $videoFilterProcesses = [];
        for ($i = 0; $i < count($commands); ++$i) {
            $command = $commands[$i];
            if ('-vf' === $command) {
                $commandSplits = explode(';', $commands[$i + 1]);
                if (1 == count($commandSplits)) {
                    $commandSplit = $commandSplits[0];
                    $command = trim($commandSplit);
                    if (preg_match("/^\[in\](.*?)\[out\]$/is", $command, $match)) {
                        $videoFilterProcesses[] = $match[1];
                    } else {
                        $videoFilterProcesses[] = $command;
                    }
                } else {
                    foreach ($commandSplits as $commandSplit) {
                        $command = trim($commandSplit);
                        if (preg_match("/^\[[^\]]+\](.*?)\[[^\]]+\]$/is", $command, $match)) {
                            $videoFilterProcesses[] = $match[1];
                        } else {
                            $videoFilterVars[] = $command;
                        }
                    }
                }
                unset($commands[$i]);
                unset($commands[$i + 1]);
                ++$i;
            }
        }
        $videoFilterCommands = $videoFilterVars;
        $lastInput = 'in';
        foreach ($videoFilterProcesses as $i => $process) {
            $command = '['.$lastInput.']';
            $command .= $process;
            $lastInput = 'p'.$i;
            if ($i === (count($videoFilterProcesses) - 1)) {
                $command .= '[out]';
            } else {
                $command .= '['.$lastInput.']';
            }

            $videoFilterCommands[] = $command;
        }
        $videoFilterCommand = implode(';', $videoFilterCommands);

        if ($videoFilterCommand) {
            $commands[] = '-vf';
            $commands[] = $videoFilterCommand;
        }

        $this->fsId = uniqid('ffmpeg-passes');
        $this->fs = $this->getTemporaryDirectory()->name($this->fsId)->create();
        $passPrefix = $this->fs->path(uniqid('pass-'));
        touch($passPrefix);
        $passes = [];
        $totalPasses = $format->getPasses();

        if (!$totalPasses) {
            throw new InvalidArgumentException('Pass number should be a positive value.');
        }

        for ($i = 1; $i <= $totalPasses; ++$i) {
            $pass = $commands;

            if ($totalPasses > 1) {
                $pass[] = '-pass';
                $pass[] = $i;
                $pass[] = '-passlogfile';
                $pass[] = $passPrefix;
            }

            $pass[] = $outputPathfile;

            $passes[] = $pass;
        }

        return $passes;
    }

    /**
     * Get the default settings from software
     * @return string 
     */
    public function getDefaultSettings($getString = false){
        $cmds = $this->defaultSettings("", $getString);
        if(is_array($cmds)){
            $cmds = implode(" ", $cmds);
        }        
        return $cmds;
    }

    /**
     * Modified by FZ to allow custom config file for video encoding, if not set use default settings
     * Set the default config file:
     * array => settings
     * string => path-to-config-file
     * string => one line config string, separated by space
     * otherwise the original default parameters are used
     * @param mixed $defaultSettings 
     * @return void 
     */
    public function setDefaultSettings( $defaultSettings ) 
    {
        $this->defaultSettings = $defaultSettings;
    }

    /**
     * change by FZ to allow custom config file for video encoding, if not set use default settings
     * 
     * Default Settings are the default parameters for video encoding, it can be set by user or use default settings. 
     * If it is an array then the defalt parameters ar these elements of array
     * If it is a string and it is a path-to-config-file it read and make commands
     * If it is a one line config string then it split by space and make commands
     * otherwise the original default parameters are used
     * 
     * @param array | string $defaultSettings 
     * @return array | string
     */
    public function defaultSettings( $defaultSettings = [], $getString = false )
    {
        $cmds = [];
        
        // I give the default parameters to the video encoding, if not set use default settings
        if( is_array($defaultSettings) && count($defaultSettings) > 0) {
            $cmds = &$defaultSettings;
        
        // config file: it can be a text file. every row is a parameter. lines starting with # are comments, and ; is used to add comments at the end of a line.
        } else if( is_string($defaultSettings ) && file_exists($defaultSettings) )  {
        
            $defcommands = file( $defaultSettings);

            foreach($defcommands as $defcommand) {
                $defcommand = trim($defcommand);
                if( strlen($defcommand) > 0 && $defcommand[0] != '#') {
                    if( ($pos = strpos($defcommand, ';') ) !== false ) {
                        $defcommand = trim(substr($defcommand, 0, $pos));
                    }
                    $cmds[] = $defcommand;
                }
            }

        // default parameters in a row, separated by space
        } else if( is_string($defaultSettings) && strlen($defaultSettings) > 0 )  {

            $defcommands = explode(" ", $defaultSettings);
            foreach($defcommands as $defcommand) {
                $defcommand = trim($defcommand);
                if( strlen($defcommand) > 0 ) {
                    $cmds[] = $defcommand;
                }
            }
        
            // Original variables if no default settings are set
        } else{
                $cmds[] = '-refs';
                $cmds[] = '6';
                $cmds[] = '-coder';
                $cmds[] = '1';
                $cmds[] = '-sc_threshold';
                $cmds[] = '40';
                $cmds[] = '-flags';
                $cmds[] = '+loop';
                $cmds[] = '-me_range';
                $cmds[] = '16';
                $cmds[] = '-subq';
                $cmds[] = '7';
                $cmds[] = '-i_qfactor';
                $cmds[] = '0.71';
                $cmds[] = '-qcomp';
                $cmds[] = '0.6';
                $cmds[] = '-qdiff';
                $cmds[] = '4';
                $cmds[] = '-trellis';
                $cmds[] = '1';
        }
        
        if( $getString ){
            $cmds = implode(" ", $cmds);            
        }
        return $cmds;
    }

    /**
     * Return base part of command.
     *
     * @return array
     */
    protected function basePartOfCommand(FormatInterface $format)
    {
        $commands = ['-y'];

        // If the user passed some initial parameters
        if ($format instanceof VideoInterface) {
            if (null !== $format->getInitialParameters()) {
                foreach ($format->getInitialParameters() as $initialParameter) {
                    $commands[] = $initialParameter;
                }
            }
        }

        $commands[] = '-i';
        $commands[] = $this->pathfile;

        return $commands;
    }
}
