<?php
	// Gets Random String of a specific length. Length cannot exceed 32 characters.
	function getUniqueCode($length = "")
	{	
		$code = md5(uniqid(rand(), true));
		if ($length != "") return substr($code, 0, $length);
		else return $code;
	}
	
	function startsWith($haystack, $needle)
	{
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}

	function endsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}

		$start  = $length * -1; //negative
		return (substr($haystack, $start) === $needle);
	}
	
	
	# Gets ReadIP Address of Client
	function getRealIpAddr()
	{
	    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
	    {
	      $ip=$_SERVER['HTTP_CLIENT_IP'];
	    }
	    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
	    {
	      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
	    }
	    else
	    {
	      $ip=$_SERVER['REMOTE_ADDR'];
	    }
	    return $ip;
	}

	# Checks Email Validity and confirms with MX Servers.
	function checkEmail($email) 
	{
	   if(eregi("^[a-zA-Z0-9_]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$]", $email)) 
	   {
	      return FALSE;
	   }
	
	   list($Username, $Domain) = split("@",$email);
	
	   if(getmxrr($Domain, $MXHost)) 
	   {
	      return TRUE;
	   }
	   else 
	   {
	      if(fsockopen($Domain, 25, $errno, $errstr, 30)) 
	      {
	         return TRUE; 
	      }
	      else 
	      {
	         return FALSE; 
	      }
	   }
	}

?>