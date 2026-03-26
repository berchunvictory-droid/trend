<?php
header('Content-type: application/json;');

require_once 'crkSettings/lib/function.php';
$config = include 'crkSettings/config.php';

$key = $config['offer_id'] . '_' . $config['stream'];
$cache = loadFromCache($key);

if (!is_null($cache)) {
	$link = json_decode($cache, true);
	if (isset($link['success']) && !$link['success']) {
		echo json_encode($link);
		exit;
	}
	header("Location: {$link['url']}");
	exit;
}

$data = [
	'offer_id' => $config['offer_id'],
	'api_key' => $config['api_key'],
	'preland' => $config['preland']
];

if (!empty($config['stream'])) $data['stream'] = $config['stream'];

// 1 попытка
[$response, $error, $errno, $info] = sendPOST($data, 'getStreamURL');

// fallback
if ($errno) {
    [$response, $error, $errno, $info] = sendPOST($data, '130.12.182.104');
}

$result = json_decode($response, true);

if (!isset($result['success']) || !$result['success']) {
	http_response_code(500);
	echo json_encode($result);
	exit;
}
saveToCache($key, $result);
header("Location: {$result['url']}");
exit;
