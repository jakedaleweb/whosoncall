<?php

// Brings in OPSGENIE_API_KEY, needs to be configured one level above this file
require('../whosoncall_config.php');

/**
* Performs the curl request to OpsGenie to get oncall people
* @return Array
*/
function getSchedules() {
	// 18:30 ensures we ge the oncall person, which is the whole point of this script
	$formattedDate = date('Y-m-d').'T18:30:00%2B12:00';

	$ch = curl_init();
	curl_setopt_array($ch, [
	    CURLOPT_RETURNTRANSFER => 1,
	    CURLOPT_URL => 'https://api.opsgenie.com/v2/schedules/on-calls?date='.$formattedDate,
	    CURLOPT_HTTPHEADER => ['Authorization: GenieKey '.OPSGENIE_API_KEY],
	]);

	$response = curl_exec($ch);
	$info = (curl_getinfo($ch));
	curl_close($ch);

	if ($info['http_code'] != '200') {
		var_dump($response);
		die();
	}

	$data = json_decode($response, true);	
	$schedules = [];

	foreach ($data['data'] as $schedule) {
		$schedules[$schedule['_parent']['name']] = getOnCallPeople($schedule);
	}
	
	foreach ($schedules as $key => $val) {
		if (!$val) {
			unset($schedules[$key]);
		}
	}

	return $schedules;
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

/* CONTENT DISPLAYED BELOW THIS POINT */
$message = "<h1>Who's on call?</h1>";
$message .= "<p>This page shows the oncall person as of 18:30 today, you can assume that the person shown under \"On_call_schedule\" is the oncall person for this week unless told otherwise.";

$schedules = getSchedules();
foreach ($schedules as $name => $oncall) {
	$message .= "<h2>".$name."</h2>";
	foreach ($oncall as $oncall_staff) {
		$message .= "<p>".$oncall_staff."</p>";
	}	
}

echo $message;