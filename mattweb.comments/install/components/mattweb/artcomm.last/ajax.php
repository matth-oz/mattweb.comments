<?
/** @global CMain $APPLICATION */
define('STOP_STATISTICS', true);
define('PUBLIC_AJAX_MODE', true);
define('NOT_CHECK_PERMISSIONS', true);

use \Bitrix\Main,
    \Bitrix\Main\Context,
    \Bitrix\Main\Application,
    \Bitrix\Main\Loader,
    \Bitrix\Main\Localization\Loc;

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

Loc::loadMessages(__FILE__);

if(isset($_POST['AJAX']) && $_POST['AJAX'] == 'Y'){

    /** 
     * write your code here
     * 
    */
    
    header('Content-Type: application/json; charset='.LANG_CHARSET);
    echo Main\Web\Json::encode($arResult);
    die();
}