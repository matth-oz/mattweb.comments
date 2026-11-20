<?
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */
use Bitrix\Main\Application,
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

$APPLICATION->SetTitle(Loc::GetMessage('COMMENTS_LIST_TITLE'));

$sTableID = "tbl_comments_entity";
$oSort = new CAdminSorting($sTableID, "ID", "asc");
$lAdmin = new CAdminList($sTableID, $oSort);

// ******************************************************************** //
//                     ФИЛЬТР: ИНИЦИАЛИЗАЦИЯ                            //
// ******************************************************************** //

// поля фильтра. Имена должны совпадать с атрибутами name полей формы find_form
$FilterArr = [
	'find_id',
	'find_article_id',
	'find_published',
];

// InitFilter создаёт глобальные переменные $find_id, $find_article_id, $find_published
// и хранит их значения в сессии между переходами по страницам списка
$lAdmin->InitFilter($FilterArr);

// проверку значений фильтра для удобства вынесем в отдельную функцию
function CheckFilter()
{
	global $lAdmin;

	// значения фильтра лежат в глобальных переменных, созданных InitFilter
	$findId = trim((string)($GLOBALS['find_id'] ?? ''));
	$findArticleId = trim((string)($GLOBALS['find_article_id'] ?? ''));

	if ($findId !== '' && !preg_match('/^\d+$/', $findId))
	{
		$lAdmin->AddFilterError(Loc::GetMessage('COMMENTS_FLT_ERR_ID'));
	}

	if ($findArticleId !== '' && !preg_match('/^\d+$/', $findArticleId))
	{
		$lAdmin->AddFilterError(Loc::GetMessage('COMMENTS_FLT_ERR_ARTICLE_ID'));
	}

	return count($lAdmin->arFilterErrors) == 0; // если ошибки есть, вернем false
}

$find_id = $find_id ?? '';
$find_article_id = $find_article_id ?? '';
$find_published = $find_published ?? '';

$arFilter = [];

// если все значения фильтра корректны, обработаем его.
// В фильтр попадают только заполненные поля: пустая строка в ORM превратилась бы
// в условие ID = '' и выборка стала бы пустой.
if (CheckFilter())
{
    if ((string)$find_id !== '')
    {
        $arFilter['ID'] = (int)$find_id;
    }

    if ((string)$find_article_id !== '')
    {
        $arFilter['ELEM_ID'] = (int)$find_article_id;
    }

    if ((string)$find_published !== '')
    {
        $arFilter['PUBLISHED'] = (int)$find_published;
    }
}

$arHeaders = [
	['id'=>'ID', "content"=>"ID", "sort"=>"ID", "default"=>true],
	['id'=>'COMMENT', "content"=>Loc::GetMessage('COMMENT_ENTITY_COMMENT_FIELD'), "sort"=>"", "default"=>true],
	['id'=>'DEPTH_LEVEL', "content"=>Loc::GetMessage('COMMENT_ENTITY_DEPTH_LEVEL_FIELD'), "sort"=>"DEPTH_LEVEL", "default"=>true],
	['id'=>'IS_PARENT', "content"=>Loc::GetMessage('COMMENT_ENTITY_IS_PARENT_FIELD'), "sort"=>"IS_PARENT", "default"=>true],
	['id'=>'PARENT_ID', "content"=>Loc::GetMessage('COMMENT_ENTITY_PARENT_ID_FIELD'), "sort"=>"PARENT_ID", "default"=>true],
	['id'=>'CREATED_AT', "content"=>Loc::GetMessage('COMMENT_ENTITY_CREATED_AT_FIELD'), "sort"=>"CREATED_AT", "default"=>true],
	['id'=>'PUBLISHED', "content"=>Loc::GetMessage('COMMENT_ENTITY_PUBLISHED_FIELD'), "sort"=>"PUBLISHED", "default"=>true],
	['id'=>'DEL_MARKED', "content"=>Loc::GetMessage('COMMENT_ENTITY_DEL_MARKED_FIELD'), "sort"=>"DEL_MARKED", "default"=>true],
	['id'=>'USER_ID', "content"=>Loc::GetMessage('COMMENT_ENTITY_USER_ID_FIELD'), "sort"=>"USER_ID", "default"=>true],
	['id'=>'SUBSCRIBE', "content"=>Loc::GetMessage('COMMENT_ENTITY_SUBSCRIBE_FIELD'), "sort"=>"SUBSCRIBE", "default"=>true],
	
];

$by = mb_strtoupper($oSort->getField());
$order = mb_strtoupper($oSort->getOrder());
$getListOrder = [
	$by => $order,
];

if ($by !== 'ID')
{
	$getListOrder['ID'] = 'ASC';
}

$rsData = Comments\CommentTable::getList([
	//'select' => $lAdmin->GetVisibleHeaderColumns(),
	'select' => ['ID', 'ELEM_ID', 'PARENT_ID', 'DEPTH_LEVEL', 'CREATED_AT', 'PUBLISHED', 'DEL_MARKED', 'USER_ID', 'COMMENT', 'SUBSCRIBE'],
	"order" => $getListOrder,
	"filter" => $arFilter,
]);

$arElemIds = $arElements = $arParents = [];

