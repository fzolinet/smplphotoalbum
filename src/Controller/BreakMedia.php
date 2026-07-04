<?php 
namespace Drupal\smplphotoalbum\Controller;

class BreakMedia{
	private $d = 20;
	private $path = "";
	private $sign = "";
	private $lastTime = 0;			// LastTime
	private $max = 1000;		// end of counter
	private $percent = 0;		// percent
	private $startTime = 0;	// beginning of process
	private $last = 0;			// Last Write into the sign file
	private $log = false;		// do you want a log into PHP
	private $logfile = "";
	private $freq = 100;		// frequency of examination
	public $cnt = 0;				// counter	

	function __construct($path = "", $sign = "" , $freq = 100 , $log = false, $max = 100){
		$this->path = $path;
		$this->sign = $sign;
		$this->freq = $freq;
		$this->log  = $log;
		$this->logfile = $this->path . "error.log";
		$this->max  = $max;
		$this->startTime = time();
		$this->last = $this->startTime;
		$this->lastTime = time() + ini_get ( "max_execution_time" ) - 10;		
		$this->writeSign(0);
		if( $this->log ) error_log("\nStart execution\n-------------",3,$this->path);
	}

	/**
	 * Summary of setFreq
	 * @param int $freq
	 * @return void
	 */
	public function setFreq(int $freq){
		$this->freq = $freq;
	}

	/**
	 * set the max value of counter
	 * @param int $max
	 * @return void
	 */
	public function setMax(int $max){
		$this->max = $max;
	}

	/**
	 * set logging true/false
	 * @param boolean $log
	 * @return void
	 */
	public function setLog(bool $log){
		$this->log = $log;
	}

	/**
	 * Examination of break
	 * @param mixed $v
	 * @return bool
	 */
	public function Break($v = ""){
		static $ti;

		$ti = time();

	  // if the time is over or client break the process
		if( $ti > $this->lastTime ) {
			$e = "Reach the max execution time";
			$this->writeSign( $v, $e );
			$this->logEvent( $ti, $e );
			return true;
	  }

		if( $v > $this->max ){
			$e = "Reach the max value";
			$this->writeSign( $v, $e );
			$this->logEvent( $ti, $e);
			return true;
		}

	  if ( $this->cnt % $this->freq == 0 ) {
			$this->writeSign($v);

			if( $this->log && !empty($v) && ( $ti != $this->last ) ) {
				error_log("Time: ". ( ($ti - $this->startTime) ) 
					." sec, " 
					.round( ( $this->freq/( $ti - $this->last ) ),2 ) 
					." round/sec, ". $this->percent 
					.PHP_EOL,
					3,
					$this->logfile
				);
				$this->last = $ti;
			}

		  if ( !file_exists ( $this->path . $this->sign )) {
		  	return true;
	    }
		}
	  return false;
	}

	/**
	 * Write data into the sign file
	 * @param mixed $r
	 * @return void
	 */
	public function writeSign( $r = 100, $event = "" ){
		static $change = 0;	
		if($change == 0){
			file_put_contents( $this->path . $this->sign, $this->percent ."%");
		}

		$this->percent = round( 100 * $r / $this->max );
		if ( ($change < $this->percent) || !empty($event) ){
			$change = $this->percent;	
			if( file_exists($this->path . $this->sign ) && is_writable($this->path . $this->sign )	)
			{	
				file_put_contents( $this->path . $this->sign, $this->percent ."%");
			}
		}		
	}

	/**
	 * reason of break
	 * @param string $event
	 * @return void
	 */
	function LogEvent( $ti, $event ){		
		if($this->log){
			error_log(
				"Event: '$event': Time: " .  ($ti - $this->startTime) . " sec, " . 
				round(( $this->freq/( $ti - $this->last ) ),2 ) . " round/sec, ". $this->percent,
				3,
				$this->logfile
			);			
		}			
	}
}