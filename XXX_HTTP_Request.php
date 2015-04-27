<?php

abstract class XXX_HTTP_Request
{
	public static function composeKeyValuePair ($key, $value = '')
	{
		$result = '';
		
		if (XXX_Type::isArray($value))
		{
			if (XXX_Type::isAssociativeArray($value))
			{
				$i = 0;
				
				foreach ($value as $subKey => $subValue)
				{
					if ($i > 0)
					{
						$result .= '&';
					}
					
					$result .= $key . '['. $subKey . ']' . '=' . XXX_String::encodeURIValue($subValue);
					
					++$i;
				}
			}
			else
			{
				for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($value); $i < $iEnd; ++$i)
				{
					if ($i > 0)
					{
						$result .= '&';
					}
					
					$result .= $key . '['. $i . ']' . '=' . XXX_String::encodeURIValue($value[$i]);
				}
			}
		}
		else
		{
			$result = $key . '=' . XXX_String::encodeURIValue($value);
		}
		
		return $result;
	}

    public static function encodeData ($data = array())
    {
        $encodedData = array();

        foreach ($data as $key => $value)
        {
            $encodedData[] = self::composeKeyValuePair($key, $value);
        }

        $encodedData = XXX_Array::joinValuesToString($encodedData, '&');

        return $encodedData;
    }

	// If you pass in data a variable with a value with @/absolute/path/to/file.ext it will upload it, and the transport method needs to be post...
	public static function execute ($uri = '', $transportMethod = 'uri', array $data = array(), $timeOut = 5, $ssl = false, $userAgentString = '')
	{
		$result = false;
		
		$transportMethod = XXX_Default::toOption($transportMethod, array('uri', 'body'), 'uri');
		$timeOut = XXX_Default::toPositiveInteger($timeOut, 300);

		$encodedData = self::encodeData(data);

		if ($transportMethod == 'uri')
		{
			if ($encodedData != '')
			{
				if (XXX_String::findFirstPosition($uri, '?') > 0)
				{
					$uri .= '&' . $encodedData;
				}
				else
				{
					$uri .= '?' . $encodedData;
				}
			}
		}
		
		if (function_exists('curl_init'))
		{
			$curlHandler = curl_init();

			$userAndPasswordMatches = XXX_String_Pattern::getMatches($uri, '://([^:]*:[^@]*)@');

			if (XXX_Array::getFirstLevelItemTotal($userAndPasswordMatches[1]))
			{
				$userAndPassword = $userAndPasswordMatches[1][0];

				$uri = XXX_String::replace($uri, $userAndPassword . '@', '');

				curl_setopt($curlHandler, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
				curl_setopt($curlHandler, CURLOPT_USERPWD, $userAndPassword);
			} 

			curl_setopt($curlHandler, CURLOPT_VERBOSE, 1);
			curl_setopt($curlHandler, CURLINFO_HEADER_OUT, 1);
			
			curl_setopt($curlHandler, CURLOPT_URL, $uri);
			
			// Do not include the header in the output
			curl_setopt($curlHandler, CURLOPT_HEADER, false);
			
			if ($userAgentString != '')
			{
				curl_setopt($curlHandler, CURLOPT_USERAGENT, $userAgentString);
			}
			
			// Follow redirection, up to 3 times (TODO: Not combinable with open_base_dir's)
			curl_setopt($curlHandler, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curlHandler, CURLOPT_MAXREDIRS, 3);
			
			
			// Silently fail rather than returning an error page as content...
			curl_setopt($curlHandler, CURLOPT_FAILONERROR, 1);
			// This one is buggy, do not use it
			//curl_setopt($curlHandler, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($curlHandler, CURLOPT_TIMEOUT, $timeOut);
			
			// Return into a variable
			curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
			
			if ($transportMethod == 'body')
			{
  				curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, 'POST');
				curl_setopt($curlHandler, CURLOPT_POST, 1);
				
				curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $data);
			}
			else
			{
				curl_setopt($curlHandler, CURLOPT_HTTPGET, 1);
			}
			
			if ($ssl == false)
			{
				curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, 0);
			}
			else if ($ssl)
			{
				curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, 1);
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
				trigger_error('Unable to open remote file: "' . $uri . '": ' . curl_error($curlHandler) . '|' . curl_getinfo($curlHandler, CURLINFO_HEADER_OUT), E_USER_ERROR);
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

