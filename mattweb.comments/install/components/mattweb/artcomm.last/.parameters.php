<?
/**
 * Created by PhpStorm
 * User: Sergey Pokoev
 * www.pokoev.ru
 * @ Академия 1С-Битрикс - 2015
 * @ academy.1c-bitrix.ru
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

if(!Loader::IncludeModule("mattweb.comments"))
	return;

$commDateFormat = [
	'F1' => '01 Января 1970',
	'F2' => '01.01.1970',
	'F3' => '01.01.1970 01:00',
];

$arComponentParameters = array(
	//"GROUPS" => array(),
	"PARAMETERS" => array(
        "ART_IBLOCK_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("ART_IBLOCK_ID_TITLE"),
			"TYPE" => "STRING",
		),
		"ART_PAGE_URL" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("ART_PAGE_URL_TITLE"),
			"TYPE" => "STRING",
			"DEFAULT" => "/moj-blog/",
		),
		"ART_URL_PARAMS" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("ART_URL_PARAMS_TITLE"),
			"TYPE" => "STRING",			
		),
		"COMM_SHOW_QUANT" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("COMM_SHOW_QUANT_TITLE"),
			"TYPE" => "STRING",
			"DEFAULT" => "5",
		),		
		"COMM_MAX_LENGTH" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("COMM_MAX_LENGTH_TITLE"),
			"TYPE" => "STRING",
			"DEFAULT" => "75",
		),		
		"COMM_DATE_FORMAT" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("COMM_DATE_FORMAT_TITLE"),
			"TYPE" => "LIST",
			"DEFAULT" => "F2",
			"VALUES" => $commDateFormat,
		),

	),
);
?>