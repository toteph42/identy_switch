-- Created with: https://brunocassol.com/mysql2sqlite/

PRAGMA journal_mode = MEMORY;
PRAGMA synchronous = OFF;
PRAGMA foreign_keys = OFF;
PRAGMA ignore_check_constraints = OFF;
PRAGMA auto_vacuum = NONE;
PRAGMA secure_delete = OFF;
BEGIN TRANSACTION;

CREATE TABLE IF NOT EXISTS `identy_switch`(
`id` INTEGER  NOT NULL ,
`user_id` INTEGER  NOT NULL,
`iid` INTEGER  NOT NULL,
`label` TEXT,
`flags` INT NOT NULL DEFAULT 0,
`imap_user` TEXT,
`imap_pwd` TEXT,
`imap_host` TEXT,
`imap_port` SMALLINT DEFAULT 0,
`imap_delim` CHAR(1),
`newmail_check` SMALLINT DEFAULT 300,
`notify_timeout` SMALLINT DEFAULT 10,
`smtp_host` TEXT,
`smtp_port` SMALLINT DEFAULT 0,
`drafts` TEXT DEFAULT '',
`sent` TEXT DEFAULT '',
`junk` TEXT DEFAULT '',
`trash` TEXT DEFAULT '',
UNIQUE KEY `user_id_label`(`user_id`, `label`),
FOREIGN KEY(`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY(`iid`) REFERENCES `identities`(`identity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
PRIMARY KEY(`id`),
INDEX `IX_identy_switch_user_id`(`user_id`),
INDEX `IX_identy_switch_iid`(`iid`)
);

COMMIT;
PRAGMA ignore_check_constraints = ON;
PRAGMA foreign_keys = ON;
PRAGMA journal_mode = WAL;
PRAGMA synchronous = NORMAL;
