<?php
declare(strict_types=1);

/*
 * 	Identy switch RoundCube Bundle
 *
 *	@copyright	(c) 2024 Forian Daeumling, Germany. All right reserved
 * 	@license 	LGPL-3.0-or-later
 */

/**
 *
 * 	Data structure
 *
 * 	config 				Configuration data
 * 		logging			Allow logging to 'logs/identy_switch.log'
 * 		check			Allow new mail checking
 * 		interval		Specify interval for checking of new mails
 * 		retries			Specify no. of retries for reading data from mail server
 * 		language		Language used
 * 		cache	 		All session variables used by identy switch
 * 		data	  		Unseen exchange data file
 * 		fp				File pointer
 * 	iid					Active identity (-1 = default user)
 * 	[n]					Cached identity data
 * 		label			Label
 * 		flags			Flags
 * 		imap_user		IMAP user
 * 		imap_pwd		IMAP password
 * 		imap_host		IMAP host
 * 		imap_port		IMAP port
 * 		smtp_host		SMTP host
 * 		smtp_port		SMTP port
 * 		notify_timeout	Notification timeout
 * 		newmail_check	New mail check interval
 * 		drafts			Draft folder name
 * 		sent			Sent folder name
 * 		junk			Junk folder name
 * 		trash			Trash folder name
 * 		unseen			# of unseen messages
 * 		checked_last	Last time checked
 * 		notify			Notify user flag
 *
 */

require_once INSTALL_PATH.'plugins/identy_switch/identy_switch_prefs.php';
require_once INSTALL_PATH.'plugins/identy_switch/identy_switch_newmails.php';

class identy_switch extends identy_switch_prefs
{
	/**
	 * 	Initialize Plugin
	 *
	 * 	{@inheritDoc}
	 * 	@see rcube_plugin::init()
	 */
	function init(): void
	{
		$rc = rcmail::get_instance();

		// Identy switch hooks and actions
		$this->add_hook('startup', 						  [ $this, 'on_startup' ]);
		$this->add_hook('render_page', 					  [ $this, 'on_render_page' ]);
		$this->add_hook('smtp_connect', 				  [ $this, 'on_smtp_connect' ]);
		$this->add_hook('template_object_composeheaders', [ $this, 'on_object_composeheaders' ]);
		$this->register_action('identy_switch_do',  	  [ $this, 'identy_switch_do_switch' ]);

		// Preference hooks and actions
		parent::init();

		// Notification hooks and action
		if ($rc->output instanceof rcmail_output_html) {
			$rc->output->add_script('identy_switch_init();', 'head_top');
			$rc->output->include_script('../../plugins/identy_switch/assets/identy_switch.js');
		}

		// New mail hooks and action
		$this->add_hook('new_messages', 				  [ $this, 'catch_newmails' ]);
		$this->add_hook('refresh', 			  			  [ $this, 'check_newmails' ]);
		$this->add_hook('ready',	 					  [ $this, 'check_newmails' ]);

		// LDAP hooks
		if ($rc->config->get('ldapAliasSync', null))
			$this->add_hook('storage_connect', [ $this, 'override_ldap_password' ]);

		$this->include_stylesheet('assets/identy_switch.css');
	}

