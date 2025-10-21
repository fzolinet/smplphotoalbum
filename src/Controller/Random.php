<?php
/* Distributions
 *
 * https://stackoverflow.com/questions/3109670/generate-random-numbers-with-probabilistic-distribution
 *
 */

namespace Drupal\smplphotoalbum\Controller;

class Random{
  private $Method;
  private $dist =Array();
  
  function __construct($type){
    $this->Method = $type;
  }
  /*
   *  returns random number using mt_rand() with a flat distribution from 0 to 1 inclusive
   */
  function random_0_1() {
    return (float) mt_rand() / (float) mt_getrandmax() ;
  }
  
  /*
   *   returns random number using mt_rand() with a flat distribution from -1 to 1 inclusive
   */
  function random_PN()  {
    return (2.0 * $this->random_0_1()) - 1.0 ;
  }  
     
  function gauss() {
    static $useExists = false ;
    static $useValue ;
    
    if ($useExists) {
      //  Use value from a previous call to this function
      //
      $useExists = false ;
      return $useValue ;
    } else {
      //  Polar form of the Box-Muller transformation
      //
      $w = 2.0 ;
      while (($w >= 1.0) || ($w == 0.0)) {
        $x = $this->random_PN() ;
        $y = $this->random_PN() ;
        $w = ($x * $x) + ($y * $y) ;
      }
      $w = sqrt((-2.0 * log($w)) / $w) ;
      
      //  Set value for next call to this function
      //
      $useValue = $y * $w ;
      $useExists = true ;
      
      return $x * $w ;
    }
  } 
  
  //  Adjust our gaussian random to fit the mean and standard deviation
  //  The division by 4 is an arbitrary value to help fit the distribution
  //      within our required range, and gives a best fit for $stddev = 1.0
  //
  function gauss_ms( $mean, $stddev ) {
    return $this->gauss() * ($stddev/4) + $mean;
  } 
  
  /*
   *   Adjust a gaussian random value to fit within our specified range
   *   by 'trimming' the extreme values as the distribution curve
   *   approaches +/- infinity
   */
  function gaussianWeightedRandom( $min,$max,$mean=0.0,$stddev=2.0 ) {
    $rand_val = $min + $max ;
    while (($rand_val < $min) || ($rand_val >= ($min + $max))) {
      $rand_val = floor($this->gauss_ms($mean,$stddev) * $max) + $min ;
      $rand_val = ($rand_val + $max) / 2 ;
    }
    
    return $rand_val ;
  }
  
  function bellWeightedRandom( $min, $max ) {
    return $this->gaussianWeightedRandom( $min, $max, 0.0, 1.0 ) ;
  } 
  /**
   * Adjust a gaussian random value to fit within our specified range
   * by 'trimming' the extreme values as the distribution curve
   * approaches +/- infinity
   * The division by 4 is an arbitrary value to help fit the distribution
   * within our required range
   * 
   * @param unknown $min
   * @param unknown $max
   * @return number
   */
  function gaussianWeightedRisingRandom( $min, $max ) {
   $rand_val = $min + $max ;
    while (($rand_val < $min) || ($rand_val >= ($min + $max))) {
      $rand_val = $max - round((abs($this->gauss()) / 4) * $max) + $min ;
    }
    
    return $rand_val ;
  }
  
  /**
   * Adjust a gaussian random value to fit within our specified range
   * by 'trimming' the extreme values as the distribution curve
   * approaches +/- infinity
   * The division by 4 is an arbitrary value to help fit the distribution
   * within our required range
   */
  function gaussianWeightedFallingRandom( $min, $max ) {
    $rand_val = $min + $max ;
    while (($rand_val < $min) || ($rand_val >= ($min + $max))) {
      $rand_val = floor((abs($this->gauss()) / 4) * $max) + $min ;
    }    
    return $rand_val ;
  }
  
  function logarithmic($mean=1.0, $lambda=5.0) {
    return ($mean * -log($this->random_0_1())) / $lambda ;
  }
  
  function logarithmicWeightedRandom( $min, $max ) {
    do {
      $rand_val = $this->logarithmic() ;
    } while ($rand_val > 1) ;
    
    return floor($rand_val * $max) + $min ;
  }
  
