<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Blinovav\Extensionrest\ExtensionRestController; 

$segments = getSegmentsFromUrl();

if (isset($segments[0])) {
    $controllerCode =  $segments[0];
    $controllerMethod = $segments[1];
    \Bitrix\Main\Loader::includeModule("blinovav.extensionrest");
    
    switch ($controllerCode) {
        case 'extension':
            $controller = new ExtensionRestController($controllerMethod);
            break; 
        default:
            die('Undefined controller');
            break;
    }
} else { 
    die('Undefined controller');
}

$result = $controller->actionIndex();

$controller->print($result, true, true);


function getSegmentsFromUrl(){
    if (!isset($_REQUEST['url'])) {
        return [];
    }
    $segments = explode('/', $_GET['url']);

    if (empty($segments[count($segments)-1]))
    {
        unset($segments[count($segments)-1]);
    }

    $segments = array_map(function($v) {
        return preg_replace('/[\'\\\*\?]/','',$v);
    }, $segments);

    return $segments;
}