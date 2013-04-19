<?php

//--------------------------------------------------------------------------------------------------
/*
* PHP port of Ruby on Rails famous distance_of_time_in_words method. 
*  See http://api.rubyonrails.com/classes/ActionView/Helpers/DateHelper.html for more details.
*
* Reports the approximate distance in time between two timestamps. Set include_seconds 
* to true if you want more detailed approximations.
*
*/
function distanceOfTimeInWords($from_time, $to_time = 0, $include_seconds = false) {
	$distance_in_minutes = round(abs($to_time - $from_time) / 60);
	$distance_in_seconds = round(abs($to_time - $from_time));

	if ($distance_in_minutes >= 0 and $distance_in_minutes <= 1) {
		if (!$include_seconds) {
			return ($distance_in_minutes == 0) ? 'less than a minute' : '1 minute';
		} else {
			if ($distance_in_seconds >= 0 and $distance_in_seconds <= 4) {
				return 'less than 5 seconds';
			} elseif ($distance_in_seconds >= 5 and $distance_in_seconds <= 9) {
				return 'less than 10 seconds';
			} elseif ($distance_in_seconds >= 10 and $distance_in_seconds <= 19) {
				return 'less than 20 seconds';
			} elseif ($distance_in_seconds >= 20 and $distance_in_seconds <= 39) {
				return 'half a minute';
			} elseif ($distance_in_seconds >= 40 and $distance_in_seconds <= 59) {
				return 'less than a minute';
			} else {
				return '1 minute';
			}
		}
	} elseif ($distance_in_minutes >= 2 and $distance_in_minutes <= 44) {
		return $distance_in_minutes . ' minutes';
	} elseif ($distance_in_minutes >= 45 and $distance_in_minutes <= 89) {
		return 'about 1 hour';
	} elseif ($distance_in_minutes >= 90 and $distance_in_minutes <= 1439) {
		return 'about ' . round(floatval($distance_in_minutes) / 60.0) . ' hours';
	} elseif ($distance_in_minutes >= 1440 and $distance_in_minutes <= 2879) {
		return '1 day';
	} elseif ($distance_in_minutes >= 2880 and $distance_in_minutes <= 43199) {
		return 'about ' . round(floatval($distance_in_minutes) / 1440) . ' days';
	} elseif ($distance_in_minutes >= 43200 and $distance_in_minutes <= 86399) {
		return 'about 1 month';
	} elseif ($distance_in_minutes >= 86400 and $distance_in_minutes <= 525599) {
		return round(floatval($distance_in_minutes) / 43200) . ' months';
	} elseif ($distance_in_minutes >= 525600 and $distance_in_minutes <= 1051199) {
		return 'about 1 year';
	} else {
		return 'over ' . round(floatval($distance_in_minutes) / 525600) . ' years';
	}
}

//--------------------------------------------------------------------------------------------------
// Common point of failure is an updated wiki namespace
function xml_edits($xml, $limit = 0)
{
	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	// Add namespaces to XPath to ensure our queries work
	$xpath->registerNamespace("wiki", "http://www.mediawiki.org/xml/export-0.8/");
	$xpath->registerNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
	$nodeCollection = $xpath->query ("//wiki:revision");
	
	$result = new stdclass;
	$result->user_id_list = array();
	$result->user_name_list = array();	
	$result->edits = array();
	
	$revision_count = 0;
	
	foreach($nodeCollection as $node)
	{
		$edit = new stdclass;
	
		$nc = $xpath->query ("wiki:id", $node);
		foreach ($nc as $n)
		{
			$edit->id = $n->firstChild->nodeValue;
		}
		$nc = $xpath->query ("wiki:timestamp", $node);
		foreach ($nc as $n)
		{
			$edit->time = $n->firstChild->nodeValue;
			$edit->timestamp = strtotime($n->firstChild->nodeValue);
		}
	
		// user id
		$nc = $xpath->query ("wiki:contributor/wiki:id", $node);
		foreach ($nc as $n)
		{
			$edit->userid = $n->firstChild->nodeValue;
			if (!in_array($edit->userid, $result->user_id_list))
			{
				array_push($result->user_id_list, $edit->userid);
			}
		}
		// IP address
		$nc = $xpath->query ("wiki:contributor/wiki:ip", $node);
		foreach ($nc as $n)
		{
			$edit->userid = $n->firstChild->nodeValue;
			if (!in_array($edit->userid, $result->user_id_list))
			{
				array_push($result->user_id_list, $edit->userid);
			}
		}
		
		// name
		$nc = $xpath->query ("wiki:contributor/wiki:username", $node);
		foreach ($nc as $n)
		{
			$edit->username = $n->firstChild->nodeValue;
			$result->user_name_list[$edit->userid] = $edit->username;
		}
		
		// text
		$nc = $xpath->query ("wiki:text", $node);
		foreach ($nc as $n)
		{
			$edit->text = $n->firstChild->nodeValue;
		}
		
		if (($limit == 0) or ($revision_count < $limit))
		{
			array_push($result->edits, $edit);
		}
		else
		{
			break;
		}
		$revision_count++;
		
	}
	return $result;
	
}


// test
if (0)
{
	$e = xml_edits($xml);
	print_r($e);
}









?>