<?php

function curl_exec_follow($ch, &$maxredirect = null) {
  
  // we emulate a browser here since some websites detect
  // us as a bot and don't let us do our job
  $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5)".
                " Gecko/20041107 Firefox/1.0";
  curl_setopt($ch, CURLOPT_USERAGENT, $user_agent );

  $mr = $maxredirect === null ? 5 : intval($maxredirect);

  if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
    curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

  } else {
    
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

    if ($mr > 0)
    {
      $original_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
      $newurl = $original_url;
      
      $rch = curl_copy_handle($ch);
      
      curl_setopt($rch, CURLOPT_HEADER, true);
      curl_setopt($rch, CURLOPT_NOBODY, true);
      curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
      do
      {
        curl_setopt($rch, CURLOPT_URL, $newurl);
        
        XXX_Type::peakAtVariable(curl_getinfo($rch));
        
        $header = curl_exec($rch);
        if (curl_errno($rch)) {
          $code = 0;
        } else {
          $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
          if ($code == 301 || $code == 302) {
            preg_match('/Location:(.*?)\n/', $header, $matches);
            $newurl = trim(array_pop($matches));
            
            // if no scheme is present then the new url is a
            // relative path and thus needs some extra care
            if(!preg_match("/^https?:/i", $newurl)){
              $newurl = $original_url . $newurl;
            }   
          } else {
            $code = 0;
          }
        }
      } while ($code && --$mr);
      
      curl_close($rch);
      
      if (!$mr)
      {
        if ($maxredirect === null)
        trigger_error('Too many redirects.', E_USER_WARNING);
        else
        $maxredirect = 0;
        
        return false;
      }
      curl_setopt($ch, CURLOPT_URL, $newurl);
    }
  }
  
        XXX_Type::peakAtVariable(curl_getinfo($ch));
  return curl_exec($ch);
}


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
			
			// Follow redirection, up to 3 times
			//curl_setopt($curlHandler, CURLOPT_FOLLOWLOCATION, true); // Not combinable with open_base_dir's
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
			
			$result = curl_exec_follow($curlHandler);
			
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
			}
		}
		
		if ($result == false)
		{
			trigger_error('Unable to open remote file', E_USER_ERROR);
		}
		
		return $result;
	}
}

?>