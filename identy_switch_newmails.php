<?php
declare(strict_types=1);

/*
 * 	Identy switch RoundCube Bundle
 *
 *	@copyright	(c) 2024 Forian Daeumling, Germany. All right reserved
 * 	@license 	LGPL-3.0-or-later
 */

// Include environment
if (!defined('INSTALL_PATH'))
	define('INSTALL_PATH', $_SERVER['DOCUMENT_ROOT'].'/');
require_once INSTALL_PATH.'program/include/iniset.php';
require_once INSTALL_PATH.'plugins/identy_switch/identy_switch_rpc.php';
require_once INSTALL_PATH.'plugins/identy_switch/identy_switch_prefs.php';

class identy_switch_newmails extends identy_switch_rpc {

	private $file;
	private $cache;
	private $fp;

    /**
     * 	Run the controller.
     */
    public function run(): void
    {
		$rc = rcmail::get_instance();

		// Get Identity id
		if (is_null($iid = rcube_utils::get_input_value('iid', rcube_utils::INPUT_GET)))
		{
			identy_switch_prefs::write_log('Cannot load identity id - stop checking', true);
			return;
		}

		// Get cache file name
		if (is_null($this->file = rcube_utils::get_input_value('cache', rcube_utils::INPUT_GET)))
		{
			identy_switch_prefs::write_log('Cannot get cache file name - stop checking');
			return;
		} else
			identy_switch_prefs::write_log('Cache file name "'.$this->file.'"', true);

		// Get cached data
		if (!file_exists($this->file))
		{
			identy_switch_prefs::write_log('Cache file "'.$this->file.'" does not exists - stop checking');
			return;
		} else
			identy_switch_prefs::write_log('Cache file loaded', true);

		// Storage initialization hook
		$rc->plugins->register_hook('storage_init', [ $this, 'set_language' ]);

		$this->cache = unserialize(file_get_contents($this->file));

		if (!$iid)
		{
			$res = [];
			foreach ($this->cache as $iid => $rec)
			{
				if (!is_numeric($iid))
					continue;

		        $host = ($_SERVER['SERVER_PORT'] != '80' ? 'ssl://' : '').$_SERVER['HTTP_HOST'].
		        		':'.$_SERVER['SERVER_PORT'];
		        $res[$iid] = new identy_switch_rpc();
				if (!$res[$iid]->open($host))
				{
					self::write_data($iid.'##'.$res[$iid]);
					identy_switch_prefs::write_log('Cannot open host "'.$host.'" - stop checking', true);
					return;
				}

				// Prepare request (no fopen() usage because "allow_url_fopen=FALSE" may be set in PHP.INI)
				$req = '/plugins/identy_switch/identy_switch_newmails.php?iid='.$iid.
					   '&cache='.urlencode($this->file);
				if (!$res[$iid]->write($req))
				{
					fclose($res[$iid]);
					self::write_data('0##Identity: '.$iid.' Cannot write to "'.$host.'" Request: "'.$req.'" - stop checking');
					return;
				}
			}

			// Collect data
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

			// Delete cache data
			@unlink($this->file);
			identy_switch_prefs::write_log('Cache file "'.$this->file.'" deleted', true);

			return;
		} else {

			$rec = $this->cache[$iid];

	   		// Must delete storage object, to get SSL status reset
			$rc->storage = null;

			// Connect
			$storage = $rc->get_storage();

			if (substr($rec['imap_host'], 4, 1) == ':')
				$rec['imap_enc'] = '';
			else
				$rec['imap_enc'] = $rec['flags'] & identy_switch::IMAP_SSL ? 'ssl' :
								   ($rec['flags'] & identy_switch::IMAP_TLS ? 'tls' : '');
			if (!$storage->connect($rec['imap_host'], $rec['imap_user'],
			  					   $rc->decrypt($rec['imap_pwd']), $rec['imap_port'], $rec['imap_enc']))
			{
				self::write_data('0##Identity '.$iid.': Cannot connect to "'.($rec['imap_enc'] ?
								 $rec['imap_enc'].'://' : '').$rec['imap_host'].':'.$rec['imap_port'].
								 '" for user "'.$rec['imap_user'].'" - stop checking');
				return;
			}

			// Get list of all subscribed folders
			$storage = $rc->get_storage();
			$folders = [ 'INBOX' ];
			if ($rec['flags'] & identy_switch::CHECK_ALLFOLDER)
				$folders += $storage->list_folders_subscribed('', '*'. null, null, true);

			// Drop exception folders (and their subfolders)
			if (is_array($folders))
			{
				foreach ($rec['folders'] as $val)
			    	if (($k = array_search($val, $folders)) !== false)
						unset($folders[$k]);
			}

			// Count unseen
			$unseen = 0;
			foreach($folders as $mbox)
			{
				unset($storage->conn->data['STATUS:'.$mbox]);
				$unseen += $storage->count($mbox, 'UNSEEN', true, false);
			}

	       	$storage->close();

	       	self::write_data($iid.'##'.$unseen);
			identy_switch_prefs::write_log('Setting unseen '.$unseen.' for identity id '.$iid, true);

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

			// Open output file
			if (!($this->fp = @fopen($this->cache['config']['data'], 'a')))
			{
				identy_switch_prefs::write_log('Error opening data file "'.$this->cache['config']['data'].'"');
				return false;
			}
			return fwrite($this->fp, time().'##'.$msg.'###') !== false ? true : false;
    	}

    	return true;
    }

}

$obj = new identy_switch_newmails();
$obj->run();
