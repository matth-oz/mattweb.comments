<?
/** @global CMain $APPLICATION */
define('STOP_STATISTICS', true);
define('PUBLIC_AJAX_MODE', true);
define('NOT_CHECK_PERMISSIONS', true);

use \Bitrix\Main,
    \Bitrix\Main\Context,
    \Bitrix\Main\Engine\CurrentUser,
    \Bitrix\Main\Application,
    \Bitrix\Main\Loader,
    \Bitrix\Main\Localization\Loc,
    \Mattweb\Comments;

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

Loc::loadMessages(__FILE__);

$arResult = [];
//$arResult['result']; // success | error
//$arResult['res_message']; //array

if(isset($_POST['AJAX']) && $_POST['AJAX'] == 'Y'){

    // add | reply | vote
    if(isset($_POST['action']) && $_POST['action'] == 'vote'){
        $voteType = trim($_POST['voteType']);
        $elemID = intval($_POST['artElemID']);
        $commentID = intval($_POST['commentID']);
        $userID = trim($_POST['userVoteID']);

        if($commentID > 0){
          Loader::includeModule('mattweb.comments');
          $arComms = [$commentID];

          $arVotes = VotesActions::getVotesList($arComms); 
          
          
            if(!empty($arVotes)){
                if($voteType == 'like' && array_key_exists('LIKES', $arVotes[$commentID])){
                    $curLikesQuant = $arVotes[$commentID]['LIKES'] + 1;
                }else{
                    $curLikesQuant = 1;
                }

                if($voteType == 'dislike' && array_key_exists('DISLIKES', $arVotes[$commentID])){
                    $curDislikesQuant = $arVotes[$commentID]['DISLIKES'] + 1;
                }else{
                    $curDislikesQuant = 1;
                }
            }
            else{

                if($voteType == 'like') $curLikesQuant = 1;
                if($voteType == 'dislike') $curDislikesQuant = 1;
            }

            $arVoteFields = [];
            $arVoteFields['COMMENT_ID'] = $commentID;
            // $arVoteFields['USER_ID'] = VotesActions::getUserVoteID();
            $arVoteFields['USER_ID'] = $userID;
            $arVoteFields['ELEM_ID'] = $elemID;
            $request = Context::getCurrent()->getRequest();
            $arVoteFields['IP'] = $request->getRemoteAddress();
            $arVoteFields['VALUE'] = ($voteType == 'like') ? 1 : -1;

            
            try{
                $result = Comments\VoteTable::add($arVoteFields);
                if($result->isSuccess()){
                    $arResult['result'] = 'success'; // success
                    if($voteType == 'like'){
                        $arResult['res_likes'] = $curLikesQuant;
                    }

                    if($voteType == 'dislike'){
                        $arResult['res_dislikes'] = $curDislikesQuant;
                    }
                }
                else{
                    $arResult['result'] = 'error'; // error
                    $arResult['res_message'][] = $result->getErrorMessages();
                }
            }
            catch(Exception $e){
                $arResult['result'] = 'error'; // error
                $arResult['res_message'][] = $e->getMessage();
            }
        }
        else{
            $arResult['result'] = 'error'; // error
            $arResult['res_message'][] = Loc::GetMessage('COMM_ADD_EMPTY_COMMENT_ID');
        }
        
    }
    else{
        $_POST['action'] = (intval($_POST['reply-id']) > 0) ? 'reply' : 'add';    
        if($_POST['action'] == 'add' || $_POST['action'] == 'reply'){           

            if(check_bitrix_sessid()){
                $allowToSend = true;

                $nameMaxLength = isset($_REQUEST['name_max']) ? $_REQUEST['name_max'] : 50;
                if(strlen(trim($_POST['name'])) > $nameMaxLength){
                    $allowToSend = false;
                    $arResult['result'] = 'error'; // error
                    $arResult['res_message'][] = Loc::GetMessage('COMM_ADD_NAME_LONGER_LIMIT'); 
                }

                $commMinLength = isset($_REQUEST['comm_min']) ? $_REQUEST['comm_min'] : 10;
                if(strlen(trim($_POST['message'])) < $commMinLength){
                    $allowToSend = false;
                    $arResult['result'] = 'error'; // error
                    $arResult['res_message'][] = Loc::GetMessage('COMM_ADD_MESS_SHORTER_LIMIT'); 
                }

                $commMaxLength = isset($_REQUEST['comm_max']) ? $_REQUEST['comm_max'] : 550;
                if(strlen(trim($_POST['message'])) > $commMaxLength){
                    $_POST['message'] = TruncateText($_POST['message'], $commMaxLength);
                }                
                
                
                if (isset($_POST['commcaptcha'])) {
                    // Build POST request
                    $recaptcha_secret = $_POST['recaptchaSCK'];
                    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
                    $recaptcha_response = $_POST['commcaptcha'];

                    // Make and decode POST request
                    $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
                    $recaptcha = json_decode($recaptcha);

                    if ($recaptcha->score < 0.5) {
                        $allowToSend = false;
                        $arResult['result'] = 'error';
                        $arResult['res_message'][] = Loc::GetMessage('COMM_ADD_RECAPTCHA_FAILED');
                    }

                }

                if($allowToSend === true){
                    Loader::includeModule('mattweb.comments');                    

                    $arFields = [];
                    $arFields['IBLOCK_ID'] = intval($_POST['iblock']);
                    $arFields['SECT_ID'] = intval($_POST['sect']);
                    $arFields['ELEM_ID'] = intval($_POST['elem']);
                    $arFields['NAME'] = $_POST['name'];
                    $arFields['EMAIL'] = $_POST['email'];


                    $arFields['HOMEPAGE'] = '';

                    $commentsUID = (CommentsActions::getCommentUserID() !== false) ? CommentsActions::getCommentUserID() : '';

                    $arFields['USERNAME'] = $commentsUID; 
                    
                    $curUserId = CurrentUser::get()->getId();
                    $arFields['USER_ID'] = (int) $curUserId;

                    $request = Context::getCurrent()->getRequest();
                    $arFields['IP'] = $request->getRemoteAddress();

                    $arFields['COMMENT'] = trim($_POST['message']);
                                      

                    if($_POST['commsubscr'] == 'Y'){
                        $arFields['SUBSCRIBE'] = 1;
                    }

                    if(isset($_POST['reply-id']) && intval($_POST['reply-id']) > 0){

                        $parentPostID = intval($_POST['reply-id']);
                        $arFields['PARENT_ID'] = $parentPostID;

                        
                        $resultTmp = Comments\CommentTable::getList(array(
                            'select' => array('ID', 'DEPTH_LEVEL'),
                            'filter' => array('ID' => $parentPostID),
                        ));
                        
                        $arResultTmp = $resultTmp->fetch();
                        $depthLevel = $arResultTmp['DEPTH_LEVEL'];

                        $newDepthLevel = $depthLevel + 1;

                        $arFields['DEPTH_LEVEL'] = $newDepthLevel;
                    }

    //file_put_contents($_SERVER["DOCUMENT_ROOT"].'/comment_log.txt', var_export($arFields, true), LOCK_EX|FILE_APPEND);


                    try{
                        $result = Comments\CommentTable::add($arFields);

                        if($result->isSuccess()){
                            $arResult['result'] = 'success'; // success
                            $arResult['res_message'][] = Loc::GetMessage('COMM_ADD_OK_MESSAGE');
                        }
                        else{
                            $arResult['result'] = 'error'; // error
                            $arErrors = $result->getErrorMessages();
                            foreach($arErrors as $key => $errMess){
                                $arResult['res_message'][] = $errMess;
                            }
                        }
                    }
                    catch(Exception $e){
                        $arResult['result'] = 'error'; // error
                        $arResult['res_message'][] = $e->getMessage();
                    }
                }
            }
            else{
                $arResult['res_message'][] = Loc::GetMessage('COMM_ADD_SESS_EXP');
            }
        }
    }

    header('Content-Type: application/json; charset='.LANG_CHARSET);
    echo Main\Web\Json::encode($arResult);
    die();
}

