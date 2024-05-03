--  Created with phpmyadmin

DROP TABLE IF EXISTS ident_switch;
CREATE TABLE IF NOT EXISTS `identy_switch`(
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(10) UNSIGNED NOT NULL,
    `iid` INT(10) UNSIGNED NOT NULL,
    `label` VARCHAR(32),
    `flags` INT NOT NULL DEFAULT 0,
    `imap_user` VARCHAR(64),
    `imap_pwd` VARCHAR(64),
    `imap_host` VARCHAR(64),
    `imap_port` SMALLINT DEFAULT 0,
    `imap_delim` CHAR(1),
    `newmail_check` SMALLINT DEFAULT 300,
    `notify_timeout` SMALLINT DEFAULT 10,
    `smtp_host` VARCHAR(64),
    `smtp_port` SMALLINT DEFAULT 0,
    `drafts` VARCHAR(64) DEFAULT '',
    `sent` VARCHAR(64) DEFAULT '',
    `junk` VARCHAR(64) DEFAULT '',
    `trash` VARCHAR(64) DEFAULT '',
    UNIQUE KEY `user_id_label`(`user_id`, `label`),
    CONSTRAINT `fk_user_id` FOREIGN KEY(`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_identity_id` FOREIGN KEY(`iid`) REFERENCES `identities`(`identity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(`id`),
    INDEX `IX_identy_switch_user_id`(`user_id`),
    INDEX `IX_identy_switch_iid`(`iid`)
);
