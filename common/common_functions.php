<?php

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
	
	// Function witch create common view of tags
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

	// Place the reCAPTCHA code in place
	function recaptcha_place_code() {
		global $RECAPTCHA_PUBLIC_KEY, $RECAPTCHA_PRIVATE_KEY;
		require_once('recaptchalib.php');
		return recaptcha_get_html($RECAPTCHA_PUBLIC_KEY);
	}

	// Check reCAPTCHA response
	function recaptcha_is_valid() {
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
                return false;
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
