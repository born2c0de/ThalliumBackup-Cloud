<?php
	include("includes/constants.php");
	include("includes/functions.php");
	include("sdk-1.5.6.2/sdk.class.php");
	include("s3functions.php");
	
	$authToken = $_REQUEST["authToken"];
	$deviceID = $_REQUEST["deviceID"];	
	$username = "";
	$region = "";
	
	if(!empty($authToken) && !empty($deviceID))
	{	
		$conn = mysql_connect(DB_HOST,DB_USERNAME,DB_PASSWORD) or die("Error:Couldn't connect to server");
		$db = mysql_select_db(DB_DBNAME,$conn) or die("Error:Couldn't select database");
		$query="SELECT * FROM users WHERE authToken = '$authToken'";
		$result = mysql_query($query) or die("Error:Query Failed-1");
		if(mysql_num_rows($result) == 1)
		{
			$row = mysql_fetch_array($result);
			$username = $row["email"];
			$region = $row["region"];
		}
		else
		{
			die("Error: Incorrect authToken");
		}
		mysql_close($conn);
		
		$newFileName = md5($username . $deviceID) . "_";
		$s3 = new AmazonS3();
		$bucket = 'com.sanchitkarve.tb.usor';		
		$response = $s3->get_object_list($bucket);
		
		
		if(!empty($response))
		{
			foreach($response as $item)
			{
				if(startsWith($item,$newFileName))
				{
					$r = $s3->delete_object($bucket,$item);				
				}
			}		
			echo "Success";
		}
		else
		{		
			echo "Success: No files found";
		}
	}
	else
	{
		echo "Error: Not all parameters set.";
	}
?>