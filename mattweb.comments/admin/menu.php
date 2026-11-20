<?
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!\Bitrix\Main\Loader::includeModule('mattweb.comments'))
{
	return false;
}

return [
    "parent_menu" => "global_menu_services",
    'section' => '',
	"sort" => 20,
    "text" => Loc::GetMessage("ADM_MENU_COMM_HEADER_TXT"),
    "title" => Loc::GetMessage("ADM_MENU_HEADER_TITLE"),
    "url" => "comments_admin_index.php?lang=".LANGUAGE_ID,
	"icon" => "util_menu_icon",
	"page_icon" => "util_page_icon",
	"items_id" => "menu_cmntadmin",
    "items" => [
        [
            "text" => Loc::GetMessage("ADM_MENU_COMMENTS_LIST_TXT"),
            "url" => "comments_list.php?lang=".LANGUAGE_ID,
            "more_url" => array(),
            "title" => Loc::GetMessage("ADM_MENU_COMMENTS_LIST_TITLE"), 
        ],
        [
            "text" => Loc::GetMessage("ADM_MENU_COMMENTS_VOTES_LIST_TXT"),
            "url" => "comments_votes.php?lang=".LANGUAGE_ID,
            "more_url" => array(),
            "title" => Loc::GetMessage("ADM_MENU_COMMENTS_VOTES_LIST_TITLE"), 
        ],
        [
            "text" => Loc::GetMessage("ADM_MENU_COMMENTS_SMILE_TYPES_LIST_TXT"),
            "url" => "comments_smile_types.php?lang=".LANGUAGE_ID,
            "more_url" => array(),
            "title" => Loc::GetMessage("ADM_MENU_COMMENTS_SMILE_TYPES_LIST_TITLE"), 
        ],
        [
            "text" => Loc::GetMessage("ADM_MENU_COMMENTS_SMILES_LIST_TXT"),
            "url" => "comments_smiles.php?lang=".LANGUAGE_ID,
            "more_url" => array(),
            "title" => Loc::GetMessage("ADM_MENU_COMMENTS_SMILES_LIST_TITLE"), 
        ],
    ],
];