while($arData = $rsData->fetch()){
	if(!in_array($arData['ELEM_ID'], $arElemIds)){
		$arElemIds[] = $arData['ELEM_ID'];
	}

	if(isset($arData['PARENT_ID']) && !in_array($arData['PARENT_ID'], $arParents)){
		$arParents[] = $arData['PARENT_ID'];
	}
}

sort($arParents);

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

while($arRes = $rsData->NavNext(true, "f_"))
{
	$row = &$lAdmin->AddRow($f_ID, $arRes);
	
	if(!empty($arElements)){
		$arRes['ARTICLE_NAME'] = $arElements[$arRes['ELEM_ID']]['NAME'];
		$row->AddViewField('ARTICLE_NAME', $arRes['ARTICLE_NAME'].' <a href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.$sourceIblockId.'&type=content&lang=ru&ID='.$arRes['ELEM_ID'].'&find_section_section='.$arElements[$arRes['ELEM_ID']]['SECT_ID'].'">['.$arRes['ELEM_ID'].']</a>');
	}

	$row->AddViewField("PARENT_ID", $f_PARENT_ID !="0" ? '<a href="/bitrix/admin/comments_list.php?ID='.$f_ID.'&lang='.LANG.'">'.$f_PARENT_ID.'</a>' : '-');
	
	$row->AddViewField("IS_PARENT", in_array($f_ID, $arParents) ? GetMessage("COMM_YES_NO_YES") : GetMessage("COMM_YES_NO_NO"));
	
	$row->AddViewField("PUBLISHED", $f_PUBLISHED=="1" ? GetMessage("COMM_YES_NO_YES") : GetMessage("COMM_YES_NO_NO"));

	$row->AddViewField("DEL_MARKED", $f_DEL_MARKED=="1" ? GetMessage("COMM_YES_NO_YES") : GetMessage("COMM_YES_NO_NO"));

	$row->AddViewField("SUBSCRIBE", $f_SUBSCRIBE=="1" ? GetMessage("COMM_YES_NO_YES") : GetMessage("COMM_YES_NO_NO"));

	$row->AddViewField("COMMENT", html_entity_decode($f_COMMENT));

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


	if($f_PUBLISHED == "0"){
		$arActions[] = [
			"ICON"=>"publish",
			"TEXT" => GetMessage("MAIN_ADMIN_MENU_PUBLISH"),
			"ACTION" => $lAdmin->ActionRedirect("comment_edit.php?action=publish&ID=".$f_ID.'&'.bitrix_sessid_get())
		];
	}
	else{
		$actionStr = $lAdmin->ActionRedirect("comment_edit.php?action=unpublish&ID=".$f_ID.'&'.bitrix_sessid_get());
		if($f_DEPTH_LEVEL > 1){
			$actionStr = "if(confirm('".GetMessageJS('COMMENTS_ADMIN_UNPUBLISH_CHAIN_COMMENT_CONFIRM')."')) ".$actionStr;
		}

		$arActions[] = [
			"ICON"=>"unpublish",
			"TEXT" => GetMessage("MAIN_ADMIN_MENU_UNPUBLISH"),
			"ACTION" => $actionStr,
		];
	}

	$row->AddActions($arActions);
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

	// menu
	$aMenu = [];
	$aMenu[] = [
		"TEXT" => GetMessage('COMMENTS_COMMENT_ADD_TITLE'),
		"TITLE" => GetMessage('COMMENTS_COMMENT_ADD_TITLE'),
		"LINK" => "comment_edit.php?lang=" . LANGUAGE_ID,
		"ICON" => "btn_new",
	];

	$session = Application::getInstance()->getSession();

	$commsMess = $session->get('COMMS_CHANGED_MESS');	
	if(!empty($commsMess)){
		CAdminMessage::ShowNote($commsMess);
		$session->remove('COMMS_CHANGED_MESS');
	}

	$commsMess = $session->get('COMMENT_ENTITYS_ADDED');
	if(!empty($commsMess)){
		CAdminMessage::ShowNote($commsMess);
		$session->remove('COMMENT_ENTITYS_ADDED');
	}

	$commsMess = $session->get('COMMENT_ENTITYS_UPDATED');
	if(!empty($commsMess)){
		CAdminMessage::ShowNote($commsMess);
		$session->remove('COMMENT_ENTITYS_UPDATED');
	}

	$adminContextMenu = new CAdminContextMenu($aMenu);
	$adminContextMenu->Show();

	// **************************************************************** //
	//                        ФИЛЬТР: ВЫВОД                             //
	// **************************************************************** //
	// Второй параметр — список полей для меню «Добавить поле».
	// Количество и порядок его элементов должны совпадать
	// со строками <tr> между Begin() и Buttons().
	$oFilter = new CAdminFilter(
		$sTableID."_filter",
		[
			'find_id' => 'ID',
			'find_article_id' => Loc::GetMessage('FIND_ARTICLE_ID'),
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
		<td nowrap><?=Loc::GetMessage('FIND_ARTICLE_ID')?>:</td>
		<td nowrap><input type="text" name="find_article_id" size="47" value="<?=htmlspecialcharsbx((string)$find_article_id)?>"></td>
	</tr>
	<tr>
		<td nowrap><?=Loc::GetMessage('FIND_PUBLISHED')?>:</td>
		<td nowrap><?
			$arPublishedRef = [
				"reference" => [GetMessage("COMM_YES_NO_YES"), GetMessage("COMM_YES_NO_NO")],
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
	<?
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
