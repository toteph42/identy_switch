<?php
declare(strict_types=1);

/*
 * 	Identity switch RoundCube Bundle
 *
 *	@copyright	(c) 2024 Forian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/identity_switch/blob/master/LICENSE
 */

class identity_switch_rpc  {

	private $host	= null;
    private $req	= null;
    private $fp		= null;

    /**
     * 	Open asynchronous communication channel
     *
     * 	@param string $host
     * 	@return string|resource
     */
	function open(string $host): mixed
	{
	    $errno = $errmsg = null;

	    $ctx = stream_context_create([
					  	  'ssl' => [
					  			'verify_peer'		=> false,
								'verify_peer_name' 	=> false,
								'allow_self_signed'	=> false,
					 	  ],
		]);

		// open async connection
		if (!($this->fp = stream_socket_client($host, $errno, $errmsg, 30,STREAM_CLIENT_ASYNC_CONNECT, $ctx)))
			return 'Cannot connect to "'.$host.'" - ['.$errno.'] '.$errmsg;

		// set timeout
		stream_set_timeout($this->fp, 30);

		// save host name
		if ($p = strpos($host, '://'))
			$host = substr($host, 3 + $p);
		if ($p = strpos($host, ':'))
			$host = substr($host, 0, $p);
		$this->host = $host;

		return $this->fp;
	}

	/**
	 * 	Write to asynchroneous communication channel
	 *
	 * 	@param string $req
	 * 	@return bool
	 */
	function write(string $req): bool
	{

		$this->req = $req;

		// finalize request
		$req = 'GET '.$req." HTTP/1.0\r\nHost: ".$this->host."\r\n\r\n";

	    return (bool)fwrite($this->fp, $req);
	}

	/**
	 * 	Read from asynchroneous communication channel
	 *
	 * 	@return string
	 */
	function read(): string
	{
		if (!($wrk = fread($this->fp, 8192)))
			return 'Errror reading from "'.$this->host.'" Request: "'.$this->req.'"';

		$head = explode("\r\n", substr($wrk, 0, $pos = strpos($wrk, "\r\n\r\n")));
	    $wrk  = substr($wrk, $pos + 4);

		// we use this approach to get "HTTP/1.0 200 OK" as well as "HTTP/1.1 200 OK"
	    if (count($head) && strpos($head[0], '200 OK') === false)
			return '"'.$head[0].'" for "'.$this->host.'" Request: "'.$this->req.'"';
		else {
	    	if (is_string($wrk) && strlen($wrk) > 1)
	        	return $wrk;
		}

		return $wrk;
	}

}
