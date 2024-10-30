<?php

/*
* class TotalCallDuration
* a Simple metric that shows the total lifetime call duration.
*/

class LmcTotalCallDuration extends Metric{

	/* Calculate the total number of calls, save the result in the private $results variable.
	*/
	
	function __construct($label = "Total Call Duration")
	{
			$this->call_duration = 0;

		parent::__construct($label);
	}
	
	function operate($record)
	{
		$this->call_duration += $record->get_duration();	
	}
	
	function tally()
	{
		if(!isset($this->call_duration))
			return false;
		else
			return $this->call_duration;
	
	
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