  function logarithmic10( $lambda=0.5 ) {
    return abs(-log10($this->random_0_1()) / $lambda) ;
  }
  
  function logarithmic10WeightedRandom( $min, $max ) {
    do {
      $rand_val = $this->logarithmic10() ;
    } while ($rand_val > 1) ;
    
    return floor($rand_val * $max) + $min ;
  }
  
  function gamma( $lambda=3.0 ) {
    $wLambda = $lambda + 1.0 ;
    if ($lambda <= 8.0) {
      //  Use direct method, adding waiting times
      $x = 1.0 ;
      for ($j = 1; $j <= $wLambda; $j++) {
        $x *= $this->random_0_1() ;
      }
      $x = -log($x) ;
    } else {
      //  Use rejection method
      do {
        do {
          //  Generate the tangent of a random angle, the equivalent of
          //      $y = tan(pi * random_0_1())
          do {
            $v1 = $this->random_0_1() ;
            $v2 = $this->random_PN() ;
          } while (($v1 * $v1 + $v2 * $v2) > 1.0) ;
          $y = $v2 / $v1 ;
          $s = sqrt(2.0 * $lambda + 1.0) ;
          $x = $s * $y + $lambda ;
          //  Reject in the region of zero probability
        } while ($x <= 0.0) ;
        //  Ratio of probability function to comparison function
        $e = (1.0 + $y * $y) * exp($lambda * log($x / $lambda) - $s * $y) ;
        //  Reject on the basis of a second uniform deviate
      } while ($this->random_0_1() > $e) ;
    }
    
    return $x ;
  }
  
  function gammaWeightedRandom( $min, $max ) {
    do {
      $rand_val = $this->gamma() / 12 ;
    } while ($rand_val > 1) ;
    
    return floor($rand_val * $max) + $min ;
  }
  
  function QaDgammaWeightedRandom( $min, $max ) {
    return round((asin($this->random_0_1()) + (asin($this->random_0_1()))) * $max / pi()) + $min ;
  }
  
  function gammaln($in)
  {
    $tmp = $in + 4.5 ;
    $tmp -= ($in - 0.5) * log($tmp) ;
    
    $ser = 1.000000000190015
    + (76.18009172947146 / $in)
    - (86.50532032941677 / ($in + 1.0))
    + (24.01409824083091 / ($in + 2.0))
    - (1.231739572450155 / ($in + 3.0))
    + (0.1208650973866179e-2 / ($in + 4.0))
    - (0.5395239384953e-5 / ($in + 5.0)) ;
    
    return (log(2.5066282746310005 * $ser) - $tmp) ;
  }
  
  function poisson( $lambda=1.0 ) {
    static $oldLambda ;
    static $g, $sq, $alxm ;
    
    if ($lambda <= 12.0) {
      //  Use direct method
      if ($lambda <> $oldLambda) {
        $oldLambda = $lambda ;
        $g = exp(-$lambda) ;
      }
      $x = -1 ;
      $t = 1.0 ;
      do {
        ++$x ;
        $t *= $this->random_0_1() ;
      } while ($t > $g) ;
    } else {
      //  Use rejection method
      if ($lambda <> $oldLambda) {
        $oldLambda = $lambda ;
        $sq = sqrt(2.0 * $lambda) ;
        $alxm = log($lambda) ;
        $g = $lambda * $alxm - $this->gammaln($lambda + 1.0) ;
      }
      do {
        do {
          //  $y is a deviate from a Lorentzian comparison function
          $y = tan(pi() * $this->random_0_1()) ;
          $x = $sq * $y + $lambda ;
          //  Reject if close to zero probability
        } while ($x < 0.0) ;
        $x = floor($x) ;
        //  Ratio of the desired distribution to the comparison function
        //  We accept or reject by comparing it to another uniform deviate
        //  The factor 0.9 is used so that $t never exceeds 1
        $t = 0.9 * (1.0 + $y * $y) * exp($x * $alxm - $this->gammaln($x + 1.0) - $g) ;
      } while ($this->random_0_1() > $t) ;
    }
    
    return $x ;
  }
  
  function poissonWeightedRandom( $min, $max ) {
    do {
      $rand_val = $this->poisson() / $max ;
    } while ($rand_val > 1) ;
    
    return floor($x * $max) + $min ;
  }
  
