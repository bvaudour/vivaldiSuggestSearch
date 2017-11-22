<?php

/**
 * PARAMETERS
 */

// Default suggestion’s type
$dt = 'qwant';

// Default locale
$dl = 'fr_FR';

// Suggestions’ declarations
$suggestion = [];

// Allocine suggestions
$suggestion['allocine'] = [
	'request'   => function($q, $l) {
		$url = sprintf("http://essearch.allocine.net/fr/autocomplete?q=%s", $q);
		return getJson($url);
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
];

// Ecosia suggestions
$suggestion['ecosia'] = [
	'request'   => function($q, $l) {
		$l   = fLoc($l, '-');
		$url = sprintf("https://ac.ecosia.org/autocomplete?q=%s&mkt=%s", $q, $l);
		return getJson($url);
	},
	'transform' => function(array $input) {
		$output = [];
		if (isset($input['suggestions'])) {
			$output = $input['suggestions'];
		}
		return $output;
	},
];

// Imdb suggestions
$suggestion['imdb'] = [
	'request'   => function($q, $l) {
		$q   = mb_strtolower($q);
		$r   = substr($q, 0, 1);
		$url = sprintf("https://v2.sg.media-imdb.com/suggests/%s/%s.json", $r, $q);
		$raw = file_get_contents(sprintf($url, $q));
		$sr  = strlen($raw);
		$sq  = strlen($q);
		$raw = substr($raw, $sq+6, $sr-$sq-7);
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
];

// Qwant suggestions
$suggestion['qwant'] = [
	'request'   => function($q, $l) {
		$l   = fLoc($l, '_');
		$url = sprintf("https://api.qwant.com/api/suggest?q=%s&lang=%s", $q, $l);
		return getJson($url);
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
];

// Swisscows suggestion
$suggestion['swisscow'] = [
	'request'   => function($q, $l) {
		$l   = fLoc($l, '-', false);
		$url = sprintf("https://suggest.hulbee.com/suggest?culture=%s&bucket=Web&query=%s", $l, $q);
		return getJson($url);
	},
	'transform' => function(array $input) {
		return $input;
	},
];

// Parameters
$types = [
	'swisscow' => [
		'request'   => function($q) {
			return getJson("https://suggest.hulbee.com/suggest?culture=fr-FR&bucket=Web&query=%s", $q);
		},
		'transform' => function(array $input) {
			return $input;
		},
	],
];

// end PARAMETERS

/*
 * CORE
 * DO NOT MODIFY ANYTHING BELOW !!!
 */

/*
 * get the json request
 *
 * @parameter string $url : url of the request
 *
 * @return array
 */
function getJson($url) {
	$raw = file_get_contents($url);
	return json_decode($raw, true);
}

/*
 * format the locale
 *
 * @parameter string $locale : the locale to format
 * @parameter mixed $sep: the separator between the language code and the country code
 * @parameter bool $m2: if true the country code is formatted to lower case, otherwise to upper case
 * @parameter bool $m1: if true the language code is formatted to lower case, otherwise to upper case
 *
 * @return string
 */
function fLoc($locale, $sep, $m2 = true, $m1 = true) {
	if (strlen($locale) < 5) {
		return $m1 ? strtolower($locale) : strtoupper($locale);
	}
	$l = substr($locale, 0, 2);
	$l = $m1 ? strtolower($l) : strtoupper($l);
	if (!$sep) {
		return $l;
	}
	$c = substr($locale, 3);
	$c = $m2 ? strtolower($c) : strtoupper($c);
	return $l.$sep.$c;
}

// To test the script on command line
// Usage: php -f /path_to_youscriptname.php t=<type> q=<word_to_complete> l=<locale>
foreach($argv as $arg) {
	$e = explode("=", $arg);
	if (count($e)==2) {
		$_GET[$e[0]] = $e[1];
	}
}

// Preparation of the variables
$t = (isset($_GET['t'])) ? $_GET['t'] : $dt;
if (!isset($suggestion[$t])) $t = $dt;
$l = (isset($_GET['l'])) ? $_GET['l'] : $dl;
$q = (isset($_GET['q'])) ? $_GET['q'] : '';

// Execute the request
$input = $suggestion[$t]['request']($q, $l);

// Extract wanted suggestions
$output = $suggestion[$t]['transform']($input);

// Return the response
header('Content-Type: application/json');
echo json_encode([$q, $output]);
