<?php
	include("../sdk-1.5.6.2/sdk.class.php");
	include("../s3functions.php");
	$s3 = new AmazonS3();
	$try = 1;
	$res="";
		$sleep = 1;
		do
		{
			$res = $s3->create_object('com.sanchitkarve.tb.usor', 'test.txt',
						array(							
							'body' => 'howdy',
							'contentType' => 'text/plain'));
			if($res->isOK())
			{
				echo "DONE";
			}
			sleep($sleep);
			$sleep *= 1;
		}while(++$try < 6);
		print_r($res);
		echo "NOT DONE";
?>