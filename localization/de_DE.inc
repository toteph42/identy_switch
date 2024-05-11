<?php
declare(strict_types=1);

/*
 * 	Identy switch RoundCube Bundle
 *
 *	@copyright	(c) 2024 Forian Daeumling, Germany. All right reserved
 * 	@license 	LGPL-3.0-or-later
 */
$labels = [];

$labels['idsw.common.caption'] = 'Daten der Identität';
$labels['idsw.common.enabled'] = 'Aktiviert';
$labels['idsw.common.label'] = 'Bezeichnung';

$labels['idsw.imap.caption'] = 'IMAP';
$labels['idsw.imap.host'] = 'Servername';
$labels['idsw.imap.auth'] = 'Verschlüsselung';
$labels['idsw.imap.auth.none'] = 'Keine';
$labels['idsw.imap.auth.ssl'] = 'SSL';
$labels['idsw.imap.auth.tls'] = 'TLS';
$labels['idsw.imap.port'] = 'Port';
$labels['idsw.imap.delim'] = 'Verzeichnistrenner';
$labels['idsw.imap.user'] = 'Benutzername';
$labels['idsw.imap.pwd'] = 'Passwort';

$labels['idsw.smtp.caption'] = 'SMTP';
$labels['idsw.smtp.host'] = 'Servername';
$labels['idsw.smtp.tls'] = 'Sichere Verbindung (TLS)';
$labels['idsw.smtp.port'] = 'Port';
$labels['idsw.smtp.auth'] = 'Verschlüsselung';
$labels['idsw.smtp.auth.imap'] = 'Wie IMAP';
$labels['idsw.smtp.auth.none'] = 'Keine';

$labels['idsw.notify.caption'] = 'Neue Nachrichten';
$labels['idsw.notify.allfolder'] = 'Alle Ordner auf neue Nachrichten prüfen';
$labels['idsw.notify.basic'] = 'Benachrichtigung im Browser bei neuer Nachricht';
$labels['idsw.notify.desktop'] = 'Desktop-Benachrichtigung bei neuer Nachricht';
$labels['idsw.notify.timeout'] = 'Desktop-Benachrichtigung schließen';
$labels['idsw.notify.sound'] = 'Akustische Meldung bei neuer Nachricht';
$labels['idsw.notify.test'] = 'Test';

$labels['idsw.err.imap.host.miss'] = 'Der Wert im Feld \'IMAP Server host name\' fehlt.';
$labels['idsw.err.imap.auth'] = 'Der Wert im Feld \'IMAP Verschlüsselung\' ist ungültig.';
$labels['idsw.err.imap.port.num'] = 'Der Wert in \'IMAP Port\' muss eine Zahl sein.';
$labels['idsw.err.imap.port.range'] = 'Der Wert in \'IMAP Port\' muss zwischen 1 und 65535 liegen.';
$labels['idsw.err.imap.delim.miss'] = 'Der Wert im Feld \'IMAP Verzeichnistrenner\' fehlt.';
$labels['idsw.err.imap.user.miss'] = 'Der Wert im Feld \'IMAP Benutzername\' fehlt.';
$labels['idsw.err.imap.pwd.miss'] = 'Der Wert im Feld \'IMAP Password\' fehlt.';
$labels['idsw.err.smtp.host.miss'] = 'Der Wert im Feld \'SMTP Servername\' fehlt.';
$labels['idsw.err.smtp.port.num'] = 'Der Wert in \'SMTP Port\' muss eine Zahl sein.';
$labels['idsw.err.smtp.port.range'] = 'Der Wert in \'SMTP Port\' muss zwischen 1 und 65535 liegen.';

$labels['identity'] = 'Identität';
$labels['notify.title'] = 'Neue E-Mails';
$labels['notify.msg'] = '%d für %s';
$labels['notify.err.autoplay'] = 'Kann Musik nicht abspielen!'."\r\n".
								 'Bitte überprüfen Sie die Einstellungen der "Automatische Wiedergabe" für diese Seite in Ihrem Browser.';
$labels['notify.err.notification'] = 'Kann Benachrichtigung nicht senden!.'."\r\n".
								 'Bitte überprüfen Sie die Einstellungen der "Benachrichtigungen" für diese Seite in Ihrem Browser.';
