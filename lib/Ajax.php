<?php

/* * ********************************************************************
 * Customization Services by ModulesGarden.com
 * Copyright (c) ModulesGarden, INBS Group Brand, All Rights Reserved 
 * (2013-12-18, 09:41:39)
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

class Ajax {

    protected $_params;

    public function processRequest($params){
        $this->_params = $params;
        $method = 'call_' . $params['action'];
        call_user_func_array(array($this, $method), array());
        $this->sendResponse(false, 'Action does not respond the standard way.');
    }

    public function __call($name, $arguments) {
        $this->sendResponse(false, 'Action "'.$name.'" is not supported');
    }

    protected function sendResponse($success, $msg, array $params = array()) {
        $res = array(
            'success'	=> $success ? 1 : 0,
            'msg'		=> $msg
        );
        $json = json_encode(array_merge($res, $params));
        echo '[AJAX_RESPONSE]' . $json . '[/AJAX_RESPONSE]';
        die();
    }

}
