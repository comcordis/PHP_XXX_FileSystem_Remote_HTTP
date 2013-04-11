<?php

abstract class XXX_HTTP_Request
{
	// If you pass in data a variable with a value with @/absolute/path/to/file.ext it will upload it, and the transport method needs to be post...
	public static function execute ($uri = '', $transportMethod = 'uri', array $data = array(), $timeOut = 5, $ssl = false)
	{
		$result = false;
		
		$transportMethod = XXX_Default::toOption($transportMethod, array('uri', 'body'), 'uri');
		$timeOut = XXX_Default::toPositiveInteger($timeOut, 5);
		
		$encodedData = array();
		
		foreach ($data as $key => $value)
		{
			$encodedData[] = $key . '=' . XXX_String::encodeURIValue($value);
		}
						
		$content = XXX_Array::joinValuesToString($encodedData, '&');
		
		
		
		if ($transportMethod == 'uri')
		{
			if ($content != '')
			{
				if (XXX_String::findFirstPosition($uri, '?') > 0)
				{
					$uri .= '&' . $content;
				}
				else
				{
					$uri .= '?' . $content;
				}
			}
		}
		
		
		if (function_exists('curl_init'))
		{		
			$curlHandler = curl_init();
			
			
			curl_setopt($curlHandler, CURLOPT_URL, $uri);
			
			// Do not include the header in the output
			curl_setopt($curlHandler, CURLOPT_HEADER, false);
			
			// Follow redirection, up to 3 times (TODO: Not combinable with open_base_dir's)
			curl_setopt($curlHandler, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curlHandler, CURLOPT_MAXREDIRS, 3);
			
			
			// Silently fail rather than returning an error page as content...
			curl_setopt($curlHandler, CURLOPT_FAILONERROR, true);
			curl_setopt($curlHandler, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($curlHandler, CURLOPT_TIMEOUT, $timeOut);
			
			// Return into a variable
			curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);
			
			if ($transportMethod == 'body')
			{
				curl_setopt($curlHandler, CURLOPT_POST, true);
	
				curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $content);
			}
			else
			{
				curl_setopt($curlHandler, CURLOPT_HTTPGET, true);
			}
			
			if ($ssl == false)
			{
				curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, false);
			}
			else if ($ssl)
			{
				curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, true);
				/*
				0: Don’t check the common name (CN) attribute
	    		1: Check that the common name attribute at least exists
	    		2: Check that the common name exists and that it matches the host name of the server
				*/
				curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, 2);
				
				// Absolute path to CA certificate of peer (So cURL trusts any server/peer certificates issued by that CA
				curl_setopt($curlHandler, CURLOPT_CAINFO, $ssl);
			}		
			
			$result = curl_exec($curlHandler);
						
			if ($result == false)
			{
				trigger_error('Unable to open remote file: "' . $uri . '": ' . curl_error($curlHandler), E_USER_ERROR);
			}
			
			curl_close($curlHandler);
		}
		else
		{
			if ($transportMethod == 'uri')
			{
				$fileHandler = fopen($uri, 'r');
				
				$content = '';
				
				if ($fileHandler)
				{
					while (!feof($fileHandler))
					{
						$chunk = fread($fileHandler, 8192);
						
						$content .= $chunk;
					}
				}
				fclose($fileHandler);
			
				$result = $content;
				
				if ($result == false)
				{
					trigger_error('Unable to open remote file: "' . $uri . '"', E_USER_ERROR);
				}
			}
		}
		
		return $result;
	}
}

?>