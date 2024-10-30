<?php

/*
* class LmcAverageCallDuration
* a Simple metric that shows the total number of calls 
*/

class LmcAverageCallDuration extends Metric{

	/* Calculate the total number of calls, save the result in the private $results variable.
	*/
	
	function __construct($label = "Average Call Duration")
	{
		parent::__construct($label);
		$this->total_duration = 0;
		$this->counter = 0;
	}
	
	function operate($record)
	{
			$this->total_duration += $record->get_duration();
			$this->counter ++;
	}
	
	function tally()
	{
		if($this->counter >0)
			$average = round($this->total_duration / $this->counter);
		else 
			$average = 0; //Prevent devide by 0
		
		return $average;
	}
	
	function format($input)
	{
		if($input == false)
			return "N/A";
		else
			return gmdate("i:s", $input); //returns formatted Minnute:Seconds
	}
} //END CLASS
?>