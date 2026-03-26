<?php
function sendPOST(array $data, string $endpoint, $forceIp = null)
{
    $payload = json_encode($data);
	$url = "https://uqiaole.info/api/v1/{$endpoint}";

    $curl = curl_init();

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 8,
    ];

    if ($forceIp) {
        $host = parse_url($url, PHP_URL_HOST);
        $options[CURLOPT_RESOLVE] = ["{$host}:443:{$forceIp}"];
    }

    curl_setopt_array($curl, $options);

    $response = curl_exec($curl);
    $error = curl_error($curl);
    $errno = curl_errno($curl);
    $info = curl_getinfo($curl);

    curl_close($curl);

    return [$response, $error, $errno, $info];
}

function getCacheFile(string $key): string {
	return dirname(dirname(__DIR__)) . '/crkSettings/links/' . md5($key) . '.json';
}

function loadFromCache(string $key) {
	$file = getCacheFile($key);
	if (!file_exists($file)) return null;

	if (filemtime($file) + 300 < time()) {
		unlink($file); // просрочено
		return null;
	}

	$data = file_get_contents($file);
	return $data ?? null;
}

function saveToCache(string $key, $data): void {
	if (!is_dir(dirname(dirname(__DIR__)) . "/crkSettings/links/")) {
		mkdir(dirname(dirname(__DIR__)) . "/crkSettings/links/", 0777, true);
	}

	file_put_contents(getCacheFile($key), json_encode($data));
}