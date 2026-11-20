<?
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */
use Bitrix\Main\Application,
Bitrix\Main\Context,
Bitrix\Main\Localization\Loc,
Bitrix\Iblock\ElementTable,
Bitrix\Main\Loader,
Bitrix\Main\Config\Option,
Bitrix\Main\UserTable,
Bitrix\Main\Engine\CurrentUser,
Mattweb\Comments;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/iblock/prolog.php');

Loc::loadLanguageFile(__FILE__);

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");

$sourceIblockId = (int) Option::get("mattweb.comments", 'source_iblock', '');
$commDelMode = Option::get("mattweb.comments", 'comm_del_mode', '');

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm(Loc::GetMessage("ACCESS_DENIED"));
}

Loader::includeModule("mattweb.comments");
Loader::includeModule("iblock");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$context = Application::getInstance()->getContext();
$request = $context->getRequest();

$session = Application::getInstance()->getSession();

// form
$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::GetMessage('COMMENTS_ADMIN_COMMENT_TITLE'),
        'TITLE' => Loc::GetMessage('COMMENTS_ADMIN_COMMENT_TITLE')
    ]
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);

// init vars
$is_create_form = true;
$is_update_form = false;

$errors = [];
$localization = [];

$ID = (int)$request->get('ID');
$save = trim((string)$request->get('save'));
$apply = trim((string)$request->get('apply'));
$action = trim((string)$request->get('action'));
$requestMethod = $request->getRequestMethod();

$session = Application::getInstance()->getSession();

