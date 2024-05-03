<?php
declare(strict_types=1);

/*
 * 	Identy switch RoundCube Bundle
 *
 *	@copyright	(c) 2024 Forian Daeumling, Germany. All right reserved
 * 	@license 	LGPL-3.0-or-later
 */

class identy_switch_prefs extends rcube_plugin
{
	public $task = '?(?!login|logout).*';

	const TABLE = 'identy_switch';

	// Flags user in database
	const ENABLED		    	= 0x0001;
	const IMAP_SSL				= 0x0004;
	const IMAP_TLS				= 0x0008;
	const SMTP_IMAP 			= 0x0010;
	const NOTIFY_BASIC			= 0x0100;
	const NOTIFY_DESKTOP		= 0x0200;
	const NOTIFY_SOUND			= 0x0400;
	const CHECK_ALLFOLDER		= 0x0800;
	const SHOW_REAL_FOLDER		= 0x1000;
	const LOCK_SPECIAL_FOLDER	= 0x2000;
	const UNSEEN				= 0x4000;

	/**
	 * 	Initialize Plugin
	 *
	 * 	{@inheritDoc}
	 * 	@see rcube_plugin::init()
	 */
	function init(): void
	{
		// Preference hooks and actions
		$this->add_hook('identity_form', 				  [ $this, 'identy_switch_form' ]);
		$this->add_hook('identity_update', 				  [ $this, 'identy_switch_update' ]);
		$this->add_hook('identity_create_after',		  [ $this, 'identy_switch_update' ]);
		$this->add_hook('identity_delete', 				  [ $this, 'identy_switch_delete' ]);
		$this->add_hook('preferences_list', 			  [ $this, 'prefs_list' ]);
		$this->add_hook('preferences_save', 			  [ $this, 'prefs_save' ]);
		$this->add_hook('identity_update', 			  	  [ $this, 'prefs_upd' ]);
	}

