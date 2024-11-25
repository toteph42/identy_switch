<?php
declare(strict_types=1);

/*
 * 	Identity switch RoundCube Bundle
 *
 *	@copyright	(c) 2024 Forian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/identity_switch/blob/master/LICENSE
 */

/**
 *
 * 	Data structure
 *
 * 	config 				configuration data
 * 		logging			allow logging to 'logs/identity_switch.log'
 * 		debug			log debug message to 'logs/identity_switch.log'
 * 		check			allow new mail checking
 * 		interval		specify interval for checking of new mails
 * 		delay			delay between each new mail check
 * 		retries			specify no. of retries for reading data from mail server
 * 		language		language used
 * 		cache	 		all session variables used by identity switch
 * 		data	  		unseen exchange data file
 * 		fp				file pointer
 * 	iid					active identity
 * 	[n]					cached identity data
 * 		label			label
 * 		flags			glags
 * 		imap_user		IMAP user
 * 		imap_pwd		IMAP password
 * 		imap_host		IMAP host
 * 		imap_delim		golder delimiter
 * 		imap_port		IMAP port
 * 		smtp_host		SMTP host
 * 		smtp_port		SMTP port
 * 		notify_timeout	notification timeout
 * 		newmail_check	new mail check interval
 * 		folders			special folder name array
 * 		unseen			# of unseen messages
 * 		checked_last	last time checked
 * 		notify			notify user flag
 *
 */

require_once INSTALL_PATH.'plugins/identity_switch/identity_switch_prefs.php';
require_once INSTALL_PATH.'plugins/identity_switch/identity_switch_newmails.php';

class identity_switch extends identity_switch_prefs
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

