<?php

/*
 * LmcError
 * Error class for LogMyCalls
 */
 
 class LmcError {
 
	 private $error_message;
	 private $error_code;
 
	function __construct($error_message, $error_code = '')
	{
	
		$this->error_message = $error_message;
		$this->error_code = $error_code;
	}

	function get_error_message()
	{
		if($this->error_message != '')
		{
			return "Error: " .$this->error_message;
		}
		else
			return false;
	}
	  function get_error_code()
	{
		if($this->error_code != '')
		{
			return "Error Code: " . $this->error_code;
		}
		else
			return false;
	}
  	
}//END CLASS