	/**
	 * 	Prefernce list in settings
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	function prefs_list(array $args): array
	{
		if ($args['section'] != 'folders' && $args['section'] != 'mailbox' && $args['section'] != 'general')
		    return $args;

		$this->add_texts('localization');

		// Common settings
		if ($args['section'] == 'general')
			return self::get_general_form($args);

		// Special folder settings
		if ($args['section'] == 'folders')
			return self::get_special_folders($args);

		// Mailbox settings
		if ($args['section'] == 'mailbox')
			return self::get_notify_form($args);

		return $args;
	}

	/**
	 * 	Save pefences in settings
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	function prefs_save(array $args): array
	{
		if ($args['section'] != 'folders' && $args['section'] != 'mailbox' && $args['section'] != 'general')
			return $args;

		$this->add_texts('localization');

		// Common settings
		if ($args['section'] == 'general')
			return self::save_general_form($args);

		// Special folder settings
		if ($args['section'] == 'folders')
			return self::save_special_folders($args);

		// Mailbox settings
		if ($args['section'] == 'mailbox')
			return self::save_notify_form($args);

		return $args;
	}

	/**
	 * 	Update identity pefences in settings
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	function prefs_upd(array $args): array
	{
		if ($args['record']['standard'])
			self::set(-1, 'label', $args['record']['name']);

		return $args;
	}

	/**
	 * 	Get special folder preferences
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	function get_special_folders(array $args): array
	{
		$no_override = array_flip((array)rcmail::get_instance()->config->get('dont_override'));

		$rec = self::get(self::get(null, 'iid'));

		$fld = rcmail_action::folder_selector([ 'noselection' 	=> '---',
												'realnames' 	=> true,
												'maxlength' 	=> 30,
												'folder_filter' => 'mail',
												'folder_rights' => 'w' ]);

		// Modify template
		$args['blocks']['main']['name'] .= ' [ '.rcube::Q($this->gettext('identity')).': '.$rec['label'].' ]';

	    $sel = new html_checkbox([ 'name' 	=> '_show_real_foldernames',
        						   'id' 	=> 'show_real_foldernames',
            					   'value' 	=> '1' ]);

	    $set = &$args['blocks']['main']['options'];
	    $set['show_real_foldernames']['content'] =
					$sel->show(($rec['flags'] & self::SHOW_REAL_FOLDER) ? '1' : '0');

		foreach (rcube_storage::$folder_types as $mbox)
		{
			if (isset($no_override[$mbox.'_mbox']))
				continue;

			$set[$mbox.'_mbox']['content'] = $fld->show($rec[$mbox], [
					  							'id' 		=> '_'.$mbox.'_mbox',
										  		'name' 		=> '_'.$mbox. '_mbox',
												'onchange' 	=> "if ($(this).val() == 'INBOX') $(this).val('')",
					  						 ]);
		}

		return $args;
	}

	/**
	 * 	Set special folder preferences
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	function save_special_folders(array $args): array
	{
	   	$iid = self::get(null, 'iid');
		$rec = self::get($iid);

		if ($args['prefs']['show_real_foldernames'])
			self::set($iid, 'flags', $rec['flags'] |= self::SHOW_REAL_FOLDER);
		else
			self::set($iid, 'flags', $rec['flags'] &= ~self::SHOW_REAL_FOLDER);

		if (isset($args['prefs']['lock_special_folders']) && $args['prefs']['lock_special_folders'] == '1')
			self::set($iid, 'flags', $rec['flags'] |= self::LOCK_SPECIAL_FOLDER);
		else
			self::set($iid, 'flags', $rec['flags'] &= ~self::LOCK_SPECIAL_FOLDER);

		foreach (rcube_storage::$folder_types as $mbox)
			if ($args['prefs'][$mbox.'_mbox'])
				self::set($iid, $mbox, $rec[$mbox] = $args['prefs'][$mbox.'_mbox']);

		if ($iid != -1)
		{
			$rc = rcmail::get_instance();

			$sql = 'UPDATE '.$rc->db->table_name(self::TABLE).
				   ' SET flags = ?, drafts = ?, sent = ?, junk = ?, trash = ?'.
				   ' WHERE iid = ?';
			$rc->db->query( $sql, $rec['flags'], $rec['drafts'], $rec['sent'], $rec['junk'], $rec['trash'], $iid);

			// Abuse $plugin['abort'] to prevent RC main from saving prefs
			$args['abort'] 	= true;
			$args['result'] = true;
		}

		return $args;
	}

	/**
	 * 	Create notification preferences
	 *
	 * 	@param array $args
	 * 	@param bool $flag
	 * 	@return array
	 */
	function get_notify_form(array $args, bool $int = false): array
	{
		if (!self::get('config', 'check'))
		{
			if (!$int)
				unset($args['blocks']['new_message']['options']['check_all_folders']);
			return $args;
		}

		$rec = self::get(isset($args['record']['identity_id']) ? $args['record']['identity_id'] :
						 self::get(null, 'iid'));

        $rc = rcmail::get_instance();

		if ($int)
		{
			$args['form']['notify'] = [];
			$set = &$args['form']['notify'];
		} else
			$set = $args['blocks']['new_message'];
		$set; // disable Eclipse warning

		$set['name'] = $this->gettext('idsw.notify.caption');

		// Check that configuration is not disabled
		$no_override = (array) $rc->config->get('dont_override');

		if ($int)
		{
			$tit = 'label';
			$val = 'value';
			$set = [];
			if (!in_array('check_all_folders', $no_override))
			{
				$set['check_all_folders'] = [];
				$s 		 = &$set['check_all_folders'];
				$s[$tit] = rcube::Q($this->gettext('idsw.notify.allfolder'));
				$cb 	 = new html_checkbox([ 'name' => '_check_all_folder', 'value' => '1' ]);
				$s[$val] = $cb->show($rec ? ($rec['flags'] & self::CHECK_ALLFOLDER ? '1' : '0') : '0');
			}
		} else {
			$tit = 'title';
			$val = 'content';
			$set = &$args['blocks']['new_message']['options'];
			if (!in_array('check_all_folders', $no_override))
			{
		        $s 		 = &$set['check_all_folders'];
		        $s; // Disable Eclipse warning
				$s[$tit] = rcube::Q($this->gettext('idsw.notify.allfolder'));
				$cb 	 = new html_checkbox([ 'name' => '_check_all_folder', 'value' => '1' ]);
				$s[$val] = $cb->show($rec ? ($rec['flags'] & self::CHECK_ALLFOLDER ? '1' : '0') : '0');
			}
		}

        $to = new html_select([ 'name' => "_notify_timeout" ]);
        foreach ([ 5, 10, 15, 30, 45, 60 ] as $sec)
            $to->add($this->gettext(['name' => 'afternseconds', 'vars' => [ 'n' => $sec ]]), $sec);

		foreach ([ 'basic' 	 => self::NOTIFY_BASIC,
				   'desktop' => self::NOTIFY_DESKTOP,
				   'sound' 	 => self::NOTIFY_SOUND ] as $type => $flag)
		{
			if (in_array('newmail_notifier_'.$type, $no_override))
				continue;

			$cb = new html_checkbox([ 			'name' 	=> '_notify_'.$type,
								  	  			'value' => '1' ]);

			switch($type)
			{
			case 'basic':
				$set['notify_basic']   = [ $tit	=> $this->gettext('idsw.notify.basic'),
										   $val => $cb->show($rec ? ($rec['flags'] & $flag ? '1' : '0') : '0').
												   html::a([ 'href' => '#',
												   'onclick' => 'identy_switch_basic(); return false',
													  	'name' => '_notify_basic_test' ],
		                	       	 	   			   	$this->gettext('idsw.notify.test')) ];
				break;

			case 'desktop':
				$set['notify_desktop'] = [ $tit	=> $this->gettext('idsw.notify.desktop'),
										   $val	=> $cb->show($rec ? ($rec['flags'] & $flag ? '1' : '0') : '0').
												   html::a(['href' => '#',
												   'onclick' => 'identy_switch_desktop(\''.
													   	rawurlencode($this->gettext('notify.title')).'\',\''.
													   	rawurlencode(sprintf($this->gettext('notify.msg'), 1,
													   	($rec ? $rec['imap_host'] : 'host'))).
													   	'\',\''.($rec ? $rec['notify_timeout'] : '10').'\',\''.
													   	rawurlencode($this->gettext('notify.err.notification')).
													   	'\'); return false',
													   	'name' => '_notify_desktop_test' ],
														$this->gettext('idsw.notify.test')) ];
				$set['notify_timeout'] = [ $tit => $this->gettext('idsw.notify.timeout'),
										   $val	=> $to->show((int)($rec ? $rec['notify_timeout'] : 10)) ];
				break;

			case 'sound':
				$set['notify.sound']   = [ $tit => $this->gettext('idsw.notify.sound'),
										   $val => $cb->show($rec ? ($rec['flags'] & $flag ? '1' : '0') : '0').
												   html::a(['href' => '#',
												   'onclick' => 'identy_switch_sound(\''.
													   	rawurlencode($this->gettext('notify.err.autoplay')).
													   	'\'); return false',
													   	'name' => '_notify_sound_test' ],
													   	$this->gettext('idsw.notify.test')) ];
			default:
				break;

			}
		}

		return $int ? $set + $this->get_general_form($args, true) : $args;
	}

