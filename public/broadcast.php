<?php

require __DIR__.'/../init.php';

header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');
header('Content-Type: application/json');

$channelId = array_key_exists('channel', $_POST) ? $_POST['channel'] : null;
$timestamp = array_key_exists('timestamp', $_POST) ? $_POST['timestamp'] : null;
$event = array_key_exists('event', $_POST) ? $_POST['event'] : null;
$data = array_key_exists('data', $_POST) ? $_POST['data'] : null;
$signature = array_key_exists('signature', $_POST) ? $_POST['signature'] : null;

if (empty($channelId) || empty($timestamp) || empty($event) || empty($data) || empty($signature)) {
    http_response_code(400);
    exit;
}

if ($signature !== hash_hmac('sha256', $channelId.$timestamp.$event.$data, PHPMQ_SECRET_KEY)) {
    http_response_code(401);
    exit;
}

if (time() >= $timestamp + PHPMQ_REQUEST_TIMEOUT) {
    http_response_code(401);
    exit;
}

destroyInactiveClients();
destroyUnusedChannels();

initChannel($channelId);

$clients = $db->query(sprintf(
    'SELECT `id` FROM `client` WHERE `channel_id` = "%s"',
    $db->real_escape_string($channelId)
));

foreach ($clients as $client) {
    $db->query(sprintf(
        'INSERT INTO `message` (`id`, `client_id`, `timestamp`, `event`, `data`) VALUES (%d, %d, SYSDATE(6), "%s", "%s")',
        random_int(1, PHP_INT_MAX),
        $client['id'],
        $db->real_escape_string(event),
        $db->real_escape_string(data)
    ));
}

http_response_code(200);
