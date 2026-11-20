<?php
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */
use Bitrix\Main,
Bitrix\Main\Application,
Bitrix\Main\Loader,
Bitrix\Main\Localization\Loc,
Mattweb\Comments;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
Loader::includeModule("mattweb.ftmorm");
IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");

if (!$USER->IsAdmin())
{
    $APPLICATION->AuthForm(Loc::GetMessage("ACCESS_DENIED"));
}

Loader::includeModule("mattweb.comments");

$app = Application::getInstance();

$context = $app->getContext();
$session = $app->getSession();

$request = $context->getRequest();

$APPLICATION->SetTitle(Loc::GetMessage('COMMENTS_SMILES_LIST_TITLE'));

$sTableID = "tbl_smile_types_entity";
// поля в SmileTable::getMap() названы в нижнем регистре (id, code, image, name, code, published, ordering, smile_type_id, smile_type),
// поэтому и колонки, и сортировка, и f_-переменные здесь тоже в нижнем регистре
$oSort = new CAdminSorting($sTableID, "id", "asc");
$lAdmin = new CAdminList($sTableID, $oSort);

// ******************************************************************** //
//                     ФИЛЬТР: ИНИЦИАЛИЗАЦИЯ                            //
// ******************************************************************** //

// поля фильтра. Имена должны совпадать с атрибутами name полей формы find_form
$FilterArr = [
    'find_id',	
    'find_published',
    'find_smiles_type_id',
];

// InitFilter создаёт глобальные переменные $find_id, find_published, $find_smiles_type_id
// и хранит их значения в сессии между переходами по страницам списка
$lAdmin->InitFilter($FilterArr);

// проверку значений фильтра для удобства вынесем в отдельную функцию
function CheckFilter()
{
    global $lAdmin;

    // значения фильтра лежат в глобальных переменных, созданных InitFilter
    $findId = trim((string)($GLOBALS['find_id'] ?? ''));
    $findSmilesTypeId = trim((string)($GLOBALS['find_smiles_type_id'] ?? ''));

    if ($findId !== '' && !preg_match('/^\d+$/', $findId))
    {
            $lAdmin->AddFilterError(Loc::GetMessage('COMMENTS_FLT_ERR_ID'));
    }

    if ($findSmilesTypeId !== '' && !preg_match('/^\d+$/', $findSmilesTypeId))
    {
            $lAdmin->AddFilterError(Loc::GetMessage('COMMENTS_FLT_ERR_ARTICLE_ID'));
    }

    return count($lAdmin->arFilterErrors) == 0; // если ошибки есть, вернем false
}

$find_id = $find_id ?? '';
$find_smiles_type_id = $find_smiles_type_id ?? '';
$find_published = $find_published ?? '';

$arFilter = [];
// если все значения фильтра корректны, обработаем его.
// В фильтр попадают только заполненные поля: пустая строка в ORM превратилась бы
// в условие ID = '' и выборка стала бы пустой.

if (CheckFilter())
{
    if ((string)$find_id !== '')
    {
        $arFilter['id'] = (int)$find_id;
    }

    if ((string)$find_smiles_type_id !== '')
    {
        $arFilter['smiles_type_id'] = (int)$find_smiles_type_id;
    }

    if ((string)$find_published !== '')
    {
        $arFilter['published'] = (int)$find_published;
    }
}