	/**
	 * 	Save notification preferences
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	function save_notify_form(array $args): array
	{
	   	$iid = self::get(null, 'iid');
		$rec = self::get($iid);
		$rc  = rcmail::get_instance();

        if (!empty($val = rcube_utils::get_input_value('_check_all_folder', rcube_utils::INPUT_POST)))
        {
        	$args['prefs']['check_all_folders'] = '1';
        	self::set($iid, 'flags', $rec['flags'] |= self::CHECK_ALLFOLDER);
        } else {
        	$args['prefs']['check_all_folders'] = '0';
        	self::set($iid, 'flags', $rec['flags'] &= ~self::CHECK_ALLFOLDER);
        }

		foreach ([ 	'basic' 	=> self::NOTIFY_BASIC,
					'desktop'	=> self::NOTIFY_DESKTOP,
					'sound'		=> self::NOTIFY_SOUND ] as $type => $flag) {
            $key = 'newmail_notifier_' . $type;
            if (!empty($val = rcube_utils::get_input_value('_notify_'.$type, rcube_utils::INPUT_POST)))
            {
                $args['prefs'][$key] = '1';
                self::set($iid, 'flags', $rec['flags'] |= $flag);
            } else {
            	$args['prefs'][$key] = '0';
                self::set($iid, 'flags', $rec['flags'] &= ~$flag);
            }
        }

        if (!empty($val = rcube_utils::get_input_value('_notify_timeout', rcube_utils::INPUT_POST)))
        {
        	$args['prefs']['newmail_notifier_desktop_timeout'] = $val;
        	self::set($iid, 'notify_timeout', $rec['notify_timeout'] = $val);
        }

		if ($iid != -1)
		{
			$sql = 'UPDATE '.$rc->db->table_name(self::TABLE).
				   ' SET flags = ?, notify_timeout = ? '.
				   ' WHERE iid = ?';
			$rc->db->query( $sql, $rec['flags'], $rec['notify_timeout'], $iid);

			// Abuse $plugin['abort'] to prevent RC main from saving prefs
			$args['abort'] 	= true;
			$args['result'] = true;
		}

		return $args;
	}

	/**
	 * 	Create general preferences
	 *
	 * 	@param array $args
	 * 	@param bool $flag
	 * 	@return array
	 */
	private function get_general_form(array $args, bool $int = false): array
	{
		if (!self::get('config', 'check'))
		{
			if (!$int)
				unset($args['blocks']['main']['options']['refresh_interval']);
			return $args;
		}

		$rc = rcmail::get_instance();

		// Check that configuration is not disabled
		if (in_array('refresh_interval', (array) $rc->config->get('dont_override')))
			return $args;

		$cfg = $rc->config->all();

		$sel = new html_select([
					'name'  => '_refresh_interval',
                    'id'    => '_refresh_interval',
                    'class' => 'custom-select'
        ]);

		$sel->add($this->gettext('never'), 0);
        foreach ([ 1, 3, 5, 10, 15, 30, 60 ] as $min)
        {
        	if (!$cfg['min_refresh_interval'] || $cfg['min_refresh_interval'] <= $min * 60)
        	{
				$lab = $rc->gettext([ 'name' => 'everynminutes', 'vars' => ['n' => $min ] ]);
                $sel->add($lab, $min);
			}
		}

		$iid = self::get(null, 'iid');
        $rec = self::get($iid);

        if ($int)
	        return [ 'refreshinterval' => [
        									'type' => 'select',
									  		'value' => $sel->show($rec['newmail_check'] / 60),
										  ]
        	];

		$args['blocks']['main']['options']['refresh_interval'] = [
                        'title'   => html::label('_refresh_interval', rcube::Q($this->gettext('refreshinterval'))),
                        'content' => $sel->show($rec['newmail_check'] / 60),
		];

		return $args;
	}

