<?php

/*
* class LongestCall
* a Simple metric that returns the longest call in the list
*/

class LmcLongestCall extends Metric{

	/* Calculate the total number of calls, save the result in the private $results variable.
	*/
	
	function __construct($label = "Longest Call Duration")
	{
		parent::__construct( $label);
		$this->longest_call = 0;
	}
	
	function operate($record)
	{
		if( $record->get_duration() > $this->longest_call)
			$this->longest_call = $record->get_duration();
	}

	function tally()
	{
			return $this->longest_call;
	}
	
	function format($input) //accepts $input in seconds
	{
		if($input == false)
			return "N/A";
		else
			return gmdate("i:s", $input); //returns formatted Minnute:Seconds

	}
} //END CLASS
?>