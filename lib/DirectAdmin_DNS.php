<?php
/********************************************************************
 *  DNS Manager Module - DirectAdmin Module
 *
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
 * You may not reverse  engineer, decompile, defeat  license  encryption
 * mechanisms, or  disassemble this software product or software product
 * license.  ModulesGarden may terminate this license if you don't
 * comply with any of the terms and conditions.
 *
 * In such event,  licensee  agrees to return
 * licensor  or destroy  all copies of software  upon termination of the
 * license.
 *
 *
 *
 ********************************************************************/
set_time_limit (30);
error_reporting(E_ALL ^ E_NOTICE);

class DirectAdmin_DNS 
{
    private $domain;
    private $ip;
    private $hasTTL = false;
    private $supportRDNS = false;
    private $requireIP = true;
    
    public $error;
    public $results = array();
    private $oconfig = array
    (
        'username'  => '',
        'password'  => '',
        'hostname'  => '',
        'port'      => '',
        'ssl'       => '',
        'defaultIP' => '',
        'ns1'       => '',
        'ns2'       => ''
    );
        
    public $config = array
    (
        'option1' =>array 
        (
            'name'=> 'username', 
            'type'=> 'text',
        ),
        'option2' =>array 
        (
            'name'=> 'password',
            'type'=> 'password',
        ),
        'option3' =>array 
        (
            'name'=> 'hostname',     
            'type'=> 'text',
        ),
        'option4' =>array 
        (
            'name'=> 'ssl',    
            'type'=> 'yesno',
        ),
        'option5' =>array 
        (
            'name'=> 'defaultIP',     
            'type'=> 'text',
        ),
        'option6' =>array 
        (
            'friendlyName'  =>  'Return error when not dot at the end name',
            'name'=> 'end_dot',     
            'type'=> 'yesno',
        ),
        'option7' =>array 
        (
            'friendlyName'  =>  'NS1',
            'name'=> 'ns1',     
            'type'=> 'text',
        ),
        'option8' =>array 
        (
            'friendlyName'  =>  'NS2',
            'name'=> 'ns2',     
            'type'=> 'text',
        ),
    );
       
    /** available records types **/
    private $availableTypes = array('A', 'NS', 'MX', 'CNAME', 'TXT', 'AAAA', 'SRV');
         /**
          * Zwraca false jezeli cos sie zepsulu. JeĹĽeli operacja siÄ™ powiodĹ‚a zwraca "cos" do sparsowania
          */
    
