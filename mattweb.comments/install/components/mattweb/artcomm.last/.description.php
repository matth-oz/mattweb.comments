<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

$arComponentDescription = array(
	"NAME" => Loc::GetMessage("ARTCOMM_LAST_COMP_NAME"),
	"DESCRIPTION" => Loc::GetMessage("ARTCOMM_LAST_COMP_DESC"),
	"ICON" => "/images/icon.gif",
	"SORT" => 30,
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "mattweb_comments", // for example "my_project"
		"NAME" => Loc::GetMessage('ARTCOMM_PARENT_TITLE')
	),
);

?>