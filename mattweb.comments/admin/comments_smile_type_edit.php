<?
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */

use Bitrix\Main\Application,
Bitrix\Main\Localization\Loc,
Bitrix\Main\Loader,
Bitrix\Main\Config\Option,
Bitrix\Main\UserTable,
Mattweb\Comments;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/iblock/prolog.php');

Loc::loadLanguageFile(__FILE__);

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

// form
$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::GetMessage('COMMENTS_SMILE_TYPE_ADMIN_COMMENT_TITLE'),
        'TITLE' => Loc::GetMessage('COMMENTS_SMILE_TYPE_ADMIN_COMMENT_TITLE')
    ]
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);

// init vars
$is_create_form = true;
$is_update_form = false;
$isEditMode = true;
$errors = [];
$localization = [];

$ID = (int)$request->get('ID');
$save = trim((string)$request->get('save'));
$apply = trim((string)$request->get('apply'));
$action = trim((string)$request->get('action'));
$requestMethod = $request->getRequestMethod();

if($ID > 0){
    
    $selectFilter = [
        'select' => ['id', 'name', 'code', 'published'],
        'filter' => ['=id' => $ID],
    ];
    
    $arSmileType = Comments\SmileTypeTable::getList($selectFilter)->fetch();
    
    if(!empty($arSmileType)){
        $is_update_form = true;
	$is_create_form = false;
        $commentSmileType = $arSmileType; // значения из БД
    }    
}
    
if($is_create_form){
    $commentSmileType = ['id' => '', 'name' => '', 'code' => '', 'published' => 1];
    $APPLICATION->SetTitle(Loc::GetMessage('COMMENTS_SMILE_TYPE_ADD_TITLE'));
}
else{
    $APPLICATION->SetTitle(Loc::GetMessage('COMMENTS_SMILE_TYPE_EDIT_TITLE', ['#ID#' => $ID]));  
}

// publish / unpublish actions
if ($is_update_form && ($action === 'publish' || $action === 'unpublish') && check_bitrix_sessid()){

    // тип смайлов
    // все смайлы этого типа не трогаем
    // проверять активность группы смайлов можно в компоненте списка коментариев, 
    // если группа неактивна - не показывать смайлы
    $dataForUpdate = [];
    switch ($action){
        case 'publish':
            $dataForUpdate['published'] = 1;
            $actionRes = 'published';
            break;

        case 'unpublish':
            $dataForUpdate['published'] = 0;
            $actionRes = 'unpublished';
            break;
    }

    $obUpdCommTypeRes = Comments\SmileTypeTable::update($ID, $dataForUpdate);

    if(!$obUpdCommTypeRes->isSuccess()){
        $errors = array_merge($errors, $obUpdCommTypeRes->getErrorMessages());
    }

    if(sizeof($errors) == 0){
         \LocalRedirect('comments_smile_types.php?lang='.LANGUAGE_ID.'&action_res='.$actionRes.'&ID='.$ID);         
    }
}

// delete action
if ($is_update_form && $action === 'delete' && check_bitrix_sessid()){
    // тип смайлов и все смайлы этого типа
    $rsSmiles = Comments\SmileTable::GetList([
        'filter' => ['smile_type_id' => $ID],
        'select' => ['id', 'code', 'image', 'name'],
        'order' => ['ordering' => 'asc'],
    ]);

    while($arSmile = $rsSmiles->fetch()){
         $obDelCommRes = Comments\SmileTable::delete($arSmile['id']);

         if(!$obDelCommRes->isSuccess()){
             $errors = array_merge($errors, $obDelCommRes->getErrorMessages());
         }
    }           

     $obDelCommTypeRes = Comments\SmileTypeTable::delete($ID);
     if(!$obDelCommTypeRes->isSuccess()){
        $errors = array_merge($errors, $obDelCommTypeRes->getErrorMessages());
     }

     if(sizeof($errors) == 0){
         $actionRes = 'deleted';
         \LocalRedirect('comments_smile_types.php?lang='.LANGUAGE_ID.'&action_res='.$actionRes.'&ID='.$ID);        
     }         
}
 

if (($save != '' || $apply != '') && $requestMethod == 'POST' && check_bitrix_sessid()){
   
    $data = [
        'name' => trim($request['name']), 
        'code' => trim($request['code']),
        'published' => intval($request['published']),        
    ];
    
    if ($is_update_form){
        $obResult = Comments\SmileTypeTable::update($ID, $data);
        $actionRes = 'updated';
    }
    else{
        $obResult = Comments\SmileTypeTable::add($data);
        $ID = $obResult->getId();
        $actionRes = 'created';
    }
    
    if ($obResult->isSuccess())
    {
        if ($save != '')
        {
            $session->set('action_res', $actionRes);
            $session->set('ID', $ID);
            
            \LocalRedirect('comments_smile_types.php?lang='.LANGUAGE_ID);
        }
        else
        {
            \LocalRedirect('comments_smile_type_edit.php?ID='.$ID.'&lang='.LANGUAGE_ID.'&action_res='.$actionRes.'&'.$tabControl->ActiveTabParam());
        }
    }
    else
    {
        $errors = $obResult->getErrorMessages();
    }

    // rewrite original value by form value to restore form
    foreach ($data as $k => $v)
    {
        $commentSmileType[$k] = $v;
    }
}

