<?php

/* * ********************************************************************
 * Customization Services by ModulesGarden.com
 * Copyright (c) ModulesGarden, INBS Group Brand, All Rights Reserved 
 * (2013-12-16, 15:02:23)
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

if (!defined('SE_BASE_DIR')){
    // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
    define('SE_BASE_DIR', dirname(dirname(__FILE__)));
}

if (!defined('DS'))
    define('DS', DIRECTORY_SEPARATOR);

function __autoload($class_name) {
    // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
    $path = dirname(__FILE__) . DS . $class_name . '.php';
    // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
    if (!file_exists($path))
        throw new Exception('File '.$path.' does not exists!');
    // phpcs:ignore PHPCS_SecurityAudit
    include_once $path;
}

function pasteAssets(){
    // phpcs:disable PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
    return '
        <script type="text/javascript">'. file_get_contents(dirname(__FILE__).DS.'..'.DS.'assets'.DS.'jquery.js') . '</script>
        <script type="text/javascript">'. file_get_contents(dirname(__FILE__).DS.'..'.DS.'assets'.DS.'module.js') . '</script>
        <style type="text/css">'		. file_get_contents(dirname(__FILE__).DS.'..'.DS.'assets'.DS.'style.css') . '</style>
        <style type="text/css">'		. file_get_contents(dirname(__FILE__).DS.'..'.DS.'assets'.DS.'bootstrap.min.css') . '</style>
                <link href="//fonts.googleapis.com/css?family=Oswald:700,400&subset=latin,latin-ext" rel="stylesheet" type="text/css">
    ';
    // phpcs:enable PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
}

function getParam($key, $default = null){
    $params = getParams();
    return isset($params[$key]) ? $params[$key] : $default;
}

function getParams(){
    parse_str($_SERVER['QUERY_STRING'], $output);
    return $output;
}

function getPOSTValues(){
    parse_str($_SERVER['POST'], $values);
    return $values;
}

function pastePaginationHtml($total, $page, $limit, array $additionalParams = array()){
    $pagesStr = '';
    $urlStr = '?';
    foreach ($additionalParams as $k => $v)
        $urlStr .= $k . '=' . $v . '&';
    $pagesCount = ceil($total / $limit);
    for ($p = 1; $p <= $pagesCount; $p++){
        $pagesStr .= '<a href="'.$urlStr.'page='.$p.'" class="paginationItem '.($page == $p ? 'current' : '').'">'.$p.'</a> ';
    }
    return '
        <div class="paginationContainer">
            <div class="paginationLeft">
                Page '.$page.' of '.($pagesCount == 0 ? 1 : $pagesCount).'; Total '.$total.'
            </div>
            <div class="paginationRight">
                '.($pagesStr ? 'Page: '.$pagesStr : '').'
            </div>
            <div style="clear:both;">
        </div>
    ';
}