/*

public static function testMultipleRequests ($uris = array())
	{
		$result = false;
		
		if (function_exists('curl_multi_init') && count($uris) > 0)
		{
			$handles = array();
			$results = array();
			
			$options = array
			(
				CURLOPT_HEADER => 0,
				CURLOPT_RETURNTRANSFER => true
			);
			
			foreach ($uris as $index => $uri)
			{
				$handle = curl_init();
				
				$options[CURLOPT_URL] = $uri;
				
				curl_setopt_array($handle, $options);
				
				$handles[$index] = $handle;
			}
			
			$multiHandle = curl_multi_init();
			
			foreach ($handles as $index => $handle)
			{
				curl_multi_add_handle($multiHandle, $handle);
			}
			
			$runningHandles = null;
			
			do
			{
				$status_curl_multi_exec = curl_multi_exec($multiHandle, $runningHandles);
			}
			while ($curl_multi_exec == CURLM_CALL_MULTI_PERFORM);
			
			while ($runningHandles && $status_curl_multi_exec == CURLM_OK)
			{
				if (curl_multi_select($multiHandle != -1))
				{
					do
					{
						$status_curl_multi_exec = curl_multi_exec($multiHandle, $runningHandles);
						echo 'Threads running: ' . $runningHandles . '<br>';
					}
					while ($status == CURLM_CALL_MULTI_PERFORM);
				}
			}
			
			foreach ($uris as $index => $uri)
			{
				$results[$index] = array();
				$results[$index]['uri'] = $uri;
				$results[$index]['error'] = curl_error($handles[$index]);
				
				if (!empty($results[$index]['error']))
				{
					$results[$index]['response'] = $response;
				}
				else
				{
					$results[$index]['response'] = curl_multi_getcontent($handles[$index]);
				}
				
				curl_multi_remove_handle($multiHandle, $handles[$index]);
			}
			
			curl_multi_close($multiHandle);
			
			$result = $results;
		}
		
		return $result;		
	}

function multiCurl($res,$options=""){

        if(count($res)<=0) return False;

        $handles = array();

        if(!$options) // add default options
            $options = array(
                CURLOPT_HEADER=>0,
                CURLOPT_RETURNTRANSFER=>1,
            );

        // add curl options to each handle
        foreach($res as $k=>$row){
            $ch{$k} = curl_init();
            $options[CURLOPT_URL] = $row['url'];
            curl_setopt_array($ch{$k}, $options);
            $handles[$k] = $ch{$k};
        }

        $mh = curl_multi_init();

        foreach($handles as $k => $handle){
            curl_multi_add_handle($mh,$handle);
            //echo "<br>adding handle {$k}";
        }

        $running_handles = null;
        //execute the handles
        do {
            $status_cme = curl_multi_exec($mh, $running_handles);
        } while ($cme == CURLM_CALL_MULTI_PERFORM);

        while ($running_handles && $status_cme == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                do {
                    $status_cme = curl_multi_exec($mh, $running_handles);
                   // echo "<br>''threads'' running = {$running_handles}";
                } while ($status == CURLM_CALL_MULTI_PERFORM);
            }
        }

        foreach($res as $k=>$row){
            $res[$k]['error'] = curl_error($handles[$k]);
            if(!empty($res[$k]['error']))
                $res[$k]['data']  = '';
            else
                $res[$k]['data']  = curl_multi_getcontent( $handles[$k] );  // get results

            // close current handler
            curl_multi_remove_handle($mh, $handles[$k] );
        }
        curl_multi_close($mh);
        return $res; // return response
}

$res = array(
        "11"=>array("url"=>"http://www.google.com"),
        "13"=>array("url"=>"http://www.wikipedia.com"),
        "25"=>array("url"=>"this doesn't exist"),

);
print_r( multiCurl($res)); 
*/

?>