<?
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */
use Bitrix\Main\Application,
Bitrix\Main\Context, 
Bitrix\Main\Request, 
Bitrix\Main\Localization\Loc,
Bitrix\Iblock\ElementTable,
Bitrix\Main\Loader,
Bitrix\Main\Config\Option,
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

$APPLICATION->SetTitle(Loc::GetMessage('COMMENTS_VOTES_TITLE'));

$context = Context::getCurrent();
$request = $context->getRequest();

$queryParams = $request->getQueryList();

$action = '';
if(isset($queryParams['action']) && trim($queryParams['action']) == 'detail_votes'){
    $commId = (int) $queryParams['comm_id'];

    if($commId > 0){
		$arCommDetail = Comments\CommentTable::getById($commId)->fetch();
		$action = trim($queryParams['action']);
	}
}

$sTableID = "tbl_comments_votes_entity";
$oSort = new CAdminSorting($sTableID, "ID", "asc");
$lAdmin = new CAdminList($sTableID, $oSort);

$arFilter = [];

if($action == 'detail_votes'){
	$arFilter = ['COMMENT_ID' => $arCommDetail['ID']];
	$arHeaders = [
		['id'=>'ID', "content"=>"ID", "sort"=>"ID", "default"=>true],
		['id'=>'USER_ID', "content"=>Loc::GetMessage('COMMENT_VOTE_ENTITY_USER_ID'), "sort"=>"USER_ID", "default"=>true],
		['id'=>'IP', "content"=>Loc::GetMessage('COMMENT_VOTE_ENTITY_IP'), "sort"=>"IP", "default"=>true],
		['id'=>'CREATED_AT', "content"=>Loc::GetMessage('COMMENT_VOTE_ENTITY_CREATED_AT'), "sort"=>"CREATED_AT", "default"=>true],
		['id'=>'VOTE_VALUE_TXT', "content"=>Loc::GetMessage('COMMENT_VOTE_ENTITY_VALUE'), "sort"=>"VOTE_VALUE_TXT", "default"=>true],
	];
}
else{
	$arHeaders = [
		['id'=>'ID', "content"=>"ID", "sort"=>"ID", "default"=>true],
		// ['id'=>'COMMENT_ID', "content"=>Loc::GetMessage('COMMENT_VOTE_ENTITY_ID'), "sort"=>"COMMENT_ID", "default"=>true],
		['id'=>'COMMENT_ELEM_COMMENT', "content"=>Loc::GetMessage('COMMENT_VOTE_COMMENT_ELEM_ENTITY_ID'), "sort"=>"COMMENT_ELEM_COMMENT", "default"=>true],
		// ['id'=>'USER_ID', "content"=>Loc::GetMessage('COMMENT_VOTE_ENTITY_USER_ID'), "sort"=>"USER_ID", "default"=>true],
		// ['id'=>'ELEM_ID', "content"=>Loc::GetMessage('COMMENT_VOTE_ENTITY_ELEM_ID'), "sort"=>"ELEM_ID", "default"=>true],
		// ['id'=>'ARTICLE_NAME', "content"=>Loc::GetMessage('COMMENT_ENTITY_ELEM_NAME'), "sort"=>"ELEM_ID", "default"=>true],
		// ['id'=>'IP', "content"=>Loc::GetMessage('COMMENT_VOTE_ENTITY_IP'), "sort"=>"IP", "default"=>true],
		['id'=>'CREATED_AT', "content"=>Loc::GetMessage('COMMENT_VOTE_ENTITY_CREATED_AT'), "sort"=>"CREATED_AT", "default"=>true],
		// ['id'=>'VALUE', "content"=>Loc::GetMessage('COMMENT_VOTE_ENTITY_VALUE'), "sort"=>"VALUE", "default"=>true],
		['id'=>'VOTE_VALUE_TXT', "content"=>Loc::GetMessage('COMMENT_VOTE_ENTITY_VALUE'), "sort"=>"VOTE_VALUE_TXT", "default"=>true],
	];
}

//$lAdmin->AddHeaders($arHeaders);

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

$arElemIds = $arElements = [];

while($arData = $rsData->fetch()){
	if(!in_array($arData['ELEM_ID'], $arElemIds)){
		$arElemIds[] = $arData['ELEM_ID'];
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



if(!empty($arElements) && $action != 'detail_votes'){
	$arHeaders[] = ['id'=>'ARTICLE_NAME', "content"=>Loc::GetMessage('COMMENT_ENTITY_ELEM_NAME'), "sort"=>"ELEM_ID", "default"=>true];	
}
$lAdmin->AddHeaders($arHeaders);

$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();

// build list
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("PAGES")));

