<?php
declare(strict_types=1);

/*
 * 	Identy switch RoundCube Bundle
 *
 *	@copyright	(c) 2024 Forian Daeumling, Germany. All right reserved
 * 	@license 	LGPL-3.0-or-later
 */
$labels = [];

$labels['idsw.common.caption'] = 'Ваши идентификационные данные';
$labels['idsw.common.noedit'] = 'Идентификационные данные нельзя ввести, пока запись не будет сначала сохранена';
$labels['idsw.common.enabled'] = 'Включено';
$labels['idsw.common.label'] = 'Метка';

$labels['idsw.imap.caption'] = 'IMAP';
$labels['idsw.imap.host'] = 'Имя сервера';
$labels['idsw.imap.port'] = 'Порт';
$labels['idsw.imap.auth'] = 'Шифрование';
$labels['idsw.imap.auth.none'] = 'Нет';
$labels['idsw.imap.auth.ssl'] = 'SSL';
$labels['idsw.imap.auth.tls'] = 'TLS';
$labels['idsw.imap.delim'] = 'Разделитель папок';
$labels['idsw.imap.user'] = 'Имя пользователя';
$labels['idsw.imap.pwd'] = 'Пароль';

$labels['idsw.smtp.caption'] = 'SMTP';
$labels['idsw.smtp.host'] = 'Имя сервера';
$labels['idsw.smtp.tls'] = 'Защищенное соединение (TLS)';
$labels['idsw.smtp.port'] = 'Порт';
$labels['idsw.smtp.auth'] = 'Шифрование';
$labels['idsw.smtp.auth.none'] = 'Нет';
$labels['idsw.smtp.auth.ssl'] = 'SSL';
$labels['idsw.smtp.auth.tls'] = 'TLS';

$labels['idsw.notify.caption'] = 'Новые письма';
$labels['idsw.notify.allfolder'] = 'Проверить все папки на новые письма';
$labels['idsw.notify.basic'] = 'Показывать уведомления в браузере при новом письме';
$labels['idsw.notify.desktop'] = 'Показывать уведомления на рабочем столе при новом письме';
$labels['idsw.notify.timeout'] = 'Закрыть уведомление на рабочем столе';
$labels['idsw.notify.sound'] = 'Проиграть звук при новом письме';
$labels['idsw.notify.test'] = 'Тест';

$labels['idsw.err.imap.host.miss'] = 'Значение в \'IMAP Server host name\' отсутствует.';
$labels['idsw.err.imap.port.num'] = 'Значение в \'IMAP Port\' должно быть числом.';
$labels['idsw.err.imap.port.range'] = 'Значение в \'IMAP Port\' должно быть между 1 и 65535.';
$labels['idsw.err.imap.delim.miss'] = 'Значение в \'IMAP Folder hierarchy delimiter\' отсутствует.';
$labels['idsw.err.imap.user.miss'] = 'Значение в \'IMAP User name\' отсутствует.';
$labels['idsw.err.imap.pwd.miss'] = 'Значение в \'IMAP Password\' отсутствует.';
$labels['idsw.err.smtp.host.miss'] = 'Значение в \'SMTP Server host name\' отсутствует.';
$labels['idsw.err.smtp.port.num'] = 'Значение в \'SMTP Port\' должно быть числом.';
$labels['idsw.err.smtp.port.range'] = 'Значение в \'SMTP Port\' должно быть между 1 и 65535.';

$labels['identity'] = 'Личность';
$labels['notify.title'] = 'Новые письма';
$labels['notify.msg'] = '%d для %s';
$labels['notify.err.autoplay'] = 'Не могу проиграть звуковой файл!'."\r\n".
								 'Пожалуйста, проверьте настройки "Автовоспроизведение" для этой страницы в вашем браузере.';
$labels['notify.err.notification'] = 'Не могу отправить уведомление!'."\r\n".
									 'Пожалуйста, проверьте настройки "Уведомление" для этой страницы в вашем браузере.';

