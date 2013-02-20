<?php

/*

// TODO, file uploads

Content-type: multipart/form-data, boundary=AaB03x

--AaB03x
content-disposition: form-data; name="field1"

Joe Blow
--AaB03x
content-disposition: form-data; name="pics"; filename="file1.txt"
Content-Type: text/plain

 ... contents of file1.txt ...
--AaB03x--

If the user also indicated an image file "file2.gif" for the answer
to 'What files are you sending?', the client might client might send
back the following data:

Content-type: multipart/form-data, boundary=AaB03x

--AaB03x
content-disposition: form-data; name="field1"

Joe Blow
--AaB03x
content-disposition: form-data; name="pics"
Content-type: multipart/mixed, boundary=BbC04y

--BbC04y
Content-disposition: attachment; filename="file1.txt"
Content-Type: text/plain

... contents of file1.txt ...
--BbC04y
Content-disposition: attachment; filename="file2.gif"
Content-type: image/gif
Content-Transfer-Encoding: binary

  ...contents of file2.gif...
--BbC04y--
--AaB03x--

*/



class XXX_FileSystem_Remote_HTTP
{
	public static function doesFileExist ($uri = '')
	{
		$result = false;
		
		$headers = get_headers($uri);
		
		$httpHeader = $headers[0];
		
		$httpHeaderParts = XXX_String::splitToArray($httpHeader, ' ');
		
		if ($httpHeaderParts[1] == '200')
		{
			$result = true;
		}
		
		return $result;
	}
	
	public static function getFileSize ($uri = '')
	{
		$result = false;
		
		$headers = get_headers($uri, 1);
		
		foreach ($headers as $key => $value)
		{
			if (XXX_String::convertToLowerCase($key) == 'content-length')
			{
				$result = $value;
				break;
			}
		}
		
		return $result;
	}
	
	public static function getFileMIMEType ($uri = '')
	{
		$result = 'application/octet-stream';
		
		$headers = get_headers($uri, 1);
		
		foreach ($headers as $key => $value)
		{
			if (XXX_String::convertToLowerCase($key) == 'content-type')
			{
				$valueParts = XXX_String::splitToArray($value, ';');
				$result = $valueParts[0];
				break;
			}
		}
		
		return $result;
	}
	
	public static function getFileContent ($uri = '', $transportMethod = 'uri', array $data = array(), $timeOut = 5, $ssl = false)
	{
		$result = false;
		
		$tempResult = XXX_HTTP_Request::execute($uri, $transportMethod, $data, $timeOut, $ssl);
		
		if ($tempResult)
		{
			$result = $tempResult;
		}
		
		return $result;
	}
	
	public static function uploadFile ($uri = '', $inputName = '', $absolutePathToFile = '', $ssl = false)
	{
		$result = false;
		
		$transportMethod = 'body';
		
		$data = array
		(
			$inputName => '@' . $absolutePathToFile
		);
		
		$timeOut = 10;
		
		$tempResult = XXX_HTTP_Request::execute($uri, $transportMethod, $data, $timeOut, $ssl);
		
		if ($tempResult)
		{
			$result = $tempResult;
		}
		
		return $result;
	}
}


?>