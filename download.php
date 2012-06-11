<?php
	include("includes/constants.php");
	include("includes/functions.php");
	include("sdk-1.5.6.2/sdk.class.php");
	include("s3functions.php");
	//get authtoken and deviceID from phone
	//get username from db
	//compute filename prefix
	//check all items in bucket
	// find all items that match user
	//create urls
	//send back urls like url1[space]url2[space]url3
	//phone downloads urls individually
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
		$userFiles = array();
		$foundFiles = false;
		foreach($response as $item)
		{
			if(startsWith($item,$newFileName))
			{
				$userfiles[] = $item;
				$foundFiles = true;
			}
		}
		// create cloudfront link if possible
		$urls = "";
		//print_r($response);
		if($foundFiles)
		{
			foreach($userfiles as $uitem)
			{
				$link = $s3->get_object_url($bucket,$uitem,'10 minutes');
				// replace domain with cloudfront and put them in urls
				$urls .= $link . " ";
			}
			//remove last space
			$urls = trim($urls);
			echo $urls;
		}
		else
		{
			echo "NOFILES";
		}
	}
	else
	{
		echo "Error: Not all parameters set.";
	}
?>