<?php

/*
* class LmcCallRecord
* a class for a call record. LmcCallData maintains a list of LmcCallRecords
*/

class LmcCallRecord {

	public $duration;
	public $tracking_number;
	public $disposition;
	public $calldate;

	function __construct($record_object)
	{
		//Precondition: $record_object is an object of type stdClass
		//Postcondition: Call Record Object is built.
		
		$this->duration = $record_object->duration;
		$this->tracking_number = $record_object->tracking_number;
		$this->disposition = $record_object->disposition;
		$this->calldate = $record_object->calldate;

	}

	function get_duration()
	{

		if(isset($this->duration) && $this->duration != '')
			return $this->duration;
		else
			return 0;

	}

	function get_disposition() {
		if(isset($this->disposition) && $this->disposition != '')
			return $this->disposition;
		else
			return false;

	}

	function get_tracking_number() {
		if(isset($this->tracking_number) && $this->tracking_number != '')
			return $this->tracking_number;
		else
			return false;

	}


	function get_calldate() {
		if(isset($this->calldate) && $this->calldate != '')
			return $this->calldate;
		else
			return false;

	}
	
	function get_timestamp() {
		if(isset($this->calldate) && $this->calldate != '')
			return strtotime($this->calldate);
		else
			return false;
	}

}
?>