// view
if ($request->get('mode') == 'list')
{
    require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_js.php');
}
else
{
    require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');
} 

// menu
$aMenu = [
    [
        'TEXT'	=> Loc::GetMessage('COMMENTS_SMILE_TYPE_ADMIN_ROWS_RETURN_TO_LIST_BUTTON'),
        'TITLE'	=> Loc::GetMessage('COMMENTS_SMILE_TYPE_ADMIN_ROWS_RETURN_TO_LIST_BUTTON'),
        'LINK'	=> 'comments_smile_types.php?lang='.LANGUAGE_ID,
        'ICON'	=> 'btn_list',
    ]
];

$adminContextMenu = new CAdminContextMenu($aMenu);
$adminContextMenu->Show();

if (!empty($errors))
{
    CAdminMessage::ShowMessage(join("\n", $errors));
}
?>

<?php
    /*здесь выводятся ошибки при обновлении и добавлении элемента*/
    if (!empty($arErrors))
    {
        CAdminMessage::ShowMessage(join("\n", $arErrors));
    }
    
    $actionRes = $request->get('action_res');
    
    if(!empty($actionRes)){
        $ID = $request->get('ID');
        
        $resultMessage = match($actionRes){
            'created' => Loc::getMessage('ACTION_RES_CREATED_MESS', ['#SMILE_TYPE_ID#' => $ID]),
            'updated' => Loc::getMessage('ACTION_RES_UPDATED_MESS', ['#SMILE_TYPE_ID#' => $ID]),
            'default' => ''
        };
        
        if(strlen($resultMessage) > 0){
            echo CAdminMessage::ShowMessage([
                'MESSAGE' => $resultMessage,
                'TYPE' => 'OK',
            ]);
        }
    }
?>
<?
/*Получаем данные об обязательных полях*/
$stTableMap = Comments\SmileTypeTable::getMap();

$arReqFields = [];
foreach($stTableMap as $field){
    $name = $field->getName();
    $isReqParam = $field->getParameter('required');
      
    if($isReqParam){
        $arReqFields[] = $name;
    }  
}
?>

<form name="form1" method="POST" action="<?=$APPLICATION->GetCurPageParam()?>">
    <?=bitrix_sessid_post()?>
    <input type="hidden" name="ID" value="<?= (int)$commentSmileType['id']?>">
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID?>">
    <?
    $tabControl->Begin();
    $tabControl->BeginNextTab();
    ?>
    <?if(!$is_create_form):?>
    <tr>
        <td width="40%"><strong>ID</strong></td>
        <td><?=(int)$commentSmileType['id'] ?: ''?></td>
    </tr>
    <?endif?>
    <tr>
        <td width="40%">
            <?SmilesActions::showFieldTitle('name', $arReqFields, Loc::GetMessage('COMMENTS_SMILE_TYPE_ENTITY_ELEM_NAME'))?>
        </td>
        <td><input type="text" size="30" name="name" value="<?=htmlspecialcharsEx($commentSmileType['name'])?>" /></td>
    </tr>
    <tr>
        <td width="40%">
            <?SmilesActions::showFieldTitle('name', $arReqFields, Loc::GetMessage('COMMENTS_SMILE_TYPE_ENTITY_ELEM_CODE'))?>
        </td>
        <td><input type="text" size="30" name="code" value="<?=htmlspecialcharsEx($commentSmileType['code'])?>" /></td>
    </tr>
    <tr>
        <td width="40%">
            <?SmilesActions::showFieldTitle('name', $arReqFields, Loc::GetMessage('COMMENTS_SMILE_TYPE_ENTITY_ELEM_PUBLISH'))?>     
        </td>
        <td>
            <input type="hidden" name="published" value="0" />
            <input type="checkbox" name="published" value="1" <?= (int)$commentSmileType['published'] === 1 ? 'checked' : '' ?>  />
        </td>
    </tr>
    <?
    //$tabControl->Buttons(array('disabled' => !$isEditMode, 'back_url' => 'comments_smile_types.php?lang='.LANGUAGE_ID));
    $tabControl->Buttons([
        'btnSave' => true,  // Кнопка «Сохранить» будет показана
        'btnApply' => true, // Кнопка «Применить» скрыта));
        'btnCancel' => true,
        'back_url' => 'comments_smile_types.php?lang='.LANGUAGE_ID,
        'disabled' => !$isEditMode,
    ]);
    
    $tabControl->End();
    ?>
</form>
<?php
if ($request->get('mode') == 'list')
{
    require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin_js.php');
}
else
{
    require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');
}
?>