  function binomial( $lambda=6.0 ) {
  }
  
  function domeWeightedRandom( $min, $max ) {
    return floor(sin($this->random_0_1() * (pi() / 2)) * $max) + $min ;
  }
  
  function sawWeightedRandom( $min, $max ) {
    return floor((atan(random_0_1()) + atan($this->random_0_1())) * $max / (pi()/2)) + $min ;
  }
  
  function pyramidWeightedRandom( $min, $max ){
    return floor(($this->random_0_1() + $this->random_0_1()) / 2 * $max) + $min ;
  }
  
  function linearWeightedRandom( $min, $max ) {
    return floor($this->random_0_1() * ($max)) + $min ;
  }
  
  
  function nonWeightedRandom( $min, $max ) {
    return rand($min,$max+$min-1) ;
  }  
  
  function LaplacianRandom( $min, $max){
    return $this->bellWeightedRandom( $min, $max );
  }

  function get($min, $maxo )
  {
    $max = $maxo-$min;
    switch($this->Method) {
      case 'gaussian' :
        $rVal = $this->gaussianWeightedRandom( $min, $max ) ;
        break ;
      case 'bell' :
        $rVal = $this->bellWeightedRandom( $min, $max ) ;
        break ;
      case 'gaussianRising' :
        $rVal = $this->gaussianWeightedRisingRandom( $min, $max ) ;
        break ;
      case 'gaussianFalling' :
        $rVal = $this->gaussianWeightedFallingRandom( $min, $max ) ;
        break ;
      case 'gamma':
        $rVal = $this->gammaWeightedRandom( $min, $max ) ;
        break ;
      case 'gammaQaD':
        $rVal = $this->QaDgammaWeightedRandom( $min, $max ) ;
        break ;
      case 'log10':
        $rVal = $this->logarithmic10WeightedRandom( $min, $max ) ;
        break ;
      case 'log':
        $rVal = $this->logarithmicWeightedRandom( $min, $max ) ;
        break ;
      case 'poisson':
        $rVal = $this->poissonWeightedRandom( $min, $max ) ;
        break ;
      case 'dome':
        $rVal = $this->domeWeightedRandom( $min, $max ) ;
        break ;
      case 'saw':
        $rVal = $this->sawWeightedRandom( $min, $max ) ;
        break ;
      case 'pyramid':
        $rVal = $this->pyramidWeightedRandom( $min, $max ) ;
        break ;
      case 'linear':
      case 'uniform':
        $rVal = $this->linearWeightedRandom( $min, $max ) ;
        break ;
      case 'laplacian':
        $rVal = $this->LaplacianRandom( $min, $max);
        break ;
      default :
        $rVal = $this->nonWeightedRandom( $min, $max ) ;
        break ;
    }
    $r = (int) $rVal;
    if(isset($this->t[$r]) ) 
      $this->t[$r]++;
    else 
      $this->t[$r] = 1;    
    return $r;
  }
  
  function distribution($path ="C:\\",  $filename = "distribution.png"){    
    $maxi = max($this->t);
    ksort($this->t);
    
    $high = $maxi /10;
    $dimg  = imagecreatetruecolor(2 * $level+20,$maxi/10 + 20);
    $color = imagecolorallocate($dimg, 128, 128, 128 );
    
    foreach($this->t AS $i => $e){
      imageline($dimg, $level + 10 + $i, $high-1, $level + 10 + $i, $high-1-$e/10, $color);     
    }    
    imagepng( $dimg, $path . $filename);    
  }
}

function purebell($min,$max,$std_deviation,$step=1) {
  $rand1 = (float)mt_rand()/(float)mt_getrandmax();
  $rand2 = (float)mt_rand()/(float)mt_getrandmax();
  $gaussian_number = sqrt(-2 * log($rand1)) * cos(2 * M_PI * $rand2);
  $mean = ($max + $min) / 2;
  $random_number = ($gaussian_number * $std_deviation) + $mean;
  $random_number = round($random_number / $step) * $step;
  if($random_number < $min || $random_number > $max) {
    $random_number = purebell($min, $max,$std_deviation);
  }
  return $random_number;
}