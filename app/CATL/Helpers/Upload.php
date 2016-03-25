<?php

namespace CATL\Helpers;

use \RuntimeException as RuntimeException;

class Upload {

	public static $newname;

	public static function file_upload_max_size()
	{
	  static $max_size = -1;

	  if ($max_size < 0) {
	    // Start with post_max_size.
	    $max_size = self::parse_size(ini_get('post_max_size'));

	    // If upload_max_size is less, then reduce. Except if upload_max_size is
	    // zero, which indicates no limit.
	    $upload_max = self::parse_size(ini_get('upload_max_filesize'));
	    if ($upload_max > 0 && $upload_max < $max_size) {
	      $max_size = $upload_max;
	    }
	  }
	  return $max_size;
	}

	public static function parse_size($size)
	{
	  $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
	  $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
	  if ($unit) {
	    // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
	    return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
	  }
	  else {
	    return round($size);
	  }
	}

	public static function check($filename)
	{
		try {

		    // Undefined | Multiple Files | $_FILES Corruption Attack
		    // If this request falls under any of them, treat it invalid.
		    if (
		        !isset($_FILES[$filename]['error']) ||
		        is_array($_FILES[$filename]['error'])
		    ) {
		        throw new RuntimeException('Invalid parameters.');
		    }

		    // Check $_FILES['upfile']['error'] value.
		    switch ($_FILES[$filename]['error']) {
		        case UPLOAD_ERR_OK:
		            break;
		        case UPLOAD_ERR_NO_FILE:
		            throw new RuntimeException('No file sent.');
		        case UPLOAD_ERR_INI_SIZE:
		        case UPLOAD_ERR_FORM_SIZE:
		            throw new RuntimeException('Exceeded filesize limit.');
		        default:
		            throw new RuntimeException('Unknown errors.');
		    }

		    // You should also check filesize here.
		    if ($_FILES[$filename]['size'] > self::file_upload_max_size()) {
		        throw new RuntimeException('Exceeded filesize limit.');
		    }

		    // DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
		    // Check MIME Type by yourself.
		    $finfo = new \finfo(FILEINFO_MIME_TYPE);
		    if (false === $ext = array_search(
		        $finfo->file($_FILES[$filename]['tmp_name']),
		        array(
                    'jpg1' => 'image/jpeg',
                    'png1' => 'image/png',
                    'gif1' => 'image/gif',
                    'csv1' => 'application/vnd.ms-excel',
                    'csv2' => 'text/plain',
                    'csv3' => 'text/csv',
                    'csv4' => 'text/tsv',
		        ),
		        true
		    )) {
		        throw new RuntimeException('Invalid file format.');
		    }

		    $ext = substr($ext, 0, -1);

		    //'application/vnd.ms-excel','text/plain','text/csv','text/tsv'

		    // You should name it uniquely.
		    // DO NOT USE $_FILES['upfile']['name'] WITHOUT ANY VALIDATION !!
		    // On this example, obtain safe unique name from its binary data.
		    self::$newname = sprintf(ROOT . '/uploads/%s.%s',
		            sha1_file($_FILES[$filename]['tmp_name']),
		            $ext
		    );

		    if (!move_uploaded_file($_FILES[$filename]['tmp_name'],self::$newname)) {
		        throw new RuntimeException('Failed to move uploaded file.');
		    }

		    return true;

		} catch (RuntimeException $e) {

		    return $e->getMessage();

		}
	}

	public static function countLines($file)
	{
		try {
			$linecount = 0;
			$handle = fopen($file, "r");
			while(!feof($handle)){
			  $line = trim(fgets($handle));
			  $newlines = preg_replace('/.*/', '', $line);
			  //echo strlen($newlines) . BR;
			  if (strlen(str_replace(',', '', trim($line) )) > strlen($newlines)) {
			  	$linecount++;
			  }
			}

			fclose($handle);
			return $linecount;
		} catch (\Exception $e) {
			return 0;
		}
	}

	public static function checkMembersHeader($file, $validHeader)
	{
		
		$ret = false;
		try {
			$handle = fopen($file, "r");
  	 		$line = fgets($handle);

  	 		if (strtolower(trim($line)) == strtolower(trim($validHeader))) {
  	 			$ret = true;
  	 		}
			fclose($handle);
			return $ret;
		} catch (\Exception $e) {
			return $ret;
		}

	}
}
