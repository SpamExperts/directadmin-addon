<?php

/* * ********************************************************************
 * Customization Services by ModulesGarden.com
 * Copyright (c) ModulesGarden, INBS Group Brand, All Rights Reserved 
 * (2013-12-23, 13:39:53)
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

class Menu {

	protected $_scope;
	protected $_logo = array(
		'url' => '/CMD_PLUGINS_ADMIN/<PLUGINNAME>',
		'img' => '/CMD_PLUGINS_ADMIN/<PLUGINNAME>/images/logo.jpg'
	);
	protected $_admin = array(
		'configuration'	=> array('title' => 'Configuration','url' => '/CMD_PLUGINS_ADMIN/<PLUGINNAME>/configuration.html'),
		'branding'	    => array('title' => 'Branding',     'url' => '/CMD_PLUGINS_ADMIN/<PLUGINNAME>/branding.html'),
		'domain_list'	=> array('title' => 'Domain List',	'url' => '/CMD_PLUGINS_ADMIN/<PLUGINNAME>'),
		'bulkprotect'	=> array('title' => 'Bulk Protect',	'url' => '/CMD_PLUGINS_ADMIN/<PLUGINNAME>/bulkprotect.html'),
		'update'		=> array('title' => 'Update',		'url' => '/CMD_PLUGINS_ADMIN/<PLUGINNAME>/update.html'),
		'support'		=> array('title' => 'Support',		'url' => '/CMD_PLUGINS_ADMIN/<PLUGINNAME>/support.html'),
	);

	
	public function __construct($scope){
		if (!in_array($scope, array('admin','user','reseller')))
			throw new Exception('No such scope');
		
		$this->_scope = $scope;
	}
	
	public function render($active){

                $str = $this->addLogo();
		$str .= '<div class="modnav">
				<ul class="nav nav-pills">
		';
		$scopeKey = '_' . $this->_scope;
		foreach ($this->$scopeKey as $key => $details){
			$str .= '<li class="'.($key == $active ? 'active' : '').'"><a href="'.$details['url'].'">'.$details['title'].'</a></li>';
		}
		return $str . '
				</ul>
				<div style="clear:both;"></div>
			</div>
		';
	}
        
	public function addLogo(){
		return '<div class="brandLogo">
					<img src="'.$this->_logo['img'].'" />
				</div>';
	}
	
}