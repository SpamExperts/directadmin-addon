<?php

/* * ********************************************************************
 * Customization Services by ModulesGarden.com
 * Copyright (c) ModulesGarden, INBS Group Brand, All Rights Reserved 
 * (2013-12-16, 14:23:03)
 * 
 *
 *  CREATED BY MODULESGARDEN       ->        http://modulesgarden.com
 *  CONTACT                        ->       contact@modulesgarden.com
 *
 *
 *
 *
 * This software is furnished under a license and may be used and copied
 * only  in  accordance  with  the  terms  of such  license and with the
 * inclusion of the above copyright notice.  This software  or any other
 * copies thereof may not be provided or otherwise made available to any
 * other person.  No title to and  ownership of the  software is  hereby
 * transferred.
 *
 *
 * ******************************************************************** */

/**
 * @author Grzegorz Draganik <grzegorz@modulesgarden.com>
 */

class DirectAdmin_API {

	public static $total = 0;
	
	protected $_host;
	protected $_login;
	protected $_pass;
	protected $_secure;
	protected $_port;

	public function __construct($host = null, $login = null, $pass = null, $secure = true, $port = 2222){
		$this->_host	= $host;
		$this->_login	= $login;
		$this->_pass	= $pass;
		$this->_secure	= $secure;
		$this->_port	= $port;
		
		if (empty($host))
			$this->_setupLocalConnection();
	}
	
	public function getDomainsMxRecords($domain, $username = null){
		$sock = $this->_getSocket();
		$sock->query('/CMD_API_DNS_ADMIN?domain='.$domain.'&urlencoded=yes');
		$result = $sock->fetch_body();
		
		if (!$result){
			throw new Exception('Unable to get DNS zone of domain ' . $domain);
		}

		$isRemote = FALSE;
		if (strlen($username) != 0) {
			$sock->query('/CMD_API_DNS_MX?domain='.$domain.'&as_user='.$username.'&urlencoded=yes');
			$mx = $sock->fetch_body();

			$lines = explode("\n", $mx);
			foreach ($lines as $line) {
				$setting = strtok($line, '=');
				if ($setting != 'internal')
					continue;

				$value = substr($line, strlen($setting)+1);
				if ($value == 'no') {
					$isRemote = TRUE;
				}
			}
		}
		
		$records = array();
		$lines = explode("\n", $result);
		foreach ($lines as $line){
			$recordType = strtok($line, '=');
			if ($recordType != 'MX')
				continue;
			
			parse_str(substr($line, strlen($recordType)+1), $details);
			foreach ($details as $record => $v){
				$fixedRecord = str_replace('_', '.', $record);

				// If the checkbox at CMD_API_DNS_MX isn't checked make sure we run an additional test
				if ($isRemote == FALSE) {
					if (substr($fixedRecord, -1) == '.' && strpos($fixedRecord, $domain) === false) {
						$isRemote = TRUE;
					}
				}

				$records[] = array(
					'original'	=> $fixedRecord,
					'full'		=> substr($fixedRecord, -1) == '.' ? substr($fixedRecord, 0, -1) : $fixedRecord . '.' . $domain,
					'isRemote'	=> $isRemote,
				);
			}
		}
		return $records;
	}
	
