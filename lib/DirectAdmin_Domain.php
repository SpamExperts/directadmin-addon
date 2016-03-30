<?php

/* * ********************************************************************
 * Customization Services by ModulesGarden.com
 * Copyright (c) ModulesGarden, INBS Group Brand, All Rights Reserved 
 * (2013-12-16, 15:30:14)
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

class DirectAdmin_Domain {

	public $domain;
	public $username;
	public $pointsto;

	public function __construct($domain, $username, $pointsto = null){
		$this->domain = $domain;
		$this->username = $username;
		$this->pointsto = $pointsto;
	}
	
	/**
	 * Checks first MX record
	 * @return bool
	 */
	public function isRemote(){
		$api = new DirectAdmin_API();
		$records = $api->getDomainsMxRecords($this->domain);
		return isset($records[0]['isRemote']) && $records[0]['isRemote'];
	}
	
}