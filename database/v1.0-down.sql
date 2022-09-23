ALTER TABLE `client`
DROP CONSTRAINT `FK_Client_ChannelId`;

ALTER TABLE `message`
DROP CONSTRAINT `FK_Message_ClientId`;

DROP TABLE `channel`;
DROP TABLE `client`;
DROP TABLE `message`;
