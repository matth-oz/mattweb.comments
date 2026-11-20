<?

use Bitrix\Main\Localization\Loc;

define('CMM_PERMS_DENY', 'D');
define('CMM_PERMS_READ', 'I');
define('CMM_PERMS_WRITE', 'P');
define('CMM_PERMS_MODERATE', 'T');
define('CMM_PERMS_FULL', 'W');

$GLOBALS['NOT_AUTH_MAX_RIGHTS'] = array(
    CMM_PERMS_DENY => Loc::GetMessage("CMM_P_D"),
    CMM_PERMS_READ => Loc::GetMessage("CMM_P_I"),
    CMM_PERMS_WRITE => Loc::GetMessage("CMM_P_P"),
);

$GLOBALS['AUTH_MAX_RIGHTS'] = array(
    CMM_PERMS_DENY => Loc::GetMessage("CMM_P_D"),
    CMM_PERMS_READ => Loc::GetMessage("CMM_P_I"),
    CMM_PERMS_WRITE => Loc::GetMessage("CMM_P_P"),
    CMM_PERMS_MODERATE => GetMessage("CMM_P_T"),
	CMM_PERMS_FULL => GetMessage("CMM_P_W")
);


$dir = __DIR__;
require_once $dir . '/traits/commentsTrait.php';
require_once $dir . '/traits/commonTrait.php';

CModule::AddAutoloadClasses('mattweb.comments', array(
    'CommentsActions' => 'classes/general/comments.php',
    'SmilesActions' => 'classes/general/smiles.php',
    'VotesActions' => 'classes/general/votes.php',
));
