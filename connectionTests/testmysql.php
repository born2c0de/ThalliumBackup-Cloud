<?php
	include("../includes/constants.php");
	$conn = mysql_connect(DB_HOST,DB_USERNAME,DB_PASSWORD) or die("Couldn't connect to server");
	$db = mysql_select_db(DB_DBNAME,$conn) or die("Couldn't select database");
	mysql_close($conn);
	echo "success";
?>