	public function getAllDomains(array $filters = array()){
		$sock = $this->_getSocket();
		$sock->query('/CMD_API_ALL_USER_USAGE');
		$result = $sock->fetch_body();
		
		if (!$result){
			return array();
		}
		
		$startIndex = 0;
		$endIndex = 10000;
		$searchReseller = null;
		$searchUsername = null;
		$searchDomain = null;
		$includePointers = true;
		
		if (isset($filters['page']) && isset($filters['limit']) && $filters['page'] && $filters['limit']){
			$startIndex = ($filters['page']-1) * $filters['limit'];
			$endIndex = $startIndex + $filters['limit'];
		}
		if (isset($filters['searchUsername']) && $filters['searchUsername'])
			$searchUsername = $filters['searchUsername'];
		if (isset($filters['searchReseller']) && $filters['searchReseller'])
			$searchReseller = $filters['searchReseller'];
		if (isset($filters['searchDomain']) && $filters['searchDomain'])
			$searchDomain = $filters['searchDomain'];
		if (isset($filters['includePointers']))
			$includePointers = !!$filters['includePointers'];

		self::$total = 0;
		$domains = array();
		$lines = explode("\n", $result);
		foreach ($lines as $line){
			$username       = strtok($line, '=');
			parse_str($line, $details);
			if (isset($details['list']) && $details['list']){
				$ds = explode('<br>', trim($details['list']));
				foreach ($ds as $dom){
					if (!$dom)
						continue;
					if ($searchDomain && strpos($dom, $searchDomain) === false)
						continue;
					if ($searchUsername && $username != $searchUsername)
						continue;
					if ($searchReseller && $details['creator'] != $searchReseller)
						continue;
					
					self::$total++;

					$domains[$dom] = array(
						'username' => $username,
					);
				}
			}

            // we should not process the pointer domains if that is the case
            if ($includePointers) {
                foreach ($details as $detailkey => $detail){
                    if (substr($detailkey, 0, 8) == 'pointer_'){
                        $pointers = explode('|', $detail);
                        foreach ($pointers as $pointer){
                            if ($pointer){
                                if ($searchDomain && strpos($pointer, $searchDomain) === false)
                                    continue;
                                if ($searchUsername && strpos($username, $searchUsername) === false)
                                    continue;
                                if ($searchReseller && strpos($details['creator'], $searchReseller) === false)
                                    continue;

                                self::$total++;
                                $domains[$pointer] = array(
                                    'username' => $username,
                                    'pointsto' => str_replace('_', '.', substr($detailkey, 8))
                                );
                            }
                        }
                    }
                }
            }

		}
		
		ksort($domains);
		
		$di = 0;
		$return = array();
		foreach ($domains as $domain => $details){
			if ($di >= $startIndex && $di <= $endIndex){
				$return[$domain] = new DirectAdmin_Domain(
					$domain,
					$details['username'],
					isset($details['pointsto']) ? $details['pointsto'] : null
				);
			}
			$di++;
		}
		
		return $return;
	}
	
	public function getSystemInfo(){
		$sock = $this->_getSocket();
		$sock->query('/CMD_API_SYSTEM_INFO');
		$result = $sock->fetch_body();
		
		if (!$result || strpos($result, '<form')){
			throw new Exception('Unable to get system info');
		}
		
		parse_str(substr($result, 2), $details);
		return $details;
	}
	
	public function getDns($domain){
		$dns = new DirectAdmin_DNS($domain);
		$dns->setConfig(array(
			'username'	=> $this->_login,
			'password'	=> $this->_pass,
			'hostname'	=> $this->_host,
			'ssl'		=> $this->_secure ? '1' : '0',
			'end_dot'	=> 1,
		));
		return $dns;
	}
	
	public function isUsersDomain($domain, $reseller = false){
		$domains = $this->getAllDomains(array(
			'page'			=> 1,
			'limit'			=> 999999,
			'searchUsername'=> $reseller ? null : getenv('USERNAME'),
			'searchReseller'=> $reseller ? getenv('USERNAME') : null,
			'searchDomain'	=> $domain,
		));
		foreach ($domains as $d){
			if ($d->domain == $domain)
				return true;
		}
		return false;
	}
	
	protected function _setupLocalConnection(){
		// THAT COMMENTED OUT WILL NOT WORK IN HOOKS -> all connection details are in directadminapi.conf

		//		if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443)
		//			$this->_secure = true;
		//		else
		//			$this->_secure = false;
		//		
		//		$this->_host	= $_SERVER['SERVER_NAME'];
		//		$this->_port	= $_SERVER['SERVER_PORT'];
		
		$conf = new Configuration('directadminapi.conf');
		
		$this->_login	= $conf->get('username');
		$this->_pass	= $conf->get('password');
		$this->_secure	= (bool)$conf->get('secure');
		$this->_host	= $conf->get('host');
		$this->_port	= $conf->get('port');
	}
	
	protected function _getSocket(){
		$sock = new DirectAdmin_HTTPSocket;
		$sock->connect( ($this->_secure ? "ssl://" : '') . $this->_host, $this->_port);
		$sock->set_login($this->_login, $this->_pass);
		
		return $sock;
	}
	
}