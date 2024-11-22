<?php
declare(strict_types=1);

/*
 * 	Identity switch RoundCube Bundle
 *
 *	@copyright	(c) 2024 Forian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/identity_switch/blob/master/LICENSE
 */

// include environment
if (!defined('INSTALL_PATH'))
	define('INSTALL_PATH', strpos($_SERVER['DOCUMENT_ROOT'], 'public_html') ?
		   realpath(__DIR__.'/../..').'/' : $_SERVER['DOCUMENT_ROOT'].'/');
require_once INSTALL_PATH.'program/include/iniset.php';
require_once INSTALL_PATH.'plugins/identity_switch/identity_switch_rpc.php';
require_once INSTALL_PATH.'plugins/identity_switch/identity_switch_prefs.php';

class identity_switch_newmails extends identity_switch_rpc {

	private $file;
	private $cache;
	private $fp;

    /**
     * 	Run the controller.
     */
    public function run(): void
    {
		$rc = rcmail::get_instance();

		// get Identity id
		if (is_null($iid = rcube_utils::get_input_value('iid', rcube_utils::INPUT_GET)))
		{
			identity_switch_prefs::write_log('Cannot load identity id - stop checking', true);
			return;
		}

		// get cache file name
		if (is_null($this->file = rcube_utils::get_input_value('cache', rcube_utils::INPUT_GET)))
		{
			identity_switch_prefs::write_log('Cannot get cache file name - stop checking');
			return;
		} else
			identity_switch_prefs::write_log('Cache file name "'.$this->file.'"', true);

		// get cached data
		if (!file_exists($this->file))
		{
			identity_switch_prefs::write_log('Cache file "'.$this->file.'" does not exists - stop checking');
			return;
		} else
			identity_switch_prefs::write_log('Cache file loaded', true);

		// storage initialization hook
		$rc->plugins->register_hook('storage_init', [ $this, 'set_language' ]);

		$this->cache = unserialize(file_get_contents($this->file));
		// save logging configuration
		$_SESSION[identity_switch_prefs::TABLE]['config'] = [
			'logging' => $this->cache['config']['logging'],
			'debug' => $this->cache['config']['debug'],
		];

		if (!$iid)
		{
			$res = [];
			foreach ($this->cache as $iid => $rec)
			{
				if (!is_numeric($iid))
					continue;

		        $host = ($_SERVER['SERVER_PORT'] != '80' ? 'ssl://' : '').$_SERVER['HTTP_HOST'].
		        		':'.$_SERVER['SERVER_PORT'];
		        $res[$iid] = new identity_switch_rpc();
				if (!$res[$iid]->open($host))
				{
					self::write_data($iid.'##'.$res[$iid]);
					identity_switch_prefs::write_log('Cannot open host "'.$host.'" - stop checking', true);
					return;
				}

				// prepare request (no fopen() usage because "allow_url_fopen=FALSE" may be set in PHP.INI)
				$req = '/plugins/identity_switch/identity_switch_newmails.php?iid='.$iid.
					   '&cache='.urlencode($this->file);
				if (!$res[$iid]->write($req))
				{
					fclose($res[$iid]);
					self::write_data('0##Identity: '.$iid.' Cannot write to "'.$host.'" Request: "'.$req.'" - stop checking');
					return;
				}

				// delay execution?
				if (count($this->cache) > 1 && isset($this->cache['config']['delay']) && $this->cache['config']['delay'] > 0)
				{
					if ($this->cache['config']['delay'] > 1000000)
					{
						identity_switch_prefs::write_log('Delay execution by "'.$this->cache['config']['delay'].'" seconds', true);
						sleep ($this->cache['config']['delay'] / 1000000);
					}
					else
					{
						identity_switch_prefs::write_log('Delay execution by "'.$this->cache['config']['delay'].'" microseconds', true);
						usleep ($this->cache['config']['delay']);
					}
				}
			}

			// collect data
			$cnt = 0;
			while (count($res) && $cnt++ < $this->cache['config']['retries'])
			{
				foreach ($res as $iid => $obj)
				{
					if ($wrk = $res[$iid]->read())
						self::write_data('0##'.$wrk);
					unset($res[$iid]);
					$cnt  = 0;
				}
				$obj; // Disable Eclipse warning
			}
			if ($cnt >= $this->cache['config']['retries'])
				self::write_data('0##Number of retries exceeded for identity '.$iid.' - stop checking');

			// delete cache data
			@unlink($this->file);
			identity_switch_prefs::write_log('Cache file "'.$this->file.'" deleted', true);

			return;
		} else {

			$rec = $this->cache[$iid];

	   		// must delete storage object, to get SSL status reset
			$rc->storage = null;

			// connect
			$storage = $rc->get_storage();

			if (substr($rec['imap_host'], 4, 1) == ':')
				$rec['imap_enc'] = '';
			else
				$rec['imap_enc'] = $rec['flags'] & identity_switch_prefs::IMAP_SSL ? 'ssl' :
								   ($rec['flags'] & identity_switch_prefs::IMAP_TLS ? 'tls' : '');
			if (!$storage->connect($rec['imap_host'], $rec['imap_user'],
			  					   $rc->decrypt($rec['imap_pwd']), $rec['imap_port'], $rec['imap_enc']))
			{
				self::write_data('0##Identity '.$iid.': Cannot connect to "'.($rec['imap_enc'] ?
								 $rec['imap_enc'].'://' : '').$rec['imap_host'].':'.$rec['imap_port'].
								 '" for user "'.$rec['imap_user'].'" - stop checking');
				return;
			}

			// get list of all subscribed folders
			$storage = $rc->get_storage();
			$folders = [ 'INBOX' ];
			if ($rec['flags'] & identity_switch_prefs::CHECK_ALLFOLDER)
				$folders += $storage->list_folders_subscribed('', '*'. null, null, true);

			// drop exception folders (and their subfolders)
			foreach ($rec['folders'] as $val)
		    	if (($k = array_search($val, $folders)) !== false)
					unset($folders[$k]);

			// count unseen
			$unseen = 0;
			foreach($folders as $mbox)
			{
				unset($storage->conn->data['STATUS:'.$mbox]);
				$unseen += $storage->count($mbox, 'UNSEEN', true, false);
			}

	       	$storage->close();

	       	self::write_data($iid.'##'.$unseen);
			identity_switch_prefs::write_log('Setting unseen count to '.$unseen.' for identity id '.$iid, true);

	       	return;
		}
    }

    /**
     * 	Set language for IMAP connection
     *
     * 	@param array $args
     * 	@return array
     */
    function set_language (array $args): array
    {
    	$args['language'] = $this->cache['config']['language'];

    	return $args;
    }

    /**
     * 	Write record to data file
     *
     * 	@param string $msg
     * 	@return bool
     */
    private function write_data (string $msg): bool
    {
    	if (!$this->fp || fwrite($this->fp, $msg) === false)
    	{
    		if (is_resource($this->fp))
    			fclose($this->fp);

			// open output file
			if (!($this->fp = @fopen($this->cache['config']['data'], 'a')))
			{
				identity_switch_prefs::write_log('Error opening data file "'.$this->cache['config']['data'].'"');
				return false;
			}
			return fwrite($this->fp, time().'##'.$msg.'###') !== false ? true : false;
    	}

    	return true;
    }

}

$obj = new identity_switch_newmails();
$obj->run();