if($ID > 0)
{
    $filter = [
        'select' => ['ID', 'ELEM_ID', 'PARENT_ID', 'DEPTH_LEVEL', 'CREATED_AT', 'PUBLISHED', 'USER_ID', 'USERNAME', 'NAME', 'HOMEPAGE', 'IP', 'EMAIL', 'COMMENT', 'SUBSCRIBE'],
        'filter' => ['=ID' => $ID],
    ];

    $comment = Comments\CommentTable::getList($filter)->fetch();
    
    if (!empty($comment) && $sourceIblockId > 0)
    {
        $is_update_form = true;
        $is_create_form = false;

        $parentId = (int) $comment['PARENT_ID'];

        $arParentComm = [];

        if($parentId > 0){
            $parentCommRes = Comments\CommentTable::getById($parentId);
            $arParentComm = $parentCommRes->fetch();

            if(!empty($arParentComm['COMMENT'])){
                $obParser = new CTextParser;                    
                $parentCommentTxt = $obParser->html_cut($arParentComm['COMMENT'], 100);
            }

            // ID статьи => [ID комментария, Уровень вложенности, превью текст комментария]
            $arCommSummary[$comment['ELEM_ID']] = [
                'PAR_COMM_ID' => (int) $parentId,
                'COM_DL' => (int) $comment['DEPTH_LEVEL'],
                'PAR_COMM_PREVIEW' => $parentCommentTxt
            ];
        }

        $rsArtData = ElementTable::getList([
            'order' => ['ID'=>'ASC'],
            'select' => ['ID', 'IBLOCK_ID', 'NAME', 'IBLOCK_SECTION_ID'],
            'filter' => [
                'IBLOCK_ID' => $sourceIblockId,
                'ID' => $comment['ELEM_ID'],
            ],
        ]);

        $arArtData = $rsArtData->fetch();
        $comment['ARTICLE_NAME'] = !empty($arArtData['NAME']) ? $arArtData['NAME'] : '-';
        $comment['ARTICLE_SECT_ID'] = !empty($arArtData['IBLOCK_SECTION_ID']) ? $arArtData['IBLOCK_SECTION_ID'] : '-';

        $sourceSectionId = !empty($arArtData['IBLOCK_SECTION_ID']) ? $arArtData['IBLOCK_SECTION_ID'] : 0;
	}

    if($is_update_form){
         $APPLICATION->SetTitle(Loc::GetMessage('COMMENTS_EDIT_TITLE', ['#ID#' => $comment['ID']]));

         $rsComment = Comments\CommentTable::getList([
            'select' => [
               'ID', 'ELEM_ID', 'PARENT_ID', 'DEPTH_LEVEL', 'CREATED_AT', 'PUBLISHED', 'USER_ID', 'USERNAME', 'NAME', 'HOMEPAGE', 'IP', 'EMAIL', 'COMMENT', 'SUBSCRIBE' 
            ],
            'filter' => [
                '=ID' => $ID
            ],
            'count_total' => true,
         ]);

         $comment['ROWS_COUNT'] = $rsComment->getCount();
    }

    // delete action
    if ($is_update_form && $action === 'delete' && check_bitrix_sessid()){

        // сам комментарий и вся ветка ответов под ним
        CommentsActions::ResetCommentTree();
        CommentsActions::GetCommentTree($comment['ID'], 1, 'down');

        $arCommIds = CommentsActions::GetPlainCommentIds();

        if(empty($arCommIds)){
            $arCommIds = [(int)$comment['ID']];
        }

        // удаляем с самого глубокого уровня, чтобы не оставлять висячих PARENT_ID
        $arCommIds = array_reverse($arCommIds);

        $errors = [];
        switch ($commDelMode){
            case 'dm_cascade':
                foreach($arCommIds as $commId){
                    $result = Comments\CommentTable::delete($commId);
                    if (!$result->isSuccess()){
                        $errors[] = join(', ', $result->getErrorMessages());
                    }
                }
            break;
            case 'dm_mark':
                foreach($arCommIds as $commId){
                    $arFields = [
                        'DEL_MARKED' => 1,
                    ];

                    $result = Comments\CommentTable::update($commId, $arFields);

                    if (!$result->isSuccess()){
                        $errors[] = join(', ', $result->getErrorMessages());
                    }
                }
            break;
        }

        if(!empty($errors)){
            CAdminMessage::ShowMessage(join("\n", $errors));
        }else{
            \LocalRedirect('comments_list.php?lang='.LANGUAGE_ID);
        }
    }


    // publish / unpublish actions
    if ($is_update_form && ($action === 'publish' || $action === 'unpublish') && check_bitrix_sessid()){

        $errors = [];

        // ветка целиком: родители до корня + все ответы под комментарием
        CommentsActions::ResetCommentTree();
        CommentsActions::GetCommentTree($comment['ID'], 1, 'up');
        CommentsActions::GetCommentTree($comment['ID'], 1, 'down');

        $pComms = CommentsActions::GetPlainCommentIds();

        $dataForUpdate = [];

        switch ($action){

            case 'publish':
                $dataForUpdate['PUBLISHED'] = 1;
                break;

            case 'unpublish':
                $dataForUpdate['PUBLISHED'] = 0;
                break;
        }

        foreach($pComms as $commId){
           $result = Comments\CommentTable::update($commId, $dataForUpdate);
           if (!$result->isSuccess()){
                $errors[] = join(', ', $result->getErrorMessages());
           }
        }

        if(!empty($errors)){
            CAdminMessage::ShowMessage(join("\n", $errors));
        }else{

            $commIdStr = join(', ', $pComms);

            if($action == 'publish'){
                $commsChangedMess = Loc::GetMessage('COMMENT_ENTITYS_PUBLISHED', ['#COMM_ID_STR#' => $commIdStr]);
            }
            else{
                $commsChangedMess = Loc::GetMessage('COMMENT_ENTITYS_UNPUBLISHED', ['#COMM_ID_STR#' => $commIdStr]);
            }
            
            $session->set('COMMS_CHANGED_MESS', $commsChangedMess);

            \LocalRedirect('comments_list.php?lang='.LANGUAGE_ID);
        }
    }

    // save action
    if (($save != '' || $apply != '') && $requestMethod == 'POST' && check_bitrix_sessid()){
        
        $data = [   
            'IBLOCK_ID' => $request['IBLOCK_ID'],
            'SECT_ID' => $request['SECT_ID'],
            'ELEM_ID' => $request['ELEM_ID'],        
            'PARENT_ID' => $request['PARENT_ID'],
            'DEPTH_LEVEL' => $request['DEPTH_LEVEL'],
            'CREATED_AT' => new \Bitrix\Main\Type\DateTime($request['CREATED_AT']),
            'PUBLISHED' => ($request['PUBLISHED'] == 'on' ? 1 : 0),
            'DEL_MARKED' => $request['DEL_MARKED'] ?? 0,
            'USER_ID' => $request['USER_ID'],
            'USERNAME' => $request['USERNAME'],
            'NAME' => $request['NAME'],
            'HOMEPAGE' => $request['HOMEPAGE'],
            'IP' => $request['IP'],
            'EMAIL' => $request['EMAIL'],
            'COMMENT' => $request['COMMENT'],
            'SUBSCRIBE' => ($request['SUBSCRIBE'] == 'on' ? 1 : 0),
            'JC_ID' => '0',
        ];

        $result = Comments\CommentTable::update($ID, $data);

        if ($result->isSuccess())
        {
            $commsSuccessMess = Loc::GetMessage('COMMENT_ENTITYS_UPDATED', ['#COMM_ID_STR#' => $ID]);
            $session->set('COMMENT_ENTITYS_UPDATED', $commsSuccessMess);
            
            if ($save != '')
            {
                \LocalRedirect('comments_list.php?lang='.LANGUAGE_ID);
            }
            else
            {
                \LocalRedirect('comment_edit.php?ID='.$ID.'&lang='.LANGUAGE_ID.'&'.$tabControl->ActiveTabParam());
            }
        }
        else
        {
            $errors = $result->getErrorMessages();
        }
    }
    
    $arCommentVotes = ['LIKES' => 0, 'DISLIKES' => 0];
    
    $arVotes = Comments\VoteTable::getList([
        'select'=>['ID', 'COMMENT_ID', 'VALUE'],
        'filter'=>['COMMENT_ID' => $ID],
    ])->fetchAll();
    
    if(!empty($arVotes)){
        foreach($arVotes as $arVote){
            if((int)$arVote['VALUE'] == 1){
                $arCommentVotes['LIKES'] += 1;
            }
            else{
               $arCommentVotes['DISLIKES'] += 1; 
            }
        }
    }
    
    $comment['VOTES'] = $arCommentVotes;    
}
else{

    // $is_create_form => true;    

   $obCurUser = CurrentUser::get();
   $curUserId = $obCurUser->getId();

    $rsCurUser =  UserTable::getList([
        'select' => ['ID', 'NAME', 'LOGIN', 'EMAIL'], 
        'filter'=> ['ID' => $curUserId]
    ]);

    $arCurUser = $rsCurUser->fetch();
            

    if (($save != '' || $apply != '') && $requestMethod == 'POST' && check_bitrix_sessid()){
        
        $newData = [        
            'IBLOCK_ID' => $request['IBLOCK_ID'],
            'SECT_ID' => $request['SECT_ID'],
            'ELEM_ID' => $request['ELEM_ID'],        
            'PARENT_ID' => $request['PARENT_ID'],
            'DEPTH_LEVEL' => $request['DEPTH_LEVEL'],
            'CREATED_AT' => new \Bitrix\Main\Type\DateTime(),
            'PUBLISHED' => ($request['PUBLISHED'] == 'on' ? 1 : 0),
            'DEL_MARKED' => $request['DEL_MARKED'] ?? 0,
            'USER_ID' => $request['USER_ID'],
            'USERNAME' => $request['USERNAME'],
            'NAME' => $request['NAME'],
            'HOMEPAGE' => $request['HOMEPAGE'],
            'IP' => $request['IP'],
            'EMAIL' => $request['EMAIL'],
            'COMMENT' => $request['COMMENT'],
            'SUBSCRIBE' => ($request['SUBSCRIBE'] == 'on' ? 1 : 0),
            'JC_ID' => '0',
        ];

        $res = Comments\CommentTable::add($newData);
        if ($res->isSuccess())
        {
            $commentId = $res->getId();
            $successRes = true;
            $commsSuccessMess = Loc::GetMessage('COMMENT_ENTITYS_ADDED', ['#COMM_ID_STR#' => $commentId]);
            
            $session->set('COMMENT_ENTITYS_ADDED', $commsSuccessMess);
            
            if ($save != '')
            {               
                \LocalRedirect('comments_list.php?lang='.LANGUAGE_ID);        
            }
            else
            {
                \LocalRedirect('comment_edit.php?ID='.$commentId.'&lang='.LANGUAGE_ID.'&'.$tabControl->ActiveTabParam());
            }
        }
        else{
            $errors[] = join("\r\n", $res->getErrorMessages());            
        }

    }
}