##
## unset($_SESSION[self::TABLE]);
		// identity switch hooks and actions
		$this->add_hook('startup', 						  [ $this, 'on_startup' ]);
		$this->add_hook('render_page', 					  [ $this, 'on_render_page' ]);
		$this->add_hook('smtp_connect', 				  [ $this, 'on_smtp_connect' ]);
		$this->add_hook('template_object_composeheaders', [ $this, 'on_object_composeheaders' ]);
		$this->register_action('identity_switch_do',  	  [ $this, 'identity_switch_do_switch' ]);

		// preference hooks and actions
		parent::init();

		// notification hooks and action
		if ($rc->output instanceof rcmail_output_html) {
			$rc->output->add_script('identity_switch_init();', 'head_top');
			$rc->output->include_script('../../plugins/identity_switch/assets/identity_switch.js');
		}

		// new mail hooks and action
		$this->add_hook('new_messages', 				  [ $this, 'catch_newmails' ]);
		$this->add_hook('refresh', 			  			  [ $this, 'check_newmails' ]);
		$this->add_hook('ready',	 					  [ $this, 'check_newmails' ]);

		// LDAP hooks
		if ($rc->config->get('ldapAliasSync', null))
			$this->add_hook('storage_connect', [ $this, 'override_ldap_password' ]);

		$this->include_stylesheet('assets/identity_switch.css');
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

		// not default user?
		if (isset($_SESSION['username']) && strcasecmp($rc->user->data['username'], $_SESSION['username']) !== 0)
		{
			// we are impersonating
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

			// first call?
			if (!isset($_SESSION[identity_switch_prefs::TABLE]['iid']) || !$_SESSION[identity_switch_prefs::TABLE]['iid'])
			{
				$iid = $rc->user->get_identity();
				$iid = $iid['identity_id'];

				// create defaults for default user
				self::get($iid);

				// set default user number
				self::set('iid', $iid);

				// collect data for default identity
				$i = $rc->user->get_identity();
				self::set($iid, 'label', $i['name']);
				self::set($iid, 'flags', self::ENABLED);

				// swap IMAP data
				self::set($iid, 'imap_user', $_SESSION['username']);
				self::set($iid, 'imap_pwd', $_SESSION['password']);
				self::set($iid, 'imap_host', $_SESSION['storage_host']);
				self::set($iid, 'imap_port', $_SESSION['storage_port']);
				if ($_SESSION['storage_ssl'] == 'ssl')
					self::set($iid, 'flags', self::get($iid, 'flags') | self::IMAP_SSL);
				if ($_SESSION['storage_ssl'] == 'tls')
					self::set($iid, 'flags', self::get($iid, 'flags') | self::IMAP_TLS);
				self::set($iid, 'imap_delim', $_SESSION['imap_delimiter']);

				// Sswap SMTP data
				$hosts = $rc->config->get('smtp_host');
				if (!is_array ($hosts))
					$hosts = [ $_SESSION['storage_host'] => $hosts ];
				$host = null;
				foreach ($hosts as $imap => $smtp)
				{
					if (!strcmp($imap, $_SESSION['storage_host']))
					{
						$host = $smtp;
						break;
					}
				}
				if (!$host)
				{
					self::write_log('Cannot discover associated SMTP host to IMAP server "'.$_SESSION['storage_host'].'" '.
									 '- substituting with "localhost"');
					$host = 'localhost';
				}

				// parse host name for special characters
				$host = rcube_utils::parse_host($host);

				if (substr($host, 3, 1) == ':')
				{
					if (strtolower(substr($host, 0, 3)) == 'ssl')
					{
						self::set($iid, 'flags', self::get($iid, 'flags') | self::SMTP_SSL);
						$host = substr($host, 6);
						self::set($iid, 'smtp_port', 465);
					}
					elseif (strtolower(substr($host, 0, 3)) == 'tls')
					{
						self::set($iid, 'flags', self::get($iid, 'flags') | self::SMTP_TLS);
						$host = substr($host, 6);
						self::set($iid, 'smtp_port', 587);
					}
					// Unknown protocoll
					if (($p = strpos($host, ':')) !== false)
					{
						self::set($iid, 'smtp_port', substr($host, $p + 1));
						$host = substr($host, 0, $p);
					}
				}
				self::set($iid, 'smtp_host', $host);

				$prefs = $rc->user->get_prefs();

				// swap nofication data
				$p = 'newmail_notifier_';
				if (isset($prefs['check_all_folders']) && $prefs['check_all_folders'])
					self::set($iid, 'flags', self::get($iid, 'flags') | self::CHECK_ALLFOLDER);
				foreach ([ 'basic' 	 => self::NOTIFY_BASIC,
						   'desktop' => self::NOTIFY_DESKTOP,
 		        		   'sound' 	 => self::NOTIFY_SOUND] as $k => $v)
		        {
		            if (isset($prefs[$p.$k]) && $prefs[$p.$k] == 1)
						self::set($iid, 'flags', self::get($iid, 'flags') | $v);
	            }
	            if (isset($prefs[$p.'_desktop_timeout']))
		            self::set($iid, 'notify_timeout', $prefs[$p.'_desktop_timeout']);

	            // swap new mail check interval
				self::set($iid, 'newmail_check', isset($prefs['refresh_interval']) ? $prefs['refresh_interval'] :
						  $rc->config->get('refresh_interval'));

				// swap special folder names
				$box = [];
				foreach (rcube_storage::$folder_types as $mbox)
					$box[$mbox] = isset($prefs[$mbox.'_mbox']) ? $prefs[$mbox.'_mbox'] : '';
				self::set($iid, 'folders', $box);
				if (isset($prefs['show_real_foldernames']) && $prefs['show_real_foldernames'] == 'true')
					self::set($iid, 'flags', self::get($iid, 'flags') | self::SHOW_REAL_FOLDER);
				self::set($iid, 'flags', self::get($iid, 'flags') | (isset($prefs['lock_special_folders']) &&
				   $prefs['lock_special_folders'] == true ? self::LOCK_SPECIAL_FOLDER : 0));

				// swap data of alternate accounts
				$sql = 'SELECT isw.* '.
					   'FROM '.$rc->db->table_name(self::TABLE).' isw '.
					   'INNER JOIN '.$rc->db->table_name('identities').' ii ON isw.iid=ii.identity_id '.
					   'WHERE isw.user_id = ?';
				$q = $rc->db->query($sql, $rc->user->data['user_id']);

				while ($r = $rc->db->fetch_assoc($q))
				{
					// is it default identity?
					if ($iid == $r['iid'])
						self::set($iid, 'label', $r['label']);
					else {
						// load default settings
						self::get($r['iid']);
						// swap saved data
						foreach ($r as $k => $v)
						{
							// skip some fields
							if ($k == 'id' || $k == 'user_id' || $k == 'iid')
								continue;
							if ($k == 'folders')
								$v = is_null($v) ? [] : json_decode($v);
							self::set($r['iid'], $k, $v);
						}
					}
				}
			}
			if ($args['template'] == 'mail')
				self::create_menu();
			break;

		case 'settings':
			$this->include_script('assets/identity_switch-form.js');
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

		// build identity table
		$acc = [];
		foreach (self::get() as $iid => $rec)
		{
			// identity switch enabled?
			if (is_numeric($iid) && is_array($rec) && ($rec['flags'] & self::ENABLED))
				$acc[rcube::Q($rec['label'])] = [ 'iid' => $iid, 'unseen' => $rec['unseen'] ];
		}

		// sort identities
		ksort($acc);

		// render UI if user has extra accounts
		if (count($acc) > 1)
		{
			$iid = self::get('iid');
			$div = '<div id="identity_switch_menu" '.
				   'class="form-control" '.
				   'onclick="identity_switch_toggle_menu()">'.
				   rcube::Q(self::get($iid, 'label')).
				   '<div id="identity_switch_dropdown"><ul>';
			foreach ($acc as $name => $rec)
				if ($rec['iid'] != $iid)
				{
					$div .= '<li onclick="identity_switch_run('.$rec['iid'].');"><a href="#">'.$name.
					  	   	'<span id="identity_switch_opt_'.$rec['iid'].'" class="unseen">'.
					  	   	($rec['unseen'] > 0 ? $rec['unseen'] : '').'</span></a></li>';
				}
			$rc->output->add_footer($div.'</ul></div></div>');
		}
	}

	/**
	 * 	Perform identity switch
	 */
	function identity_switch_do_switch(): void
	{
		$rc = rcmail::get_instance();

		$rc->session->remove('folders');
		$rc->session->remove('unseen_count');

		// update current unseen counter
		$iid = self::get('iid');
		$folders = [ 'INBOX' ];
		$storage = $rc->get_storage();
		if (self::get($iid, 'flags') & identity_switch_prefs::CHECK_ALLFOLDER)
			$folders += $storage->list_folders_subscribed('', '*'. null, null, true);
		$unseen  = 0;
		foreach ($folders as $mbox)
			$unseen += $storage->count($mbox, 'UNSEEN', true, false);
		self::set($iid, 'unseen', $unseen);
        self::set($iid, 'checked_last', time());

		// get new account
		$rec = self::get($iid = rcube_utils::get_input_value('identity_switch_iid', rcube_utils::INPUT_POST));

		$this->write_log('Switching to identity "'.$rec['imap_user'].'"');

		// swap data
		self::swap($iid, $rec);

		$rc->output->redirect(
			[
				'_task' => 'mail',
				'_mbox' => 'INBOX',
			]
		);
	}

	/**
	 * 	Send mail
	 *
	 * 	@param array $args
	 * 	@return array
	 */
	function on_smtp_connect(array $args): array
	{
		$rc = rcmail::get_instance();

		$rec = self::get(self::get('iid'));

		$args['smtp_user'] = $rec['imap_user'];
        $args['smtp_pass'] = $rec['imap_pwd'] && ($rec['flags'] & (self::SMTP_SSL|self::SMTP_TLS)) ?
        					 $rc->decrypt($rec['imap_pwd']) : '';
		$args['smtp_host'] = $rec['smtp_host'].':'.$rec['smtp_port'];
		if ($rec['flags'] & (self::SMTP_SSL|self::SMTP_TLS))
			$args['smtp_host'] = ($rec['flags'] & self::SMTP_SSL ? 'ssl' : 'tls').'://'.$args['smtp_host'];

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
				$rc->output->add_script('identity_switch_fixIdent('.self::get('iid').');', 'docready');
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

    	// do not do anything for default identity
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
                // replace 'password' with the password you want to use
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
        // unexpected input?
        if (empty($args['diff']['new']))
            return $args;

        $iid = self::get('iid');
        $n   = 0;
        foreach (explode(':', $args['diff']['new']) as $id)
        	if (strlen($id) > 1)
        		$n++;
        self::set($iid, 'unseen', (int)(self::get($iid, 'unseen')) + $n);
        self::set($iid, 'checked_last', time());
        self::set($iid, 'notify', true);

		self::do_notify();

 		return $args;
	}

	/**
	 * 	Check for number of new mails
	 */
	function check_newmails($args) {

		// get configuration
		if(!is_array($cfg = self::get('config')))
			return $args;

		// new mail check disabled?
		if (!self::get('config', 'check'))
		{
			self::write_log('New mail check disabled - stop checking', true);
			return $args;
		}

		// only allow call under special conditions
		if (!isset($args['action']) || ($args['action'] != 'refresh' && $args['action'] != 'getunread'))
			return $args;

		self::write_log('Starting new mail check with arguments "'.serialize($args).'"."', true);
		self::write_log('Configuration loaded "'.serialize($cfg).'".', true);

		// make a copy of our cached data
		$cache = self::get();

		// check if we're outside waiting window
		$chk = 0;
		foreach ($cache as $iid => $rec)
		{
			if (!is_integer($iid))
				continue;

			if ((int)$rec['flags'] & identity_switch_prefs::ENABLED && (int)$rec['checked_last'] + $cfg['interval'] < time())
				$chk++;
			else
				unset($cache[$iid]);
		}

		if (!$chk)
		{
			if (!$chk)
				self::write_log('No accounts to check - stop checking', true);

			return $args;
		}

		self::write_log('Check allowed for '.$chk.' account(s)', true);

		if ($chk && !file_exists($cfg['cache']))
		{
			// The host, we want to reach out
			if (!is_resource($cfg['fp']))
			{
			    $host = ($_SERVER['SERVER_PORT'] != '80' ? 'ssl://' : '').$_SERVER['HTTP_HOST'].':'.$_SERVER['SERVER_PORT'];
			    self::set('config', 'fp', $cfg['fp'] = new identity_switch_rpc());
				if (is_string($cfg['fp']->open($host)))
				{
					$this->write_log('New mail chking error - '.$cfg['fp'].' for '.$host.' - stop checking');
					return $args;
				}
				self::write_log('Host "'.$host.'" opened', true);
			}

			// save data for background sharing
			file_put_contents($cfg['cache'], serialize($cache));

			self::write_log('Cache file "'.$cfg['cache'].'" created');

    		// prepare request (no fopen() usage because "allow_url_fopen=FALSE" may be set in PHP.INI)
			$req = '/plugins/identity_switch/identity_switch_newmails.php?iid=0&cache='.urlencode($cfg['cache']);
			if (!$cfg['fp']->write($req))
			{
				fclose($cfg['fp']);
				self::set('config', 'fp', $cfg['fp'] = 0);
				$this->write_log('Cannot write to "'.$host.'" Request: "'.$req.'" - stop checking');
				return $args;
			}
			self::write_log('Starting request "'.$req.'"', true);
		}

		// check for data file
		$n = 0;
		while (!file_exists($cfg['data']))
		{
			if ($n++ > 60)
			{
				self::write_log('No data file exist - stop checking', true);
				return $args;
			}
			sleep (1);
		}

		// load data file
		self::write_log('Loading and deleting data file', true);
		$wrk = file_get_contents($cfg['data']);
		@unlink($cfg['data']);

		// process data lines
		if (is_string($wrk))
		{
			foreach (explode('###', $wrk) as $line)
			{
				if (!$line)
					continue;

				$r = explode('##', $line);
				// #35 bad formatted returned string
				if (!is_array($r))
					continue;

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

			self::write_log('Starting notification.', true);

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

		//  control array
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
			// skip unwanted entries
			if (!is_numeric($iid))
				continue;

			// set unseen to provide to browser
			$ctl[$cnt]['iid'] 	 = $iid;
			$ctl[$cnt]['unseen'] = $rec['unseen'];

			// should we notify?
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

		$rc->output->command('plugin.identity_switch_notify', $ctl);
	}

}
