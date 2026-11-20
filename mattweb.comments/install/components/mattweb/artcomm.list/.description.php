<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

$arComponentDescription = array(
	"NAME" =>  Loc::GetMessage("ARTCOMM_LIST_COMP_NAME"),
	"DESCRIPTION" => Loc::GetMessage("ARTCOMM_LIST_COMP_DESC"),
	"ICON" => "/images/news_line.gif",
	"SORT" => 20,
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "mattweb_comments",
		"NAME" => Loc::GetMessage('ARTCOMM_PARENT_TITLE')
	),
);
?>