	/**
	 * 	Startup script
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	function on_startup(array $args): array
	{
		$rc = rcmail::get_instance();

		if (strcasecmp($rc->user->data['username'], $_SESSION['username']) !== 0)
		{
			// We are impersonating
			$rc->config->set('imap_cache', null);
			$rc->config->set('messages_cache', false);

			if ($args['task'] == 'mail')
			{
				$this->add_texts('localization/');
				$rc->config->set('create_default_folders', false);
			}
		}

		return $args;
	}

	/**
	 * 	Dispatch action
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	function on_render_page(array $args): array
	{
		$rc = rcmail::get_instance();

		switch ($rc->task)
		{
		case 'mail':

			$this->add_texts('localization');

			// Add onclick() handler to make sure selection menu will be closed
			#$args['content'] = str_replace('<body', '<body onclick="identy_switch_toggle_menu(true)" ', $args['content']);

			// First call?
			if (!self::get(null, 'iid'))
			{
				// load configuration
				$this->load_config();
				foreach ($rc->config->get('identy_switch.config', []) as $k => $v)
				{
					if ($k == 'logging')
						self::set('config', $k, $v, false);
					if ($k == 'check')
						self::set('config', $k, $v, true);
					if ($k == 'interval')
						self::set('config', $k, $v, 30);
					if ($k == 'retries')
						self::set('config', $k, $v, 10);
				}
				self::set('config', 'language', $_SESSION['language']);

				// Set default user
				self::set(null, 'iid', -1);

				// Collect data for default identity
				$i = $rc->user->get_identity();
				self::set(-1, 'label', $i['name']);
				self::set(-1, 'flags', self::ENABLED);

				// Swap IMAP data
				self::set(-1, 'imap_user', $_SESSION['username']);
				self::set(-1, 'imap_pwd', $_SESSION['password']);
				self::set(-1, 'imap_host', $_SESSION['storage_host']);
				self::set(-1, 'imap_port', $_SESSION['storage_port']);
				if ($_SESSION['storage_ssl'] == 'ssl')
					self::set(-1, 'flags', (int)self::get(-1, 'flags') | self::IMAP_SSL);
				if ($_SESSION['storage_ssl'] == 'tsl')
					self::set(-1, 'flags', (int)self::get(-1, 'flags') | self::IMAP_TLS);
				self::set(-1, 'imap_delim', $_SESSION['imap_delimiter']);

				// Swap SMTP data
				$host = $rc->config->get('smtp_host');
				$p = 0;
				if (substr($host, 3, 1) == ':')
				{
					self::set(-1, 'smtp_port', 465);
					self::set(-1, 'flags', self::get(-1, 'flags') | self::SMTP_IMAP);
					$host = substr($host, 6);
				} else
					self::set(-1, 'smtp_port', 587);
				if (($p = strpos($host, ':')) !== false)
				{
					self::set(-1, 'smtp_port', substr($host, $p + 1));
					self::set(-1, 'smtp_host', substr($host, 0, $p));
				} else
					self::set(-1, 'smtp_host', $host);

				$prefs = $rc->user->get_prefs();

				// Swap nofication data
				$p = 'newmail_notifier_';
				if (isset($prefs['check_all_folders']) && $prefs['check_all_folders'])
					self::set(-1, 'flags', self::get(-1, 'flags') | self::CHECK_ALLFOLDER);
				foreach ([ 'basic' => self::NOTIFY_BASIC, 'desktop' => self::NOTIFY_DESKTOP,
 		        		   'sound' => self::NOTIFY_SOUND] as $k => $v)
		        {
		            if (isset($prefs[$p.$k]) && $prefs[$p.$k] == 1)
						self::set(-1, 'flags', self::get(-1, 'flags') | $v);
	            }
	            self::set(-1, 'notify_timeout', isset($prefs[$p.'_desktop_timeout']) ?
	            									  $prefs[$p.'_desktop_timeout'] : 10);

	            // Swap new mail check interval
				self::set(-1, 'newmail_check', isset($prefs['refresh_interval']) ? $prefs['refresh_interval'] :
						  $rc->config->get('refresh_interval'));

				// Swap special folder names
				foreach (rcube_storage::$folder_types as $mbox)
					self::set(-1, $mbox, isset($prefs[$mbox.'_mbox']) ? $prefs[$mbox.'_mbox'] : '');
				if (isset($prefs['show_real_foldernames']))
					self::set(-1, 'flags', self::get(-1, 'flags') | self::SHOW_REAL_FOLDER);
				if (!in_array('lock_special_folders', isset($prefs['dont_override']) ?
							  $prefs['dont_override'] : [] ))
					self::set(-1, 'flags', self::get(-1, 'flags') | (isset($prefs['lock_special_folders']) &&
									   $prefs['lock_special_folders'] == '1' ? self::LOCK_SPECIAL_FOLDER : 0));

				// Volatile variables
				self::set(-1, 'unseen', 0);
				self::set(-1, 'checked_last', 0);
				self::set(-1, 'notify', false);

				// Swap data of alternative accounts
				$sql = 'SELECT isw.* '.
					   'FROM '.$rc->db->table_name(self::TABLE).' isw '.
					   'INNER JOIN '.$rc->db->table_name('identities').' ii ON isw.iid=ii.identity_id '.
					   'WHERE isw.user_id = ?';
				$q = $rc->db->query($sql, $rc->user->data['user_id']);

				while ($r = $rc->db->fetch_assoc($q))
				{
					foreach ($r as $k => $v)
					{
						if ($k == 'id' || $k == 'user_id' || $k == 'iid')
							continue;
						self::set($r['iid'], $k, $v);
					}
					// Volatile variables
					self::set($r['iid'], 'unseen', 0);
					self::set($r['iid'], 'checked_last', 0);
					self::set($r['iid'], 'notify', false);
				}
			}
			if ($args['template'] == 'mail')
				self::create_menu();
			break;

		case 'settings':
			$this->include_script('assets/identy_switch-form.js');
			break;
		}

		return $args;
	}

	/**
	 * 	Create selection menu
	 */
	protected function create_menu(): void
	{
		$rc = rcmail::get_instance();

		// Build identity table
		$acc = [];
		foreach (self::get() as $iid => $rec)
		{
			// Identy switch enabled?
			if (is_numeric($iid) && is_array($rec) && ($rec['flags'] & self::ENABLED))
				$acc[rcube::Q($rec['label'])] = [ 'iid' => $iid, 'unseen' => $rec['unseen'] ];
		}

		// Sort identities
		ksort($acc);

		// Render UI if user has extra accounts
		if (count($acc) > 1)
		{
			$iid = self::get(null, 'iid');
			$div = '<div id="identy_switch_menu" '.
				   'class="form-control" '.
				   'onclick="identy_switch_toggle_menu()">'.
				   rcube::Q(self::get($iid, 'label')).
				   '<div id="identy_switch_dropdown"><ul>';
			foreach ($acc as $name => $rec)
				if ($rec['iid'] != $iid)
				{
					$div .= '<li onclick="identy_switch_run('.$rec['iid'].');"><a href="#">'.$name.
					  	   	'<span id="identy_switch_opt_'.$rec['iid'].'" class="unseen">'.
					  	   	($rec['unseen'] > 0 ? $rec['unseen'] : '').'</span></a></li>';
				}
			$rc->output->add_footer($div.'</ul></div></div>');
		}
	}