$arHeaders = [
    ['id'=>'id', "content"=>"ID", "sort"=>"id", "default"=>true],
    ['id'=>'code', "content"=>Loc::GetMessage('SMILE_ENTITY_CODE_FIELD'), "sort"=>"code", "default"=>true],
    ['id'=>'image', "content"=>Loc::GetMessage('SMILE_ENTITY_IMAGE_FIELD'), "sort"=>"image", "default"=>true],
    ['id'=>'name', "content"=>Loc::GetMessage('SMILE_ENTITY_NAME_FIELD'), "sort"=>"name", "default"=>true],
    ['id'=>'published', "content"=>Loc::GetMessage('SMILE_ENTITY_PUBLISHED_FIELD'), "sort"=>"published", "default"=>true],
    ['id'=>'ordering', "content"=>Loc::GetMessage('SMILE_ENTITY_ORDERING_FIELD'), "sort"=>"ordering", "default"=>true],
    ['id'=>'smiles_type_name', "content"=>Loc::GetMessage('SMILE_ENTITY_SMILE_TYPE_FIELD'), "sort"=>"smiles_type_name", "default"=>true],
    ['id'=>'smiles_type_published', "content"=>Loc::GetMessage('SMILE_TYPE_ENTITY_PUBLISHED_FIELD'), "sort"=>"smiles_type_published", "default"=>true],
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

$rsDataSmt = Comments\SmileTable::getList([
    //'select' => $lAdmin->GetVisibleHeaderColumns(),
    'select' => ['id', 'code', 'image', 'name', 'published', 'ordering', 'smiles_type_'=>'smile_type'],
    "order" => $getListOrder,
    "filter" => $arFilter,
]);

$lAdmin->AddHeaders($arHeaders);

$rsData = new CAdminResult($rsDataSmt, $sTableID);
$rsData->NavStart();

// build list
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("PAGES")));
//echo SmilesActions::IMG_PATH;
while($arRes = $rsData->NavNext(true, "f_"))
{
    // dump($arRes);
     
    $row = &$lAdmin->AddRow($f_id, $arRes);
    
    $row->AddViewField("published", ((int)$f_published == 1) ? GetMessage("SMILES_SMILE_YES_NO_YES") : GetMessage("SMILES_SMILE_YES_NO_NO"));
    
    /*if($f_smiles_type_id == SmilesActions::EMOJI_SMILES_TYPE_ID){
        $row->AddViewField("code", '<span alt="'.$f_name.'">'.htmlspecialchars($f_code).'</span>');
    }*/
    
    if($f_smiles_type_id == SmilesActions::EMOJI_SMILES_TYPE_ID){
        $row->AddViewField("image", '<span alt="'.$f_name.'">'.$f_code.'</span>');  
    }
    else{
        $cssStyle = ' style="';
        if($f_smiles_type_id == SmilesActions::BX_SMILES_TYPE_ID){
            $cssStyle .= 'max-width: 20px;';
        }
        $cssStyle .= '"';
        
        $row->AddViewField("image", '<img src="'.SmilesActions::IMG_PATH.$f_image.'"'.$cssStyle.' alt="'.$f_name.'" data-type-id="'.$f_smiles_type_id.'" />');    
    }    
    $row->AddViewField("smiles_type_published", ((int)$f_smiles_type_published == 1) ? 
        '<span style="color:#087632;">'.GetMessage("SMILES_SMILE_YES_NO_YES").'</span>' :
        '<span style="color:#c40f0f;">'.GetMessage("SMILES_SMILE_YES_NO_NO").'</span>');
    $row->AddViewField("smiles_type_name", '<span>'.$f_smiles_type_name.'['.$f_smiles_type_id.']</span>');
    
    
    $can_edit = true;
    $arActions = [];

    $arActions[] = [
        "ICON"=>"edit",
        "TEXT"=>GetMessage($can_edit ? "MAIN_ADMIN_MENU_EDIT" : "MAIN_ADMIN_MENU_VIEW"),
        "ACTION"=>$lAdmin->ActionRedirect("comments_smile_edit.php?ID=".$f_id)
    ];

    $arActions[] = [
        "ICON"=>"delete",
        "TEXT" => GetMessage("MAIN_ADMIN_MENU_DELETE"),
        "ACTION" => "if(confirm('".GetMessageJS('SMILES_SMILE_ADMIN_DELETE_COMMENT_CONFIRM')."')) ".
                $lAdmin->ActionRedirect("comments_smile_edit.php?action=delete&ID=".$f_id.'&'.bitrix_sessid_get())
    ];

    if($f_published == "0"){
            $arActions[] = [
                    "ICON"=>"publish",
                    "TEXT" => GetMessage("MAIN_ADMIN_MENU_PUBLISH"),
                    "ACTION" => $lAdmin->ActionRedirect("comments_smile_edit.php?action=publish&ID=".$f_id.'&'.bitrix_sessid_get())
            ];
    }
    else{
            $actionStr = $lAdmin->ActionRedirect("comments_smile_edit.php?action=unpublish&ID=".$f_id.'&'.bitrix_sessid_get());
            $actionStr = "if(confirm('".GetMessageJS('SMILES_SMILE_ADMIN_UNPUBLISH_COMMENT_CONFIRM')."')) ".$actionStr;
            
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
            "TEXT" => GetMessage('SMILE_ADD_TITLE'),
            "TITLE" => GetMessage('SMILE_ADD_TITLE'),
            "LINK" => "comments_smile_edit.php?lang=" . LANGUAGE_ID,
            "ICON" => "btn_new",
    ];

    $adminContextMenu = new CAdminContextMenu($aMenu);
    $adminContextMenu->Show();
        
    // **************************************************************** //
    //                        ФИЛЬТР: ВЫВОД                             //
    // **************************************************************** //
    // Второй параметр — список полей для меню «Добавить поле».
    // Количество и порядок его элементов должны совпадать
    // со строками <tr> между Begin() и Buttons().
    
    
    
    $arSmileTypeRef = [];
         
    $rsDataSmt = Comments\SmileTypeTable::getList([
        'select' => ['id', 'name', 'code', 'published'],
        "order" => ['id' => 'asc'],
    ]);
    
    while($arDataSmt = $rsDataSmt->fetch()){
        $arSmileTypeRef["reference"][] = $arDataSmt['name'];
        $arSmileTypeRef["reference_id"][] = $arDataSmt['id'];
    }
    
    $oFilter = new CAdminFilter(
        $sTableID."_filter",
        [
            'find_id' => 'ID',
            'find_smiles_type_id' => Loc::GetMessage('FIND_SMILES_TYPE_ID'),
            'find_published' => Loc::GetMessage('FIND_PUBLISHED'),
        ]
    );
    
    ?>
    <form name="find_form" method="GET" action="<?=$APPLICATION->GetCurPage()?>?">
        <input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
        <?
        // Begin() открывает контейнер фильтра и <table>, Buttons() её закрывает,
        // поэтому все <tr> должны находиться строго между ними.
        $oFilter->Begin();
        ?>
        <tr>
            <td nowrap>ID:</td>
            <td nowrap><input type="text" name="find_id" size="47" value="<?=htmlspecialcharsbx((string)$find_id)?>"></td>
        </tr>
        <tr>
            <td nowrap><?=Loc::GetMessage('FIND_SMILES_TYPE_ID')?>:</td>
            <td nowrap><?
            echo SelectBoxFromArray("find_smiles_type_id", $arSmileTypeRef, htmlspecialcharsbx((string)$find_smiles_type_id), GetMessage("COMMENTS_FLT_ALL"));
            ?></td>
        </tr>
        <tr>
            <td nowrap><?=Loc::GetMessage('FIND_PUBLISHED')?>:</td>
            <td nowrap><?
                    $arPublishedRef = [
                            "reference" => [GetMessage("SMILES_SMILE_YES_NO_YES"), GetMessage("SMILES_SMILE_YES_NO_NO")],
                            "reference_id" => ["1", "0"],
                    ];
                    echo SelectBoxFromArray("find_published", $arPublishedRef, htmlspecialcharsbx((string)$find_published), GetMessage("COMMENTS_FLT_ALL"));
            ?></td>
        </tr>
        <?
        $oFilter->Buttons(["table_id" => $sTableID, "url" => $APPLICATION->GetCurPage(), "form" => "find_form"]);
        $oFilter->End();
        ?>
    </form>
    <?}

$lAdmin->CheckListMode();

$actionRes = $session['action_res'];

if(!empty($actionRes)){
   $ID = $session['ID'];
        
   $resultMessage = match($actionRes){
        'published' => Loc::getMessage('ACTION_RES_PUBLISHED_MESS', ['#SMILE_ID#' => $ID]),
        'unpublished' => Loc::getMessage('ACTION_RES_UNPUBLISHED_MESS', ['#SMILE_ID#' => $ID]),
        'deleted' => Loc::getMessage('ACTION_RES_DELETED_MESS', ['#SMILE_ID#' => $ID]),
        'created' => Loc::getMessage('ACTION_RES_CREATED_MESS', ['#SMILE_ID#' => $ID]),
        'updated' => Loc::getMessage('ACTION_RES_UPDATED_MESS', ['#SMILE_ID#' => $ID]),
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
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
}
else
{
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
}
?>

