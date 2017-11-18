<?php

// Parameters
$types = [
	'qwant'    => [
		'request'   => function($q) {
			return getJson('https://api.qwant.com/api/suggest?q=%s&lang=fr_fr', $q);
		},
		'transform' => function(array $input) {
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
		},
	],
	'allocine' => [
		'request'   => function($q) {
			return getJson('http://essearch.allocine.net/fr/autocomplete?geo2=83085&q=%s', $q);
		},
		'transform' => function(array $input) {
			$output = [];
			foreach ($input as $e) {
				if (isset($e['title1'])) {
					$output[] = $e['title1'];
				} else if (isset($e['title2'])) {
					$output[] = $e['title2'];
				}
			}
			return $output;
		},
	],
	'imdb'     => [
		'request'   => function($q) {
			$q   = mb_strtolower($q);
			$url = 'https://v2.sg.media-imdb.com/suggests/'.substr($q, 0, 1).'/%s.json';
			$raw = file_get_contents(sprintf($url, $q));
			$raw = substr($raw, strlen($q)+6);
			$raw = substr($raw, 0, strlen($raw)-1);
			return json_decode($raw, true);
		},
		'transform' => function(array $input) {
			$output = [];
			if (isset($input['d'])) {
				$elt = $input['d'];
				foreach ($elt as $e) {
					if (isset($e['l'])) {
						$output[] = $e['l'];
					}
				}
			}
			return $output;
		},
	],
];

// Declare default type here
$default = 'qwant';

// DO NOT MODIFY BELOW!!!!
function getJson($url, $q) {
	$url = sprintf($url, $q);
	$raw = file_get_contents($url);
	return json_decode($raw, true);
}

// To test the script on command line
// Usage: php -f /path_to_youscriptname.php t=<type> q=<word_to_complete>
foreach($argv as $arg) {
	$e = explode("=", $arg);
	if (count($e)==2) {
		$_GET[$e[0]] = $e[1];
	}
}

// Preparation of the variables
$t = (isset($_GET['t'])) ? $_GET['t'] : $default;
if (!isset($types[$t])) $t = $default;
$q = (isset($_GET['q'])) ? $_GET['q'] : '';

// Execute the request
$input = $types[$t]['request']($q);

// Extract wanted suggestions
$output = $types[$t]['transform']($input);

// Return the response
header('Content-Type: application/json');
echo json_encode([$q, $output]);
