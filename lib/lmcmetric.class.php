<?php

/* class Metric
*  Define metrics the build from the call dataset. Sub classes get a couple of 
*  functions that help, like labels and printing the output of operate. They
*  must implement operate for their specific operation.
*  Todo: offer a caching feature for counter-based, or similar to x is the largest in y
*  to speed up compute
*/

abstract class Metric {

	public $results;
	public $label;
	
	public function __construct($label = false){
		$this->results = false;
		$this->label = $label;
	}

	abstract public function format($input); //Implemented by specific metric classes
	abstract public function operate($record); //Implemented by specific metric classes
	abstract public function tally(); //Implemented by specific metric classes

	function value($echo = false){
		if($this->results != false)
		{
			$res =  $this->results;
		}
		else
		{
			$res = $this->format($this->tally());
			$this->results = $res; //save for any future requests to this metric during runtime
		}
		
		if($echo):
			echo $res;
			return true;
		else:
			return $res;
		endif;
	}
	
	public function label() {
		return $this->label;
	}


}
?>