while($arRes = $rsData->NavNext(true, "f_"))
{
	$row = &$lAdmin->AddRow($f_ID, $arRes);
	
	if(!empty($arElements)){
		$arRes['ARTICLE_NAME'] = $arElements[$arRes['ELEM_ID']]['NAME'];	
		$row->AddViewField('ARTICLE_NAME', $arRes['ARTICLE_NAME'].' <a href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.$sourceIblockId.'&type=content&lang=ru&ID='.$arRes['ELEM_ID'].'&find_section_section='.$arElements[$arRes['ELEM_ID']]['SECT_ID'].'">['.$arRes['ELEM_ID'].']</a>');
	}

	$row->AddViewField("VOTE_VALUE_TXT", $f_VALUE == 1 ? '&#128077;' : '&#128078;');
	
	$row->AddViewField("COMMENT_ELEM_COMMENT", trim($f_COMMENT_ELEM_COMMENT).' <a href="/bitrix/admin/comment_edit.php?ID='.$f_COMMENT_ID.'&lang=ru">['.$f_COMMENT_ID.']</a>');
	
	$can_edit = true;
	$arActions = [];

    $arActions[] = [
		"ICON"=>"edit",
		"TEXT"=>GetMessage($can_edit ? "MAIN_ADMIN_MENU_EDIT" : "MAIN_ADMIN_MENU_VIEW"),
		"ACTION"=>$lAdmin->ActionRedirect("comment_edit.php?ID=".$f_ID)
	];

	$arActions[] = [
		"ICON"=>"delete",
		"TEXT" => GetMessage("MAIN_ADMIN_MENU_DELETE"),
		"ACTION" => "if(confirm('".GetMessageJS('COMMENTS_ADMIN_DELETE_COMMENT_CONFIRM')."')) ".
			$lAdmin->ActionRedirect("comment_edit.php?action=delete&ID=".$f_ID.'&'.bitrix_sessid_get())
	];
}

// ******************************************************************** //
//                             ВЫВОД                                    //
// ******************************************************************** //
// CAdminList работает в нескольких режимах. Режим берётся из GET-параметра mode
// (см. CAdminList::getModeList(), bitrix/modules/main/interface/admin_list.php):
//   normal   — обычная страница: шапка админки, меню, фильтр, список (режим по умолчанию);
//   list     — AJAX-перерисовка ТОЛЬКО таблицы списка (сортировка, переход по страницам,
//              настройка колонок) — браузер уже показывает страницу, ему нужен один HTML таблицы;
//   frame    — AJAX-ответ на групповое действие над отмеченными строками;
//   excel    — выгрузка списка в файл Excel;
//   settings — окно настройки колонок списка.
//
// isListMode() === true означает именно режим list, то есть «отдать голую таблицу».
// Полная шапка админки в этом случае не нужна и только сломала бы ответ, поэтому
// вместо prolog_admin_after.php подключается prolog_admin_js.php — минимальный пролог,
// который ничего не печатает, а лишь проверяет авторизацию.
if ($lAdmin->isListMode())
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
}
else
{
	// обычный режим: печатаем шапку админки, контекстное меню и фильтр
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");


if($action == 'detail_votes'){
	?>
	<style>
		.comment-detail-wrap{
			font-size: 13px;
			background: -webkit-linear-gradient(top, rgba(244, 233, 141, .3), rgba(232, 209, 62, .3), rgba(225, 194, 40, .3));
			border-radius: 5px;
			border: 1px solid;
			border-color: #d3c6a3 #cabc90 #c1b37f #c9bc8f;
			-webkit-box-shadow: inset 0 1px 0 #fff;
			box-shadow: inset 0 1px 0 #fff;
			color: #716536;
			display: inline-block;
			margin: 16px 0;
			font-size: 13px;
			line-height: 18px;
			text-shadow: 0 1px 0 rgba(255, 255, 255, 0.7);
			padding: 15px 30px 15px 18px;
		}
		.comment-detail-ttl{
			font-weight: bold;
		}
	</style>
	<div class="comment-detail-wrap">
		<h3>Комментарий #<?=$arCommDetail['ID']?></h3>
		<p><span class="comment-detail-ttl"><?=Loc::GetMessage('COMMENT_VOTE_COMMENT_ENTITY_NAME_FIELD')?>:</span>&nbsp;<?=$arCommDetail['NAME']?></p>
		<p><span class="comment-detail-ttl"><?=Loc::GetMessage('COMMENT_VOTE_COMMENT_ENTITY_EMAIL_FIELD')?>:</span>&nbsp;<?=$arCommDetail['EMAIL']?></p>		
		<p><span class="comment-detail-ttl"><?=Loc::GetMessage('COMMENT_VOTE_COMMENT_ENTITY_CREATED_AT_FIELD')?>:</span>&nbsp;<?=$arCommDetail['CREATED_AT']?></p>
		<p><span class="comment-detail-ttl"><?=Loc::GetMessage('COMMENT_VOTE_COMMENT_ELEM_ENTITY_ID')?>:</span>&nbsp;<?=$arCommDetail['COMMENT']?></p>		
	</div>	
	<?
}


	// menu
	$aMenu = [];
	$aMenu[] = [
		"TEXT" => GetMessage('COMMENTS_VOTES_GROUPED_TITLE'),
		"TITLE" => GetMessage('COMMENTS_VOTES_GROUPED_TITLE'),
		"LINK" => "comments_votes_grouped.php?lang=" . LANGUAGE_ID,
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

if($action == 'detail_votes'){?>
<h3>Голоса за комментарий #<?=$arCommDetail['ID']?></h3>
<?}

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
