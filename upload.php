<?php
	include("includes/constants.php");
	include("includes/functions.php");
	include("sdk-1.5.6.2/sdk.class.php");
	include("s3functions.php");
	
	
	// get authtoken, deviceid and file from post
	// use authtoken to get username and region
	// use md5(username.deviceid)_originalfilename as filename for s3 bucket
	// store in appropriate (i.e. region) bucket
	$authToken = $_GET["authToken"];
	$deviceID = $_GET["deviceID"];
	$fileName = $_FILES["uploadedFile"]["name"];
	$username = "";
	$region = "";
	
	if(!empty($authToken) && !empty($deviceID) && !empty($fileName))
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
		$fileContents = file_get_contents($_FILES["uploadedFile"]["tmp_name"]);
		$newFileName = md5($username . $deviceID) . "_" . $fileName;
		$s3 = new AmazonS3();
		//echo "s3 : " . $s3 . "<br>";
		//echo "bucketname : " . S3_BUCKET_OR . "<br>";
		//echo "newfilename : " . $newFileName . "<br>";
		//echo "contents : " . $fileContents . "<br>";
		//uploadObject($s3, $bucket, $key, $data, $acl = S3_ACL_PRIVATE, $contentType = "text/plain")
		if(uploadObject($s3,S3_BUCKET_OR,$newFileName,$fileContents,S3_ACL_PUBLIC,"binary/octet-stream"))
		{
			echo "Success";
		}
		else
		{
			echo "Error: Upload failed";
		}
	}
	else
	{
		echo "Error: Not all parameters set.";
	}
	
?>