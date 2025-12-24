<?php
namespace Drupal\smplphotoalbum\Controller;

/*
 * Long process breaking
 */
class BreakTime{
	private $d = 20;
	private $path = "";
	private $sign = "";
	private $lastTime;			// LastTime
	private $max = 1000;		// end of counter
	private $percent = 0;		// percent
	private $startTime;			// beginning of process
	private $last;					// Last Write into the sign file
	private $log = false;		// do you want a log into PHP
	private $freq;					// frequency of examination
	public $cnt = 0;				// counter

	function __construct($path = "", $sign = "" , $freq = 100 , $log = false, $max = 100){
		$this->path = $path;
		$this->sign = $sign;
		$this->freq = $freq;
		$this->log  = $log;
		$this->max  = $max;
		$this->startTime = time();
		$this->last = $this->startTime;
		$this->lastTime = time() + ini_get ( "max_execution_time" ) - 10;		
		$this->write_sign(0);
		if( $this->log ) error_log("\nStart execution\n-------------");
	}

	/**
	 * Summary of setFreq
	 * @param int $freq
	 * @return void
	 */
	function setFreq(int $freq){
		$this->freq = $freq;
	}

	/**
	 * set the max value of counter
	 * @param int $max
	 * @return void
	 */
	function setMax(int $max){
		$this->max = $max;
	}

	/**
	 * set logging true/false
	 * @param boolean $log
	 * @return void
	 */
	function setLog(bool $log){
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
			$this->write_sign( $v, $e );
			$this->logEvent($e, $ti);
			return true;
	  }

		if( $v > $this->max ){
			$e = "Reach the max execution count";
			$this->write_sign( $v, $e );
			$this->logEvent( $e, $ti );
			return true;
		}

	  if ( $this->cnt % $this->freq == 0 ) {
			$this->write_sign($v);

			if( $this->log && !empty($v) && ( $ti != $this->last ) ) {
				error_log("Time: ". ( ($ti - $this->startTime) ) ." sec, " . round(( $this->freq/( $ti - $this->last ) ),2 ) . " round/sec, ". $this->perc );
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
	function write_sign( $r = 100, $event = "" ){
		static $change = 0;		
		$this->percent = round( 100 * $r / $this->max )."%";
		if ( $change != $this->percent || !empty($event) ){
			$change = $this->percent;
			$out = $this->percent . " - ". $event;
			file_put_contents( $this->path . $this->sign, $this->percent );
		}		
	}

	/**
	 * reason of break
	 * @param string $event
	 * @return void
	 */
	function LogEvent( $event, $ti ){		
		if($this->log){
			error_log("Reason: '$event': Time: ". ( ($ti - $this->startTime) ) ." sec, " . round(( $this->freq/( $ti - $this->last ) ),2 ) . " round/sec, ". $this->perc );			
		}			
	}
}