/*получаем данные о статьях*/

$arArtData = [];
$rsArtData = ElementTable::getList([
    'order' => ['ID'=>'ASC'],
    'select' => ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID'],
    'filter' => [
        'IBLOCK_ID' => $sourceIblockId,
        'ACTIVE' => 'Y',
    ],
]);

while($artDataItem = $rsArtData->fetch()){
    $arArtData[$artDataItem['ID']] = $artDataItem['NAME'];                      
}


// menu
$aMenu = [
    [
        'TEXT'	=> Loc::GetMessage('COMMENTS_ADMIN_ROWS_RETURN_TO_LIST_BUTTON'),
        'TITLE'	=> Loc::GetMessage('COMMENTS_ADMIN_ROWS_RETURN_TO_LIST_BUTTON'),
        'LINK'	=> 'comments_list.php?lang='.LANGUAGE_ID,
        'ICON'	=> 'btn_list',
    ]
];

$adminContextMenu = new CAdminContextMenu($aMenu);
$adminContextMenu->Show();


/*Получаем данные об обязательных полях*/
$cmTableMap = Comments\CommentTable::getMap();

$arReqFields = [];
foreach($cmTableMap as $field){
    $name = $field->getName();
    $isReqParam = $field->getParameter('required');
      
    if($isReqParam){
        $arReqFields[] = $name;
    }  
} 