	/**
	 * 	Perform identity switch
	 */
	function identy_switch_do_switch(): void
	{
		$rc = rcmail::get_instance();

		$rc->session->remove('folders');
		$rc->session->remove('unseen_count');

		// Force reload of unseen counter
		$iid = self::get(null, 'iid');
		self::set($iid, 'flags', self::get($iid, 'flags') | self::UNSEEN);
		self::set($iid, 'unseen', 0);
        self::set($iid, 'checked_last', 0);

		// Get new account
		$iid = rcube_utils::get_input_value('identy_switch_iid', rcube_utils::INPUT_POST);
		$rec = self::get($iid);

		if ($iid == -1)
			$this->write_log('Switching mailbox back to default identity "'.$rec['imap_user'].'"');
		else
			$this->write_log('Switching mailbox to identity "'.$rec['imap_user'].'"');

		$_SESSION['_name'] 				= $rec['label'];
		$_SESSION['username'] 			= $rec['imap_user'];
		$_SESSION['password'] 			= $rec['imap_pwd'];
		$_SESSION['storage_host'] 		= $rec['imap_host'];
		$_SESSION['storage_port'] 		= $rec['imap_port'];
		$_SESSION['storage_ssl'] 		= $rec['flags'] & self::IMAP_SSL ? 'ssl' :
										  ($rec['flags'] & self::IMAP_TLS ? 'tls' : '');
		$_SESSION['imap_delimiter'] 	= $rec['imap_delim'];
		$_SESSION['unseen']				= $rec['unseen'];
		self::set(null, 'iid', $iid);

		$prefs = $rc->user->get_prefs();

		// Set special folder
		$prefs['show_real_foldernames'] = $rec['flags'] & self::SHOW_REAL_FOLDER ? '1' : '0';
		$prefs['lock_special_folders'] = $rec['flags'] & self::LOCK_SPECIAL_FOLDER ? '1' : '0';
		foreach (rcube_storage::$folder_types as $mbox)
			$prefs[$mbox.'_mbox'] = $rec[$mbox];
		$prefs['check_all_folders'] = $rec['flags'] & self::CHECK_ALLFOLDER ? '1' : '0';
		$prefs['newmail_notifier_desktop_timeout'] = $rec['notify_timeout'];

		// Set notification
		foreach ([ 	self::NOTIFY_BASIC		=> 'basic',
					self::NOTIFY_DESKTOP	=> 'desktop',
					self::NOTIFY_SOUND		=> 'sound' ] as $k => $v)
			if ($rec['flags'] & $k)
				$prefs['newmail_notifier_'.$v] = '1';
		$prefs['newmail_notifier_timeout'] = $rec['notify_timeout'];
	    $rc->user->save_prefs($prefs);

		$rc->output->redirect(
			[
				'_task' => 'mail',
				'_mbox' => 'INBOX',
			]
		);
	}

