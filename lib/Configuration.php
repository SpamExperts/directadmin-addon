<?php

/* * ********************************************************************
 * Customization Services by ModulesGarden.com
 * Copyright (c) ModulesGarden, INBS Group Brand, All Rights Reserved 
 * (2013-12-18, 12:25:06)
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

if (!defined('PLUGIN_NAME')) {
    define('PLUGIN_NAME', basename(realpath(dirname(__FILE__) . '/../')));
}

class Configuration
{
	protected $_filename;
	protected $_values;

	public function __construct($filename = null)
    {
        $this->_filename = PLUGIN_NAME . DS . 'configuration.conf';
		if ($filename) {
            $this->_filename = PLUGIN_NAME . DS . $filename;
        }
		
		$this->_load();
	}
	
	protected function _load()
    {
		$path = dirname(__FILE__) . DS . '..' . DS . '..' . DS . $this->_filename;
		if (!file_exists($path))
			throw new Exception('Configuration file '.$this->_filename.' does not exists', 404);
		
		$file = str_replace('.conf', '', $this->_filename);
		exec($this->_getConfigScriptPath() . ' --config '.$file, $output, $return);
		
		$this->_values = parse_ini_string(implode("\r\n", $output));
	}
	
	public function get($key, $default = null)
    {
		return isset($this->_values[$key]) ? stripslashes($this->_values[$key]) : $default;
	}
	
	public function set($key, $value)
    {
		$this->_values[$key] = $value;
	}
	
	public function save()
    {
		$str = '';
		foreach ($this->_values as $k => $v) {
			$str .= addslashes($k.'="'.addslashes($v).'"') . "\n";
		}
		
		$file = str_replace('.conf', '', $this->_filename);
		exec($this->_getConfigScriptPath() . ' --save '.$file.' "'.$str.'" 2>&1', $output, $return);
	}
	
	private function _getConfigScriptPath()
    {
		return realpath(dirname(__FILE__) . '/../') . '/scripts/getconfig';
	}
	
}
