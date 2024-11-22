--
-- 	Identity switch RoundCube Bundle
--
--	@copyright	(c) 2024 Forian Daeumling, Germany. All right reserved
-- 	@license 	https://github.com/toteph42/identity_switch/blob/master/LICENSE
--
-- Created with: https://sqliteonline.com/

CREATE TABLE IF NOT EXISTS `identity_switch`(
	`id` INTEGER  NOT NULL ,
	`user_id` INTEGER  NOT NULL REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
	`iid` INTEGER  NOT NULL REFERENCES identities(identity_id) ON DELETE CASCADE ON UPDATE CASCADE UNIQUE,
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
	UNIQUE (user_id, label)
);

CREATE INDEX IX_identity_switch_user_id ON identity_switch(user_id);
CREATE INDEX IX_identity_switch_iid on identity_switch(iid);
