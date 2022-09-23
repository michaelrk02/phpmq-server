<?php

require __DIR__.'/config.php';

$db = null;

function initDatabase()
{
    global $db;

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new \mysqli(PHPMQ_DB_HOST, PHPMQ_DB_USER, PHPMQ_DB_PASS, PHPMQ_DB_NAME);
}

function initChannel($channelId)
{
    global $db;

    $channelExists = $db->query(sprintf(
        'SELECT (COUNT(*) > 0) `expr` FROM `channel` WHERE `id` = "%s"',
        $db->real_escape_string($channelId)
    ))->fetch_assoc()['expr'];

    if (!$channelExists) {
        $db->query(sprintf(
            'INSERT INTO `channel` (`id`) VALUES ("%s")',
            $db->real_escape_string($channelId)
        ));
    }
}

function initClient($channelId)
{
    global $db;

    $clientId = random_int(1, PHP_INT_MAX);

    $db->query(sprintf(
        'INSERT INTO `client` (`id`, `channel_id`, `ping_at`) VALUES (%d, "%s", NOW())',
        $clientId,
        $db->real_escape_string($channelId)
    ));

    return $clientId;
}

function destroyClient($clientId)
{
    global $db;

    $db->begin_transaction();
    $db->query(sprintf(
        'DELETE FROM `message` WHERE `client_id` = %d',
        $clientId
    ));
    $db->query(sprintf(
        'DELETE FROM `client` WHERE `id` = %d',
        $clientId
    ));
    $db->commit();
}

function destroyInactiveClients()
{
    global $db;

    $clients = $db->query(sprintf(
        'SELECT `id` FROM `client` WHERE NOW() > DATE_ADD(`ping_at`, INTERVAL %d SECOND)',
        PHPMQ_CLIENT_TIMEOUT
    ))->fetch_all(MYSQLI_ASSOC);

    foreach ($clients as $client) {
        destroyClient($client['id']);
    }
}

function destroyChannel($channelId)
{
    global $db;

    $clients = $db->query(sprintf(
        'SELECT `id` FROM `client` WHERE `channel_id` = "%s"',
        $db->real_escape_string($channelId)
    ))->fetch_all(MYSQLI_ASSOC);

    foreach ($clients as $client) {
        destroyClient($client['id']);
    }

    $db->query(sprintf(
        'DELETE FROM `channel` WHERE `id` = "%s"',
        $channelId
    ));
}

function destroyUnusedChannels()
{
    global $db;

    $channels = $db->query(sprintf(
        'SELECT `id` FROM (SELECT `channel`.`id`, SUM(`client`.`id` IS NOT NULL) `clients` FROM `channel` LEFT JOIN `client` ON `client`.`channel_id` = `channel`.`id` GROUP BY `id`) `channel_clients` WHERE `clients` = 0'
    ))->fetch_all(MYSQLI_ASSOC);

    foreach ($channels as $channel) {
        destroyChannel($channel['id']);
    }
}

initDatabase();
