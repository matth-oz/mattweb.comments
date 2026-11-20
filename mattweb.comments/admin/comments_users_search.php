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

// fid - id поля html-формы, кукда должно возвращаться выбранное значение
// scl - css класс элемента (span) для отображения названиея команды
if(isset($_GET['fid']) && isset($_GET['scl'])){
	$fId = trim($_GET['fid']);
	$sCl = trim($_GET['scl']);

	if(!empty($fId) && !empty($sCl)){
		$APPLICATION->SetTitle(GetMessage('COMMENT_ENTITYS_USER_SEARCH_TITLE'));

        $sTableID = "tbl_users_entity";
        $oSort = new CAdminSorting($sTableID, "ID", "desc");
        $lAdmin = new CAdminList($sTableID, $oSort);

        $arHeaders = [
            ["id"=>"ID", "content"=>GetMessage("COMMENT_ENTITYS_USERS_LIST_ROW_NUMBER"), "sort"=>"ID", "default"=>true],
            ["id"=>"LOGIN", "content"=>GetMessage("COMMENT_ENTITYS_USERS_LIST_ROW_LOGIN"), "sort"=>"LOGIN", "default"=>true],
            ["id"=>"EMAIL", "content"=>GetMessage("COMMENT_ENTITYS_USERS_LIST_ROW_EMAIL"), "sort"=>"EMAIL", "default"=>true],
            ["id"=>"NAME", "content"=>GetMessage("COMMENT_ENTITYS_USERS_LIST_ROW_NAME"), "sort"=>"NAME", "default"=>true],
            ["id"=>"LAST_NAME", "content"=>GetMessage("COMMENT_ENTITYS_USERS_LIST_ROW_LAST_NAME"), "sort"=>"LAST_NAME", "default"=>true],
        ];
        
        $lAdmin->AddHeaders($arHeaders);

        $by = mb_strtoupper($oSort->getField());
        $order = mb_strtoupper($oSort->getOrder());

        $getListOrder = [
            $by => $order,
        ];

        $UsersRes =  UserTable::getList([
            'select' => ['ID', 'NAME', 'LOGIN', 'NAME', 'EMAIL', 'LAST_NAME'], 
            'filter'=> ['ACTIVE' => 'Y'],
            'order' => $getListOrder,
        ]);

        $UsersRes = new CAdminResult($UsersRes, $sTableID);

        $UsersRes->NavStart();

        // build list
        $lAdmin->NavText($UsersRes->GetNavPrint(GetMessage("PAGES")));

        while($arRes = $UsersRes->NavNext(true, "f_")){
            $row = $lAdmin->AddRow(
				$f_ID, 
				$arRes,				
			);

			$arActions = [];
			$arActions[] = [
				"ICON" => "",
				"TEXT" => GetMessage("COMMENT_ENTITYS_CHOOSE_USER"),
				"ACTION" => "javascript:SelEl('".CUtil::JSEscape($f_ID)."', '".htmlspecialcharsbx(CUtil::JSEscape($arRes["NAME"]), ENT_QUOTES)."')",
			]; 

			$row->AddActions($arActions);

        }

    }
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?>

<?if(!empty($fId) && !empty($sCl)):?>
<script>
	function SelEl(id, name)
	{
		let el, snEl;
		el = window.opener.document.getElementById('<?echo $fId?>');

		if(el){
			el.value = id;
		}

		snEl = window.opener.document.querySelector('.<?=$sCl?>');
		if(snEl){
			snEl.innerHTML = name;
		}

		window.close();
	}	
</script>
<?
$lAdmin->CheckListMode();
$lAdmin->DisplayList();
?>
<?else:?>
	<?showError(GetMessage('COMMENT_ENTITYS_SEARCH_EMPTY_PARAMS'));?>
<?endif?>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");