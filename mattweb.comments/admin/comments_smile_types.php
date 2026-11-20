<?php
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */
use Bitrix\Main,
Bitrix\Main\Application,
Bitrix\Main\Loader,
Bitrix\Main\Localization\Loc,
Bitrix\Main\Config\Option,
Mattweb\Comments;


require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");

// $sourceIblockId = (int) Option::get("mattweb.comments", 'source_iblock', '');

if (!$USER->IsAdmin())
{
    $APPLICATION->AuthForm(Loc::GetMessage("ACCESS_DENIED"));
}

$app = Application::getInstance();
$context = $app->getContext();
$session = $app->getSession();

Loader::includeModule("mattweb.comments");

$APPLICATION->SetTitle(Loc::GetMessage('COMMENTS_SMILE_TYPES_TITLE'));

$sTableID = "tbl_smile_types_entity";
// поля в SmileTypeTable::getMap() названы в нижнем регистре (id, name, code, published),
// поэтому и колонки, и сортировка, и f_-переменные здесь тоже в нижнем регистре
$oSort = new CAdminSorting($sTableID, "id", "asc");
$lAdmin = new CAdminList($sTableID, $oSort);

$arHeaders = [
    ['id'=>'id', "content"=>"ID", "sort"=>"id", "default"=>true],
    ['id'=>'name', "content"=>Loc::GetMessage('SMILE_TYPE_ENTITY_NAME_FIELD'), "sort"=>"name", "default"=>true],
    ['id'=>'code', "content"=>Loc::GetMessage('SMILE_TYPE_ENTITY_CODE_FIELD'), "sort"=>"code", "default"=>true],
    ['id'=>'published', "content"=>Loc::GetMessage('SMILE_TYPE_ENTITY_PUBLISHED_FIELD'), "sort"=>"published", "default"=>true],
];

$by = mb_strtolower($oSort->getField());
$order = mb_strtoupper($oSort->getOrder());
$getListOrder = [
    $by => $order,
];

if ($by !== 'id')
{
    $getListOrder['id'] = 'ASC';
}

$rsDataSmt = Comments\SmileTypeTable::getList([
    //'select' => $lAdmin->GetVisibleHeaderColumns(),
    'select' => ['id', 'name', 'code', 'published'],
    "order" => $getListOrder,
    // "filter" => $arFilter,
]);

$lAdmin->AddHeaders($arHeaders);

$rsData = new CAdminResult($rsDataSmt, $sTableID);
$rsData->NavStart();

// build list
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("PAGES")));

while($arRes = $rsData->NavNext(true, "f_"))
{
   // dump($arRes);
    $row = &$lAdmin->AddRow($f_id, $arRes);
    $row->AddViewField("published", in_array($f_published, $arRes) ? GetMessage("SMILE_TYPES_YES_NO_YES") : GetMessage("SMILE_TYPES_YES_NO_NO"));
    
    $can_edit = true;
    $arActions = [];

    $arActions[] = [
        "ICON"=>"edit",
        "TEXT"=>GetMessage($can_edit ? "MAIN_ADMIN_MENU_EDIT" : "MAIN_ADMIN_MENU_VIEW"),
        "ACTION"=>$lAdmin->ActionRedirect("comments_smile_type_edit.php?ID=".$f_id)
    ];

    $arActions[] = [
        "ICON"=>"delete",
        "TEXT" => GetMessage("MAIN_ADMIN_MENU_DELETE"),
        "ACTION" => "if(confirm('".GetMessageJS('SMILE_TYPES_ADMIN_DELETE_COMMENT_CONFIRM')."')) ".
                $lAdmin->ActionRedirect("comments_smile_type_edit.php?action=delete&ID=".$f_id.'&'.bitrix_sessid_get())
    ];

    if($f_published == "0"){
            $arActions[] = [
                    "ICON"=>"publish",
                    "TEXT" => GetMessage("MAIN_ADMIN_MENU_PUBLISH"),
                    "ACTION" => $lAdmin->ActionRedirect("comments_smile_type_edit.php?action=publish&ID=".$f_id.'&'.bitrix_sessid_get())
            ];
    }
    else{
            $actionStr = $lAdmin->ActionRedirect("comments_smile_type_edit.php?action=unpublish&ID=".$f_id.'&'.bitrix_sessid_get());
            $actionStr = "if(confirm('".GetMessageJS('SMILE_TYPES_ADMIN_UNPUBLISH_CHAIN_COMMENT_CONFIRM')."')) ".$actionStr;
            
            $arActions[] = [
                    "ICON" => "unpublish",
                    "TEXT" => GetMessage("MAIN_ADMIN_MENU_UNPUBLISH"),
                    "ACTION" => $actionStr,
            ];            
    }

    $row->AddActions($arActions);
}

if ($lAdmin->isListMode())
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
}
else
{
    // обычный режим: печатаем шапку админки, контекстное меню и фильтр
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

    // menu
    $aMenu = [];
    $aMenu[] = [
            "TEXT" => GetMessage('SMILE_TYPE_ADD_TITLE'),
            "TITLE" => GetMessage('SMILE_TYPE_ADD_TITLE'),
            "LINK" => "comments_smile_type_edit.php?lang=" . LANGUAGE_ID,
            "ICON" => "btn_new",
    ];

    $adminContextMenu = new CAdminContextMenu($aMenu);
    $adminContextMenu->Show();        
}

$lAdmin->CheckListMode();

$actionRes = $session['action_res'];

if(!empty($actionRes)){
   $ID = $session['ID'];
        
   $resultMessage = match($actionRes){
        'published' => Loc::getMessage('ACTION_RES_PUBLISHED_MESS', ['#SMILE_TYPE_ID#' => $ID]),
        'unpublished' => Loc::getMessage('ACTION_RES_UNPUBLISHED_MESS', ['#SMILE_TYPE_ID#' => $ID]),
        'deleted' => Loc::getMessage('ACTION_RES_DELETED_MESS', ['#SMILE_TYPE_ID#' => $ID]),
        'created' => Loc::getMessage('ACTION_RES_CREATED_MESS', ['#SMILE_TYPE_ID#' => $ID]),
        'updated' => Loc::getMessage('ACTION_RES_UPDATED_MESS', ['#SMILE_TYPE_ID#' => $ID]),
    };
}

if(strlen($resultMessage) > 0){
    echo CAdminMessage::ShowMessage([
        'MESSAGE' => $resultMessage,
        'TYPE' => 'OK',
    ]);
}

unset($session['action_res']);
unset($session['ID']);

$lAdmin->DisplayList();

if ($lAdmin->isListMode())
{
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
}
else
{
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
}
?>


