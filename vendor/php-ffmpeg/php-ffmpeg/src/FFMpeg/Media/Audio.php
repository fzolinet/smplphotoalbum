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
use FFMpeg\Filters\Audio\AudioFilterInterface;
use FFMpeg\Filters\Audio\AudioFilters;
use FFMpeg\Filters\Audio\SimpleFilter;
use FFMpeg\Filters\FilterInterface;
use FFMpeg\Format\FormatInterface;
use FFMpeg\Format\ProgressableInterface;

class Audio extends AbstractStreamableMedia
{
    private $defaultSettings = null;
    /**
     * {@inheritdoc}
     *
     * @return AudioFilters
     */
    public function filters()
    {
        return new AudioFilters($this);
    }

    /**
     * {@inheritdoc}
     *
     * @return Audio
     */
    public function addFilter(FilterInterface $filter)
    {
        if (!$filter instanceof AudioFilterInterface) {
            throw new InvalidArgumentException('Audio only accepts AudioFilterInterface filters');
        }

        $this->filters->add($filter);

        return $this;
    }

    /**
     * Exports the audio in the desired format, applies registered filters.
     *
     * @param string $outputPathfile
     *
     * @return Audio
     *
     * @throws RuntimeException
     */
    public function save(FormatInterface $format, $outputPathfile)
    {
        $listeners = null;

        if ($format instanceof ProgressableInterface) {
            $listeners = $format->createProgressListener($this, $this->ffprobe, 1, 1, 0);
        }

        $commands = $this->buildCommand($format, $outputPathfile);

        try {
            $this->driver->command($commands, false, $listeners);
        } catch (ExecutionFailureException $e) {
            $this->cleanupTemporaryFile($outputPathfile);
            throw new RuntimeException('Encoding failed', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * Returns the final command as a string, useful for debugging purposes.
     *
     * @param string $outputPathfile
     *
     * @return string
     *
     * @since 0.11.0
     */
    public function getFinalCommand(FormatInterface $format, $outputPathfile)
    {
        return implode(' ', $this->buildCommand($format, $outputPathfile));
    }

    /**
     * Builds the command which will be executed with the provided format.
     *
     * @param string $outputPathfile
     *
     * @return string[] An array which are the components of the command
     *
     * @since 0.11.0
     */
    protected function buildCommand(FormatInterface $format, $outputPathfile)
    {
        $commands = ['-y', '-i', $this->pathfile];

        $filters = clone $this->filters;
        $filters->add(new SimpleFilter($format->getExtraParams(), 10));

        if ($this->driver->getConfiguration()->has('ffmpeg.threads')) {
            $filters->add(new SimpleFilter(['-threads', $this->driver->getConfiguration()->get('ffmpeg.threads')]));
        }
        if (null !== $format->getAudioCodec()) {
            $filters->add(new SimpleFilter(['-acodec', $format->getAudioCodec()]));
        }

        foreach ($filters as $filter) {
            $commands = array_merge($commands, $filter->apply($this, $format));
        }

        if (null !== $format->getAudioKiloBitrate()) {
            $commands[] = '-b:a';
            $commands[] = $format->getAudioKiloBitrate().'k';
        }
        if (null !== $format->getAudioChannels()) {
            $commands[] = '-ac';
            $commands[] = $format->getAudioChannels();
        }
        $commands[] = $outputPathfile;

        // changed by FZ
        $def = $this->defaultSettings( $this->defaultSettings );
        $commands =array_merge($commands, $def);
        return $commands;
    }

    /**
     * get the default settings from software
     * @return string|false 
     */
    public function getDefaultSettings(){
        $cmds = $this->defaultSettings("", $getString);
        if(is_array($cmds)){
            $cmds = implode(" ", $cmds);
        }        
        return $cmds;
    }

    /**
     * Sets the default settings for the audio processing.
     *
     * @param string $defaultSettings The default settings as a string or file path.
     */
    public function setDefaultSettings( $defaultSettings = ""){
        $this->defaultSettings = $defaultSettings;
    }
    
    /**
     * Made by FZ.
     * Default settings are the default parameters for audio encoding
     * @param array | string $defaultSettings      
     * @return array|false 
     */
    public function defaultSettings($defaultSettings = [], $getString = false){
        $cmds = [];

        // The array is the default settings
        if(is_array($defaultSettings) && count($defaultSettings() >0 )){
            $cmds = &$defaultSettings;
        
        // The string is file path to a file with the default settings
        } else if( is_string($defaultSettings) && strlen($defaultSettings) > 0 && file_exists($defaultSettings) ){
            $cmds = file($defaultSettings, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach( $cmds AS $i => $cmd ){
                $cmd = trim($cmd);
                if($strlen($cmd) >0 && substr($cmd, 0, 1) == "#" ){
                    unset($cmds[$i]);
                } else if( $pos = strpos($cmd, ';') !== false ){                
                    $cmds[$i] = trim( substr( $cmd, 0, $pos ) );
                }
            }
        
        // The string is the default settings
        } else if( is_string($defaultSettings) && strlen($defaultSettings) > 0 ){
            $cmds = explode(" ", $defaultSettings);

        // The format is used to get the default settings
        } else{
            if ($format instanceof AudioInterface) {
                if (null !== $format->getAudioKiloBitrate()) {
                    $cmds[] = '-b:a';
                    $cmds[] = $format->getAudioKiloBitrate().'k';
                }
                if (null !== $format->getAudioChannels()) {
                    $cmds[] = '-ac';
                    $cmds[] = $format->getAudioChannels();
                }
            }
        }         

        return $cmds;
    }
    /**
     * Gets the waveform of the video.
     *
     * @param int   $width
     * @param int   $height
     * @param array $colors Array of colors for ffmpeg to use. Color format is #000000 (RGB hex string with #)
     *
     * @return Waveform
     */
    public function waveform($width = 640, $height = 120, $colors = [Waveform::DEFAULT_COLOR])
    {
        return new Waveform($this, $this->driver, $this->ffprobe, $width, $height, $colors);
    }

    /**
     * Concatenates a list of audio files into one unique audio file.
     *
     * @param array $sources
     *
     * @return Concat
     */
    public function concat($sources)
    {
        return new Concat($sources, $this->driver, $this->ffprobe);
    }
}
