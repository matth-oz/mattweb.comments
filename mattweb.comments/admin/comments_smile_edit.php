<?
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */

use Bitrix\Main\Application,
Bitrix\Main\Localization\Loc,
Bitrix\Main\Loader,
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
        'TAB' => Loc::GetMessage('COMMENTS_SMILE_ADMIN_COMMENT_TITLE'),
        'TITLE' => Loc::GetMessage('COMMENTS_SMILE_ADMIN_COMMENT_TITLE')
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

/*
 *  SmilesActions::EMOJI_SMILES_TYPE_ID
 *  SmilesActions::BX_SMILES_TYPE_ID
 *  SmilesActions::IMG_PATH
 * 
 * SmilesActions::SMILE_PUBLISHED
 * SmilesActions::SMILE_UNPUBLISHED
 * 
*/
if($ID > 0){
    $selectFilter = [
        'select' => ['id', 'code', 'image', 'name', 'published', 'ordering', 'smiles_type_'=>'smile_type', /*'smiles_type_name', 'smiles_type_published'*/],
        'filter' => ['=id' => $ID],
    ];
    
    $arSmile = Comments\SmileTable::getList($selectFilter)->fetch();
    
     if(!empty($arSmile)){
        $is_update_form = true;
	$is_create_form = false;
        $commentSmile = $arSmile; // значения из БД
    }
}

if($is_create_form){
    $commentSmile = ['id' => '', 'code' => '', 'image' => '', 'name' => '', 'published' => SmilesActions::SMILE_PUBLISHED, 'ordering' => '', 'smile_type_id' => ''];
    $APPLICATION->SetTitle(Loc::GetMessage('COMMENTS_SMILE_ADD_TITLE'));
}
else{
    $APPLICATION->SetTitle(Loc::GetMessage('COMMENTS_SMILE_EDIT_TITLE', ['#ID#' => $ID]));  
}

// publish / unpublish actions
if ($is_update_form && ($action === 'publish' || $action === 'unpublish') && check_bitrix_sessid()){
    switch ($action){
        case 'publish':
            $commentSmile['published'] = SmilesActions::SMILE_PUBLISHED;
            $actionRes = 'published';
            break;

        case 'unpublish':
            $commentSmile['published'] = SmilesActions::SMILE_UNPUBLISHED;
            $actionRes = 'unpublished';
            break;
    }
    
    $obUpdCommSmileRes = Comments\SmileTable::update($ID, $commentSmile);
    
    if(!$obUpdCommSmileRes->isSuccess()){
        $errors = array_merge($errors, $obUpdCommSmileRes->getErrorMessages());
    }

    if(sizeof($errors) == 0){
        $session->set('action_res', $actionRes);
        $session->set('ID', $ID);
        \LocalRedirect('comments_smiles.php?lang='.LANGUAGE_ID);         
    }
}

// delete action
if ($is_update_form && $action === 'delete' && check_bitrix_sessid()){
    $obDelCommSmileRes = Comments\SmileTable::delete($ID);

    if(!$obDelCommSmileRes->isSuccess()){
        $errors = array_merge($errors, $obDelCommSmileRes->getErrorMessages());
    }
    
    if(sizeof($errors) == 0){
        $session->set('action_res', 'deleted');
        $session->set('ID', $ID);
        \LocalRedirect('comments_smiles.php?lang='.LANGUAGE_ID);        
    }    
}

