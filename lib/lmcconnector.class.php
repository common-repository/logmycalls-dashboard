<?php

/* class LogMyCallsApi
* Provides an layer of abstraction to grab call records, convert them to our LmcCallRecord type,
* retrieve and validate subscription details, and build and send requests to the LMC Api.
*/

class LmcConnector {

	private $api_key;
	private $api_secret;
	private $debug;
	private $cache_transient;
	private $force_reload;
	private $organization_id;
	
	public function __construct($api_key='', $api_secret=''){
		
		//Debug Flag
		$this->debug = true;
		
		if($api_key != '')
			$this->set_api_key( $api_key ); //use provided credentials
		else
			$this->set_api_key( $this->get_saved_key()); // or get saved credentials

		if($api_secret != '')
			$this->set_api_secret($api_secret);
		else
			$this->set_api_secret($this->get_saved_secret());

		$this->cache_transient = '';
	}
	
	/*
	* Factory to run "check_credentials" member function without an instantiated object
	*/
   public static function validate_credentials($api_key, $api_secret)
   {
		$conn = new LmcConnector($api_key, $api_secret);
		if( $conn->check_credentials())
			return true;
		else
			return false;
   }
   
   	/**
	*  Setters for api key and api secret
	*/
	
	function set_api_secret($secret) {
		$this->api_secret = $secret;
		return true; //No validation for now.
	}

	function set_api_key($key) {
		$this->api_key = $key;
		return true; //No validation for now.
	}
	
	function set_organization_id($ouid) {
		$this->organization_id = $ouid;
		return true; //No validation for now.
	}
	
   function get_saved_key()
   {
	   $options = get_option('logmycalls-settings');
		if(isset($options['api_key']))
			return $options['api_key'];
		else
			return false;
   }
   function get_saved_secret()
   {
	   $options = get_option('logmycalls-settings');
		if(isset($options['api_secret']))
			return $options['api_secret'];
		else
			return false;
   }
   
   private function api_secret() {
	
		if(!isset($this->api_secret))
		{
			if(!$this->set_api_secret( $this->get_saved_secret() )) //Use the setters to set the api key with saved options.
				return false;
		}
		else
			return $this->api_secret;
		return $this->api_secret();
	}
	
	/*
	* Save Historical Data in wp_options,if it's available in the 'call_records' var.
	*/

	private function api_key() {
	
		if(!isset($this->api_key))
		{
			if(!$this->set_api_key( $this->get_saved_key() )) //Use the setters to set the api key with saved options.
				return false;
		}
		else
			return $this->api_key;
			
		return $this->api_key();
	}
	
   
   /*
   *  verify loaded credentials with LogMyCalls service. Checks are performed when settings are saved.
   */
   	
   	private function check_credentials()
   	{
   		$response = json_decode($this->send_request($this->build_request(),'getSubscriptionInfo'));
		if($response == false)
			return false;
		
		if($response->status != "success")
			return true;
		else	
			return false;   	
   	}

	/** 
	*  Get the ouid from LogMyCalls and save in options on success
	*/

	 function organization_id() {
		if(!is_numeric($this->organization_id) || $this->organization_id == ''){
			$response = $this->logmycallsinteg_get_subscription_data();		
			if($response->status == "success" && $response->matches > 0  ):
				//Valid subscription data...continue
				$results = $response->results;
				
				//Just grab the info for the first product, because it's got the ouid we need.
				$first_product = $results[0];
				
				$org_id = $first_product->organizational_unit_id;
				$this->set_organization_id( $org_id);					
				return $this->organization_id(); //Use the same getter, returning (hopefully) filled out orgid.
			endif; 
		
			return false;
		}
		else
			return $this->organization_id;
	}
	

	/** 
	*  Build Requests to send to LogMyCalls API, including keys and ouid
	*/
	
	 function build_request($criteria_list = '', $meta_list = ''){
	
		if($criteria_list == '')
			$criteria_list = array();
		if($meta_list == '')
			$meta_list = array();
	
			$data = array();
		$data["criteria"] = $criteria_list;
		foreach($meta_list as $meta_key => $meta_value):
			if($meta_key == "api_key" || $meta_key == "api_secret")
				continue; 
			//Keys Shouldn't be sent here, but just in case, don't save them, because we're using what's called from our getters.
			
			$data[$meta_key] = $meta_value;
		endforeach;
		if($this->api_key() == false || $this->api_secret() ==false)
			return false;
		else{
			$data["api_key"] = $this->api_key();
			$data["api_secret"] = $this->api_secret();
			$data["ouid"] = $this->organization_id();
		}
		
		//var_dump($data);
		
		return $data;
	}
	/**
	*  Retrieve Call Records from API, return array of type class CallRecord if calls match
	*/
	
	function get_call_records($start_timestamp = '', $end_timestamp = '')
	{
		$date_format_string = "Y-m-d H:i:s";
	
		if(is_numeric($start_timestamp) && is_numeric($end_timestamp))
			$criteria = array("start_calldate"=>date($date_format_string, $start_timestamp), "end_calldate"=>date($date_format_string, $end_timestamp));
		else
			$criteria = array();
		
   		$response = json_decode($this->send_request($this->build_request($criteria , array()),'getCallDetails'));
		$records = array();
		$record_counter = 0;
		$finished = false;
		
		if ($response != false)
		{
			if($response->status == 'success')
			{
				$record_match_total = $response->matches;
				while(!$finished)
				{
					foreach($response->results as $record)
					{
						$records[] = new LmcCallRecord($record);
						$record_counter++;
					}
							
					//Keep loading...
					if($record_counter == $record_match_total){
						$finished = true;
						continue;
					}
					unset($response);
					unset($meta);
					$meta = array("start"=>$record_counter);
					$response = json_decode($this->send_request($this->build_request($criteria , $meta),'getCallDetails'));
					$continue_retrieval = true;
					//LogMyCalls has a 100 record-at-a-time maximum, so we need to make extra calls if necessary.
				
				}
			}	
		}
	
		if(sizeof($records) > 0)
			return $records;
		else
			return $records;
	}
	
	/*
	*  Send request to the api
	*/
	
	function send_request($req, $verb)
	{
		if(LMC_DEBUG == true)
		{
			ob_start();
			var_dump($req);
			$req_dump = ob_get_contents();
			ob_end_clean();
			error_log($verb . ", " . $req_dump); //Dump out the contents of the request to the error log.
		}

		require_once 'api.class.php'; //Only include when we have to make a request.
		$api = new LmcApi();
		if($req != false){
			$response = $api->post_json($req, $verb);
			
			if(LMC_DEBUG == true)
			{
				ob_start();
				var_dump($response);
				$res_dump = ob_get_contents();
				ob_end_clean();
				error_log($res_dump); //Dump out the contents of the response to the error log.
			}
			
			return $response;
		}
		else
			return false;
	}
	
	/**
	*  Get Subscription Data From LogMyCalls
	*/

	function logmycallsinteg_get_subscription_data() {
		$saved_option = get_option('logmycallsinteg_subscription');
		//$force_reload = $this->force_reload(true);
		
		if( $saved_option != "" || $saved_option != false ):
			 $subscription_json = get_option('logmycallsinteg_subscription');
		else:
			//Need to go get this from the LogMyCalls API...
			$subscription_json = $this->send_request($this->build_request(), "getSubscriptionInfo");
			if(json_decode($subscription_json)->results == "success")
				update_option('logmycallsinteg_subscription', $subscription_json);
			else
				return false;
			//save the entire jquery response in the wp_options table.
		endif;
		
		return json_decode($subscription_json);
	}	
}

?>
