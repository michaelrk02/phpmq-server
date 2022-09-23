<?php

require __DIR__.'/../init.php';

header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');
header('Content-Type: application/json');

$clientId = array_key_exists('client', $_POST) ? $_POST['client'] : null;
$timestamp = array_key_exists('timestamp', $_POST) ? $_POST['timestamp'] : null;
$signature = array_key_exists('signature', $_POST) ? $_POST['signature'] : null;

if (empty($timestamp) || empty($clientId) || empty($signature)) {
    http_response_code(400);
    exit;
}

if ($signature !== hash_hmac('sha256', $clientId.$timestamp, PHPMQ_SECRET_KEY)) {
    http_response_code(401);
    exit;
}

if (time() >= $timestamp + PHPMQ_REQUEST_TIMEOUT) {
    http_response_code(401);
    exit;
}

$db->query(sprintf(
    'UPDATE `client` SET `ping_at` = NOW() WHERE `id` = %d',
    $clientId
));

http_response_code(200);