	/**
	 * 	Send Mail
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	function on_smtp_connect(array $args): array
	{
		$rc = rcmail::get_instance();

		$rec = self::get(null, (string)self::get(null, 'iid'));

		$args['smtp_user'] = $rec['imap_user'];
        $args['smtp_pass'] = $rec['imap_pwd'] && ($rec['flags'] & self::SMTP_IMAP) ?
        					 $rc->decrypt($rec['imap_pwd']) : '';
		$args['smtp_host'] = $rec['smtp_host'].':'.$rec['smtp_port'];
		if (substr($args['smtp_host'], 4, 1) == ':')
			$this->write_log('SMTP server already contains protocol, ignoring session security settings.');
		elseif (($rec['flags'] & self::SMTP_IMAP) && $rec['flags'] & (self::IMAP_SSL|self::IMAP_TLS))
			$args['smtp_host'] = ($rec['flags'] & self::IMAP_SSL ? 'ssl' : 'tls').'://'.$args['smtp_host'];

		return $args;
	}

	/**
	 * 	Change userid in composer window to select proper identity
	 *
	 * 	@param array $args
	 */
	function on_object_composeheaders(array $args): void
	{
		if ($args['id'] == '_from')
		{
			$rc = rcmail::get_instance();
			if (strcasecmp($_SESSION['username'], $rc->user->data['username']) !== 0)
				$rc->output->add_script('identy_switch_fixIdent('.self::get(null, 'iid').');', 'docready');
		}
	}

	/**
	 * 	Override LDAP password
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	function override_ldap_password(array $args): array
    {
		$rc = rcmail::get_instance();

    	// Do not do anything for default identity
        if (strcasecmp($args['user'], $rc->user->data['username']) === 0)
        	return $args;

        $sql = 'SELECT imap_pwd FROM '.$rc->db->table_name(self::TABLE).' WHERE imap_user = ?';
        $q = $rc->db->query($sql, $args['user']);
        $r = $rc->db->fetch_assoc($q);

        if(is_array($r))
        {
        	if($r['imap_pwd'])
            {
            	$this->write_log('Override IMAP password for user "' .$args['user'].'"');
                // Replace 'password' with the password you want to use
                $args['pass'] = $rc->decrypt($r['imap_pwd']);
            }
         }

		return $args;
	}

	/**
	 * 	Catch new mail notification for default user
	 */
	function catch_newmails(array $args): array
	{
        // Unexpected input?
        if (empty($args['diff']['new']))
            return $args;

        $iid = self::get(null, 'iid');
        $n   = 0;
        foreach (explode(':', $args['diff']['new']) as $id)
        	if (strlen($id) > 1)
        		$n++;
        self::set($iid, 'unseen', self::get($iid, 'unseen') + $n);
        self::set($iid, 'checked_last', time());
        self::set($iid, 'notify', true);

		self::do_notify();

 		return $args;
	}

