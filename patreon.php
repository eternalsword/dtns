<?php

function curl_get_file_contents($URL) {
	$c = curl_init();
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_URL, $URL);
	$contents = curl_exec($c);
	curl_close($c);

	if ($contents) return $contents;
		else return FALSE;
}

$allowed_origins = array('http://s.codepen.io', 'http://www.dailytechnewsshow.com/');
$origin = $_SERVER['HTTP_ORIGIN'];
if(is_null($origin) || in_array($origin, $allowed_origins)) {
	header("Access-Control-Allow-Origin: $origin");
	header('Access-Control-Allow-Methods: GET');
}
else {
	die();
}
header('Content-Type: application/json');
$status_header = 'HTTP/1.0 400 Bad Request';
$json = new stdClass;
if(array_key_exists('account_name', $_GET)) {
	$account_name = $_GET['account_name'];
	if(is_string($account_name) && !empty($account_name)) {
		$status_header = 'HTTP/1.0 200 OK';
		$headers[] = '';
		$uri = 'http://www.patreon.com/' . $account_name;
		$contents = curl_get_file_contents($uri);
		$patreon = new DOMDocument();
		if($patreon->loadHTML($contents)) {
			$json->total_earnings = $patreon->getElementById('totalEarnings')->textContent;
		}
		else {
			$status_header = 'HTTP/1.0 500 Internal Server Error';
			$json->error = '500 Internal Server Error';
			$json->error_message = 'There was an error retrieving or parsing the contents of the patreon page.';
		}
	}
	else {
		$json->error = '400 Bad Request';
		$json->error_message = 'The account_name parameter is malformed.';
	}
}
else {
	$json->error = '400 Bad Request';
	$json->error_message= 'The account_name parameter is missing.';
}
header($status_header);
echo json_encode($json);