    private function get($function, $params=array()) 
    {
        $username = urlencode($this->oconfig['username']);
        $password = urlencode($this->oconfig['password']);
        $port = $this->oconfig['port'];
        $url = ($this->oconfig['ssl'] == '1' ? 
        'https://'.$username.':'.$password.'@'.$this->oconfig['hostname'].':'.$port :
        'http://'.$username.':'.$password.'@'.$this->oconfig['hostname'].':'.$port).'/'.$function;


        if(is_array($params)) 
        {
            $url .= '?';
            foreach($params as $key=>$value) 
            {
                $value = urlencode($value);
                $key = urlencode($key);
                $url .= "{$key}={$value}&";
            }
        }

        $ch = curl_init();
        $chOptions = array (
            CURLOPT_URL => trim($url, '&'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30
        );
        curl_setopt_array($ch, $chOptions);
        $out = curl_exec($ch); 
       
        if($out === false) 
        {
            $this->error = ucwords(curl_error($ch)); 
            return false;
        }
        
        //parsujemy
        $out = urldecode($out);
        parse_str($out, $parsed);
         
        if(isset($parsed['error']))
        {
            if($parsed['error'] > 0)
            { 
                $this->results[] = array('success' => 0, 'info' => 'Error occured:'.$parsed['text'].' '.$parsed['details']);
                return false;
            }
            else
            {
                $this->results[] = array('success' => 1, 'info' => 'Info:'.$parsed['text']);
                return true;
            }
        }

        return $out;
    }
    
    public function testConnection()
    {   
        $out = $this->get('CMD_API_SHOW_USER_USAGE', array('user' => $this->oconfig['username']));
        if($out === false)
            return array(
                'status' => false,
                'error' => $this->error
            );
        elseif(strpos($out, '<html>') !== false)
           return array(
                'status' => false,
                'error' => 'Incorrect username or password'
            );
        else
            return array(
                'status' => true,
                'error' => ''
            );
    }
    
    public function requireIP(){
        return $this->requireIP;
    }
    
    public function isTTL(){
        return $this->hasTTL;
    }
    
    public function supportRDNS(){
        return $this->supportRDNS;
    }
    
    public function setIP($ip)
    {
        $this->ip = $ip;
    }
    
    public function getDefaultIP(){
        return $this->oconfig['defaultIP'];
    }

    public function __construct($domain) 
    {
        $this->domain = $domain;
    }

    public function setConfig($config) 
    {
        if(isset($config['username'])) $this->oconfig['username'] = $config['username'];
        if(isset($config['password'])) $this->oconfig['password'] = $config['password'];
        if(isset($config['hostname'])) $this->oconfig['hostname'] = $config['hostname'];
        if(isset($config['port'])) $this->oconfig['port'] = $config['port'];
        if(isset($config['ssl'])) $this->oconfig['ssl'] = $config['ssl'];
        if(isset($config['defaultIP'])) $this->oconfig['defaultIP'] = $config['defaultIP'];
        if(isset($config['end_dot'])) $this->oconfig['end_dot'] = $config['end_dot'];
        //nameservers
        if(isset($config['ns1'])) $this->oconfig['ns1'] = $config['ns1'];
        if(isset($config['ns2'])) $this->oconfig['ns2'] = $config['ns2'];
    }

    public function zoneExists() 
    {
        $out = $this->get('CMD_API_DNS_ADMIN', array(
            'domain'    =>  $this->domain
        ));

        if($out == false || strpos($out, '<html>') !== false){
            $this->results[] = strpos($out, 'You cannot execute that command') ? 'You cannot execute that command' : 'Unexpected error'; 
            return false;
        } else {
            return true;
        } 
    }

    public function getRecords($recordType=false) 
    {
        $out = $this->get('CMD_API_DNS_ADMIN', array(
                'domain'    => $this->domain,
                'urlencoded' =>  'yes'
        ));

        if($out == false)
        {
            return false;
        }
        
        /** TRZEBA TO ZMIENIC!!! **/
        $exploded = explode("\n", $out);
        $arr = array();
        foreach($exploded as $ex)
        {
            $line = explode('=', $ex, 2);
            $data = explode('&', $line[1]);
            foreach($data as $d)
            {
                $x = explode('=', $d, 2);
                if(isset($x[0]) && isset($x[1]))
                {
                    $arr[$line[0]][][$x[0]] = $x[1];
                }
            }
        }
        
        $out = array();
        $i = 0;
        foreach($arr as $type => $r)
            foreach($r as $records)
            {
                if($recordType && $type != $recordType)
                {
                    continue;
                }

                foreach($records as $name => $value)
                {
                    if(empty($name))
                    {
                        continue;
                    }
                
                    switch($type)
                    {
                        case 'SRV':
                            $srv = explode(" ", $value);
                            $out[] = array(
                                'line'      =>  $i,
                                'name'      =>  $name,
                                'value'     =>  $srv[3],
                                'type'      =>  $type,
                                'ttl'       =>  '',
                                'priority'  =>  $srv[0],
                                'weight'    =>  $srv[1],
                                'port'      =>  $srv[2]
                            );
                            break;
                        case 'NS':
                            $out[] = array(
                                'line'      =>  $i,
                                'name'      =>  $name,
                                'value'     =>  $value,
                                'type'      =>  $type,
                                'ttl'       =>  '',
                                'priority'  =>  ''
                            );
                            break;    
                        case 'MX':
                            $out[] = array(
                                'line'      =>  $i,
                                'name'      =>  $name,
                                'value'     =>  '',
                                'type'      =>  $type,
                                'ttl'       =>  '',
                                'priority'  =>  $value
                            );
                            break;
                        case 'TXT': 
                            $out[] = array(
                                'line'      =>  $i,
                                'name'      =>  $name,
                                'value'     =>  trim($value,"\""),
                                'type'      =>  $type,
                                'ttl'       =>  '',
                                'priority'  =>  ''
                            );
                            break;
                        default:
                            $out[] = array(
                                'line'      =>  $i,
                                'name'      =>  $name,
                                'value'     =>  $value,
                                'type'      =>  $type,
                                'ttl'       =>  '',
                                'priority'  =>  ''
                            );
                    }
                    $i++;
                }
            }

        return $out;
    }

    public function add($data)
    {
        global $_LANG;
        
        if(empty($data))
        {
            return false;
        }

        foreach($data as $record)
        {
            $out['action'] = 'add';
            $out['domain'] = $this->domain;
            $out['type'] = strtoupper($record['type']);  
            if(filter_var($record['name'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6) === false && $this->oconfig['end_dot'] == 1 && (count(explode('.', $record['name'])) > 1 && substr($record['name'], -1) != '.')){
                $this->results[] = array('success' => 0, 'info' => ''.(isset($_LANG['directadmin_error1']) ? $_LANG['directadmin_error1'] : 'Error occured: Cannot Add Record. Invalid \'name\' field. At the end of the domain name must be a .dot'));
                return false;
            }
            if($out['type'] != 'TXT' && filter_var($record['value'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6) === false && $this->oconfig['end_dot'] == 1 && (count(explode('.', $record['value'])) > 1 && substr($record['value'], -1) != '.')){
                $this->results[] = array('success' => 0, 'info' => ''.(isset($_LANG['directadmin_error2']) ? $_LANG['directadmin_error2'] : 'Error occured: Cannot Add Record. Invalid \'value\' field. At the end of the domain name must be a .dot'));
                return false;
            }
            $out['name'] = $record['name'];
            
            switch($out['type'])
            {
                case 'SRV':
                    $out['value'] = $record['priority'].' '.$record['weight'].' '.$record['port'].' '.$record['value'];
                    break;
                case 'TXT': 
                    $out['value'] = '"'.trim($record['value'], '"').'"';
                    break;
                case 'MX':
                    $out['value'] = $record['priority'];
                    break;
                default:
                  $out['value'] = $record['value'];  
            }

            $this->get('CMD_API_DNS_ADMIN', $out);
        }
    }

    /* DA nie posiada update */
    public function modify($data) 
    {   
        global $_LANG; 
        
        if(empty($data))
        {
            return false;
        }
        //single page or subpages?
        $type = strtoupper($data[0]['type']);
        $single = false;
        foreach($data as $d)
        {
            if(strtoupper($d['type']) != $type)
            {
                $single = true;
                break;
            }
        }
        
        $records = array();
        if($single)
        {
            $records = $this->getRecords();
        }
        else
        {
            $records = $this->getRecords($type);
        }

        $delete = array();
        $add = array(); 
        foreach($data as $key => $record)
        {  
            $record['type'] = strtoupper($record['type']); 
            $diff = false;
            switch($record['type']){
                case 'MX' : if($records[$record['line']]['name'] != $record['name'] || $records[$record['line']]['value'] != $record['value'] || $records[$record['line']]['priority'] != $record['priority']) $diff = true; break;
                case 'SRV': if($records[$record['line']]['name'] != $record['name'] || $records[$record['line']]['value'] != $record['value'] || $records[$record['line']]['priority'] != $record['priority'] || $records[$record['line']]['weight'] != $record['weight'] || $records[$record['line']]['port'] != $record['port']) $diff = true; break;
                default   : if($records[$record['line']]['name'] != $record['name'] || $records[$record['line']]['value'] != $record['value']) $diff = true; break;
            }
            if($diff === true)
            {   
                if(filter_var($record['name'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6) === false && $this->oconfig['end_dot'] == 1 && (count(explode('.', $record['name'])) > 1 && substr($record['name'], -1) != '.')){
                    $this->results[] = array('success' => 0, 'info' => ''.(isset($_LANG['directadmin_error1']) ? $_LANG['directadmin_error1'] : 'Error occured: Cannot Add Record. Invalid \'name\' field. At the end of the domain name must be a .dot'));
                    return false;
                }
                if($record['type'] != 'TXT' && filter_var($record['value'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6) === false && $this->oconfig['end_dot'] == 1 && (count(explode('.', $record['value'])) > 1 && substr($record['value'], -1) != '.')){
                    $this->results[] = array('success' => 0, 'info' => ''.(isset($_LANG['directadmin_error2']) ? $_LANG['directadmin_error2'] : 'Error occured: Cannot Add Record. Invalid \'value\' field. At the end of the domain name must be a .dot'));
                    return false;
                }
                $delete[] = $records[$record['line']];
                $add[] = $record;
            }
           
        }      
        $this->delete($delete);
        $this->add($add);
    }

    public function delete($data)
    { 
        if(empty($data))
        {
            return false;
        }

        foreach($data as $record)
        {
            $record['type'] = strtoupper($record['type']);
            $out['domain'] = $this->domain;
            $out['action'] = 'select';
            switch($record['type'])
            {
                case 'SRV':
                    $records = $this->getRecords('SRV');
                    foreach($records as $r)
                    {
                        if($record['name'] == $r['name'] && $record['value'] == $r['value'])
                        {
                            $out[strtolower($record['type']).'recs0'] = 'name='.$record['name'].'&value='.$r['priority'].' '.$r['weight'].' '.$r['port'].' '.$r['value'];
                            break;
                        }
                    }
                    break;
                case 'MX': 
                    $records = $this->getRecords('MX');
                    foreach($records as $r)
                    {
                        if($record['name'] == $r['name'] && $record['value'] == $r['value'])
                        {
                            $out[strtolower($record['type']).'recs0'] = 'name='.$r['name'].'&value='.$r['priority'];
                            break;
                        }
                    }
                    break;
                case 'NS':
                    $out[strtolower($record['type']).'recs0'] = 'name='.$record['value'].'&value='.$record['name'];
                    break;
                case 'TXT':
                    $out[strtolower($record['type']).'recs0'] = 'name='.$record['name'].'&value="'.$record['value'].'"';
                    break;
                default:
                    $out[strtolower($record['type']).'recs0'] = 'name='.$record['name'].'&value='.$record['value'];
            }
       
            $this->get('CMD_API_DNS_ADMIN', $out); 
        }
   
    }

    
    public function activateZone() 
    {
        if($this->ip != '') {
            if(!filter_var($this->ip, FILTER_VALIDATE_IP)) {
                $this->error = 'IP is not valid!';
                return false;
            }
        } else {
            $this->ip = $this->oconfig['defaultIP'];
        }
        $out = $this->get('CMD_API_DNS_ADMIN', array(
            'action'    =>  'create',
            'domain'    =>  $this->domain,
            'ip'        =>  $this->ip,
            'ns1'       =>  $this->oconfig['ns1'],
            'ns2'       =>  $this->oconfig['ns2']
        ));

        if($out !== true){ 
            $this->getErrorFromOutput($out);  
            return false;
        } else {
            return true;
        }
    }

    public function terminateZone() 
    {
        $out = $this->get('CMD_API_DNS_ADMIN', array(
                'action'    =>  'delete',
                'select0'   =>  $this->domain
                ));
        if($out !== true){ 
            $this->getErrorFromOutput($out);        
            return false;
        } else {
            return true;
        }
    }
    
    public function getAvailableTypes()
    {
        return $this->availableTypes;
    }
    
    private function getErrorFromOutput($out){
        if(strpos($out, 'You cannot execute that command') !== false)
            $this->error = 'You cannot execute that command';
        elseif(strpos($out, 'Please enter your Username and Password') !== false)
            $this->error = 'Incorrect username or password';
        elseif(strpos($out, 'Your IP is blacklisted') !== false)
            $this->error = 'Your IP is blacklisted';
        else
            $this->error = empty($this->error) ? 'Unexpected error' : $this->error;
    }
}
