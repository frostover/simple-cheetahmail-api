<?php

//Simple Cheetahmail API
//Nathan Corbin, admin@frostover.com
//Github: https://github.com/frostover/simple-cheetahmail-api
//Oct 11 2016

class CheetahMailAPI {
	public static $LOGIN_URL = "https://ebm.cheetahmail.com/api/login1";
	public static $SETUSER_URL = "https://ebm.cheetahmail.com/api/setuser1";

	private static $USERNAME = '';
	private static $PASSWORD = '';

	//Activates the api to send requests
	public static function activate() {
		//Checks to see if the cookie is valid or not
		$cookie = CheetahMailAPI::getCookie();
		if(!isset($cookie)) {
			//We need to reinit the cookie
			CheetahMailAPI::sendLoginRequest(CheetahMailAPI::$USERNAME, CheetahMailAPI::$PASSWORD);

			//Check and make sure it's valid
			$cookie = CheetahMailAPI::getCookie();
			if(!isset($cookie)) {
				return false;
			}
		} else {
			return true;
		}

		return true;
	}

	//Sends a login request to the server
	public static function sendLoginRequest($username, $password) {
		if( !isset($username) || empty($username) || !isset($password) || empty($password)) {
			throw new Exception('Username and/or password is required. Please check and make sure they are filled in.');
		}

		//Build the login url
		$url = CheetahMailAPI::$LOGIN_URL . '?name='.$username.'&cleartext='.$password;

		//Submit request via curl
		$response = CheetahMailAPI::submitRequest($url);

		//Check response, if it contains err:auth it failed
		if(strpos($response, 'err:auth') !== false) {
			return false;
		} else {
			//Need to save this cookie, clear the old data first
			file_put_contents("cookie.txt", "");

			//Seems really dumb that they send back required data as a cookie...
			preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
			$cookie = $matches[1][0];

			//Store in JSON so we can check the time
			$json = array(
				'cookie' => $cookie,
				'time'   => date('Y-m-d H:i:s'),
			);
			
			//Write the JSON to file
			file_put_contents('cookie.txt', json_encode($json));

			//Successful
			return true;
		}
	}

	//Adds a user to the campaign
	public static function addToCampaign($email, $sub, $fname = "", $lname = "") {
		//Build the url, make sure to encode incase of spaces / special chars
		$url = CheetahMailAPI::$SETUSER_URL . '?email='.urlencode($email).'&FNAME='.urlencode($fname).'&LNAME='.urlencode($lname).'&sub='.$sub;

		//Make sure we have a valid cookie
		$cookie = CheetahMailAPI::getCookie();
		if(!isset($cookie)) {
			throw new Exception('Could not locate saved transactional cookie.');
		}

		//Submit the request
		$response = CheetahMailAPI::submitRequest($url, $cookie);

		var_dump($response);

		//Check the response
		if(strpos($response, 'err') !== false) {
			throw new Exception('Could not add user to campaign. Reason: ' . $response);
		}

		//Success
		return true;
	}

	//Returns the cookie for all API requests
	public static function getCookie() {
		//Read contents of the file
		$file = file_get_contents('cookie.txt');

		//Make sure it has data
		if(empty($file) || !isset($file)) {
			return null;
		}

		//Check if the timestamp is valid (expires every 8 hours)
		$json = json_decode($file);
		$time = $json->time;

		if (strtotime($time) <= strtotime('-8 hours')) {
    		return null;
		}

		//Return the cookie
		return $json->cookie;
	}

	//Global submit cURL function that is pretty generic
	public static function submitRequest($url, $cookie = "", $type = "GET", $params = array()) {
		$response = '';

		try {
			// Get cURL resource
			$curl = curl_init();

			if (FALSE === $curl)
        		throw new Exception('Failed to init cURL, make sure it is enabled.');

			//Sets the cookie API
			//The cookie name can change so just add it directly how it's passed
			if(!empty($cookie)) {
				curl_setopt($curl, CURLOPT_HTTPHEADER, array('Cookie: ' . $cookie));
			}

			if($type == 'GET') {
				// Set some options - we are passing in a useragent too here
				curl_setopt_array($curl, array(
					CURLOPT_HEADER => 1,
				    CURLOPT_RETURNTRANSFER => 1,
				    CURLOPT_URL => $url,
				    CURLOPT_USERAGENT => 'cURL Request'
				));
			} else {
				//TODO: implement POST type and assign vars
			}

			// Send the request & save response to $resp
			$response = curl_exec($curl);

			//Error out
			if (FALSE === $response)
        		throw new Exception(curl_error($curl), curl_errno($curl));

			// Close request to clear up some resources
			curl_close($curl);
		} catch(Exception $e) {
			throw new Exception("Could not submit request. Reason: " . $e, 1);
		}

		//Send back to response
		return $response;
	}
}