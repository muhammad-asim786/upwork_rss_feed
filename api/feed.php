<?php
/**
 * Serves stored RSS feed by name.
 * GET: name (feed name) → returns XML.
 */
header('Content-Type: application/rss+xml; charset=utf-8');

$redisUrl = getenv('UPSTASH_REDIS_REST_URL') ?: getenv('KV_REST_API_URL');
$redisToken = getenv('UPSTASH_REDIS_REST_TOKEN') ?: getenv('KV_REST_API_TOKEN');

$feedName = isset($_GET['name']) ? trim($_GET['name']) : '';
if ($feedName === '') {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Missing name parameter';
    return;
}

if (!$redisUrl || !$redisToken) {
    http_response_code(503);
    header('Content-Type: text/plain');
    echo 'Feed storage not configured';
    return;
}

$redisKey = 'feed:' . $feedName;
$getUrl = rtrim($redisUrl, '/') . '/get/' . rawurlencode($redisKey);

$ch = curl_init($getUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $redisToken],
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200) {
    http_response_code(502);
    header('Content-Type: text/plain');
    echo 'Failed to load feed';
    return;
}

$data = json_decode($res, true);
$xml = isset($data['result']) ? $data['result'] : '';

if ($xml === '' || $xml === null) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Feed not found';
    return;
}

echo $xml;