	/**
	 * 	Check for number of new mails
	 */
	function check_newmails($args) {

		$rc = rcmail::get_instance();

		// Get configuration
		if(!is_array($cfg = self::get('config')))
			return $args;

		// First time call?
		if (!isset($cfg['cache']))
		{
			self::set('config', 'cache', $cfg['cache'] = $rc->config->get('temp_dir',
					  sys_get_temp_dir()).'/identy_switch_cache.'.session_id());
			self::set('config', 'data', $cfg['data'] = str_replace('_cache', '_ret', $cfg['cache']));
			self::set('config', 'fp', $cfg['fp'] = 0);
		}

		// Feature disabled?
		if (!$cfg['check'])
			return $args;

		// Only allow call under special conditions
		if (!isset($args['action']) || $args['action'] != 'refresh')
			return $args;

		// Make a copy of our cached data
		$cache = self::get();

		// Check if we're outside waiting window
		$chk = 0;
		foreach ($cache as $iid => $rec)
		{
			if (!is_integer($iid))
				continue;

			if ((int)$rec['checked_last'] + $cfg['interval'] < time())
				$chk++;
			else
				unset($cache[$iid]);
		}

		// Check for data file
		$data_file = file_exists($cfg['data']);

		if (!$chk && !$data_file)
			return $args;

		if ($chk && !file_exists($cfg['cache']))
		{
			// The host, we want to reach out
			if (!is_resource($cfg['fp']))
			{
			    $host = ($_SERVER['SERVER_PORT'] != '80' ? 'ssl://' : '').$_SERVER['HTTP_HOST'].':'.$_SERVER['SERVER_PORT'];
			    self::set('config', 'fp', $cfg['fp'] = new identy_switch_rpc());
				if (is_string($cfg['fp']->open($host)))
				{
					$this->write_log('NewMail: error - '.$cfg['fp']);
					return $args;
				}
			}

			// Save data for background sharing
			file_put_contents($cfg['cache'], serialize($cache));

    		// Prepare request (no fopen() usage because "allow_url_fopen=FALSE" may be set in PHP.INI)
			$req = '/plugins/identy_switch/identy_switch_newmails.php?iid=0&cache='.urlencode($cfg['cache']);
			if (!$cfg['fp']->write($req))
			{
				$this->write_log('NewMail: Cannot write to "'.$host.'" Request: "'.$req.'"');
				fclose($cfg['fp']);
				self::set('config', 'fp', $cfg['fp'] = 0);
				return $args;
			}
		}

		// Check for data file
		if (!$data_file)
			return $args;

		// Load data file
		$wrk = file_get_contents($cfg['data']);
		@unlink($cfg['data']);

		// Process data lines
		if (is_string($wrk))
		{
			foreach (explode('###', $wrk) as $line)
			{
				if (!$line)
					continue;

				$r = explode('##', $line);
				// Check for error message
				if (!$r[1] && isset($r[2]))
				{
					$this->write_log('NewMail error: '.$r[2]);
					continue;
				}

				$rec = &self::get($r[1]);
				if ($r[2] != $rec['unseen'])
				{
					if ($r[2] > $rec['unseen'])
					{
						// Allow to notify
					 	if (!($rec['flags'] & self::UNSEEN))
					 		self::set($r[1], 'notify', true);
						else
							self::set($r[1], 'flags', $rec['flags'] & ~self::UNSEEN);
					}
					self::set($r[1], 'unseen', $r[2]);
				}
				self::set($r[1], 'checked_last', $r[0]);
			}

			self::do_notify();
		}

		return $args;
	}

	/**
	 * 	Do notification
	 */
	function do_notify(): void
	{
        $rc = rcmail::get_instance();

		$this->add_texts('localization');

		//  Control array
		$ctl    = [];
		$ctl[0] = [
					'autoplay'		=> rawurlencode($this->gettext('notify.err.autoplay')),
					'notification'	=> rawurlencode($this->gettext('notify.err.notification')),
					'title'			=> rawurlencode($this->gettext('notify.title')),
		];

		$cnt   = 1;
		$sound = false;
		$basic = false;
		foreach (self::get() as $iid => $rec)
		{
			// Skip unwanted entries
			if (!is_numeric($iid))
				continue;

			// Set unseen to provide to browser
			$ctl[$cnt]['iid'] 	 = $iid;
			$ctl[$cnt]['unseen'] = $rec['unseen'];

			// Should we notify?
			if ($rec['notify'])
			{
				self::set($iid, 'notify', false);

				if ($rec['flags'] & self::NOTIFY_BASIC && !$basic)
				{
					$basic = true;
					$ctl[$cnt]['basic'] = 1;
				}

			    if ($rec['flags'] & self::NOTIFY_DESKTOP)
			    	$ctl[$cnt]['desktop'] =  [
			    		'text' 		=> rawurlencode(sprintf($this->gettext('notify.msg'), $rec['unseen'],
								  	   $rec['label'])),
			    		'timeout'	=> $rec['notify_timeout'],
					];

				if ($rec['flags'] & self::NOTIFY_SOUND && !$sound)
				{
					$sound = true;
					$ctl[$cnt]['sound'] = 1;
				}
			}
			$cnt++;
		}

		$rc->output->command('plugin.identy_switch_notify', $ctl);
	}

}
