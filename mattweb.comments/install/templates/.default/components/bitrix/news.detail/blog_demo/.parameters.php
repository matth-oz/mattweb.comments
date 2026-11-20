<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arTemplateParameters = array(
	"DISPLAY_DATE" => Array(
		"NAME" => GetMessage("T_IBLOCK_DESC_NEWS_DATE"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "Y",
	),
	"DISPLAY_NAME" => Array(
		"NAME" => GetMessage("T_IBLOCK_DESC_NEWS_NAME"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "Y",
	),
	"DISPLAY_PICTURE" => Array(
		"NAME" => GetMessage("T_IBLOCK_DESC_NEWS_PICTURE"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "Y",
	),
	"DISPLAY_PREVIEW_TEXT" => Array(
		"NAME" => GetMessage("T_IBLOCK_DESC_NEWS_TEXT"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "Y",
	),
	"USE_SHARE" => Array(
		"NAME" => GetMessage("T_IBLOCK_DESC_NEWS_USE_SHARE"),
		"TYPE" => "CHECKBOX",
		"MULTIPLE" => "N",
		"VALUE" => "Y",
		"DEFAULT" =>"N",
		"REFRESH"=> "Y",
	),	
);

if ($arCurrentValues["USE_SHARE"] == "Y")
{
	$arTemplateParameters["SHARE_HIDE"] = array(
		"NAME" => GetMessage("T_IBLOCK_DESC_NEWS_SHARE_HIDE"),
		"TYPE" => "CHECKBOX",
		"VALUE" => "Y",
		"DEFAULT" => "N",
	);

	$arTemplateParameters["SHARE_TEMPLATE"] = array(
		"NAME" => GetMessage("T_IBLOCK_DESC_NEWS_SHARE_TEMPLATE"),
		"DEFAULT" => "",
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"COLS" => 25,
		"REFRESH"=> "Y",
	);
	
	if (trim($arCurrentValues["SHARE_TEMPLATE"]) == '')
		$shareComponentTemlate = false;
	else
		$shareComponentTemlate = trim($arCurrentValues["SHARE_TEMPLATE"]);

	include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/main.share/util.php");

	$arHandlers = __bx_share_get_handlers($shareComponentTemlate);

	$arTemplateParameters["SHARE_HANDLERS"] = array(
		"NAME" => GetMessage("T_IBLOCK_DESC_NEWS_SHARE_SYSTEM"),
		"TYPE" => "LIST",
		"MULTIPLE" => "Y",
		"VALUES" => $arHandlers["HANDLERS"],
		"DEFAULT" => $arHandlers["HANDLERS_DEFAULT"],
	);

	$arTemplateParameters["SHARE_SHORTEN_URL_LOGIN"] = array(
		"NAME" => GetMessage("T_IBLOCK_DESC_NEWS_SHARE_SHORTEN_URL_LOGIN"),
		"TYPE" => "STRING",
		"DEFAULT" => "",
	);
	
	$arTemplateParameters["SHARE_SHORTEN_URL_KEY"] = array(
		"NAME" => GetMessage("T_IBLOCK_DESC_NEWS_SHARE_SHORTEN_URL_KEY"),
		"TYPE" => "STRING",
		"DEFAULT" => "",
	);
}

// Комментарии к статье
if(!Loader::IncludeModule("mattweb.comments"))
	return;

// allow_comm_subscr
$isAllowCommSubscr = (Option::get('mattweb.comments', 'allow_comm_subscr') == 'Y') ? true : false;

$arCommFormPos = array(
	'BEFORE_LIST' => Loc::GetMessage('COMMENTS_FORM_POSITION_BEFORE_TITLE'),
	'AFTER_LIST' => Loc::GetMessage('COMMENTS_FORM_POSITION_AFTER_TITLE'),
);

$arCommSort = array(
	'COMM_ASC' => Loc::GetMessage('COMMENTS_SORT_ASC_TITLE'),
	'COMM_DESC'=> Loc::GetMessage('COMMENTS_SORT_DESC_TITLE'),
);

$arCommStyle = array(
	'COMM_TREE' => Loc::GetMessage('COMMENTS_STYLE_TREE_TITLE'),
	'COMM_LIST' => Loc::GetMessage('COMMENTS_STYLE_LIST_TITLE'),
);

$arReqFields = array(
	'NAME' => Loc::GetMessage('FORM_REQUIRED_FIELD_NAME_TITLE'),
	'EMAIL' => Loc::GetMessage('FORM_REQUIRED_FIELD_EMAIL_TITLE'),
);

$arTemplateParameters["SHOW_COMMENTS_FORM"] = array(
	"PARENT" => "ADDITIONAL_SETTINGS",
	"NAME" => GetMessage("SHOW_COMMENTS_FORM_TITLE"),
	"TYPE" => "CHECKBOX",
	"REFRESH" => "Y",
	"DEFAULT" => "N",
);

if ($arCurrentValues["SHOW_COMMENTS_FORM"] == "Y")
{
	$arTemplateParameters["COMMENTS_SYMBOL_COUNTER"] = array(
		"PARENT" => "ADDITIONAL_SETTINGS",
		"NAME" => GetMessage("COMMENTS_SYMBOL_COUNTER_TITLE"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "N",
	);

	$arTemplateParameters["COMMENTS_STYLE"] = array(
		"PARENT" => "ADDITIONAL_SETTINGS",
		"NAME" => GetMessage("COMMENTS_STYLE_TITLE"),
		"TYPE" => "LIST",
		"DEFAULT" => "COMM_TREE",
		"VALUES" => $arCommStyle,

	);

	$arTemplateParameters["COMMENTS_FORM_POSITION"] = array(
		"PARENT" => "ADDITIONAL_SETTINGS",
		"NAME" => GetMessage("COMMENTS_FORM_POSITION_TITLE"),
		"TYPE" => "LIST",
		"DEFAULT" => "AFTER_LIST",
		"VALUES" => $arCommFormPos,
	);

	$arTemplateParameters["FORM_REQUIRED_FIELDS"] = array(
		"PARENT" => "ADDITIONAL_SETTINGS",
		"NAME" => GetMessage("FORM_REQUIRED_FIELDS_TITLE"),
		"TYPE" => "LIST",
		"DEFAULT" => "",
		"MULTIPLE" => "Y",
		"VALUES" => $arReqFields,
	);

	$arTemplateParameters["COMMENT_MAX_LENGTH"] = array(
		"PARENT" => "ADDITIONAL_SETTINGS",
		"NAME" => GetMessage("COMMENT_MAX_LENGTH_TITLE"),
		"TYPE" => "STRING",
		"DEFAULT" => "550",
	);

	$arTemplateParameters["COMMENT_MIN_LENGTH"] = array(
		"PARENT" => "ADDITIONAL_SETTINGS",
		"NAME" => GetMessage("COMMENT_MIN_LENGTH_TITLE"),
		"TYPE" => "STRING",
		"DEFAULT" => "10",
	);

}

$arTemplateParameters["COMMENTS_SORT"] = array(
	"PARENT" => "ADDITIONAL_SETTINGS",
	"NAME" => GetMessage("COMMENTS_SORT_TITLE"),
	"TYPE" => "LIST",
	"DEFAULT" => "COMM_DESC",
	"VALUES" => $arCommSort,
);

$arTemplateParameters["NAME_FIELD_LENGTH"] = array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("NAME_FIELD_LENGTH_TITLE"),
	"TYPE" => "STRING",
	"DEFAULT" => "50",
);

if ($isAllowCommSubscr) {
	$arTemplateParameters["NEW_COMMENTS_ALLOW_SUBSCR"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("NEW_COMMENTS_ALLOW_SUBSCR_TITLE"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "N",
	);
}

?>

