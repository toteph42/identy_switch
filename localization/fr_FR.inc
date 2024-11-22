<?php
declare(strict_types=1);

/*
 * 	Identity switch RoundCube Bundle
 *
 *	@copyright	(c) 2024 Forian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/identity_switch/blob/master/LICENSE
 */
$labels = [];

$labels['idsw.common.caption'] = 'Données de votre identité';
$labels['idsw.common.noedit'] = 'Les informations liées à l\'identité ne peuvent être saisies qu\'après le premier enregistrement';
$labels['idsw.common.enabled'] = 'Activer';
$labels['idsw.common.label'] = 'Nom à afficher';

$labels['idsw.imap.caption'] = 'IMAP';
$labels['idsw.imap.host'] = 'Nom du serveur IMAP';
$labels['idsw.imap.port'] = 'Port IMAP';
$labels['idsw.imap.auth'] = 'Chiffrement';
$labels['idsw.imap.auth.none'] = 'None';
$labels['idsw.imap.auth.ssl'] = 'SSL';
$labels['idsw.imap.auth.tls'] = 'TLS';
$labels['idsw.imap.delim'] = 'Séparateur de dossier';
$labels['idsw.imap.user'] = 'Nom d\'utilisateur';
$labels['idsw.imap.pwd'] = 'Mot de passe';

$labels['idsw.smtp.caption'] = 'SMTP';
$labels['idsw.smtp.host'] = 'Nom du serveur SMTP';
$labels['idsw.smtp.tls'] = 'Connexion sécurisée (TLS)';
$labels['idsw.smtp.port'] = 'Port SMTP';
$labels['idsw.smtp.auth'] = 'Encryption';
$labels['idsw.smtp.auth.none'] = 'None';
$labels['idsw.smtp.auth.ssl'] = 'SSL';
$labels['idsw.smtp.auth.tls'] = 'TLS';

$labels['idsw.notify.caption'] = 'Nouveaux messages';
$labels['idsw.notify.allfolder'] = 'Vérifier la présence de nouveaux messages dans tous les dossiers';
$labels['idsw.notify.basic'] = 'Afficher les notifications du navigateur en cas de nouveau message';
$labels['idsw.notify.desktop'] = 'Afficher les notifications sur le bureau en cas de nouveau message';
$labels['idsw.notify.timeout'] = 'Fermeture de la notification du bureau';
$labels['idsw.notify.sound'] = 'Diffusion d\'un son lors de la réception d\'un nouveau message';
$labels['idsw.notify.test'] = 'Test';

$labels['idsw.err.imap.host.miss'] = 'La valeur du champ "Serveur IMAP" est manquante.';
$labels['idsw.err.imap.port.num'] = 'La valeur du champ "Port IMAP" doit être un nombre.';
$labels['idsw.err.imap.port.range'] = 'La valeur du champ "Port IMAP" doit être comprise entre 1 et 65535.';
$labels['idsw.err.imap.delim.miss'] = 'La valeur du champ "Séparateur de dossier" est manquante.';
$labels['idsw.err.imap.user.miss'] = 'La valeur du champ "Nom d\'utilisateur" est manquante.';
$labels['idsw.err.imap.pwd.miss'] = 'La valeur du champ "Mot de passe" est manquante.';
$labels['idsw.err.smtp.host.miss'] = 'La valeur du champ "Serveur SMTP" est manquante.';
$labels['idsw.err.smtp.port.num'] = 'La valeur du champ "Port SMTP" doit être un nombre.';
$labels['idsw.err.smtp.port.range'] = 'La valeur du champ "Port SMTP" doit être comprise entre 1 et 65535.';

$labels['identity'] = 'Identité';
$labels['notify.title'] = 'Nouveaux messages';
$labels['notify.msg'] = '%d pour %s';
$labels['notify.err.autoplay'] = 'Le fichier son ne peut pas être joué !'."\r\n".
								 'Vérifier vos paramètres "Autoplay" pour cette page de votre navigateur.';
$labels['notify.err.notification'] = 'La notification ne peut être envoyée !'."\r\n".
									 'Vérifier vos paramètres "Notification"  pour cette page de votre navigateur.';

