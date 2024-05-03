--  Created with phpmyadmin

INSERT INTO identy_switch(
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
    `notify_timeout`,
    `newmail_check`,
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
    `notify_timeout`,
    `newmail_check`,
    `smtp_host`,
    `smtp_port`,
    `drafts_mbox`,
    `sent_mbox`,
    `junk_mbox`,
    `trash_mbox`
FROM
    ident_switch;
DROP TABLE IF EXISTS ident_switch;