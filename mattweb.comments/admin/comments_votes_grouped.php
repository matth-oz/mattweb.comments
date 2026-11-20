<?
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */
use Bitrix\Main\Application,
Bitrix\Main\Context, 
Bitrix\Main\Request, 
Bitrix\Main\Localization\Loc,
Bitrix\Iblock\ElementTable,
Bitrix\Main\Config\Option,
Bitrix\Main\Loader,
Mattweb\Comments;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");

$sourceIblockId = (int) Option::get("mattweb.comments", 'source_iblock', '');

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm(Loc::GetMessage("ACCESS_DENIED"));
}

Loader::includeModule("mattweb.comments");
Loader::includeModule("iblock");

$APPLICATION->SetTitle(Loc::GetMessage('COMMENTS_VOTES_GROUPED_TITLE'));

$context = Context::getCurrent();
$request = $context->getRequest();

$queryParams = $request->getQueryList();

$notes = $errors = [];

if(isset($queryParams['action']) && $queryParams['action'] == 'delete_votes'){
    $commId = (int) $queryParams['comm_id'];

    if($commId > 0){
        $rsData = Comments\VoteTable::getList([
            'select' => ['ID', 'COMMENT_ID'],            
            "filter" => ['COMMENT_ID' => $commId],
        ]);

        while($arData = $rsData->fetch()){            
            Comments\VoteTable::delete($arData['ID']);

            $notes[] = Loc::GetMessage('COMMENT_ALL_VOTES_DELETED', ['#COMM_ID#'=>$commId]); 
        }        
    }      
}

$sTableID = "tbl_comments_votes_grouped_entity";
$oSort = new CAdminSorting($sTableID, "ID", "asc");
$lAdmin = new CAdminList($sTableID, $oSort);

$arFilter = [];

$arHeaders = [
	['id'=>'ID', "content"=>"ID", "sort"=>"ID", "default"=>true],
    ['id'=>'COMMENT_ID', "content"=>Loc::GetMessage('COMMENT_VOTE_ENTITY_ID'), "sort"=>"COMMENT_ID", "default"=>true],
    ['id'=>'COMMENT_ELEM_COMMENT', "content"=>Loc::GetMessage('COMMENT_VOTE_COMMENT_ELEM_ENTITY_ID'), "sort"=>"COMMENT_ELEM_COMMENT", "default"=>true],
    // ['id'=>'ARTICLE_NAME', "content"=>Loc::GetMessage('COMMENT_ENTITY_ELEM_NAME'), "sort"=>"ELEM_ID", "default"=>true],
    ['id'=>'COMMENT_VOTES_COUNT', "content"=>Loc::GetMessage('COMMENT_VOTE_VOTES_COUNT'), "sort"=>"COMMENT_VOTES_COUNT", "default"=>true],
];

// $lAdmin->AddHeaders($arHeaders);

$by = mb_strtoupper($oSort->getField());
$order = mb_strtoupper($oSort->getOrder());
$getListOrder = [
	$by => $order,
];

if ($by !== 'ID')
{
	$getListOrder['ID'] = 'ASC';
}

$rsData = Comments\VoteTable::getList([
	'select' => ['ID', 'COMMENT_ID', 'COMMENT_ELEM_' => 'COMMENT', 'USER_ID', 'ELEM_ID', 'IP', 'CREATED_AT', 'VALUE'],
	"order" => $getListOrder,
	"filter" => $arFilter,
]);

$arElemIds = $arElements = $arVotes = [];

while($arData = $rsData->fetch()){
	if(!in_array($arData['ELEM_ID'], $arElemIds)){
		$arElemIds[] = $arData['ELEM_ID'];
	}

    if($arData['VALUE'] > 0){
        $arVotes[$arData['COMMENT_ID']]['LIKE'] += 1;
    }
    else{
        $arVotes[$arData['COMMENT_ID']]['DISLIKE'] += 1;
    }
}

if(!empty($arElemIds) && $sourceIblockId > 0){
	$rsArtData = ElementTable::getList([
		'order' => ['ID'=>'ASC'],
		'select' => ['ID', 'IBLOCK_ID', 'NAME', 'IBLOCK_SECTION_ID'],
		'filter' => [
			'IBLOCK_ID' => $sourceIblockId,
			'ID' => $arElemIds,
		],
	]);

	while($arArtData = $rsArtData->fetch()){
		$arElements[$arArtData['ID']] = [
			'NAME' => $arArtData['NAME'],
			'SECT_ID' => $arArtData['IBLOCK_SECTION_ID'],
		];
	}
}

if(!empty($arElements)){
	$arHeaders[] = ['id'=>'ARTICLE_NAME', "content"=>Loc::GetMessage('COMMENT_ENTITY_ELEM_NAME'), "sort"=>"ELEM_ID", "default"=>true];	
}

