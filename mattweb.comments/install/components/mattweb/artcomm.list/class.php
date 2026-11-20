<?
use \Bitrix\Main,
    \Bitrix\Main\Engine\CurrentUser,
    \Bitrix\Main\Loader,
    \Bitrix\Main\Config\Option,
    \Bitrix\Main\Context,
    \Bitrix\Main\Localization\Loc,
    \Bitrix\Iblock,
    \Bitrix\Highloadblock\HighloadBlockTable as HLBT;


if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class ArtcommListComponent extends CBitrixComponent
{
    
    protected $currentUser;
    protected $isUserAdmin;

    public function __construct($component = null){
        
        $this->currentUser = CurrentUser::get();        
        $this->isUserAdmin = $this->currentUser->isAdmin();

        parent::__construct($component);
    }

    /**
     * проверяет подключение необходиимых модулей
     * @throws LoaderException
     */
    protected function checkModules()
    {
        if (!Main\Loader::includeModule('mattweb.comments'))
            throw new Main\LoaderException(Loc::getMessage('MATTWEB_COMMENTS_MODULE_NOT_INSTALLED'));

        if (!Main\Loader::includeModule('iblock'))
            throw new Main\LoaderException(Loc::getMessage('IBLOCK_MODULE_NOT_INSTALLED'));
    }

    protected function checkElemId()
    {
        if ($this->arParams["ART_ELEMENT_ID"] <= 0) {
            throw new \Exception(Loc::getMessage('ART_ELEMENT_ID_IS_EMPTY'));
        }
    }

    protected function checkAddCommentRights(){
        // D7 авторизован ли посетитель
        $isUserAuth = ($this->currentUser->getId() > 0);
        
        // P - it is allowed to add comments
        $commNauthMaxRights = Option::get('mattweb.comments', 'comm_not_auth_max_rights');
         // P - it is allowed to add comments
        $commAuthMaxRights = Option::get('mattweb.comments', 'comm_auth_max_rights');

        if((!$isUserAuth && $commNauthMaxRights >= 'P') || ($isUserAuth && $commAuthMaxRights >= 'P')){
            return true;
        }

        return false;
    }

    protected function getCommentUrl($path) {
        $protocol = (CMain::IsHTTPS() === true) ? 'https://' : 'http://';
            
        $context = \Bitrix\Main\Application::getInstance()->getContext();
        $server = $context->getServer();
        $host = $server->getHttpHost();
        
        return $protocol.$host.$path;
    }
    
    public function onPrepareComponentParams($arParams)
    {        
        
        $isUserAuth = ($this->currentUser->getId() > 0);

        $arParams["ART_ELEMENT_ID"] = intval($arParams["ART_ELEMENT_ID"]);

        $arParams["ART_IBLOCK_ID"] = intval($arParams["ART_IBLOCK_ID"]);
        $arParams["ART_SECTION_ID"] = intval($arParams["ART_SECTION_ID"]);


        $arParams["COMMENTS_SORT"] = trim($arParams["COMMENTS_SORT"]);
        if (strlen($arParams["COMMENTS_SORT"]) <= 0) {
            $arParams["COMMENTS_SORT"] = "COMM_DESC";
        }
        
        $arParams["COMMENTS_STYLE"] = trim($arParams["COMMENTS_STYLE"]);
        if (strlen($arParams["COMMENTS_STYLE"]) <= 0) {
            $arParams["COMMENTS_STYLE"] = "COMM_TREE";
        }

        $arParams["SHOW_COMMENTS_FORM"] = $arParams["SHOW_COMMENTS_FORM"] == "Y";

        $arParams["COMM_TITLE_FORMS"] = [
            Loc::getMessage('COMM_TITLE_F1'),
            Loc::getMessage('COMM_TITLE_F2'),
            Loc::getMessage('COMM_TITLE_F3'),
        ];

        $arParams["COMMENT_MIN_LENGTH"] = (!empty($arParams["COMMENT_MIN_LENGTH"])) ? $arParams["COMMENT_MIN_LENGTH"] : "10"; 
        $arParams["COMMENT_MAX_LENGTH"] = (!empty($arParams["COMMENT_MAX_LENGTH"])) ? $arParams["COMMENT_MAX_LENGTH"] : "550";
        $arParams["NAME_FIELD_LENGTH"] = (!empty($arParams["NAME_FIELD_LENGTH"])) ? $arParams["NAME_FIELD_LENGTH"] : "50";
        
        $useCaptcha = Option::get('mattweb.comments', 'use_captcha');

        if($useCaptcha == 'Y'){
            $arParams["FORM_CAPTCHA_SK"] = Option::get('mattweb.comments', 'captcha_sitekey');
            $arParams["FORM_CAPTCHA_SCK"] = Option::get('mattweb.comments', 'captcha_secret');
        }

        $arParams["USE_RECAPTCHA"] = false;
        if(!$isUserAuth && strlen(trim($arParams["FORM_CAPTCHA_SK"])) != 0 && strlen(trim($arParams["FORM_CAPTCHA_SCK"])) != 0){        
            $arParams["USE_RECAPTCHA"] = true;
        }
        
        return $arParams;
    }

    public function executeComponent()
    {
            // подключаем языковой файл
            $this->includeComponentLang('class.php');
            
            try{
                $this->checkModules();
                $this->checkElemId();
            }
            catch(\Exception $e){
                $this->arResult['result'] = 'error'; // error
                $this->arResult['res_message'][] = $e->getMessage();
            }

            $this->arResult['IS_ALLOW_USE_SMILES']= Option::get('mattweb.comments', 'allow_use_smiles');

            $this->arResult['SMILES'] = SmilesActions::getSmilesList();
            $this->arResult['IS_EMOJI'] = SmilesActions::isCurrentTypeEmoji() ? 'Y' : 'N';

            $cookieUserMark = VotesActions::getCommentUserID();
            if(!$cookieUserMark){
                VotesActions::setCommentUserID();
            }

            $this->arResult['USER_VOTE_ID'] = $cookieUserMark;

            if($this->arParams['ART_IBLOCK_ID'] == 0 || $this->arParams['ART_SECTION_ID'] == 0) {
                $obElemInfo = \CIBlockElement::GetByID($this->arParams["ART_ELEMENT_ID"]);
                $arElemInfo = $obElemInfo->Fetch();

                $this->arParams['ART_IBLOCK_ID'] = $arElemInfo['IBLOCK_ID'];
                $this->arParams['ART_SECTION_ID'] = $arElemInfo['IBLOCK_SECTION_ID'];
            }

            $filter = ['ELEM_ID' => $this->arParams["ART_ELEMENT_ID"], 'PUBLISHED'=> 1, '!DEL_MARKED' => 1];
            $select = ['ID', 'ELEM_ID', 'PARENT_ID', 'DEPTH_LEVEL', 'CREATED_AT', 'NAME', 'USERNAME', 'COMMENT', 'IP'];
            $order = [];
            if($this->arParams["COMMENTS_SORT"] == "COMM_DESC"){
                $order['CREATED_AT'] = 'desc';
            } else{
                $order['CREATED_AT'] = 'asc';
            }

            $arComms = \CommentsActions::getCommentsList($order, $filter, $select);

            if(!empty($arComms)){

                $arCommKeys = array_keys($arComms);

                $arVotes = VotesActions::getVotesList($arCommKeys);
                $arUserVotes = VotesActions::getVotesUserList($this->arResult['USER_VOTE_ID'], $arCommKeys);

                foreach($arComms as &$arComm){
                    if(!empty($this->arParams["ART_DETAIL_URL"])){
                        $commentUrl = $this->arParams["ART_DETAIL_URL"].'#comment-'.$arComm['COMMENT_ID'];
                        $arComm['COMMENT_URL'] = $this->getCommentUrl($commentUrl);               
                    }

                    if(array_key_exists($arComm['COMMENT_ID'], $arUserVotes)){
                        $arComm['USER_CAN_VOTE'] = 'N';
                        $arComm['USER_VOTE_TXT'] = $arUserVotes[$arComm['COMMENT_ID']];
                    }

                    if(array_key_exists($arComm['COMMENT_ID'], $arVotes)){
                        $arComm['VOTES']['LIKES'] = (isset($arVotes[$arComm['COMMENT_ID']]['LIKES'])) ? $arVotes[$arComm['COMMENT_ID']]['LIKES'] : 0;
                        $arComm['VOTES']['DISLIKES'] = (isset($arVotes[$arComm['COMMENT_ID']]['DISLIKES'])) ? $arVotes[$arComm['COMMENT_ID']]['DISLIKES'] : 0;
                    }
                    else{
                        $arComm['VOTES']['LIKES'] = 0;
                        $arComm['VOTES']['DISLIKES'] = 0;
                    }
                }

                $qComm = count($arComms);

                $this->arResult['COMMENTS_COUNT'] = \CommentsActions::getWordform(
                    $qComm,
                    $this->arParams["COMM_TITLE_FORMS"][0],
                    $this->arParams["COMM_TITLE_FORMS"][1],
                    $this->arParams["COMM_TITLE_FORMS"][2]
                );

                $arCommsTree = \CommentsActions::getCommentsTree($arComms);
                $this->arResult['COMMENTS'] = $arCommsTree;

            }

            //dump($this->arResult);
            //dump($this->arParams);

            $userCommMaxQuant = Option::get('mattweb.comments', 'comm_max_quant');

            // D7 авторизован ли посетитель
            $curUserId = (int) $this->currentUser->getId();
            
            $commentsUID = '';
            if($curUserId <= 0){
                $commentsUID = CommentsActions::getCommentUserID();
            }

            // Получаем id если авторизован или берем из куки если не авторизован
            // Если id или куки есть пытаемся получить все комментарии пользователя к текущей статье
            $curUserCommQuant = 0;
            if($curUserId > 0 || strlen($commentsUID) > 0){
                $curUserCommQuant = CommentsActions::countUserComments($this->arParams["ART_ELEMENT_ID"], $commentsUID, $curUserId);
            }

            // Если удачно - считаем их количество 
            // Если их кол-во больше чем указано в настройках форму не показываем
            
            
            // иначе показываем форму для добавления комментов

            // если нет ни id, ни куки и можно комментировать неавторизованным, тоже показываем форму

            if($this->isUserAdmin){
                $this->arResult["SHOW_COMMENTS_FORM"] = 'Y';
                $this->arResult["USER_ENOUGH_RIGHTS"] = 'Y';
                $this->arResult["USER_COMM_LIMIT_EXCEED"] = 'N';
            }
            else{
                $this->arResult["SHOW_COMMENTS_FORM"] = ($this->arParams["SHOW_COMMENTS_FORM"] && $this->checkAddCommentRights() && $curUserCommQuant < $userCommMaxQuant) ? "Y" : "N";
                $this->arResult["USER_ENOUGH_RIGHTS"] = ($this->checkAddCommentRights()) ? 'Y' : 'N';
                $this->arResult["USER_COMM_LIMIT_EXCEED"] = ($curUserCommQuant >= $userCommMaxQuant) ? 'Y' : 'N';
            }
           

            if($this->arResult["SHOW_COMMENTS_FORM"] == 'Y'){

                // $arResult["COMMENT_FORM"]
                $arReqFields = $this->arParams['FORM_REQUIRED_FIELDS'];

                $arCommentForm = [
                    [
                        'FIELD' => 'input',
                        'TYPE' => 'hidden',
                        'NAME' => 'reply-id',
                        'FIELD_ID' => 'reply-id',
                        'VALUE' => '',
                        'CSS_CLASS' => '',
                    ],
                    [
                        'FIELD' => 'input',
                        'TYPE' => 'hidden',
                        'NAME' => 'AJAX',
                        'FIELD_ID' => '',
                        'VALUE' => 'Y',
                        'CSS_CLASS' => '',
                    ],
                    [
                        'FIELD' => 'input',
                        'TYPE' => 'hidden',
                        'NAME' => 'iblock',
                        'FIELD_ID' => '',
                        'VALUE' => $this->arParams['ART_IBLOCK_ID'],
                        'CSS_CLASS' => '',
                    ],
                    [
                        'FIELD' => 'input',
                        'TYPE' => 'hidden',
                        'NAME' => 'elem',
                        'FIELD_ID' => '',
                        'VALUE' => $this->arParams['ART_ELEMENT_ID'],
                        'CSS_CLASS' => '',
                    ],
                ];

                if(!empty($this->arParams['ART_SECTION_ID']) && $this->arParams['ART_SECTION_ID'] != 0){
                    $arCommentForm[] = [
                        'FIELD' => 'input',
                        'TYPE' => 'hidden',
                        'NAME' => 'sect',
                        'FIELD_ID' => '',
                        'VALUE' => $this->arParams['ART_SECTION_ID'],
                        'CSS_CLASS' => '',
                    ];
                }
                
                if($this->currentUser->getId()){
                    $loginVal = $this->currentUser->getLogin();                    
                }

                // NAME, EMAIL, SITE, COMMENT
                $arFieldName = [
                    'FIELD' => 'input',
                    'FIELD_ID' => 'contact-name',
                    'LABEL'=> Loc::GetMessage('LABEL_NAME_FIELD'),
                    'PLACEHOLDER'=> Loc::GetMessage('LABEL_NAME_FIELD'),
                    'REQUIRED' => (in_array('NAME', $arReqFields)) ? 'Y' : 'N',
                    'ERR_MESS' => Loc::GetMessage('FIELD_CANT_BE_EMPTY'),
                    'TYPE' => 'text',
                    'NAME' => 'name',
                    'VALUE' => $loginVal,
                    'CSS_CLASS' => '',
                ];
                $arCommentForm[] = $arFieldName;

                $emailVal = $this->currentUser->getEmail();
                $arFieldEmail = [
                    'FIELD' => 'input',
                    'FIELD_ID' => 'contact-email',
                    'LABEL' => Loc::GetMessage('LABEL_EMAIL_FIELD'),
                    'PLACEHOLDER' => Loc::GetMessage('LABEL_EMAIL_FIELD'),
                    'ERR_MESS'=> Loc::GetMessage('FIELD_WRONG_EMAIL'),
                    'REQUIRED' => (in_array('EMAIL', $arReqFields)) ? 'Y' : 'N',
                    'TYPE' => 'email',
                    'NAME' => 'email',
                    'VALUE' => $emailVal,
                    'CSS_CLASS' => '',
                ];
                $arCommentForm[] = $arFieldEmail;
                
                $arFieldSite = [
                    'FIELD' => 'input',
                    'FIELD_ID' => 'contact-homepage',
                    'LABEL'=> Loc::GetMessage('LABEL_SITE_FIELD'),
                    'PLACEHOLDER' => Loc::GetMessage('LABEL_SITE_FIELD'),
                    'ERR_MESS'=> Loc::GetMessage('FIELD_WRONG_SITE'),
                    'REQUIRED' => (in_array('HOMEPAGE', $arReqFields)) ? 'Y' : 'N',
                    'TYPE' => 'text',
                    'NAME' => 'homepage',
                    'VALUE' => '',
                    'CSS_CLASS' => '',
                ];
                $arCommentForm[] = $arFieldSite;

                $arFieldSite = [
                    'FIELD' => 'textarea',
                    'FIELD_ID' => 'message',
                    'LABEL'=> Loc::GetMessage('LABEL_COMMENT_FIELD'),
                    'COLS' => '30',
                    'ROWS' => '10',
                    'PLACEHOLDER' => Loc::GetMessage('LABEL_COMMENT_FIELD'),
                    'ERR_MESS' => Loc::GetMessage('FIELD_CANT_BE_EMPTY'),
                    'REQUIRED' => 'Y',
                    'NAME' => 'message',
                    'VALUE' => '',
                    'CSS_CLASS' => '',
                ];
                $arCommentForm[] = $arFieldSite;

                if($this->arParams['NEW_COMMENTS_ALLOW_SUBSCR'] == 'Y'){
                    $arFieldSite = [
                        'FIELD' => 'input',
                        'FIELD_ID' => 'comm_subscr',
                        'LABEL'=> Loc::GetMessage('LABEL_NEW_COMMENTS_ALLOW_SUBSCR'),
                        'TYPE' => 'checkbox',
                        'REQUIRED' => 'N',
                        'NAME' => 'commsubscr',
                        'VALUE' => 'Y',
                        'CSS_CLASS' => 'j-commsubscr',
                    ];
                    $arCommentForm[] = $arFieldSite;
                }

                $this->arResult["CF_FIELDS"] = $arCommentForm;
            }

            // передаем эти данные в шаблон
            $this->includeComponentTemplate();
    }


    public function renderComments(array $arComms, string $commentStyle, int $prevDepthLevel = 1){

        if(empty($arComms)){return false;}
        if($commentStyle != 'COMM_TREE' && $commentStyle != 'COMM_LIST'){$commentStyle = 'COMM_LIST';}

        $arSmiles = \SmilesActions::getSmilesSRArray();

        if($commentStyle == 'COMM_TREE'){
            echo '<ol class="children">';
            $qc = count($arComms);
            $i = 1;
        }

        foreach ($arComms as $key => $arComm) {
            if(is_array($arComm['ANSWERS'])){
                echo '<li class="single_comment_area" id="comment-'.$arComm['COMMENT_ID'].'">';
                        echo '<div class="comment-wrapper d-flex">';
                            echo '<div class="comment-author">';
                            echo '</div>';
                            echo '<div class="comment-content">';
                            echo '<span class="comment-date">'.$arComm['CREATED_AT'].'</span>';
                            if(!empty($arComm['COMMENT_URL'])){
                                echo '<a href="'.$arComm['COMMENT_URL'].'" class="comment-link j-comm-link" title="'.Loc::GetMessage('COMMENT_COPY_LINK').'">#</a>';
                            }
                            echo '<h5>'.$arComm['NAME'].'</h5>';
                            $commentTxt = \CommentsActions::replaceBBCode($arComm['COMMENT']);
                            if($this->arResult['IS_EMOJI'] != 'Y'){
                                $commentTxt = \SmilesActions::replaceSmiles($commentTxt, $arSmiles);
                            }                            
                            echo '<p>'.$commentTxt.'</p>';
                            echo '<a href="#" class="j-reply j-open-popup" data-comment-id="'.$arComm['COMMENT_ID'].'">'.Loc::GetMessage('COMMENT_REPLY_TITLE').'</a>';
                            echo '<a class="comm-like j-vote';
                            if($arComm['USER_CAN_VOTE'] == 'N'){echo ' vote-disabled';}
                            if($arComm['USER_VOTE_TXT'] == 'LIKE'){echo ' active';}
                            echo'" data-comment-id="'.$arComm['COMMENT_ID'].'" data-vote="like" href="#"><span class="glyphicon glyphicon-thumbs-up">&#128077;</span> <span class="thumbs-up-count j-vote-count">'.$arComm['VOTES']['LIKES'].'</span></a>';
                            echo '<a class="comm-dislike j-vote';
                            if($arComm['USER_CAN_VOTE'] == 'N'){echo ' vote-disabled';}
                            if($arComm['USER_VOTE_TXT'] == 'DISLIKE'){echo ' active';}
                            echo '" data-comment-id="'.$arComm['COMMENT_ID'].'" data-vote="dislike" href="#"><span class="glyphicon glyphicon-thumbs-down">&#128078;</span> <span class="thumbs-down-count j-vote-count">'.$arComm['VOTES']['DISLIKES'].'</span></a>';
                            echo '</div>';
                        echo '</div>';
                if($commentStyle == 'COMM_LIST'){
                    echo '</li>';
                }

                $this->renderComments($arComm['ANSWERS'], $commentStyle, $arComm['DEPTH_LEVEL']);
            }else{
                echo '<li class="single_comment_area" id="comment-'.$arComm['COMMENT_ID'].'">';
                    echo '<div class="comment-wrapper d-flex">';
                        echo '<div class="comment-author">';
                        echo '</div>';
                        echo '<div class="comment-content">';
                        echo '<span class="comment-date">'.$arComm['CREATED_AT'].'</span>';
                        if(!empty($arComm['COMMENT_URL'])){
                            echo '<a href="'.$arComm['COMMENT_URL'].'" class="comment-link j-comm-link" title="'.Loc::GetMessage('COMMENT_COPY_LINK').'">#</a>';
                        }
                        echo '<h5>'.$arComm['NAME'].'</h5>';
                        $commentTxt = \CommentsActions::replaceBBCode($arComm['COMMENT']);
                        if($this->arResult['IS_EMOJI'] != 'Y'){
                            $commentTxt = \SmilesActions::replaceSmiles($commentTxt, $arSmiles);
                        }  
                        echo '<p>'.$commentTxt.'</p>';
                        echo '<a href="#" class="j-reply j-open-popup" data-comment-id="'.$arComm['COMMENT_ID'].'">'.Loc::GetMessage('COMMENT_REPLY_TITLE').'</a>';
                        echo '<a class="comm-like j-vote';
                        if($arComm['USER_CAN_VOTE'] == 'N'){echo ' vote-disabled';}
                        if($arComm['USER_VOTE_TXT'] == 'LIKE'){echo ' active';}
                        echo '" data-comment-id="'.$arComm['COMMENT_ID'].'" data-vote="like" href="#"><span class="glyphicon glyphicon-thumbs-up">&#128077;</span> <span class="thumbs-up-count j-vote-count">'.$arComm['VOTES']['LIKES'].'</span></a>';
                        
                        echo '<a class="comm-dislike j-vote';
                        if($arComm['USER_CAN_VOTE'] == 'N'){echo ' vote-disabled';}
                        if($arComm['USER_VOTE_TXT'] == 'DISLIKE'){echo ' active';}
                        echo '" data-comment-id="'.$arComm['COMMENT_ID'].'" data-vote="dislike" href="#"><span class="glyphicon glyphicon-thumbs-down">&#128078;</span> <span class="thumbs-down-count j-vote-count">'.$arComm['VOTES']['DISLIKES'].'</span></a>';
                        echo '</div>';
                    echo '</div>';
                if($commentStyle == 'COMM_LIST'){
                    echo '</li>';
                }
            }

            if($commentStyle == 'COMM_TREE'){
                if($i == $qc && $arComm['DEPTH_LEVEL'] > $prevDepthLevel){
                    echo str_repeat('</li></ol>', $arComm['DEPTH_LEVEL'] - $prevDepthLevel);
                }
                
                $i++;
            }

        }
    }
}