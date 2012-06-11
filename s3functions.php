<?php
	function uploadObject($s3, $bucket, $key, $data, $acl = S3_ACL_PRIVATE, $contentType = "text/plain")
	{
		$try = 1;
		$sleep = 1;
		$res = "";
		do
		{
			$res = $s3->create_object($bucket, $key,
						array(							
							'body' => $data,
							'acl' => 'private',
							'contentType' => $contentType
							));
			if($res->isOK())
			{
				return true;
			}
			sleep($sleep);
			$sleep *= 2;
		}while(++$try < 6);
		return $res;
	}
	
function getSignedURL($resource, $timeout)
{
	//This comes from key pair you generated for cloudfront
	$keyPairId = "APKAIA3QRQOKVKEQDHZA";

	$expires = time() + $timeout; //Time out in seconds
	$json = '{"Statement":[{"Resource":"'.$resource.'","Condition":{"DateLessThan":{"AWS:EpochTime":'.$expires.'}}}]}';		
	
	//Read Cloudfront Private Key Pair
	$fp=fopen("private_key.pem","r"); 
	$priv_key=fread($fp,8192); 
	fclose($fp); 

	//Create the private key
	//$key = openssl_get_privatekey($priv_key);
	$key = openssl_get_privatekey("file://private_key.pem");
	if(!$key)
	{
		echo "<p>Failed to load private key!</p>";
		return;
	}
	
	//Sign the policy with the private key
	if(!openssl_sign($json, $signed_policy, $key, OPENSSL_ALGO_SHA1))
	{
		echo '<p>Failed to sign policy: '.openssl_error_string().'</p>';
		return;
	}
	
	//Create url safe signed policy
	$base64_signed_policy = base64_encode($signed_policy);
	$signature = str_replace(array('+','=','/'), array('-','_','~'), $base64_signed_policy);

	//Construct the URL
	$url = $resource.'?Expires='.$expires.'&Signature='.$signature.'&Key-Pair-Id='.$keyPairId;
	
	return $url;
}

/**
 * Function: get_private_object_url()
 * 	Generates a time-limited and/or query signed request for a private file with additional optional restrictions.
 *
 * Parameters:
 * 	$distribution_hostname - _string_ (Required) The hostname of the distribution.
 *	$file_pattern - _string_ (Required) The filename of the object. Query parameters can be included. You can use multi-character match wild cards (*) or a single-character match wild card (?) anywhere in the string.
 * 	$expires - _integer_|_string_ (Required) The expiration time can be expressed either as a number of seconds since Unix Epoch, or any string that `strtotime()` can understand.
 * 	$opt - _array_ (Optional) Associative array of parameters which can have the following keys:
 *
 * Keys for the $opt parameter:
 *	BecomeAvailable - _integer_|_string_ (Optional) The time when the private URL becomes active. Can be expressed either as a number of seconds since Unix Epoch, or any string that `strtotime()` can understand. This is the same as `DateLessThan` that the documentation discusses.
 *	IPAddress - _string_ (Optional) A single IP address to restrict the access to.
 * 	Secure - _boolean_ (Optional) Whether or not to use HTTPS as the protocol scheme.
 *
 * Returns:
 * 	_string_ The file URL with authentication parameters.
 *
 * See Also:
 * 	[Serving Private Content](http://docs.amazonwebservices.com/AmazonCloudFront/latest/DeveloperGuide/PrivateContent.html)
 */
function get_private_object_url($distribution_hostname, $file_pattern, $expires, $opt = null)
{
	if (!$opt) $opt = array();

	$resource = '';
	$expiration_key = 'Expires';
	$expires = strtotime($expires);
	$conjunction = (strpos($file_pattern, '?') === false ? '?' : '&');

	// Determine the protocol scheme
	switch (substr($distribution_hostname, 0, 1) === 's')
	{
		// Streaming
		case 's':
			$scheme = 'rtmp';
			$resource = str_replace(array('%3F', '%3D', '%26', '%2F'), array('?', '=', '&', '/'), rawurlencode($file_pattern));
			break;

		// Default
		case 'd':
		default:
			$scheme = 'http';
			$scheme .= (isset($opt['Secure']) && $opt['Secure'] === true ? 's' : '');
			$resource = $scheme . '://' . $distribution_hostname . '/' . str_replace(array('%3F', '%3D', '%26', '%2F'), array('?', '=', '&', '/'), rawurlencode($file_pattern));
			break;
	}

	// Generate default policy
	$raw_policy = array(
		'Statement' => array(
			array(
				'Resource' => $resource,
				'Condition' => array(
					'DateLessThan' => array(
						'AWS:EpochTime' => $expires
					)
				)
			)
		)
	);

	// Become Available
	if (isset($opt['BecomeAvailable']))
	{
		// Switch to 'Policy' instead
		$expiration_key = 'Policy';

		// Update the policy
		$raw_policy['Statement'][0]['Condition']['DateGreaterThan'] = array(
			'AWS:EpochTime' => strtotime($opt['BecomeAvailable'])
		);
	}

	// IP Address
	if (isset($opt['IPAddress']))
	{
		// Switch to 'Policy' instead
		$expiration_key = 'Policy';

		// Update the policy
		$raw_policy['Statement'][0]['Condition']['IpAddress'] = array(
			'AWS:SourceIp' => $opt['IPAddress']
		);
	}

	// Munge the policy
	$json_policy = str_replace('\/', '/', json_encode($raw_policy));
	$json_policy = decode_uhex($json_policy);
	$encoded_policy = strtr(base64_encode($json_policy), '+=/', '-_~');

	// Generate the signature
	//Read Cloudfront Private Key Pair
	$fp=fopen("private_key.pem","r"); 
	$priv_key=fread($fp,8192); 
	fclose($fp); 
	openssl_sign($json_policy, $signature, $priv_key);
	$signature = strtr(base64_encode($signature), '+=/', '-_~');
	
	//added
	//$expiration_key = "Policy";

	return $scheme . '://' . $distribution_hostname . '/'
		. str_replace(array('%3F', '%3D', '%26', '%2F'), array('?', '=', '&', '/'), rawurlencode($file_pattern))
		. $conjunction
		. ($expiration_key === 'Expires' ? ($expiration_key . '=' . $expires) : ($expiration_key . '=' . $encoded_policy))
		. '&Key-Pair-Id=' . "APKAIA3QRQOKVKEQDHZA"
		. '&Signature=' . $signature;
}

/**
 * Function: decode_uhex()
 * 	Decodes \uXXXX entities into their real unicode character equivalents.
 *
 * Parameters:
 * 	$s - _string_ (Required) The string to decode.
 *
 * Returns:
 * 	_string_ The decoded string.
 */
function decode_uhex($s)
{
	preg_match_all('/\\\u([0-9a-f]{4})/i', $s, $matches);
	$matches = $matches[count($matches) - 1];
	$map = array();

	foreach ($matches as $match)
	{
		if (!isset($map[$match]))
		{
			$map['\u' . $match] = html_entity_decode('&#' . hexdec($match) . ';', ENT_NOQUOTES, 'UTF-8');
		}
	}

	return str_replace(array_keys($map), $map, $s);
}
?>