	/**
	 * 	Save general preferences
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	private function save_general_form(array $args): array
	{
	   	$iid = self::get(null, 'iid');
		$rec = self::get($iid);

        if (!empty($val = rcube_utils::get_input_value('_refresh_interval', rcube_utils::INPUT_POST)))
        {
        	$args['prefs']['refresh_interval'] = $val;
        	self::set($iid, 'newmail_check', $rec['newmail_check'] = $val);
        }

		if ($iid != -1)
		{
			$rc = rcmail::get_instance();

			$sql = 'UPDATE '.$rc->db->table_name(self::TABLE).
				   ' SET newmail_check = ? '.
				   ' WHERE iid = ?';
			$rc->db->query( $sql, $rec['newmail_check'], $iid);

			// Abuse $plugin['abort'] to prevent RC main from saving prefs
			$args['abort'] 	= true;
			$args['result'] = true;
		}

		return $args;
	}

	/**
	 * 	Create identy switch preferences
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	function identy_switch_form(array $args): array
	{
		$rc = rcmail::get_instance();

		$email = isset($args['record']['email']) ? $args['record']['email'] : '';

		// Do not show options for default identity
		if ($email && strcasecmp($email, $rc->user->data['username']) === 0)
			return $args;

		// Apply config.inc.php
		if (is_array($cfg = $this->get_config($email)))
		{
			if (isset($args['record']['identity_id']))
			{
				$iid = (int)$args['record']['identity_id'];

				self::write_log('Applying predefined configuration for "'.$email.'".');

				// Parse and set host and related information
				$url = parse_url($cfg['imap']);

				self::set($iid, 'imap_host', isset($url['host']) ? rcube::Q($url['host'], 'url') : '');
				self::set($iid, 'imap_port', isset($url['port']) ? intval($url['port']) : '');
				if (strcasecmp('tls', $url['scheme']) === 0)
					self::set($iid, 'flags', (int)self::get($iid, 'flags') | self::IMAP_TLS);
				if (strcasecmp('ssl', $url['scheme']) === 0)
					self::set($iid, 'flags', (int)self::get($iid, 'flags') | self::IMAP_SSL);

				$url = parse_url($cfg['smtp']);
				self::set($iid, 'smtp_host', isset($url['host']) ? rcube::Q($url['host'], 'url') : '');
				self::set($iid, 'smtp_port', isset($url['port']) ? intval($url['port']) : '');
				if (strcasecmp('imap', $url['scheme']) !== 0)
					self::set($iid, 'flags', (int)self::get($iid, 'flags') | self::SMTP_IMAP);

				// Set up user name
				if ($cfg['user'])
				{
					switch (strtoupper($cfg['user']))
					{
					case 'EMAIL':
						self::set($iid, 'imap_user', $email);
						break;

					case 'MBOX':
						self::set($iid, 'imap_user', strstr($email, '@', true));

					default:
						break;
					}
				}
			}
		}

		$this->add_texts('localization');

		$args['form']['common'] = [
			'name' 	  => $this->gettext('idsw.common.caption'),
			'content' => $this->get_common_form($args),
		];

		$args['form']['imap'] = [
			'name' 	  => $this->gettext('idsw.imap.caption'),
			'content' => $this->get_imap_form($args),
		];

		$args['form']['smtp'] = [
			'name' 	  => $this->gettext('idsw.smtp.caption'),
			'content' => $this->get_smtp_form($args),
		];

		if (self::get('config', 'check'))
			$args['form']['notify'] = [
				'name' 	  => $this->gettext('idsw.notify.caption'),
				'content' => $this->get_notify_form($args, true),
			];

		return $args;
	}

	/**
	 * 	Get common preferences
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	private function get_common_form(array &$args): array
	{

		$rec = null;

		// Edit existing identity record?
		if (isset($args['record']['identity_id']))
	        $rec = self::get($args['record']['identity_id']);

        $args['record']['label'] = $rec ? $rec['label'] : '';

        $ise = $rec ? ($rec['flags'] & self::ENABLED ? '1' : '0') : '0';
		$ena = new html_checkbox([   'name' 	=> '_enabled',
								     'onchange' => 'identy_switch_enabled();',
								  	 'value' 	=> $ise ]);

		return [
			'enabled'		=> [ 'label' => $this->gettext('idsw.common.enabled'),
								 'value' => $ena->show($ise), ],
			'label' 		=> [ 'label' => $this->gettext('idsw.common.label'),
								 'type' => 'text', 'maxlength' => 32 ],
		];
	}

	/**
	 * 	Get IMAP preferences
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	private function get_imap_form(array &$args): array
	{

        // Not creating new identity
		if (isset($args['record']['identity_id']))
			$rec = self::get($args['record']['identity_id']);
		else
			$rec = null;

		$authType = new html_select([ 'name' => "_imap_auth" ]);
		$authType->add($this->gettext('idsw.imap.auth.none'), 'none');
		$authType->add($this->gettext('idsw.imap.auth.ssl'), 'ssl');
		$authType->add($this->gettext('idsw.imap.auth.tls'), 'tls');

        $args['record']['imap_host'] 	= $rec ? $rec['imap_host'] : '';
        $enc 							= $rec ? ($rec['flags'] & self::IMAP_SSL ? 'ssl' :
										  ($rec['flags'] & self::IMAP_TLS ? 'tls' : 0)) : '0';
        $args['record']['imap_port'] 	= $rec ? $rec['imap_port'] : '';
        $args['record']['imap_user'] 	= $rec ? $rec['imap_user'] : '';
        $args['record']['imap_pwd'] 	= $rec ? rcmail::get_instance()->decrypt($rec['imap_pwd']) : '';
        $args['record']['imap_delim'] 	= $rec ? $rec['imap_delim'] : '';

		return [
			'imap_host'	 	=> [ 'label' => $this->gettext('idsw.imap.host'),
								 'type'  => 'text', 'maxlength' => 64 ],
			'imap_auth' 	=> [ 'label' => $this->gettext('idsw.imap.auth'),
								 'value' => $authType->show($enc) ],
			'imap_port' 	=> [ 'label' => $this->gettext('idsw.imap.port'),
								 'type' => 'text', 'maxlength' => 5 ],
			'imap_user' 	=> [ 'label' => $this->gettext('idsw.imap.user'),
								 'type' => 'text', 'maxlength' => 32 ],
			'imap_pwd' 		=> [ 'label' => $this->gettext('idsw.imap.pwd'),
								 'type' => 'password', 'maxlength' => 64 ],
			'imap_delim'	=> [ 'label' => $this->gettext('idsw.imap.delim'),
							  	 'type' => 'text', 'maxlength' => 1 ],
		];
	}

	/**
	 * 	Get SMTP preferences
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	private function get_smtp_form(array &$args): array
	{
        // Not creating new identity
		if (isset($args['record']['identity_id']))
			$rec = self::get($args['record']['identity_id']);
		else
			$rec = null;

		$authType = new html_select([ 'name' => "_smtp_auth" ]);
		$authType->add($this->gettext('idsw.smtp.auth.none'), 'none');
		$authType->add($this->gettext('idsw.smtp.auth.imap'), 'imap');

        $args['record']['smtp_host'] = $rec ? $rec['smtp_host'] : '';
        $args['record']['smtp_port'] = $rec ? $rec['smtp_port'] : '';
        $enc						 = $rec ? ($rec['flags'] & self::SMTP_IMAP ? 'imap' : 'none') : 'none';

        return [
			'smtp_host'	 	=> [ 'label' => $this->gettext('idsw.smtp.host'),
								 'type' => 'text', 'maxlength' => 64 ],
			'smtp_port' 	=> [ 'label' => $this->gettext('idsw.smtp.port'),
								 'type' => 'text', 'maxlength' => 5 ],
			'smtp_auth' 	=> [ 'label' => $this->gettext('idsw.smtp.auth'),
								 'value' => $authType->show($enc) ],
		];
	}

	/**
	 * 	Create/Update identity
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	function identy_switch_update(array $args): array
	{
		$rc = rcmail::get_instance();

		$data = $this->check_field_values();
		if (isset($data['err']))
		{
			$this->add_texts('localization');
			$args['abort'] = true;
			$args['message'] = $this->gettext('err.'.$data['err']);

			return $args;
		}

		// Any identy_switch data?
		if (!count($data))
			return $args;

		$data['iid'] = $args['id'];

		$sql = 'SELECT id, flags FROM '.$rc->db->table_name(self::TABLE).
			   ' WHERE iid = ? AND user_id = ?';
		$q = $rc->db->query($sql, $data['iid'], $rc->user->ID);
		$r = $rc->db->fetch_assoc($q);

		// Record already exists, will update it
		if ($r)
		{
			// Record enabled?
			if (count($data) == 1)
			{
				self::set($data['iid'], 'flags', $data['flags'] = self::get($data['iid'], 'flags') & ~self::ENABLED);

				$sql = 'UPDATE '.$rc->db->table_name(self::TABLE).' SET flags = ? WHERE iid = ?';
				$q = $rc->db->query($sql, $data['flags'], $data['iid']);
				$r = $rc->db->fetch_assoc($q);

				return $args;
			}

			$sql = 'UPDATE ' .
				$rc->db->table_name(self::TABLE) .
				' SET flags = ?, label = ?, imap_host = ?, imap_port = ?, imap_delim = ?,'.
				' imap_user = ?, imap_pwd = ?, smtp_host = ?, smtp_port = ?, '.
				' notify_timeout = ?, newmail_check = ?, user_id = ?, iid = ?' .
				' WHERE id = ?';
		}
		// No record exists, create new one
		else if ($data['flags'] & self::ENABLED)
		{
			$sql = 'INSERT INTO ' .
				$rc->db->table_name(self::TABLE) .
				'(flags, label, imap_host, imap_port, imap_delim, imap_user, imap_pwd,'.
				' smtp_host, smtp_port, notify_timeout, newmail_check, user_id, iid)'.
				' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
		}

		if ($sql)
		{
			// Do we need to update password?
			if (isset($data['imap_pwd']))
				$data['imap_pwd'] = $rc->encrypt($data['imap_pwd']);

			## $rc->db->set_debug(true);
			$rc->db->query(
				$sql,
				$data['flags'],
				$data['label'],
				$data['imap_host'],
				$data['imap_port'],
				$data['imap_delim'],
				$data['imap_user'],
				$data['imap_pwd'],
				$data['smtp_host'],
				$data['smtp_port'],
				$data['notify_timeout'],
				$data['newmail_check'],
				$rc->user->ID,
				$data['iid'],
				is_bool($r) ? 0 : $r['id'],
			);

			// Update cache
			foreach ($data as $k => $v)
				if ($k != 'iid')
					self::set($data['iid'], $k, $v);
		}

		return $args;
	}

	/**
	 * 	Delete identity
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	function identy_switch_delete(array $args): array
	{
		$rc = rcmail::get_instance();

		$sql = 'DELETE FROM '.$rc->db->table_name(self::TABLE).' WHERE iid = ? AND user_id = ?';
		$q = $rc->db->query($sql, $args['id'], $rc->user->ID);
		if ($rc->db->affected_rows($q))
			$this->write_log('Deleted identity "'.$args['id'].'"');

		self::del($args['id']);

		return $args;
	}

	/**
	 * 	Check settings field values
	 *
	 * 	@return array
	 */
	private function check_field_values(): array
	{
		$retVal = [];

		$iid = (string)rcube_utils::get_input_value('_iid', rcube_utils::INPUT_POST);
		if (!isset($_SESSION[self::TABLE][$iid]))
			return $retVal;

		if ($this->get_field_value($iid, 'enabled'))
			$retVal['flags'] = self::ENABLED;
		else
			$retVal['flags'] &= ~self::ENABLED;

		if (!($retVal['label'] = $this->get_field_value($iid, 'label')))
			$retVal['label'] = 'identy_switch';

		if (!($retVal['imap_host'] = $this->get_field_value($iid, 'imap_host')))
			$retVal['err'] = 'imap.host.miss';
		if (!($retVal['imap_auth'] = $this->get_field_value($iid, 'imap_auth')))
			$retVal['err'] = 'imap.auth';
		$retVal['imap_port'] = $this->get_field_value($iid, 'imap_port');
		if (!($retVal['imap_user'] = $this->get_field_value($iid, 'imap_user')))
			$retVal['err'] = 'imap.user.miss';
		if (!($retVal['imap_pwd'] = $this->get_field_value($iid, 'imap_pwd', true, true)))
			$retVal['err'] = 'imap.pwd.miss';
		if (!($retVal['imap_delim'] = $this->get_field_value($iid, 'imap_delim')))
			$retVal['err'] = 'imap.delim.miss';

		// Check for overrides
		if ($retVal['imap_host'] && strpos($retVal['imap_host'], '://'))
		{
			$retVal['imap_auth'] = strtolower(substr($retVal['imap_host'], 0, 3));
			$retVal['imap_host'] = substr($retVal['imap_host'], 6);
			if ($p = strpos($retVal['imap_host'], ':'))
			{
				$retVal['imap_port'] = substr($retVal['imap_host'], $p + 1);
				$retVal['imap_host'] = substr($retVal['imap_host'], 0, $p);
			}
		}
		if (!$retVal['imap_port'])
			$retVal['err'] = 'imap.port.num';
		elseif (!ctype_digit($retVal['imap_port']))
			$retVal['err'] = 'imap.port.num';
		elseif (($retVal['imap_port'] < 1 || $retVal['imap_port'] > 65535))
			$retVal['err'] = 'imap.port.range';
		if ($retVal['imap_auth'] == 'ssl')
			$retVal['flags'] |= self::IMAP_SSL;
		elseif ($retVal['imap_auth'] == 'tls')
			$retVal['flags'] |= self::IMAP_TLS;

		if (!($retVal['smtp_host'] = $this->get_field_value($iid, 'smtp_host')))
			$retVal['err'] = 'smtp.host.miss';
		$retVal['smtp_port'] = $this->get_field_value($iid, 'smtp_port');

		// Check for overrides
		if ($retVal['smtp_host'])
		{
			if (strpos($retVal['smtp_host'], '://'))
				$retVal['smtp_host'] = substr($retVal['smtp_host'], 6);
			if ($p = strpos($retVal['smtp_host'], ':'))
			{
				$retVal['smtp_port'] = substr($retVal['smtp_host'], $p + 1);
				$retVal['smtp_host'] = substr($retVal['smtp_host'], 0, $p);
			}
		}
		if (!$retVal['smtp_port'])
			$retVal['err'] = 'smtp.port.num';
		elseif (!ctype_digit($retVal['smtp_port']))
			$retVal['err'] = 'smtp.port.num';
		elseif ($retVal['smtp_port'] < 1 || $retVal['smtp_port'] > 65535)
			$retVal['err'] = 'smpt.port.range';
		if (($retVal['smtp_auth'] = $this->get_field_value($iid, 'smtp_auth')) == 'imap')
			$retVal['flags'] |= self::SMTP_IMAP;

		// Check notification options
		if ($this->get_field_value($iid, 'notify_all_folder'))
			$retVal['flags'] |= self::CHECK_ALLFOLDER;
		if ($this->get_field_value($iid, 'notify_basic'))
			$retVal['flags'] |= self::NOTIFY_BASIC;
		if ($this->get_field_value($iid, 'notify_desktop'))
			$retVal['flags'] |= self::NOTIFY_DESKTOP;
		if ($this->get_field_value($iid, 'notify_sound'))
			$retVal['flags'] |= self::NOTIFY_SOUND;
		$retVal['notify_timeout'] = $this->get_field_value($iid, 'notify_timeout');
		$retVal['newmail_check'] = $this->get_field_value($iid, 'refresh_interval') * 60;

		return $retVal;
	}

