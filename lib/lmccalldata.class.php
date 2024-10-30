<?php

/* LmcCallData class
*  Call data acts contains most of the caching logic, holding and maintaining the 
*  data (List of LmcCallRecords) implementing caching in wp_options, and providing 
*  an interface to get call records by daterange.
*/

class LmcCallData {

	private $call_data;
	private $api; // API class

	function __construct($api)
	{
		$this->api = $api;
		$this->call_data = false;
	}
	
	function get_call_data(){
		if(isset($this->call_data) && $this->call_data != false)
			return $this->call_data;
		else
			return false;
	}
	
	function set_call_data($cd)
	{
		if(isset($cd) && $cd != false)
			$this->call_data = $cd;
		else
			$this->call_data = false;
	}
		
	/*
	* retrieve the call data, optionally with a date range
	*/

	public function get_call_records($start_date='', $end_date='') {
		
		$date_format= "Y-m-d H:i:s";

		$calls = $this->get_call_data();
	
		if(!is_numeric($start_date) && !is_numeric($end_date) && $calls != false)
			{
				return $calls;
			}
			elseif(is_numeric($start_date) && is_numeric($end_date))
			{
				//Find all records in the desired date range.
					
				$in_timerange = array();
				if($calls != false):
					foreach($calls as $record):
						$timestamp = $record->get_timestamp();
						if($timestamp != false && $start_date < $timestamp && $end_date > $timestamp):
							$in_timerange[] = $record;
						else:
						endif;
					endforeach;
				endif;
				return $in_timerange;
			}
			else
				return false;
		}
	
		private function get_last_updated()
		{
			return get_option('logmycalls_last_updated');
		}	
	
		private function get_historical_data($start_date = '', $end_date = '')
		{
			$historical = get_option('logmycalls_historical');
	
			return $historical;
	}

	/*
	 * receive Metrics objects, fill in with call data value
	 */
	 
	 public function get_metrics($metrics_array, $start_date='', $end_date='' )
	{	
		if($start_date=='')
			$start_date = 0;
		if($end_date=='')
			$end_date = time();
		
		$metrics_number_of = sizeof($metrics_array);
		$local_metrics = $metrics_array;
		$populated_metrics = array();
		foreach($this->call_data as $record):
			for($i=0 ; $i< $metrics_number_of; $i++):
				$timestamp = $record->get_timestamp();
				if($timestamp != false && $start_date < $timestamp && $end_date > $timestamp):
					$local_metrics[$i]->operate($record);
				endif;
			endfor;
		endforeach;
	}
	

	/* 
	* Setter for historical data (Also updates the timestamp)
	*/

	private function save_historical($new_value)
	{
		if(update_option('logmycalls_historical',$new_value))
			return true;
		else
			return false;	
	}

	/*
	* Get all call data, in time range, merging historical data with newly retrieved data.
	*/

	public function load($start_date = '', $end_date = '')
	{
		//check the cache to see if it's already there
		$historical_data = $this->get_historical_data();
		$last_updated = $this->get_last_updated();

		if(!$last_updated || empty($historical_data) || !$historical_data )
			$new_records = $this->api->get_call_records();
		else
			$new_records = $this->api->get_call_records($last_updated, time());

		//historical value exists, pull up latest updated timestamp
		$full_array = $historical_data;
		$newest_record = 0; //Find the latest record, and use that as the "last updated" time for future API requests.
		
		if($new_records != false):
		
			foreach($new_records as $new_record):
				$full_array[] = $new_record;
				if($new_record->get_timestamp() > $newest_record)
					$newest_record = $new_record->get_timestamp() + 1; //need to add a second or the latest will be included in the next sweep
			endforeach;
		endif;
		
		if($newest_record != 0)
			update_option('logmycalls_last_updated', $newest_record);

		$this->set_call_data($full_array);
		$this->save_historical($full_array);

		if($full_array == false)
			return false;
		else
			return true;
	}
}
?>