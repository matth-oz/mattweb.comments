<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arCurrentValues */

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

if(!Loader::IncludeModule("mattweb.comments"))
	return;

// allow_comm_subscr
$isAllowCommSubscr = (Option::get('mattweb.comments', 'allow_comm_subscr') == 'Y') ? true : false;

// allow use smiles
$isAllowUseSmiles = (Option::get('mattweb.comments', 'allow_use_smiles') == 'Y') ? true : false;

// allow use captcha
$isAllowUseCaptcha = (Option::get('mattweb.comments', 'use_captcha') == 'Y') ? true : false;
$captchaSk = Option::get('mattweb.comments', 'captcha_sitekey');
$captchaSck = Option::get('mattweb.comments', 'captcha_secret');


$arCommSort = array(
	'COMM_ASC' => Loc::GetMessage('COMMENTS_SORT_ASC_TITLE'),
	'COMM_DESC'=> Loc::GetMessage('COMMENTS_SORT_DESC_TITLE'),
);

$arCommStyle = array(
	'COMM_TREE' => Loc::GetMessage('COMMENTS_STYLE_TREE_TITLE'),
	'COMM_LIST' => Loc::GetMessage('COMMENTS_STYLE_LIST_TITLE'),
);

$arCommFormPos = array(
	'BEFORE_LIST' => Loc::GetMessage('COMMENTS_FORM_POSITION_BEFORE_TITLE'),
	'AFTER_LIST' => Loc::GetMessage('COMMENTS_FORM_POSITION_AFTER_TITLE'),
);

$arReqFields = array(
	'NAME' => Loc::GetMessage('FORM_REQUIRED_FIELD_NAME_TITLE'),
	'EMAIL' => Loc::GetMessage('FORM_REQUIRED_FIELD_EMAIL_TITLE'),
);

$arComponentParameters = array(
    "PARAMETERS" => array(
        "ART_ELEMENT_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("ART_ELEMENT_ID_TITLE"),
			"TYPE" => "STRING",
		),
		"ART_IBLOCK_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("ART_IBLOCK_ID_TITLE"),
			"TYPE" => "STRING",
		),
		"ART_SECTION_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("ART_SECTION_ID_TITLE"),
			"TYPE" => "STRING",
		),
		"ART_DETAIL_URL" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("ART_DETAIL_URL_TITLE"),
			"TYPE" => "STRING",
		),
		"COMMENTS_SORT" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("COMMENTS_SORT_TITLE"),
			"TYPE" => "LIST",
			"DEFAULT" => "COMM_DESC",
			"VALUES" => $arCommSort,
		),
		"COMMENTS_STYLE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("COMMENTS_STYLE_TITLE"),
			"TYPE" => "LIST",
			"DEFAULT" => "COMM_TREE",
			"VALUES" => $arCommStyle,
		),		
    ),
);

if ($isAllowCommSubscr) {
	$arComponentParameters["PARAMETERS"]["NEW_COMMENTS_ALLOW_SUBSCR"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("NEW_COMMENTS_ALLOW_SUBSCR_TITLE"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "N",
	);
}

if($isAllowUseCaptcha){
	$arComponentParameters["PARAMETERS"]["FORM_CAPTCHA_SK"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("FORM_CAPTCHA_SK_TITLE"),
		"TYPE" => "STRING",
		"DEFAULT" => $captchaSk,
	);

	$arComponentParameters["PARAMETERS"]["FORM_CAPTCHA_SCK"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("FORM_CAPTCHA_SCK_TITLE"),
		"TYPE" => "STRING",
		"DEFAULT" => $captchaSck,
	);
}

$arComponentParameters["PARAMETERS"]["SHOW_COMMENTS_FORM"] = array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("SHOW_COMMENTS_FORM_TITLE"),
	"TYPE" => "CHECKBOX",
	"REFRESH" => "Y",
	"DEFAULT" => "N",
);

if ($arCurrentValues["SHOW_COMMENTS_FORM"] == "Y") {
	
	$arComponentParameters["PARAMETERS"]["COMMENTS_SYMBOL_COUNTER"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("COMMENTS_SYMBOL_COUNTER_TITLE"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "N",
	);

	if($isAllowUseSmiles){
		$arComponentParameters["PARAMETERS"]["COMMENT_ALLOW_USE_SMILES"] = array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("COMMENT_ALLOW_USE_SMILES_TITLE"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		);
	}
	
	$arComponentParameters["PARAMETERS"]["COMMENTS_FORM_POSITION"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("COMMENTS_FORM_POSITION_TITLE"),
		"TYPE" => "LIST",
		"DEFAULT" => "AFTER_LIST",
		"VALUES" => $arCommFormPos,
	);

	$arComponentParameters["PARAMETERS"]["FORM_REQUIRED_FIELDS"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("FORM_REQUIRED_FIELDS_TITLE"),
		"TYPE" => "LIST",
		"DEFAULT" => "",
		"MULTIPLE" => "Y",
		"VALUES" => $arReqFields,
	);

	$arComponentParameters["PARAMETERS"]["NAME_FIELD_LENGTH"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("NAME_FIELD_LENGTH_TITLE"),
		"TYPE" => "STRING",
		"DEFAULT" => "50",
	);

	$arComponentParameters["PARAMETERS"]["COMMENT_MIN_LENGTH"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("COMMENT_MIN_LENGTH_TITLE"),
		"TYPE" => "STRING",
		"DEFAULT" => "10",
	);

	$arComponentParameters["PARAMETERS"]["COMMENT_MAX_LENGTH"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("COMMENT_MAX_LENGTH_TITLE"),
		"TYPE" => "STRING",
		"DEFAULT" => "550",
	);
}
