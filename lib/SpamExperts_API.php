<?php

/* * ********************************************************************
 * Customization Services by ModulesGarden.com
 * Copyright (c) ModulesGarden, INBS Group Brand, All Rights Reserved 
 * (2013-12-16, 15:46:02)
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

class SpamExperts_API {

	protected $_host;
	protected $_login;
	protected $_pass;

	/**
	 * {"messages":{"error":["Domain already exists.","Failed to add domain 'gmail.com'"]},"result":null}
	 * @param string $hostname
	 * @param string $username
	 * @param string $password
	 */
	public function __construct($hostname, $username, $password){
		$this->_host = $hostname;
		$this->_login = $username;
		$this->_pass = $password;
	}

	/**
	 * {"messages":[],"result":{"present":0}}
	 * @param string $domain
	 * @throws Exception
	 */
	public function getDomainStatus($domain){
		$sock = $this->_getSocket();
		
		$sock->query('/api/domain/exists/domain/'.$domain.'/format/json');
		$result = $sock->fetch_body();
		$json = json_decode($result, true);
		
		if (!$result || !$json)
			throw new Exception('Unable to get domain status.', 500);
		
		// the “present” field value can be 1 or 0 indicating the domain presents or is missing in the spamfilter.
		if (isset($json['result']['present'])){
			return (bool)$json['result']['present'];
		}
		throw new Exception( isset($json['messages']['error']) ? implode('. ', $json['messages']['error']) : 'Unknown error.' );
	}
	
	/**
	 * 
	 * @param array $domains
	 * @return array
	 */
	public function toggleDomains(array $domains){
		$conf = new Configuration();
		$daApi = new DirectAdmin_API();
		
		$toProtect = array();
		$toUnprotect = array();
		foreach ($domains as $domain){
			try {
				$status = $this->getDomainStatus($domain);
			} catch (Exception $e){
				$status = false;
			}
			
			if (!$status){ // protect
				$toProtect[] = $domain;
			} else { // unprotect
				$toUnprotect[] = $domain;
			}
		}
		
		$results = $this->protectDomains($toProtect, $conf, $daApi);
		$results = array_merge($results, $this->unprotectDomains($toUnprotect, $conf, $daApi) );
		
		return $results;
	}
	
	/**
	 * 
	 * @param string $domain
	 * @return bool
	 * @throws Exception
	 */
	public function login($domain){
		$sock = $this->_getSocket();
		
		$sock->query('/api/authticket/create/username/'.$domain.'/format/json/');
		$result = $sock->fetch_body();
		$json = json_decode($result, true);
		
		if (!$result || !$json)
			throw new Exception('Unable to get domain login token.', 500);
		
		// If the “result” field is not empty further redirect user to: {Spampanel URL}/?authticket=96902e3989150ebedffcb6a9f7c01a1070aa4d8b
		// (in a new browser window/tab preferrably).
		// {"messages":[],"result":"96902e3989150ebedffcb6a9f7c01a1070aa4d8b"}
		if (isset($json['result']) && $json['result'])
			return $json['result'];
		return null;
	}
	
	/**
	 * 
	 * @param array $domains
	 * @param Configuration $conf main configuration object
	 * @param DirectAdmin_API $daApi
	 * @return array Results for each domain
	 */
	public function protectDomains(array $domains, Configuration $conf, DirectAdmin_API $daApi){
		$results = array();
		$mxes = '';
		
		// each domain in the spamfilter has at least one destination route a hostname where all the
		// clean email should be delivered and it is reasonable to use actual MX records on a
		// domain’s MX records switching as a destination for the clean email.
		if (!$conf->get('use_existing_mx_as_routes')){
			$defaultMXes = array();
			if ($conf->get('primary_mx'))
				$defaultMXes[] = $conf->get('primary_mx');
			if ($conf->get('secondary_mx'))
				$defaultMXes[] = $conf->get('secondary_mx');
			if ($conf->get('tertiary_mx'))
				$defaultMXes[] = $conf->get('tertiary_mx');
			$mxes = implode('","', $defaultMXes);
		}
		
		// load domains if checking is required
		if (!$conf->get('process_addon_and_parked_domains') || $conf->get('do_not_protect_remote_domains')){
			$allDomains = $daApi->getAllDomains();
		}
		
		foreach ($domains as $domain){
			if (!$conf->get('process_addon_and_parked_domains')){
				if (isset($allDomains[$domain]) && $allDomains[$domain]->pointsto){
					$results[$domain] = array('action' => 'protect', 'result' => false, 'msg' => 'Pointers domains are disabled for adding to the Spam Filter');
					continue;
				}
			}
			if ($conf->get('do_not_protect_remote_domains')){
				if (isset($allDomains[$domain]) && $allDomains[$domain]->isRemote()){
					$results[$domain] = array('action' => 'protect', 'result' => false, 'msg' => 'Remote domains are disabled for adding to the Spam Filter');
					continue;
				}
			}
			
			if ($conf->get('use_existing_mx_as_routes')){
				try {
					$records = array();
					$mxrecords = $daApi->getDomainsMxRecords($domain);
					foreach ($mxrecords as $rec)
						$records[] = $rec['full'];
					$mxes = implode('","', $records);
				} catch (Exception $e){}
			}
			$sock = $this->_getSocket();
			$sock->query('/api/domain/add/domain/'.$domain.'/destinations/["'.$mxes.'"]/format/json/');
			$result = $sock->fetch_body();
			$json = json_decode($result, true);

			if (!$result || !$json){
				$results[$domain] = array('action' => 'protect', 'result' => false);
				continue;
			}

			// in case the result doesn’t contain errors it is safe to assume that the domain has been added (protected) successfully.
			if (!isset($json['messages']['error']) || empty($json['messages']['error'])){
				$results[$domain] = array('action' => 'protect', 'result' => true);

				// After a domain is successfully protected and the “Automatically change the MX records for domains” option is active
				// it is required to update it’s MX records with the “Primary MX”, “Secondary MX” and “Tertiary MX” fields’ values
				if ($conf->get('automatically_change_mx')){
					$newMX = array();
					$defaultMX = array('primary_mx' => '10', 'secondary_mx' => '20', 'tertiary_mx' => '30');
					foreach (array('primary_mx','secondary_mx','tertiary_mx') as $mx_key){
						if ($conf->get($mx_key)) {

							$MXPriority = $conf->get($mx_key . '_priority');
							$value = empty($MXPriority) ? $defaultMX[$mx_key] : $MXPriority;

							$newMX[] = array(
								'type' => 'MX',
								'name' => $conf->get($mx_key) . '.',
								'value' => $value,
								'priority' => $value,
							);
						}
					}
					
					if (!empty($newMX)){
						$dns = $daApi->getDns($domain);
						$currentMX = $dns->getRecords('MX');
						if ($currentMX)
							$dns->delete($currentMX);
						$dns->modify($newMX);
					}
				}

			} else {
				$results[$domain] = array('action' => 'protect', 'result' => false, 'msg' => implode('<br/>', $json['messages']['error']));
			}
		}
		return $results;
	}
	
	public function unprotectDomains(array $domains, Configuration $conf, DirectAdmin_API $daApi){
		$results = array();

		foreach ($domains as $domain){
            $routes = array();
			$sock = $this->_getSocket();

            if ($conf->get('automatically_change_mx')) {
                $sock->query('/api/domain/getroute/domain/' . $domain . '/format/json/');
                $result = $sock->fetch_body();
                $json = json_decode($result, true);
                $routes = $json['result'];
            }

			$sock->query('/api/domain/remove/domain/'.$domain.'/format/json/');
			$result = $sock->fetch_body();
			$json = json_decode($result, true);

			if (!$result || !$json){
				$results[$domain] = array('action' => 'unprotect', 'result' => false);
				continue;
			}

			// in case the result doesn’t contain errors it is safe to assume that the domain has been removed (unprotected) successfully.
			if (!isset($json['messages']['error']) || empty($json['messages']['error'])) {
                if ($conf->get('automatically_change_mx')) {
                    $newMX = array();
                    foreach ($routes as $key => $route) {
                        $route = $this->removePort($route);
                        $route = $this->reverseDNS($route);

                        if ($route) {
                            $newMX[] = array(
                                'type' => 'MX',
                                'name' => $route . ".",
                                'value' => '',
                                'priority' => (1 + $key) * 10,
                            );
                        }
                    }

                    $dns = $daApi->getDns($domain);
                    $currentMX = $dns->getRecords('MX');

                    if ($currentMX) {
                        $dns->delete($currentMX);
                    }
                    $dns->modify($newMX);
                }

                //last chance: check if we have some mx, if not use hostname
                $currentMX = $dns->getRecords('MX');

                if (!$currentMX) {
                    $newMX[] = array(
                        'type' => 'MX',
                        'name' => gethostname().".",
                        'value' => '',
                        'priority' => (1 + $key) * 10,
                    );

                    $dns->modify($newMX);
                }

				$results[$domain] = array('action' => 'unprotect', 'result' => true);
			} else {
				$results[$domain] = array('action' => 'unprotect', 'result' => false);
			}
		}
		return $results;
	}
	
	protected function _getSocket(){
		$sock = new DirectAdmin_HTTPSocket();
		$sock->connect($this->_host, 80);
		$sock->set_login($this->_login, $this->_pass);
		
		$sock->setDebugMode(true);
		$sock->setLogPath(dirname(__FILE__) . '/../prospamfilter.log');

		return $sock;
	}

    /**
     * Try to get hostname from ip
     *
     * @param $record
     * @return mixed
     */

    private static function reverseDNS($record) {
        if(filter_var($record, FILTER_VALIDATE_IP) == $record){
            $hostname = gethostbyaddr($record);
            if ($hostname && $hostname != $record) {
                return $hostname;
            }

            return false;
        }

        return $record;
    }

    /**
     * Removes port from gathered route
     *
     * @param type $route
     * @return sanitized route
     */

    private static function removePort($route){
        $x = explode('::', $route);
        if (count($x)>1) {
            array_pop($x);
        }

        return implode("::", $x);
    }
	
}