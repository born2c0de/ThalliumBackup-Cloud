<?php
	include("includes/constants.php");
	include("includes/functions.php");
	
	$username = $_POST["username"];
	$password = $_POST["password"];
	$deviceID = $_POST["deviceID"];
	$deviceName = $_POST["deviceName"];
	$region = $_POST["region"];
	if(!empty($username) && !empty($password) && !empty($deviceID) && !empty($deviceName) && !empty($region))
	{
		/*
			connect to database
			check if username exists
				if username exists, check if password is correct. If not return error.
					if password is correct, add deviceID and deviceName for user. If deviceID is same, don't do anything.
				if username doesnt exist, create user and create device and link them. echo success
				
		*/
		$conn = mysql_connect(DB_HOST,DB_USERNAME,DB_PASSWORD) or die("Couldn't connect to server");
	    $db = mysql_select_db(DB_DBNAME,$conn) or die("Couldn't select database");
	    $query="SELECT * FROM users WHERE email = '" . $username . "'";
	    $result = mysql_query($query) or die("Query Failed-1");
	                		//echo "Number of Rows : " . mysql_num_rows($result);
	    // If username does not exist
		if(mysql_num_rows($result) == 0)
	    {	                			
	        $ip = getRealIpAddr();
	        $regDate = gmdate(DATE_W3C);
	        $query = "INSERT INTO users(email,password,regIP,regDate,region) VALUES ('$username','$password','$ip','$regDate','$region')";
			$result = mysql_query($query) or die("User Registration failed-1");
			// Get uid
			$query = "SELECT * FROM users WHERE email = '$username'";
			$result = mysql_query($query) or die("User Registration failed-2");
			if(mysql_num_rows($result) == 1)
			{
				$row = mysql_fetch_array($result);
				$uid = $row['uid'];
			}
			else
			{
				die("User Registration failed-3");
			}
			// Store device
			$query = "INSERT INTO devices(did,uid,deviceName) VALUES ('$deviceID','$uid','$deviceName')";
			$result = mysql_query($query) or die("User Registration failed-4");
			mysql_close($conn);
			echo "Success";	                				        
		}
		else //user is probably registering a new device
		{
			$row = mysql_fetch_array($result);
			$uid = $row['uid'];
			
			$storedpassword = $row['password'];
			// if password matches
			if($password == $storedpassword)
			{
				$query = "SELECT * FROM devices WHERE uid = '" . $uid . "' AND did = '" . $deviceID . "'";
				$result = mysql_query($query) or die("User Registration failed-5");
				
				if(mysql_num_rows($result) == 0)
				{
					//this is a new device
					$query = "INSERT INTO devices(did,uid,deviceName) VALUES ('$deviceID','$uid','$deviceName')";	
					$result = mysql_query($query) or die("User Registration failed-6");
				}//else dont do anything
				echo "Success";
			}
			else
			{
				echo "Error: Can't add new device. Incorrect username or password.";
			}		
		}
	}
	else
	{
		echo "Error: Not all parameters set";
	}
?>