if (($save != '' || $apply != '') && $requestMethod == 'POST' && check_bitrix_sessid()){
   
    $fileErrors = [];
    $imgName = '';
    if(!empty(trim($request['image_current']))/* && strlen($_FILES['image']['tmp_name']) == 0*/){
        $imgName = trim($request['image_current']);    
    }
    
    if(!empty($_FILES['image']['tmp_name'])){
       $typesAllowed = ["image/gif", "image/png"];
       
       $fileType = trim($_FILES['image']['type']);
       if(in_array($fileType, $typesAllowed)){
           if($_FILES['image']['size'] <= 102400){
               
               if($_FILES['image']['error'] == UPLOAD_ERR_OK){
                   $targetPath = $_SERVER['DOCUMENT_ROOT'] . SmilesActions::IMG_PATH . $_FILES['image']['name'];
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)){
                        $imgName = $_FILES['image']['name'];                       
                    } else {
                        $fileErrors[] = Loc::getMessage('FILE_ERR_SAVE_ERROR');
                    }
               }   
               else{
                  $fileErrors[] = Loc::getMessage('FILE_ERR_UPLOAD_ERROR', ['#FILE_UPLOAD_ERR_TXT#' => $_FILES['image']['error']]);
               }
           } 
           else{
               $fileErrors[] = Loc::getMessage('FILE_ERR_LARGE_SIZE');
           }
       }
       else{
           $fileErrors[] = Loc::getMessage('FILE_ERR_TYPE_NOT_ALLOWED');
       }
    }
  
    $data = [
        'code' => trim($request['code']),
        'name' => trim($request['name']), 
        // 'image' => trim($request['image']),
        'image' => $imgName,
        'published' => intval($request['published']),
        'ordering' => intval($request['ordering']),
        'smile_type_id' => intval($request['smile_type_id']),
    ];
    
    if(sizeof($fileErrors) == 0){
        if ($is_update_form){
            $obResult = Comments\SmileTable::update($ID, $data);
            $actionRes = 'updated';
        }
        else{
            $obResult = Comments\SmileTable::add($data);
            $ID = $obResult->getId();
            $actionRes = 'created';
        }    

        if ($obResult->isSuccess())
        {
            if ($save != '')
            {
                $session->set('action_res', $actionRes);
                $session->set('ID', $ID);
                \LocalRedirect('comments_smiles.php?lang='.LANGUAGE_ID);
            }
            else
            {
                \LocalRedirect('comments_smile_edit.php?ID='.$ID.'&lang='.LANGUAGE_ID.'&action_res='.$actionRes.'&'.$tabControl->ActiveTabParam());
            }
        }
        else
        {
            $errors = array_merge($errors, $obResult->getErrorMessages());
        }
    } // if(sizeof($fileErrors)
    else{
       $errors = array_merge($errors, $fileErrors); 
    }
    
    // rewrite original value by form value to restore form
    foreach ($data as $k => $v)
    {
        $commentSmile[$k] = $v;
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
        'TEXT'	=> Loc::GetMessage('COMMENTS_SMILE_ADMIN_ROWS_RETURN_TO_LIST_BUTTON'),
        'TITLE'	=> Loc::GetMessage('COMMENTS_SMILE_ADMIN_ROWS_RETURN_TO_LIST_BUTTON'),
        'LINK'	=> 'comments_smiles.php?lang='.LANGUAGE_ID,
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

<?
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
$smTableMap = Comments\SmileTable::getMap();

$arReqFields = [];
foreach($smTableMap as $field){
    $name = $field->getName();
    $isReqParam = $field->getParameter('required');
      
    if($isReqParam){
        $arReqFields[] = $name;
    }  
}
?>    
<?
/*Формируем массив для выпадающего списка тип смайла (коллекция)*/
$arSmileTypeRef = [];

$rsDataSmt = Comments\SmileTypeTable::getList([
    'select' => ['id', 'name', 'code', 'published'],
    "order" => ['id' => 'asc'],
]);

while($arDataSmt = $rsDataSmt->fetch()){
    $arSmileTypeRef["reference"][] = $arDataSmt['name'];
    $arSmileTypeRef["reference_id"][] = $arDataSmt['id'];
}
?>
<form name="form1" method="POST" action="<?=$APPLICATION->GetCurPageParam()?>" enctype="multipart/form-data">
    <?=bitrix_sessid_post()?>
    <input type="hidden" name="ID" value="<?= (int)$commentSmile['id']?>">
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID?>">
    <?
    $tabControl->Begin();
    $tabControl->BeginNextTab();
    ?>
    <?if(!$is_create_form):?>
    <tr>
        <td width="40%"><strong>ID</strong></td>
        <td><?=(int)$commentSmile['id'] ?: ''?></td>
    </tr>
    <?endif?>
    <tr><!-- code -->
        <td width="40%">
            <?SmilesActions::showFieldTitle('code', $arReqFields, Loc::GetMessage('COMMENTS_SMILE_ENTITY_ELEM_CODE'))?>
        </td>
        <td><input type="text" size="30" name="code" value="<?=htmlspecialcharsEx($commentSmile['code'])?>" /></td>
    </tr>
    <tr><!-- image -->
        <td width="40%">
            <?SmilesActions::showFieldTitle('image', $arReqFields, Loc::GetMessage('COMMENTS_SMILE_ENTITY_ELEM_IMAGE'))?> 
        </td>
        <td>
            <?if(!$is_create_form):?>
                <?if($commentSmile['smile_type_id'] == SmilesActions::EMOJI_SMILES_TYPE_ID):?>
                <span alt="<?=$commentSmile['name']?>"><?=$commentSmile['code']?></span>
                <?else:
                    $cssStyle = ' style="';
                    if($commentSmile['smile_type_id'] == SmilesActions::BX_SMILES_TYPE_ID){
                        $cssStyle .= 'max-width: 20px;';
                    }
                    $cssStyle .= '"';
                ?>
                <img 
                    src="<?=SmilesActions::IMG_PATH.$commentSmile['image']?>"<?=$cssStyle?> 
                    alt="<?=$commentSmile['name']?>" 
                    data-type-id="<?=$commentSmile['smiles_type_id']?>" 
                />
                <input type="file" name="image">
                <input type="hidden" name="image_current" value="<?=$commentSmile['image']?>">
                <?endif?>
            <?else:?>        
                <!-- Здесь выводим поде для загрузки файла-изображения -->
                <input type="file" name="image">
            <?endif?>
        </td>      
    </tr>
    <tr><!-- name -->
        <td width="40%">
            <?SmilesActions::showFieldTitle('name', $arReqFields, Loc::GetMessage('COMMENTS_SMILE_ENTITY_ELEM_NAME'))?>        
        </td>
        <td><input type="text" size="30" name="name" value="<?=htmlspecialcharsEx($commentSmile['name'])?>" /></td>        
    </tr>
    <tr><!-- published -->
        <td width="40%">
            <?SmilesActions::showFieldTitle('published', $arReqFields, Loc::GetMessage('COMMENTS_SMILE_ENTITY_ELEM_PUBLISHED'))?>
        </td>
        <td>
            <input type="hidden" name="published" value="0" />
            <input type="checkbox" name="published" value="1" <?= (int)$commentSmile['published'] === 1 ? 'checked' : '' ?>  />           
        </td>        
    </tr>
    <tr><!-- ordering -->
        <td width="40%">
            <?SmilesActions::showFieldTitle('ordering', $arReqFields, Loc::GetMessage('COMMENTS_SMILE_ENTITY_ELEM_ORDERING'))?>    
        </td>
        <td><input type="text" size="5" name="ordering" value="<?=htmlspecialcharsEx($commentSmile['ordering'])?>" /></td>     
    </tr>
    <tr><!-- smile_type_id -->
        <td width="40%">
            <?SmilesActions::showFieldTitle('smiles_type_id', $arReqFields, Loc::GetMessage('COMMENTS_SMILE_ENTITY_ELEM_TYPE'))?>
        </td>
        <td>  
            <?echo SelectBoxFromArray("smile_type_id", $arSmileTypeRef, htmlspecialcharsbx((string)$commentSmile['smiles_type_id']), GetMessage("COMMENTS_FLT_ALL"));?>
        </td>        
    </tr>
    <?
    //$tabControl->Buttons(array('disabled' => !$isEditMode, 'back_url' => 'comments_smiles.php?lang='.LANGUAGE_ID));
    $tabControl->Buttons([
        'btnSave' => true,  // Кнопка «Сохранить» будет показана
        'btnApply' => true, // Кнопка «Применить» скрыта));
        'btnCancel' => true,
        'back_url' => 'comments_smiles.php?lang='.LANGUAGE_ID,
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