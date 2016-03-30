<?php

/* * ********************************************************************
 * Customization Services by ModulesGarden.com
 * Copyright (c) ModulesGarden, INBS Group Brand, All Rights Reserved 
 * (2013-12-16, 14:17:40)
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



class Socket {

    private $api_url = '';
    private $api_port = '';
    private $api_login = '';
    private $api_password = '';

    public $fp;

    public function __construct($url, $port = 80, $username = null, $password = null){
        $this->api_url = $url;
        $this->api_port = $port;
        $this->api_login = $username;
        $this->api_password = $password;

        $this->_initFsock();
    }
    
    private function _initFsock() {
        
        $this->fp = fsockopen($this->api_url, $this->api_port, $errno, $errstr, 5);
        if(!$this->fp){
            die("Error: $errstr ($errno)\n");
        }
    }

	/**
	 * Example:
	 * $f->sendRequest("POST", "/login.php", array(
	 *		"Content-Type"	=> "application/json"
	 *		"User-Agent"	=> "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0",
	 *		"Referer"		=> "http://modulesgarden.com",
	 *		// ....
	 * ), array(
	 *		"post1" => "value1"
	 * ));
	 * @param string $method POST or GET
	 * @param string $resource E.g. /login.php
	 * @param array $headers
	 * @param array $data
	 * @return array Keys: http_code', 'res_raw_hearers', 'res_hearers', 'content'
	 */
    public function sendRequest($method, $resource, array $headers = array(), array $data = array()){
        
        fputs($this->fp, "$method $resource HTTP/1.1\r\n");
        fputs($this->fp, "Host: {$this->api_url}\r\n");
		
		if ($this->api_login && $this->api_password)
			fputs($this->fp, "Authorization: Basic ".base64_encode($this->api_login.":".$this->api_password)."\r\n");

        if(!empty($data)){
			$poststring = http_build_query($data);
            fputs($this->fp, "Content-length: ".strlen($poststring)."\r\n");
        }
		foreach ($headers as $name => $value){
            fputs($this->fp, "{$name}: {$value}\r\n");
		}
        
        fputs($this->fp, "Connection: close\r\n");
        fputs($this->fp, "\r\n");
        if(isset($poststring)){
            fputs($this->fp, $poststring . "\r\n\r\n");
        }

        stream_set_timeout($this->fp, 60);

        $output = "";
        $headers= "";
        $headers_arr = array();
        $is_header = 1;
        while(!feof($this->fp)) {
            $buffer = fgets($this->fp, 128);
            if ($buffer == FALSE)
                break;
            if (!$is_header)
				$output .= $buffer;
            if ($buffer == "\r\n")
                $is_header = 0;
            if ($is_header)
				$headers .= $buffer;
        }
        fclose($this->fp);

        $head = explode("\r\n", $headers);
        if(!empty($head)){
            foreach($head as $h) {
                if(strpos($h, ':') !== FALSE)
					$headers_arr[(substr($h, 0, strpos($h,':')))] = trim(substr($h,strpos($h,':')+1));
            }
        }
        
        list($protocol,$http_code,$message) = explode(" ",$head[0]);
        
        $a = array(
			'http_code'		=> $http_code,
			'res_raw_hearers'=> $headers,
			'res_hearers'	=> $headers_arr,
			'content'		=> $output
		);
		var_dump($a);
    }

}
