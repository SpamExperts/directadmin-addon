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

$domain = $argv[1];
if (!$domain)
    die('Error: empty domain');

// phpcs:ignore PHPCS_SecurityAudit.BadFunctions
include_once dirname(__FILE__) . '/../../lib/plugin.php';

$conf = new Configuration();
if ($conf->get('automatically_delete_domains')){

    // auto-adding to spamexperts
    // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.EasyXSS.EasyXSSwarn
    echo 'Deleting domain '.$domain.' from the SpamExperts<br/>';

    $hostname = $conf->get('api_hostname');
    $username = $conf->get('api_username');
    $password = $conf->get('api_password');

    // no credentials in configuration
    if (!$hostname || !$username || !$password)
        die('Unable to delete domain from the SpamExperts - check credentials on plugin\'s configuration');

    $api = new SpamExperts_API($hostname, $username, $password);

    // we should remove domain pointers when a domain is removed from DA
    $daApi = new DirectAdmin_API;

    $allDomains = $daApi->getAllDomains(array(
            'searchDomain' => $domain,
            'includePointers' => true,
    ));

    $domains = array();
    foreach ($allDomains as $addon => $props) {
        if ($props->pointsto == $domain) {
            $domains[] = $addon;
        }
    }

    $domains[] = $domain;

    $res = $api->unprotectDomains($domains, $conf, $daApi);

    if (isset($res[$domain]['result'])){
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.EasyXSS.EasyXSSwarn
        die( $res[$domain]['result'] ? 'Domain has been deleted from the SpamExperts' : 'Error during deleting domain from the SpamExperts' );

    } else {
        die('Error during adding domain to the SpamExperts');
    }

} else {
    // auto-adding to spamexperts disabled -> no action
}