// получаем сообщения из сессии
$commsMess = $session->get('COMMENT_ENTITYS_UPDATED');
if(!empty($commsMess)){
    CAdminMessage::ShowNote($commsMess);
    $session->remove('COMMENT_ENTITYS_UPDATED');
}

$commsMess = $session->get('COMMENT_ENTITYS_ADDED');
if(!empty($commsMess)){
    CAdminMessage::ShowNote($commsMess);
    $session->remove('COMMENT_ENTITYS_ADDED');
}

if (!empty($errors))
{
	CAdminMessage::ShowMessage(join("\n", $errors));
}
?>
<form name="form1" method="POST" action="<?=$APPLICATION->GetCurPageParam()?>">
	<?=bitrix_sessid_post()?>   

    <?if($is_update_form):?>
	<input type="hidden" name="ID" value="<?= htmlspecialcharsbx($comment['ID'])?>">
    <?endif?>

	<input type="hidden" name="lang" value="<?= LANGUAGE_ID?>">

    <input type="hidden" name="IBLOCK_ID" value="<?=$sourceIblockId?>">
    <input type="hidden" name="SECT_ID" value="<?=$sourceSectionId?>">

	<?
	$tabControl->Begin();
	$tabControl->BeginNextTab();
	?>
	<?if($is_update_form):?>
    <tr>
		<td width="40%"><strong>ID</strong></td>
		<td><?=htmlspecialcharsEx($comment['ID'])?></td>
	</tr>
    <?endif?>
    
    <tr>
        <td width="40%">
            <?CommentsActions::showFieldTitle('ARTICLE_NAME', $arReqFields, Loc::GetMessage('COMMENT_ENTITY_ELEM_NAME'))?>
        </td>
		<td>
            <select name="ELEM_ID" id="F_ELEM_ID">
                <option value="">Выберите</option>
                <?foreach($arArtData as $key=>$artItemName):?>
                <?$selected = ($key == $comment['ELEM_ID']) ? ' selected' : '';?>                    
                <option value="<?=$key?>"<?=$selected?>><?=$artItemName?></option>
                <?endforeach?>
            </select>
            <?//=htmlspecialcharsEx($comment['ARTICLE_NAME'])?>
        </td>
	</tr>
    <?if($is_update_form && ((int) $comment['VOTES']['LIKES']) > 0 || (int) $comment['VOTES']['DISLIKES'] > 0):?>
    <tr>
        <td width="40%">Голоса</td>
        <td>
            &#128077; <?=$comment['VOTES']['LIKES']?> &nbsp;|&nbsp; &#128078; <?=$comment['VOTES']['DISLIKES']?>
        </td>
    </tr>
    <?endif?>
    <?$dlVal = !empty($comment['DEPTH_LEVEL']) ? htmlspecialcharsEx($comment['DEPTH_LEVEL']) : '1'?>
    <tr>
        <td width="40%">
            <?CommentsActions::showFieldTitle('PARENT_ID', $arReqFields, Loc::GetMessage('COMMENT_ENTITYS_PARENT_ID_FIELD'))?>            
        </td>
        <td>
            <?
                $parentCommentTxt = '';
                if(!empty($arParentComm['COMMENT'])){

                    $obParser = new CTextParser;                    
                    $parentCommentTxt = $obParser->html_cut($arParentComm['COMMENT'], 100);
                }

                $disabledFld = (intval($comment['ELEM_ID']) == 0) ? ' disabled' : '';                
            ?>
        
            <input<?=$disabledFld?> type="text" name="PARENT_ID" id="F_PARENT_ID" size="3" value="<?= (!empty($comment['PARENT_ID'])) ? htmlspecialcharsbx($comment['PARENT_ID']) : 0?>">
            <input<?=$disabledFld?> type="button" title="<?=Loc::GetMessage('COMMENT_ENTITYS_PARENT_ID_CHOOSE')?>" name="F_PARENT_ID_BTN" id="F_PARENT_ID_BTN" value="..." data-propid="n0">
            <span class="j-comm-val" id="J_COMM_VAL"><?=$parentCommentTxt?></span>            
        </td>
    </tr>
    <tr>
        <td width="40%">
            <?CommentsActions::showFieldTitle('DEPTH_LEVEL', $arReqFields, Loc::GetMessage('COMMENT_ENTITYS_DEPTH_LEVEL_FIELD'))?> 
        </td>
        <td>
            
            <input type="text" size="30" id="F_DEPTH_LEVEL" name="DEPTH_LEVEL" value="<?=$dlVal?>" />
        </td>
    </tr>
    <?if($is_update_form):?>
	<tr>
        <td width="40%">
            <?CommentsActions::showFieldTitle('CREATED_AT', $arReqFields, Loc::GetMessage('COMMENT_ENTITYS_CREATED_AT_FIELD'))?>            
        </td>
        <td>
            <?=htmlspecialcharsEx($comment['CREATED_AT'])?>
        </td>
    </tr>
    <?endif?>
    <tr>
        <td width="40%"></td>
        <td>
            <?            
            $pubChecked = (intval($comment['PUBLISHED']) === 1) ? ' checked' : '';
				?>
                <input type="checkbox" id="f_PUBLISHED" name="PUBLISHED"<?=$pubChecked?> />&nbsp;
                <label for="f_PUBLISHED"><?CommentsActions::showFieldTitle('PUBLISHED', $arReqFields, Loc::GetMessage('COMMENT_ENTITYS_PUBLISHED_FIELD'))?></label>
        </td>
    </tr>
    <tr>
        <td width="40%"></td>
        <td><?
                $subscrChecked = $comment['SUBSCRIBE'] == 1 ? ' checked' : '';
				?>
                <input id="f_SUBSCRIBE" type="checkbox" name="SUBSCRIBE"<?=$subscrChecked?> />
                &nbsp;
                <label for="f_SUBSCRIBE"><?CommentsActions::showFieldTitle('SUBSCRIBE', $arReqFields, Loc::GetMessage('COMMENT_ENTITYS_SUBSCRIBE_FIELD'))?></label>
        </td>
    </tr>
	<tr>
        <td width="40%">
            <?CommentsActions::showFieldTitle('USER_ID', $arReqFields, Loc::GetMessage('COMMENT_ENTITYS_USER_ID_FIELD'))?>  
        </td>
        <td>            
            <?
                $userId = '0';
                $userId = (!empty($comment['USER_ID'])) ? $comment['USER_ID'] : $arCurUser['ID'];
                $useLogin = '';
                $useLogin = (!empty($arCurUser['LOGIN'])) ? $arCurUser['LOGIN'] : '';
            ?>
            <input type="text" name="USER_ID" id="F_USER_ID" size="3" value="<?= $userId?>">
            <input type="button" title="<?=Loc::GetMessage('COMMENT_ENTITYS_USER_ID_CHOOSE')?>" onclick="jsUtils.OpenWindow('/bitrix/admin/comments_users_search.php?fid=F_USER_ID&scl=j-comm-user', 900, 700);" name="F_USER_ID_BTN" id="F_USER_ID_BTN" value="..." data-propid="n1">
            <span class="j-comm-user"><?=$useLogin?></span>
        </td>
    </tr>
    <tr>
        <td width="40%">
           <?CommentsActions::showFieldTitle('USERNAME', $arReqFields, Loc::GetMessage('COMMENT_ENTITYS_USERNAME_FIELD'))?>           
        </td>
        <td>
            <?
                $loginVal = '';
                $loginVal = (!empty($comment['USERNAME'])) ? $comment['USERNAME'] : $arCurUser['LOGIN'];
            ?>
            <input type="text" size="30" name="USERNAME" value="<?=htmlspecialcharsEx($loginVal)?>" />
        </td>
    </tr>
    <tr>
        <td width="40%">
            <?CommentsActions::showFieldTitle('NAME', $arReqFields, Loc::GetMessage('COMMENT_ENTITYS_NAME_FIELD'))?>
        </td>
        <td>
            <?
                $nameVal = '';
                $nameVal = (!empty($comment['NAME'])) ? $comment['NAME'] : $arCurUser['NAME'];
            ?>
            <input type="text" name="NAME" size="30" value="<?= htmlspecialcharsbx($nameVal)?>">
        </td>
    </tr>
    <tr>
        <td width="40%">
            <?CommentsActions::showFieldTitle('HOMEPAGE', $arReqFields, Loc::GetMessage('COMMENT_ENTITYS_HOMEPAGE_FIELD'))?>
        </td>
        <td>
            <input type="text" name="HOMEPAGE" size="30" value="<?= htmlspecialcharsbx($comment['HOMEPAGE'])?>">
        </td>
    </tr>
    <tr>
        <td width="40%">
            <?
                $server = $context->getServer();
                $curIp = $server->get('REMOTE_ADDR');

                $ip = (!empty($comment['IP'])) ? $comment['IP'] : $curIp; 
            ?>
            <?CommentsActions::showFieldTitle('IP', $arReqFields, Loc::GetMessage('COMMENT_ENTITYS_IP_FIELD'))?>            
        </td>
        <td>
            <input type="text" name="IP" size="30" value="<?=$ip?>" />
        </td>
    </tr>
    <tr>
        <td width="40%">
            <?CommentsActions::showFieldTitle('EMAIL', $arReqFields, Loc::GetMessage('COMMENT_ENTITYS_EMAIL_FIELD'))?>           
        </td>
        <td>
            <?
                $emailVal = '';
                $emailVal = (!empty($comment['EMAIL'])) ? $comment['EMAIL'] : $arCurUser['EMAIL'];
            ?>
            <input type="text" name="EMAIL" size="30" value="<?= htmlspecialcharsbx($emailVal)?>">
        </td>
    </tr>
    <tr>
        <td width="40%">
            <?CommentsActions::showFieldTitle('COMMENT', $arReqFields, Loc::GetMessage('COMMENT_ENTITYS_COMMENT_FIELD'))?>
        </td>
        <td>
            <textarea name="COMMENT" cols="70" rows="10"><?= trim($comment['COMMENT'])?></textarea>
        </td>
    </tr>
    <?
	$tabControl->Buttons(array('disabled' => !$USER->IsAdmin(), 'back_url' => 'comments_list.php?lang='.LANGUAGE_ID));
	$tabControl->End();
	?>