	/**
	 * 	Get field value in settings
	 *
	 * 	@param string $iid		identy_switch id
	 * 	@param string $field 	Field name
	 * 	@param bool $trim		Whether to trim data
	 * 	@param bool $html		Allow HTML tags in field value
	 * 	@return string|NULL		Request parameter value or NULL if not set
	 */
	private function get_field_value(string $iid, string $field, bool $trim = true, bool $html = false): ?string
	{
		if (!($rc = rcube_utils::get_input_value('_'.$field, rcube_utils::INPUT_POST, $html)) && $iid)
		{
			if ($field == 'imap_auth')
			{
				$rc = (int)self::get($iid, 'flags');
				if ($rc & self::IMAP_SSL)
					$rc = 'ssl';
				elseif ($rc & self::IMAP_TLS)
					$rc = 'tls';
				else
					$rc = '';
			}
			elseif ($field == 'smtp_auth')
			{
				$rc = (int)self::get($iid, 'flags');
				$rc = $rc & self::SMTP_IMAP ? 'imap' : '';
			}
			elseif ($field == 'notify_all_folder')
			{
				$rc = (int)self::get($iid, 'flags');
				$rc = $rc & self::CHECK_ALLFOLDER ? '1' : '0';
			}
			elseif ($field == 'notify_basic')
			{
				$rc = (int)self::get($iid, 'flags');
				$rc = $rc & self::NOTIFY_BASIC ? '1' : '0';
			}
			elseif ($field == 'notify_desktop')
			{
				$rc = (int)self::get($iid, 'flags');
				$rc = $rc & self::NOTIFY_DESKTOP ? '1' : '0';
			}
			elseif ($field == 'notify_sound')
			{
				$rc = (int)self::get($iid, 'flags');
				$rc = $rc & self::NOTIFY_SOUND ? '1' : '0';
			}
			else
				$rc = self::get($iid, $field);
		}

		if (!$trim)
			return $rc;

		if (is_null($rc))
			return $rc;

		if (!is_string($rc))
			$rc = (string)$rc;

		$s = trim($rc);
		if (!$s)
			return null;
		else
			$rc = $s;

		return $rc;
	}

