--
-- 	Identity switch RoundCube Bundle
--
--	@copyright	(c) 2024 Forian Daeumling, Germany. All right reserved
-- 	@license 	https://github.com/toteph42/identity_switch/blob/master/LICENSE
--
--  Created with phpmyadmin

INSERT INTO identity_switch(
    `id`,
    `user_id`,
    `iid`,
    `label`,
    `flags`,
    `imap_user`,
    `imap_pwd`,
    `imap_host`,
    `imap_port`,
    `imap_delim`,
    `smtp_host`,
    `smtp_port`,
    `drafts`,
    `sent`,
    `junk`,
    `trash`
)
SELECT
    `id`,
    `user_id`,
    `iid`,
    `label`,
    `flags`,
    `username`,
    `password`,
    `imap_host`,
    `imap_port`,
    `imap_delimiter`,
    `smtp_host`,
    `smtp_port`,
    `drafts_mbox`,
    `sent_mbox`,
    `junk_mbox`,
    `trash_mbox`
FROM
    ident_switch;
DROP TABLE IF EXISTS ident_switch;