</form>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const fElemIdSel = document.getElementById('F_ELEM_ID');
    const fParentIdFld = document.getElementById('F_PARENT_ID');
    const fParentIdBtn = document.getElementById('F_PARENT_ID_BTN');
    const fDepthLevel = document.getElementById('F_DEPTH_LEVEL');
    const jCommVal = document.getElementById('J_COMM_VAL');

    let commSummaryObj = {};

    <?if(!empty($arCommSummary)):?>
        commSummaryObj = <?=json_encode($arCommSummary)?>;
    <?endif?>
  
    let commSearchUrl = '/bitrix/admin/comments_search.php?fid=F_PARENT_ID&scl=j-comm-val&fdlid=F_DEPTH_LEVEL&vdlid=<?=$dlVal?>';

    <?if($comment['ELEM_ID'] > 0):?>
        commSearchUrl += '&elemIdSel=' + '<?=$comment['ELEM_ID']?>';
    <?endif?>

    <?if($comment['ID'] > 0):?>
        commSearchUrl += '&curCommId=<?=$comment['ID']?>';
    <?endif?>

    fElemIdSel.addEventListener('change', (event) => {
        let elemIdRegEx = /&elemIdSel=\d+&curCommId=\d+/g;

        if(elemIdRegEx.test(commSearchUrl)){
            commSearchUrl = commSearchUrl.replace(elemIdRegEx, '');
        }
        
        if(fElemIdSel.value !== ''){
            fParentIdFld.disabled = false;
            fParentIdBtn.disabled = false;           

            commSearchUrl += '&elemIdSel=' + fElemIdSel.value;
            <?if($comment['ID'] > 0):?>
            commSearchUrl += '&curCommId=<?=$comment['ID']?>';
            <?endif?>

            let elemId = fElemIdSel.value;
            if(elemId in commSummaryObj){               
                commSummaryObjVal = commSummaryObj[elemId];
               
                fDepthLevel.value = commSummaryObjVal.COM_DL;
                fParentIdFld.value = commSummaryObjVal.PAR_COMM_ID;
                jCommVal.innerHTML = commSummaryObjVal.PAR_COMM_PREVIEW;

            }
            else{                
                fDepthLevel.value = '1';
                fParentIdFld.value = '';
                jCommVal.innerHTML = '';
            } 

        }
        else{           
            fDepthLevel.value = '1';
            fParentIdFld.value = '';
            jCommVal.innerHTML = '';

            fParentIdFld.disabled = true;
            fParentIdBtn.disabled = true;
        }       
    });

    fParentIdBtn.addEventListener('click', (event) => {        
        jsUtils.OpenWindow(commSearchUrl, 900, 700);
    });
});
</script>