	/**
	 * 	Process config.inc.php
	 *
	 * 	@param string $email	Users eMail
	 * 	@return bool|array
	 */
	private function get_config(string $email): bool|array
	{
		if (!($p = strstr($email, '@')) || !($dom = substr($p, 1)))
			return false;

		// Load config.inc.php
		$this->load_config();

		$cfg = rcmail::get_instance()->config->get('identy_switch.config', []);
		if ($cfg = isset($cfg[$dom]) ? $cfg[$dom] : false)
		{
			// Host must be specified!
			if (!$cfg['imap'])
				return false;
		}

		return $cfg;
	}

	/**
	 * 	Set variable in cache
	 *
	 * 	@param string|int $sect
	 * 	@param array|string $var
	 * 	@param string|int|bool $val
	 * 	@param string|int|bool $default
	 */
	protected function set(string|int $sect = null, array|string $var,
						 mixed $val = null, string|int|bool $default = null): void
	{
		if (!isset($_SESSION[self::TABLE]))
			$_SESSION[self::TABLE] = [];

		if ($sect && !isset($_SESSION[self::TABLE][$sect]))
			$_SESSION[self::TABLE][$sect] = [];

		if ($sect)
		{
			if (is_array($var))
				foreach ($var as $k => $v)
					$_SESSION[self::TABLE][$sect][$k] = is_null($v) ? $default : $v;
			else
				$_SESSION[self::TABLE][$sect][$var] = $val;
		} else
			if (is_array($var))
				foreach ($var as $k => $v)
					$_SESSION[self::TABLE][$k] = $v;
			else
				$_SESSION[self::TABLE][$var] = is_null($val) ? $default : $val;
	}

