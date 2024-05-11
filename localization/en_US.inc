<?php
declare(strict_types=1);

/*
 * 	Identy switch RoundCube Bundle
 *
 *	@copyright	(c) 2024 Forian Daeumling, Germany. All right reserved
 * 	@license 	LGPL-3.0-or-later
 */
$labels = [];

$labels['idsw.common.caption'] = 'Data of your identity';
$labels['idsw.common.enabled'] = 'Enabled';
$labels['idsw.common.label'] = 'Label';

$labels['idsw.imap.caption'] = 'IMAP';
$labels['idsw.imap.host'] = 'Server host name';
$labels['idsw.imap.port'] = 'Port';
$labels['idsw.imap.auth'] = 'Encryption';
$labels['idsw.imap.auth.none'] = 'None';
$labels['idsw.imap.auth.ssl'] = 'SSL';
$labels['idsw.imap.auth.tls'] = 'TLS';
$labels['idsw.imap.delim'] = 'Folder hierarchy delimiter';
$labels['idsw.imap.user'] = 'Username';
$labels['idsw.imap.pwd'] = 'Password';

$labels['idsw.smtp.caption'] = 'SMTP';
$labels['idsw.smtp.host'] = 'Server host name';
$labels['idsw.smtp.tls'] = 'Secure connection (TLS)';
$labels['idsw.smtp.port'] = 'Port';
$labels['idsw.smtp.auth'] = 'Authorization';
$labels['idsw.smtp.auth.imap'] = 'As IMAP';
$labels['idsw.smtp.auth.none'] = 'None';

$labels['idsw.notify.caption'] = 'New messages';
$labels['idsw.notify.allfolder'] = 'Check all folders for new messages';
$labels['idsw.notify.basic'] = 'Display browser notifications on new message';
$labels['idsw.notify.desktop'] = 'Display desktop notifications on new message';
$labels['idsw.notify.timeout'] = 'Close desktop notification';
$labels['idsw.notify.sound'] = 'Play sound on new message';
$labels['idsw.notify.test'] = 'Test';

$labels['idsw.err.imap.host.miss'] = 'Value in \'IMAP Server host name\' missing.';
$labels['idsw.err.imap.auth'] = 'Value in \'IMAP Authorization\' field is invalid.';
$labels['idsw.err.imap.port.num'] = 'Value in \'IMAP Port\' field must be a number.';
$labels['idsw.err.imap.port.range'] = 'Value in \'IMAP Port\' field must be between 1 and 65535.';
$labels['idsw.err.imap.delim.miss'] = 'Value in \'IMAP Folder hierarchy delimiter\' missing.';
$labels['idsw.err.imap.user.miss'] = 'Value in \'IMAP User name\' missing.';
$labels['idsw.err.imap.pwd.miss'] = 'Value in \'IMAP Password\' missing.';
$labels['idsw.err.smtp.host.miss'] = 'Value in \'SMTP Server host name\' missing.';
$labels['idsw.err.smtp.port.num'] = 'Value in \'SMTP Port\' field must be a number.';
$labels['idsw.err.smtp.port.range'] = 'Value in \'SMTP Port\' field must be between 1 and 65535.';

$labels['identity'] = 'Identity';
$labels['notify.title'] = 'New Emails';
$labels['notify.msg'] = '%d for %s';
$labels['notify.err.autoplay'] = 'Cannot play sound file!'."\r\n".
								 'Please check your "Autoplay" settings for this page in your browser.';
$labels['notify.err.notification'] = 'Cannot send notification!'."\r\n".
									 'Please check your "Notification" settings for this page in your browser.';

