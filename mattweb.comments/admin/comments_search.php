<?php
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */
use Bitrix\Main,
\Bitrix\Main\Entity,
Bitrix\Main\Loader,
Bitrix\Main\UserTable,
Mattweb\Comments;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

// id выбранной статьи - фильтр для списка комментариев
$elemIdSel = trim($_GET['elemIdSel']);
if(!empty($elemIdSel)){

    // fid - id поля html-формы, кукда должно возвращаться выбранное значение
    // scl - css класс элемента (span) для отображения названиея команды
    if(isset($_GET['fid']) && isset($_GET['scl'])){

        $fId = trim($_GET['fid']);
        $sCl = trim($_GET['scl']);
    
        // id поля с уровнем вложенности 
        $fdlid = trim($_GET['fdlid']);

        Loader::IncludeModule('mattweb.comments');

        if(!empty($fId) && !empty($sCl)){
            $APPLICATION->SetTitle(GetMessage('COMMENT_ENTITYS_SEARCH_TITLE'));

            $sTableID = "tbl_comments_entity";
            $oSort = new CAdminSorting($sTableID, "ID", "desc");
            $lAdmin = new CAdminList($sTableID, $oSort);

            $arHeaders = [
                ["id"=>"ID", "content"=>GetMessage("COMMENT_ENTITYS_COMMENT_ID_LIST_ROW"), "sort"=>"ID", "default"=>true],
                ["id"=>"ELEM_ID", "content"=>GetMessage("COMMENT_ENTITYS_ELEM_ID_LIST_ROW"), "sort"=>"ELEM_ID", "default"=>true],
                ["id"=>"DEPTH_LEVEL", "content"=>GetMessage("COMMENT_ENTITYS_DEPTH_LEVEL_LIST_ROW"), "sort"=>"DEPTH_LEVEL", "default"=>true],
                ["id"=>"COMMENT", "content"=>GetMessage("COMMENT_ENTITYS_COMMENT_LIST_ROW"), "sort"=>"COMMENT", "default"=>true],
                ["id"=>"USERNAME", "content"=>GetMessage("COMMENT_ENTITYS_USERNAME_LIST_ROW"), "sort"=>"USERNAME", "default"=>true],
                ["id"=>"NAME", "content"=>GetMessage("COMMENT_ENTITYS_NAME_LIST_ROW"), "sort"=>"NAME", "default"=>true],
                ["id"=>"USER_ID", "content"=>GetMessage("COMMENT_ENTITYS_USER_ID_LIST_ROW"), "sort"=>"USER_ID", "default"=>true],
            ];

            $lAdmin->AddHeaders($arHeaders);

            $by = mb_strtoupper($oSort->getField());
            $order = mb_strtoupper($oSort->getOrder());

            $getListOrder = [
                $by => $order,
            ];

            $arFilter = ["ELEM_ID" => $elemIdSel];

            if(isset($_GET['curCommId']) && intval($_GET['curCommId']) > 0){
                $arFilter['!ID'] = intval($_GET['curCommId']);
            }

            $CommentsRes = Comments\CommentTable::getList([
                'select' => ["ID", "ELEM_ID", "DEPTH_LEVEL", "COMMENT", "USERNAME", "NAME", "USER_ID"],
                'filter' => $arFilter,
                'order' => $getListOrder,
            ]);

            $CommentsRes = new CAdminResult($CommentsRes, $sTableID);

            $CommentsRes->NavStart();
            
            // build list
            $lAdmin->NavText($CommentsRes->GetNavPrint(GetMessage("PAGES")));

            while($arRes = $CommentsRes->NavNext(true, "f_")){
                $row = $lAdmin->AddRow(
                    $f_ID, 
                    $arRes,				
                );

                $nextDl = $arRes['DEPTH_LEVEL'] + 1;

                $arActions = [];
                $arActions[] = [
                    "ICON" => "",
                    "TEXT" => GetMessage("COMMENT_ENTITYS_CHOOSE_COMMENT"),
                    "ACTION" => "javascript:SelEl('".CUtil::JSEscape($f_ID)."', '".htmlspecialcharsbx(CUtil::JSEscape($arRes["COMMENT"]), ENT_QUOTES)."', '".htmlspecialcharsbx(CUtil::JSEscape($nextDl), ENT_QUOTES)."')",
                ]; 

                $row->AddActions($arActions);
            }
        }  



    }
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
}
else{
    showError(GetMessage('COMMENT_ENTITYS_SEARCH_EMPTY_ART_PARAMS'));
}

?>
<?if(!empty($elemIdSel)):?>
    <?if(!empty($fId) && !empty($sCl) && !empty($fdlid)):?>
    <script>
        function SelEl(id, name, fdlval)
        {
            let el, snEl, curCommId;
            el = window.opener.document.getElementById('<?echo $fId?>');
            el_dl = window.opener.document.getElementById('<?echo $fdlid?>');

            // console.log(typeof curCommId);

            // curCommId = 0;

            <?if(isset($_GET['curCommId']) && intval($_GET['curCommId']) > 0):?>
            curCommId = <?=intval($_GET['curCommId'])?>;
            <?endif?>

            if(typeof curCommId !== 'undefined' && curCommId > id){
                if(el){
                    el.value = id;
                }

                if(el_dl){
                    el_dl.value = fdlval;
                }

                snEl = window.opener.document.querySelector('.<?=$sCl?>');
                if(snEl){
                    snEl.innerHTML = name;
                }

                window.close();
            }
            else{
                alert(<?=GetMessage('COMMENT_ENTITYS_SEARCH_WRONG_COMMENT_ID')?>);
            }            
        }	
    </script>
    <?
    $lAdmin->CheckListMode();
    $lAdmin->DisplayList();
    ?>
    <?else:?>
        <?showError(GetMessage('COMMENT_ENTITYS_SEARCH_EMPTY_PARAMS'));?>
    <?endif?>
<?endif?>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");


