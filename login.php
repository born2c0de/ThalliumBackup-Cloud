<?php
	include("includes/constants.php");
	include("includes/functions.php");
	
	$username = $_POST["username"];
	$password = $_POST["password"];
	
	if(!empty($username) && !empty($password))
	{
		/*
			connect to database
			check for username and password match
			if match, generate authToken, store in database and echo AuthToken and all device profiles
		*/
		$conn = mysql_connect(DB_HOST,DB_USERNAME,DB_PASSWORD) or die("Error:Couldn't connect to server");
	    $db = mysql_select_db(DB_DBNAME,$conn) or die("Error:Couldn't select database");
	    $query="SELECT * FROM users WHERE email = '" . $username . "' AND password = '$password'";
	    $result = mysql_query($query) or die("Error:Query Failed-1");
	                		//echo "Number of Rows : " . mysql_num_rows($result);
	    // If username and password match
		if(mysql_num_rows($result) == 1)
	    {	                			
			$authToken = getUniqueCode(32);	        
	        $query = "UPDATE users SET authToken = '$authToken' WHERE email ='$username'";
			$result = mysql_query($query) or die("Error:AuthToken Registration failed-1");			
			$query="SELECT did, deviceName FROM users u, devices d WHERE email = '" . $username . "' AND d.uid = u.uid";
			$result = mysql_query($query) or die("Error:Device Profile Lookup failed-1");			
			mysql_close($conn);
			echo $authToken;
			while($row = mysql_fetch_array($result))
			{
				echo ":";
				$rDid = $row["did"];
				$rDeviceName = $row["deviceName"];
				echo "$rDid" . ":" . "$rDeviceName";
			}						                				        
			
		}
		else //incorrect username or password
		{
			echo "Error:Incorrect Username or Password.";			
		}
	}
	else
	{
		echo "Error:Not all parameters set";
	}
?>