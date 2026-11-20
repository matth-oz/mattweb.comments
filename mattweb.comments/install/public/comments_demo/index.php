<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("description", "Демо-страница статьи с комментариями");
$APPLICATION->SetTitle("Демо-страница статьи с комментариями");
?><?$APPLICATION->IncludeComponent(
	"bitrix:news", 
	"blog_demo", 
	[
		"ADD_ELEMENT_CHAIN" => "N",
		"ADD_SECTIONS_CHAIN" => "Y",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"BROWSER_TITLE" => "-",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "360",
		"CACHE_TYPE" => "N",
		"CHECK_DATES" => "Y",
		"DETAIL_ACTIVE_DATE_FORMAT" => "d.m.Y",
		"DETAIL_DISPLAY_BOTTOM_PAGER" => "Y",
		"DETAIL_DISPLAY_TOP_PAGER" => "N",
		"DETAIL_FIELD_CODE" => [
			0 => "",
			1 => "",
		],
		"DETAIL_PAGER_SHOW_ALL" => "Y",
		"DETAIL_PAGER_TEMPLATE" => "",
		"DETAIL_PAGER_TITLE" => "Страница",
		"DETAIL_PROPERTY_CODE" => [
			0 => "SHOW_COMMENTS",			
			1 => "",
		],
		"DETAIL_SET_CANONICAL_URL" => "N",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"DISPLAY_DATE" => "Y",
		"DISPLAY_NAME" => "Y",
		"DISPLAY_PICTURE" => "Y",
		"DISPLAY_PREVIEW_TEXT" => "Y",
		"DISPLAY_TOP_PAGER" => "N",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"IBLOCK_ID" => "#DEMO_IBLOCK_ID#",
		"IBLOCK_TYPE" => "#DEMO_IBLOCK_TYPE#",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
		"LIST_ACTIVE_DATE_FORMAT" => "j M Y",
		"LIST_FIELD_CODE" => [
			0 => "DATE_CREATE",
			1 => "",
		],
		"LIST_PROPERTY_CODE" => [
			0 => "",
			1 => "",
		],
		"MESSAGE_404" => "",
		"META_DESCRIPTION" => "-",
		"META_KEYWORDS" => "-",
		"NEWS_COUNT" => "20",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => "arrows",
		"PAGER_TITLE" => "Новости",
		"PREVIEW_TRUNCATE_LEN" => "110",
		"SEF_FOLDER" => "/comments_demo/",
		"SEF_MODE" => "Y",
		"SET_LAST_MODIFIED" => "N",
		"SET_STATUS_404" => "Y",
		"SET_TITLE" => "Y",
		"SHOW_404" => "Y",
		"SORT_BY1" => "ACTIVE_FROM",
		"SORT_BY2" => "SORT",
		"SORT_ORDER1" => "DESC",
		"SORT_ORDER2" => "ASC",
		"STRICT_SECTION_CHECK" => "Y",
		"USE_CATEGORIES" => "N",
		"USE_FILTER" => "N",
		"USE_PERMISSIONS" => "N",
		"USE_RATING" => "N",
		"USE_RSS" => "N",
		"USE_SEARCH" => "N",
		"USE_SHARE" => "Y",
		"COMPONENT_TEMPLATE" => "blog_demo",
		"MAX_VOTE" => "5",
		"VOTE_NAMES" => [
			0 => "1",
			1 => "2",
			2 => "3",
			3 => "4",
			4 => "5",
			5 => "",
		],
		"DISPLAY_AS_RATING" => "rating",
		"USE_DETAIL_RATING" => "Y",
		"FILE_404" => "/404.php",
		"SHARE_HIDE" => "N",
		"SHARE_TEMPLATE" => "",
		"SHARE_HANDLERS" => [
			0 => "mailru",
			1 => "vk",
		],
		"SHARE_SHORTEN_URL_LOGIN" => "",
		"SHARE_SHORTEN_URL_KEY" => "",

		"NAME_FIELD_LENGTH" => "50", // mattweb:artcomm.list
		"NEW_COMMENTS_ALLOW_SUBSCR" => "Y", // mattweb:artcomm.list
		"SHOW_COMMENTS_FORM" => "Y", // mattweb:artcomm.list
		"COMMENTS_SYMBOL_COUNTER" => "N", // mattweb:artcomm.list
		"COMMENTS_STYLE" => "COMM_TREE", // mattweb:artcomm.list
		"COMMENTS_FORM_POSITION" => "AFTER_LIST", // mattweb:artcomm.list
		"FORM_REQUIRED_FIELDS" => [
			0 => "NAME",
			1 => "EMAIL",
		], // mattweb:artcomm.list
		"COMMENT_MAX_LENGTH" => "550", // mattweb:artcomm.list
		"COMMENT_MIN_LENGTH" => "10", // mattweb:artcomm.list
		"COMMENTS_SORT" => "COMM_DESC", // mattweb:artcomm.list
		"FORM_CAPTCHA_SK" => "", // mattweb:artcomm.list
		"FORM_CAPTCHA_SCK" => "", // mattweb:artcomm.list
		"SEF_URL_TEMPLATES" => [
			"news" => "",
			"section" => "",
			"detail" => "#ELEMENT_CODE#/",
		]
	],
	false
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>