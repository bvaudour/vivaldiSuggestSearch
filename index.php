<?php

// To test the script on command line
// Usage: php -f /path_to_youscriptname.php t=<type> q=<word_to_complete>
foreach($argv as $arg) {
	$e = explode("=", $arg);
	if (count($e)==2) {
		$_GET[$e[0]] = $e[1];
	}
}

// Declare your search engine (<type>) here, with the official autosuggest URL of the search engine
$types = [
	'qwant'    => 'https://api.qwant.com/api/suggest?q=%s&lang=fr_fr',
	'allocine' => 'http://essearch.allocine.net/fr/autocomplete?geo2=83085&q=%s',
];

// Declare your default type here
$default = 'qwant';

// Declare your function transformation here
// function takes thes input as array representation of the request and should return an array of the need suggestions
function transformQwant(array $input) {
	$output = [];
	if (isset($input['data'])) {
		$elt = $input['data'];
		if (isset($elt['items'])) {
			$elt = $elt['items'];
			foreach ($elt as $e) {
				if (isset($e['value'])) {
					$output[] = $e['value'];
				}
			}
		}
	}
	return $output;
}

function transformAllocine(array $input) {
	$output = [];
	foreach ($input as $e) {
		if (isset($e['title1'])) {
			$output[] = $e['title1'];
		} else if (isset($e['title2'])) {
			$output[] = $e['title2'];
		}
	}
	return $output;
}

// Preparation of the variables
$t = (isset($_GET['t'])) ? $_GET['t'] : $default;
if (!isset($types[$t])) $t = $default;
$q = (isset($_GET['q'])) ? $_GET['q'] : '';
$url = sprintf($types[$t], $q);

// Get the needed data to prepare the autosuggestion
$raw  = file_get_contents($url);
$json = json_decode($raw, true);

// Apply transform functions to make the suggestions Vivaldi-compliant
$results = [$q];
switch ($t) {
	case 'qwant':
		$results[] = transformQwant($json);
		break;
	case 'allocine':
		$results[] = transformAllocine($json);
		break;
}

// Return the formatted output
header('Content-Type: application/json');
echo json_encode($results);