$lAdmin->AddHeaders($arHeaders);


$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();

// build list
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("PAGES")));

$arShowedComments = [];

while($arRes = $rsData->NavNext(true, "f_"))
{
    
    if(in_array($arRes['COMMENT_ID'], $arShowedComments)) continue;
   	$row = &$lAdmin->AddRow($f_ID, $arRes);

	if(!empty($arElements)){
		$arRes['ARTICLE_NAME'] = $arElements[$arRes['ELEM_ID']]['NAME']; 
		$row->AddViewField('ARTICLE_NAME', $arRes['ARTICLE_NAME'].' <a href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.$sourceIblockId.'&type=content&lang=ru&ID='.$arRes['ELEM_ID'].'&find_section_section='.$arElements[$arRes['ELEM_ID']]['SECT_ID'].'">['.$arRes['ELEM_ID'].']</a>');
   	}
	
	$row->AddViewField("COMMENT_ELEM_COMMENT", trim($f_COMMENT_ELEM_COMMENT).' <a href="/bitrix/admin/comment_edit.php?ID='.$f_COMMENT_ID.'&lang=ru">['.$f_COMMENT_ID.']</a>');
    
    $arVotes[$f_COMMENT_ID]['LIKE'] = !empty($arVotes[$f_COMMENT_ID]['LIKE']) ? $arVotes[$f_COMMENT_ID]['LIKE'] : '0';
    $arVotes[$arRes['COMMENT_ID']]['DISLIKE'] = !empty($arVotes[$f_COMMENT_ID]['DISLIKE']) ? $arVotes[$f_COMMENT_ID]['DISLIKE'] : '0';

    $row->AddViewField("COMMENT_VOTES_COUNT", '&#128077; '.$arVotes[$f_COMMENT_ID]['LIKE'].' | &#128078; '.$arVotes[$arRes['COMMENT_ID']]['DISLIKE']);

    $can_edit = true;
	$arActions = [];
    
    $arActions[] = [
		//"ICON"=>"edit",
		"TEXT"=>GetMessage("COMMENT_ALL_VOTES_DETAIL"),
		"ACTION"=>$lAdmin->ActionRedirect("comments_votes.php?action=detail_votes&comm_id=".$f_COMMENT_ID),
	];

    $arActions[] = [
		"ICON"=>"delete",
		"TEXT" => GetMessage("COMMENT_ALL_VOTES_DELETE"),
		"ACTION" => "if(confirm('".GetMessageJS('COMMENT_ALL_VOTES_DELETE_CONFIRM')."')) ".
			$lAdmin->ActionRedirect("comments_votes_grouped.php?action=delete_votes&comm_id=".$f_COMMENT_ID.'&'.bitrix_sessid_get())
	];

    $row->AddActions($arActions);
    $arShowedComments[] = $arRes['COMMENT_ID'];
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
		"TEXT" => GetMessage('COMMENTS_VOTES_LIST_TITLE'),
		"TITLE" => GetMessage('COMMENTS_VOTES_LIST_TITLE'),
		"LINK" => "comments_votes.php?lang=" . LANGUAGE_ID,
		// "ICON" => "btn_new",
	];

	$adminContextMenu = new CAdminContextMenu($aMenu);
	$adminContextMenu->Show();
}

// CheckListMode() — «развилка» по режиму работы списка. В обычном режиме (normal)
// метод не делает ничего и управление идёт дальше, к DisplayList(). А вот в остальных
// случаях он сам формирует ответ и завершает выполнение скрипта через die():
//   list / frame — печатает HTML одной только таблицы (AJAX-ответ) и умирает;
//   excel        — отдаёт заголовки Content-Type: application/vnd.ms-excel,
//                  вызывает DisplayExcel() и умирает.
// Отсюда два правила:
//   1) вызывать его нужно ПОСЛЕ того, как все строки уже добавлены через AddRow(),
//      иначе в Excel и в AJAX уедет пустой список;
//   2) вызывать ДО DisplayList(), иначе список напечатается дважды.
$lAdmin->CheckListMode();

if(!empty($notes)){
    CAdminMessage::ShowNote(join("\n", $notes));
}

if(!empty($errors)){
    CAdminMessage::ShowMessage(join("\n", $errors));
}

// печать таблицы списка вместе с шапкой, постраничной навигацией,
// панелью групповых действий и ошибками фильтра
$lAdmin->DisplayList();

// Парная концовка к прологу выше. До ветки isListMode() выполнение, как правило,
// не доходит: в режиме list скрипт уже завершился внутри CheckListMode().
// Ветка оставлена для полноты — так этот шаблон приведён в документации Битрикса.
if ($lAdmin->isListMode())
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
}
else
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
}
?>