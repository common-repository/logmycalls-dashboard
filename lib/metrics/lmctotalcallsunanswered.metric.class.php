<?php

/*
* class TotalCalls
* a Simple metric that shows the total number of calls 
*/

class LmcTotalCallsUnanswered extends Metric{

	/* Calculate the total number of calls, save the result in the private $results variable.
	*/
	
	function __construct($label = "Total Calls Unanswered")
	{
		parent::__construct($label);
		$this->counter = 0;
	}
	
	function operate($record)
	{
		if($record->get_disposition() != 'ANSWERED')
			$this->counter ++;
	}
	
	function tally()
	{
		return $this->counter;
	}
	
	function format($input)
	{
		return $input;
	}
} //END CLASS
?>