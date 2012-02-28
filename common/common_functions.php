<?php
	// Function which creates the small icon in the JPEG format
	// $image = $_FILES['file']['name'];
	// $uploadedfile = $_FILES['file']['tmp_name'];
	function create_resized_picture($image, $uploadedfile, $new_file_name) {
		global $PIXEL_WIDTH, $PIXEL_HEIGHT;
		$errors = "";
		
		if ($image && $uploadedfile) {
			$filename = stripslashes($image);
			$extension = get_image_extension($filename);
			$extension = strtolower($extension);
			if (($extension != "jpg") && ($extension != "jpeg") && ($extension != "png") && ($extension != "gif")) {
				$errors = "Unknown file format.";
			} else {
				if ($extension == "jpg" || $extension == "jpeg" ) {
					$src = imagecreatefromjpeg($uploadedfile);
				} else if($extension == "png") {
					$src = imagecreatefrompng($uploadedfile);
				} else {
					$src = imagecreatefromgif($uploadedfile);
				}
				
				$pw = $PIXEL_WIDTH;
				$ph = $PIXEL_HEIGHT;
				
				$tmp = create_cropped_thumbnail($src, $pw, $ph);
				$filename = "pixels/".$new_file_name.'.jpeg';

				imagejpeg($tmp,$filename,100);

				imagedestroy($src);
				imagedestroy($tmp);

				return $filename;
			}
		}
		return null;
	}

	function create_cropped_thumbnail($img_src, $thumbnail_width, $thumbnail_height) {
		$width_orig = imagesx($img_src);
		$height_orig = imagesy($img_src);
		$ratio_orig = $width_orig/$height_orig;
		
		if ($thumbnail_width/$thumbnail_height > $ratio_orig) {
		   $new_height = $thumbnail_width/$ratio_orig;
		   $new_width = $thumbnail_width;
		} else {
		   $new_width = $thumbnail_height*$ratio_orig;
		   $new_height = $thumbnail_height;
		}
		
		$x_mid = $new_width/2;  //horizontal middle
		$y_mid = $new_height/2; //vertical middle
		
		$process = imagecreatetruecolor(round($new_width), round($new_height)); 
		
		imagecopyresampled($process, $img_src, 0, 0, 0, 0, $new_width, $new_height, $width_orig, $height_orig);
		$thumb = imagecreatetruecolor($thumbnail_width, $thumbnail_height); 
		imagecopyresampled($thumb, $process, 0, 0, ($x_mid-($thumbnail_width/2)), ($y_mid-($thumbnail_height/2)), $thumbnail_width, $thumbnail_height, $thumbnail_width, $thumbnail_height);

		imagedestroy($process);
		return $thumb;
	}

	// Function for getting the file extension
	function get_image_extension($str) {
		 $i = strrpos($str,".");
		 if (!$i) { return ""; } 
		 $l = strlen($str) - $i;
		 $ext = substr($str,$i+1,$l);
		 return $ext;
	}

	// Function, which returns the address of the corrent web page
	function current_page_url() {
		$pageURL = 'http';
		if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		$pageURL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}
	
	// Convert text to safe for SQL view
	function _validate_text($data) {
		$FORBIDDEN = array("javascript", "--");
		$data = str_ireplace($FORBIDDEN, " ", $data);
		$data = mysql_real_escape_string($data);
		return $data;
	}
	
	// Functiin witch create coomon view of tags
	function _validate_tags($data) {
		$SYMBOLS = array("~","`","!","@","#","$","%","^","&","*","(",")","_","+","=","-",":",";","\"","'","{","}","[","]","|","\\","?","/",">",".","<",",","№","\n","\r","\t");
		$data = str_replace($SYMBOLS, "", $data);
		$data = str_replace("  ", " ", $data);
		$data = _validate_text($data);
		$data = strtolower($data);
		return $data;
	}
	
	// Convert email to safe for SQL view, and do not check if it looks like email
	function _validate_email($data) {
		$data = _validate_text($data);
		return $data;
	}
	
	// Function for checking if strings begins with the search string
	function _string_begins_with($string, $search) {
		return (strncmp($string, $search, strlen($search)) == 0);
	}
	
	// Convert URL to safe for SQL and standard view
	function _validate_url($data) {
		$data = _validate_text($data);
		$prefix = "http://";
		if (strncmp($data, $prefix, strlen($prefix)) != 0)
			$data = $prefix . $data;
		return $data;
	}
	
	// Convert int to safe for SQL view
	function _validate_int($data) {
		$data = mysql_real_escape_string($data);
		return $data;
	}
	
	// Convert float to safe for SQL view
	function _validate_float($data) {
		$data = mysql_real_escape_string($data);
		return $data;
	}
	
	// Convert values to the safe for SQL view
	function validate_data($data, $type) {
		switch ($type) {
			case "text":
				$data = _validate_text($data);
				return $data;
				break;
			case "tags":
				$data = _validate_tags($data);
				return $data;
				break;
			case "email":
				$data = _validate_email($data);
				return $data;
				break;
			case "url":
				$data = _validate_url($data);
				return $data;
				break;
			case "int":
				$data = _validate_int($data);
				return $data;
				break;
			case "float":
				$data = _validate_float($data);
				return $data;
				break;
			default:
				return $data;
		}
	}
	
	// Validate email address
	function is_email_valid($email) {
		$result = true;
		if(!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $email)) {
			$result = false;
		}
		return $result;
	}

	// Create search request. If empty string is given all items will be found
	function build_search_query($search_string, $number_of_results_to_display, $page) {
		// Setup initial values
		$table_name = " pixel_data ";
		$search_query_start = "SELECT * FROM  ";
		$search_query_end = " ORDER BY Tags";
		$search_query = "";

		$start_from_pixel = ($page - 1) * $number_of_results_to_display;
		$limit = " LIMIT " . $start_from_pixel . "," . $number_of_results_to_display;
		
		$SYMBOLS = array("~","`","!","@","#","$","%","^","&","*","(",")","_","+","=","-",":",";","\"","'","{","}","[","]","|","\\","?","/",">",".","<",",","№","\n","\r","\t");
		$search_clean_string = str_replace($SYMBOLS, " ", $search_string);
		$search_words = explode(" ", $search_clean_string);
		$final_search_words = array();
		if (count($search_words) > 0) {
			foreach ($search_words as $word) {
				$final_search_words[] = $word;
			}
		}
		
		// Create sequence of LIKE for each word
		$where_list = array();
		if (count($final_search_words) > 0) {
			foreach ($final_search_words as $sq) {
				$where_list[] = " where Description like '%$sq%' or Tags like '%$sq%' or Title like '%$sq%' ";;
			}
		}

		// Make request
		$tail = "";
		$body = "";
		if (count($where_list) > 0) {
			for ($i = 0; $i < count($where_list); $i++) {
				if ($i == count($where_list) - 1) {
					// Last element
					$body .= $table_name;
					$tail =  $where_list[$i] . $tail;
				} else {
					// Not last element
					$body .= " (" . $search_query_start;
					$tail = ") as table" . $i . $where_list[$i] . $tail;
				}
			}
		}
		$search_query = $search_query_start . $body . $tail . $search_query_end . $limit;
		
		return $search_query;
	}

	// Place the reCAPTCHA code in place
	function recaptcha_code() {
		global $RECAPTCHA_PUBLIC_KEY, $RECAPTCHA_PRIVATE_KEY;
		require_once('recaptchalib.php');
		return recaptcha_get_html($RECAPTCHA_PUBLIC_KEY);
	}

	// Check reCAPTCHA response
	function recaptcha_check() {
		global $RECAPTCHA_PUBLIC_KEY, $RECAPTCHA_PRIVATE_KEY;
		require_once('recaptchalib.php');
		
		// The response from reCAPTCHA
		$resp = null;
		// The error code from reCAPTCHA, if any
		$error = null;

		$resp = recaptcha_check_answer ($RECAPTCHA_PRIVATE_KEY,
                                        $_SERVER["REMOTE_ADDR"],
                                        $_REQUEST["recaptcha_challenge_field"],
                                        $_REQUEST["recaptcha_response_field"]);

        if ($resp->is_valid) {
                return true;
        } else {
                // Set the error code so that we can display it
                $error = $resp->error;
        }
	}

	// Function for adding "..." to the long line
	function truncate($text, $numb) {
		//$text = html_entity_decode($text, ENT_QUOTES);
		if (mb_strlen($text, "UTF-8") > $numb) {
			$text = mb_substr($text, 0, $numb, "UTF-8");
			$etc = "...";
			$text = $text . $etc;
		}
		//$text = htmlentities($text, ENT_QUOTES);
		return $text;
	}
?>
