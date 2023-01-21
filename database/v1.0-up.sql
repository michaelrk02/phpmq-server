CREATE TABLE `channel` (
    `id` VARCHAR(256),
    PRIMARY KEY (`id`)
);

CREATE TABLE `client` (
    `id` BIGINT,
    `channel_id` VARCHAR(256),
    `ping_at` DATETIME,
    PRIMARY KEY (`id`)
);

CREATE TABLE `message` (
    `id` BIGINT,
    `client_id` BIGINT,
    `timestamp` DATETIME(6),
    `event` VARCHAR(128),
    `data` TEXT,
    PRIMARY KEY (`id`)
);

ALTER TABLE `client`
ADD CONSTRAINT `FK_Client_ChannelId` FOREIGN KEY (`channel_id`) REFERENCES `channel` (`id`);

ALTER TABLE `message`
ADD CONSTRAINT `FK_Message_ClientId` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`);