	/**
	 * 	Get cached variable
	 *
	 * 	@param string|int $sect
	 * 	@param string $var
	 * 	@return mixed
	 */
	protected function get(string|int $sect = null, string $var = null): mixed
	{
		if (!$sect && !$var)
		{
			if (!isset($_SESSION[self::TABLE]))
				$_SESSION[self::TABLE] = [];
			return $_SESSION[self::TABLE];
		}

		if ($sect)
			return $var ? (isset($_SESSION[self::TABLE][$sect][$var]) ? $_SESSION[self::TABLE][$sect][$var] : '') :
				   (isset($_SESSION[self::TABLE][$sect]) ? $_SESSION[self::TABLE][$sect] : '');
		else
			return isset($_SESSION[self::TABLE][$var]) ? $_SESSION[self::TABLE][$var] : '';
	}

	/**
	 * 	Delete cached variable
	 *
	 * 	@param string|int $sect
	 * 	@param string $var
	 */
	protected function del(string|int $sect = null, string $var = null): void
	{
		if (!$sect && !$var)
			$_SESSION[self::TABLE] = [];

		if ($sect)
			unset($_SESSION[self::TABLE][$sect]);
		else
			unset($_SESSION[self::TABLE][$var]);
	}

	/**
	 * 	Write log message
	 *
	 * 	@param string $txt 		Log message
	 */
	protected function write_log(string $txt): void
	{
		if (self::get('config', 'logging'))
			rcmail::get_instance()->write_log('identy_switch', $txt);
	}

}
