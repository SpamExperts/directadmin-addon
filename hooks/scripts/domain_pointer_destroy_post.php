<?php

/* * ********************************************************************
 * Customization Services by ModulesGarden.com
 * Copyright (c) ModulesGarden, INBS Group Brand, All Rights Reserved 
 * (2014-01-02, 11:16:59)
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

/**
 * TODO For now the pointer domain should be treated as normal domain in spampanel
 * until we add the option to process the pointer, subdomain as aliases in spampanel
 */

$domain = $argv[1];
if (!$domain)
	die('Error: empty domain');

include_once dirname(__FILE__) . '/../../lib/plugin.php';

$conf = new Configuration();
if ($conf->get('automatically_delete_domains') &&
    $conf->get('process_addon_and_parked_domains')
) {
	
	// auto-adding to spamexperts
	echo 'Deleting domain '.$domain.' from the SpamExperts<br/>';
	
	$hostname = $conf->get('api_hostname');
	$username = $conf->get('api_username');
	$password = $conf->get('api_password');
	
	// no credentials in configuration
	if (!$hostname || !$username || !$password)
		die('Unable to delete pointer domain from the SpamExperts - check credentials on plugin\'s configuration');
	
	$api = new SpamExperts_API($hostname, $username, $password);
	$res = $api->unprotectDomains(array($domain), $conf, new DirectAdmin_API());
	
	if (isset($res[$domain]['result'])){
		
		die( $res[$domain]['result'] ? 'Pointer domain has been deleted from the SpamExperts' : 'Error during deleting pointer domain from the SpamExperts' );
		
	} else {
		die('Error during pointer domain removal in SpamExperts ControlPanel');
	}
	
} else {
	// auto-adding to spamexperts disabled -> no action
}