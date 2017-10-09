<?php

// Brings in OPSGENIE_API_KEY, needs to be configured one level above this file
require('../whosoncall_config.php');

/**
* Performs the curl request to OpsGenie to get oncall people
* @param String $message
*/
function getSchedules(&$message) {
	// 18:30 ensures we ge the oncall person, which is the whole point of this script
	$formattedDate = date('Y-m-d').'T18:30:00%2B12:00';
	$url = 'https://api.opsgenie.com/v2/schedules/on-calls?date='.$formattedDate;
	$data = doCurl($url);

	$schedules = [];
	foreach ($data['data'] as $schedule) {
		$schedules[$schedule['_parent']['name']] = getOnCallPeople($schedule);
	}
	
	foreach ($schedules as $key => $val) {
		if (!$val) {
			unset($schedules[$key]);
		}
	}

	foreach ($schedules as $name => $oncall) {
		$message .= "<h2>".$name."</h2>";
		foreach ($oncall as $oncall_staff) {
			$message .= "<p><a href='?oncall_staff=$oncall_staff'>".$oncall_staff."</a></p>";
		}	
	}
}


/**
* Returns all oncall users for a particular schedule
* @param Array $schedule
* @return Array $users 
*/
function getOnCallPeople($schedule)
{	
	$users = [];
	foreach($schedule['onCallParticipants'] as $user) {
		$users[] = $user['name'];
	}
	return $users;
}

/**
* Generic method to hit required URL with a curl
* @param String $url
* @return Array $response
*/
function doCurl($url)
{
	$ch = curl_init();
	curl_setopt_array($ch, [
	    CURLOPT_RETURNTRANSFER => 1,
	    CURLOPT_URL => $url,
	    CURLOPT_HTTPHEADER => ['Authorization: GenieKey '.OPSGENIE_API_KEY],
	]);

	$response = curl_exec($ch);
	$info = (curl_getinfo($ch));
	curl_close($ch);

	if ($info['http_code'] != '200') {
		var_dump($response);
		die();
	}

	$response = json_decode($response, true);	
	return $response;
}

/**
* Gets details of the supplied user
* @param String $email
* @return String $message
*/
function getUserDetails($email)
{
	$url = 'https://api.opsgenie.com/v2/users/'.$email.'/contacts';
	$response = doCurl($url);

	$details = [];
	foreach ($response['data'] as $method) {
		$details[$method['method']] = $method['to'];
	}

	$message = '';
	foreach ($details as $type => $value) {
		$message .= "<p>$type: $value</p>";
	}
	return $message;
}

/* CONTENT DISPLAYED BELOW THIS POINT */
$message = "<h1>Who's on call?</h1>";
$message .= "<p>This page shows the oncall person as of 18:30 today, you can assume that the person shown under \"On_call_schedule\" is the oncall person for this week unless told otherwise.";
getSchedules($message);

if (isset($_GET['oncall_staff'])) {
	$message = getUserDetails($_GET['oncall_staff']);
}

echo $message;