<?php

require __DIR__.'/../init.php';

header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');
header('Content-Type: text/event-stream');

$channelId = array_key_exists('channel', $_GET) ? $_GET['channel'] : null;
$timestamp = array_key_exists('timestamp', $_GET) ? $_GET['timestamp'] : null;
$signature = array_key_exists('signature', $_GET) ? $_GET['signature'] : null;

if (empty($channelId) || empty($timestamp) || empty($signature)) {
    http_response_code(400);
    exit;
}

if ($signature !== hash_hmac('sha256', $channelId.$timestamp, PHPMQ_SECRET_KEY)) {
    http_response_code(401);
    exit;
}

if (time() >= $timestamp + PHPMQ_REQUEST_TIMEOUT) {
    http_response_code(401);
    exit;
}

ignore_user_abort(true);

destroyInactiveClients();
destroyUnusedChannels();

initChannel($channelId);
$clientId = initClient($channelId);

http_response_code(200);

echo 'event: phpmq_client_id'."\n";
echo 'data: '.$clientId."\n\n";
ob_flush();
flush();

$pingCounter = 0;

while (true) {
    if ($pingCounter === 0) {
        echo ':ping'."\n\n";
        ob_flush();
        flush();

        if (connection_aborted()) {
            destroyClient($clientId);
            destroyInactiveClients();
            destroyUnusedChannels();
            exit;
        }

        $db->query(sprintf(
            'UPDATE `client` SET `ping_at` = NOW() WHERE `id` = %d',
            $clientId
        ));
    }

    if ($pollCounter === 0) {
        $messages = $db->query(sprintf(
            'SELECT * FROM `message` WHERE `client_id` = %d ORDER BY `timestamp`',
            $clientId
        ))->fetch_all(MYSQLI_ASSOC);

        $db->query(sprintf(
            'DELETE FROM `message` WHERE `client_id` = %d',
            $clientId
        ));

        foreach ($messages as $message) {
            echo 'event: '.$message['event']."\n";
            echo 'data: '.$message['data']."\n\n";
            ob_flush();
            flush();
        }
    }

    sleep(1);

    $pingCounter = ($pingCounter + 1) % PHPMQ_PING_INTERVAL;
    $pollCounter = ($pollCounter + 1) % PHPMQ_POLL_INTERVAL;
}
