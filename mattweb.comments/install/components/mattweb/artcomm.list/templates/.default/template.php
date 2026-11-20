<?
use \Bitrix\Main\Localization\Loc;
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

if($arParams['USE_RECAPTCHA']){
    $this->addExternalJS("https://www.google.com/recaptcha/api.js?render=".$arParams['FORM_CAPTCHA_SK']);
}

$queryStr = '?name_max='.$arParams['NAME_FIELD_LENGTH'];
$queryStr .= '&comm_min='.$arParams['COMMENT_MIN_LENGTH'];
$queryStr .= '&comm_max='.$arParams['COMMENT_MAX_LENGTH'];

$formAction = $componentPath.'/ajax.php'.$queryStr;
$arSmiles = \SmilesActions::getSmilesSRArray();
?>
<div class="comment_area clearfix">
    <div class="add-comment-wrap top">
        <h2><?=Loc::GetMessage('COMMENT_LIST_TITLE')?></h2>
        <?if(is_array($arResult["COMMENTS"]) && count($arResult["COMMENTS"]) > 0):?>            
            <h4 class="headline"><?=$arResult["COMMENTS_COUNT"]?></h4>
        <?endif?>
        
        <?if($arResult["SHOW_COMMENTS_FORM"] == "Y"):?>
            <?if($arParams["COMMENTS_FORM_POSITION"] == 'BEFORE_LIST'):?>
                <a class="add-comment-btn j-open-popup" href="#"><?=Loc::GetMessage('COMMENT_ADD_TITLE')?></a>
            <?endif?>        
        <?else:?>
            <?if($arParams["COMMENTS_FORM_POSITION"] == 'BEFORE_LIST'):?>
                <?if($arResult['USER_ENOUGH_RIGHTS'] == 'N'):?>
                <p class="err-mwc-mess"><?=Loc::GetMessage('REGISTER_ADD_COMMENTS')?></p>
                <?endif?>

                <?if($arResult['USER_COMM_LIMIT_EXCEED'] == 'Y'):?>
                <p class="err-mwc-mess"><?=Loc::GetMessage('USER_COMM_LIMIT_EXCEED_MESS')?></p>
                <?endif?>
            <?endif?>            
        <?endif?>
    </div>
    <?// здесь список комментов?>
    <?if(is_array($arResult["COMMENTS"]) && count($arResult["COMMENTS"]) > 0):?>
       <ol>
       <?
        foreach($arResult["COMMENTS"] as $arComment):?>
            <li class="single_comment_area" id="comment-<?=$arComment['COMMENT_ID']?>">
                <div class="comment-wrapper d-flex">
                    <!-- Comment Meta -->
                    <div class="comment-author">
                        <!--img src="img/blog-img/9.jpg" alt=""-->
                    </div>
                    <!-- Comment Content -->
                    <div class="comment-content">
                        <span class="comment-date"><?=$arComment['CREATED_AT']?></span>
                        <?if(!empty($arComment['COMMENT_URL'])):?>
                            <a href="<?=$arComment['COMMENT_URL']?>" class="comment-share-link j-comm-link" title="<?=Loc::GetMessage('COMMENT_COPY_LINK')?>">#</a>
                        <?endif?>
                        <h5><?=$arComment['NAME']?></h5>
                        <?
                        $commentTxt = \CommentsActions::replaceBBCode($arComment['COMMENT']);
                        $commentTxt = \SmilesActions::replaceSmiles($commentTxt, $arSmiles);                      
                        ?>
                        <p><?=$commentTxt?></p>
                        <?if($arResult["SHOW_COMMENTS_FORM"] == "Y"):?>
                        <a href="#" class="j-reply j-open-popup" data-comment-id="<?=$arComment['COMMENT_ID']?>"><?=Loc::GetMessage('COMMENT_REPLY_TITLE');?></a>  
                        <?endif?>
                        <a class="comm-like j-vote<?if($arComment['USER_CAN_VOTE'] == 'N'):?> vote-disabled<?endif?><?if($arComment['USER_VOTE_TXT'] == 'LIKE'):?> active<?endif?>" data-comment-id="<?=$arComment['COMMENT_ID']?>" href="#"  data-vote="like"><span class="glyphicon glyphicon-thumbs-up">&#128077;</span> <span class="thumbs-up-count j-vote-count"><?=$arComment['VOTES']['LIKES']?></span></a>
                        <a class="comm-dislike j-vote<?if($arComment['USER_CAN_VOTE'] == 'N'):?> vote-disabled<?endif?><?if($arComment['USER_VOTE_TXT'] == 'DISLIKE'):?> active<?endif?>" data-comment-id="<?=$arComment['COMMENT_ID']?>" href="#"  data-vote="dislike"><span class="glyphicon glyphicon-thumbs-down">&#128078;</span> <span class="thumbs-down-count j-vote-count"><?=$arComment['VOTES']['DISLIKES']?></span></a>
                    </div>
                </div>

                <?if(is_array($arComment['ANSWERS'])):?>
                    <?$component->renderComments($arComment['ANSWERS'], $arParams['COMMENTS_STYLE']);?>
                <?endif?>
            </li>
        <?endforeach?>
        </ol>
    <?else:?>
        <p class="zero-comments"><?=Loc::GetMessage('NO_COMMENTS_YET')?></p>
    <?endif?>
    <div class="add-comment-wrap bottom">
        <?if($arResult["SHOW_COMMENTS_FORM"] == "Y"):?>
            <?if($arParams["COMMENTS_FORM_POSITION"] == 'AFTER_LIST'):?>
                <a class="add-comment-btn j-open-popup" href="#"><?=Loc::GetMessage('COMMENT_ADD_TITLE')?></a>
            <?endif?>                        
        <?else:?>
            <?if($arParams["COMMENTS_FORM_POSITION"] == 'AFTER_LIST'):?>                 
                <?if($arResult['USER_ENOUGH_RIGHTS'] == 'N'):?>
                <p class="err-mwc-mess"><?=Loc::GetMessage('REGISTER_ADD_COMMENTS')?></p>
                <?endif?>

                <?if($arResult['USER_COMM_LIMIT_EXCEED'] == 'Y'):?>
                <p class="err-mwc-mess"><?=Loc::GetMessage('USER_COMM_LIMIT_EXCEED_MESS')?></p>
                <?endif?>
            <?endif?>            
        <?endif?>
    </div>
</div>
<?if($arResult["SHOW_COMMENTS_FORM"] == "Y"):?>
    <?//dump($arResult['SMILES_REPLACE']);?>
    <!-- Leave A Comment -->
    <div class="j-leave-comment leave-comment-area clearfix">
        <a href="#" class="close-popup j-close-popup"><i class="fa fa-window-close-o"></i></a>
        <div class="comment-form">
            <h4 class="headline"><?=Loc::GetMessage('COMMENT_ADD_FORM_TITLE')?></h4>

            <!-- Comment Form -->
            <form action="<?=$formAction?>" method="post" class="j-popup" data-ajax>
                <?=bitrix_sessid_post()?>
                <?if($arParams['USE_RECAPTCHA']):?>
                <input type="hidden" name="commcaptcha" id="commcaptcha" />
                <?endif?>
                <div class="row">
                    <?foreach($arResult["CF_FIELDS"] as $arField):?>
                        <?if($arField['FIELD'] == 'input'):?>
                            <?if($arField['TYPE'] == 'hidden'):?>
                                <input type="<?=$arField['TYPE']?>" name="<?=$arField['NAME']?>"<?if(!empty($arField['FIELD_ID'])):?> id="<?=$arField['FIELD_ID']?>"<?endif?> value="<?=$arField['VALUE']?>" />
                            <?elseif($arField['TYPE'] == 'checkbox'):?>
                            <div class="col-12">
                                <div class="form-group<?if(!empty($arField['CSS_CLASS'])):?> <?=$arField['CSS_CLASS']?><?endif?>" style="overflow: hidden;">
                                    <input <?if($arField['REQUIRED'] == 'Y'):?>data-required="<?=$arField['NAME']?>"<?endif?> type="<?=$arField['TYPE']?>" name="<?=$arField['NAME']?>" class="form-control chkbx" id="<?=$arField['FIELD_ID']?>" value="<?=$arField['VALUE']?>" <?if($arField['REQUIRED'] == 'Y'):?>data-error="<?=$arField['ERR_MESS']?>"<?endif?> />
                                    <label for="<?=$arField['FIELD_ID']?>"><?=$arField['LABEL']?></label>
                                </div>
                            </div>
                            <?else:?>
                            <div class="col-12">
                                <div class="form-group<?if(!empty($arField['CSS_CLASS'])):?> <?=$arField['CSS_CLASS']?><?endif?>">
                                    <input <?if($arField['REQUIRED'] == 'Y'):?>data-required="<?=$arField['NAME']?>"<?endif?> type="<?=$arField['TYPE']?>" class="form-control" name="<?=$arField['NAME']?>" id="<?=$arField['FIELD_ID']?>" placeholder="<?=$arField['PLACEHOLDER']?><?if($arField['REQUIRED'] == 'Y'):?>*<?endif?>" <?if($arField['REQUIRED'] == 'Y'):?>data-error="<?=$arField['ERR_MESS']?>"<?endif?> <?if($arField['VALUE'] !=''):?>value="<?=$arField['VALUE']?>"<?endif?> />

                                    <div class="form__error"></div>
                                </div>
                            </div>
                            <?endif?>
                        <?else:?>
                            <div class="col-12">
                                <div class="form-group<?if(!empty($arField['CSS_CLASS'])):?> <?=$arField['CSS_CLASS']?><?endif?>">

                                    <?if(!empty($arResult['SMILES']) && $arResult['IS_ALLOW_USE_SMILES'] == 'Y'):?>
                                        <div class="smiles-block">
                                        <?foreach($arResult['SMILES'] as $arSmile):?>
                                            <?if($arResult['IS_EMOJI'] == 'Y'):?>
                                                <a class="smile-item j-smile-item" href="#" data-typing="<?=htmlspecialchars($arSmile['SHORT_CODE'])?>"><span title="<?=$arSmile['NAME']?>" style="width: 20px;"><?=$arSmile['SHORT_CODE']?></span></a>
                                            <?else:?>
                                                <a class="smile-item j-smile-item" href="#" data-typing="<?=$arSmile['SHORT_CODE']?>"><img src="<?=$arSmile['PATH']?>" alt="<?=$arSmile['NAME']?>" title="<?=$arSmile['NAME']?>" style="width: 20px;"/></a>
                                            <?endif?>
                                        <?endforeach?>
                                        </div>
                                    <?endif?>

                                    <textarea <?if($arField['REQUIRED'] == 'Y'):?>data-required="<?=$arField['NAME']?>"<?endif?> class="form-control" name="<?=$arField['NAME']?>" id="<?=$arField['FIELD_ID']?>" 
                                        cols="<?=$arField['COLS']?>" rows="<?=$arField['ROWS']?>" placeholder="<?=$arField['PLACEHOLDER']?><?if($arField['REQUIRED'] == 'Y'):?>*<?endif?>" <?if($arField['REQUIRED'] == 'Y'):?>data-error="<?=$arField['ERR_MESS']?>"<?endif?>></textarea>
                                    <div class="form__error"></div>
                                </div>
                            </div>
                        <?endif?>
                    <?endforeach?>
                    <div class="col-12">
                        <button type="submit" class="btn nikki-btn"><?=Loc::GetMessage('COMMENT_ADD_SUBMIT_TITLE')?></button>
                    </div>
                </div>
            </form>

        </div>
    </div>
    <!-- Leave A Comment success result-->
    <div class="form-res-block j-form-res-block">
        <a href="#" class="close-popup j-close-popup"><i class="fa fa-window-close-o"></i></a>
        <div class="result-txt j-result-txt">
        </div>
    </div>
    <div class="popup__bg"></div>
    <?if($arParams['USE_RECAPTCHA']):?>
    <script>
        grecaptcha.ready(function() {
            grecaptcha.execute('<?=$arParams['FORM_CAPTCHA_SK']?>', {action: 'homepage'}).then(function(token) {
            document.querySelector("#commcaptcha").value = token;
            });
        });
    </script>
    <?endif?>
    <script>
        $(function(){
            /* Отправка формы для добавления комментария производится в файле /local/templates/js/app.js*/

            let commentPopup = new Popup({
                popupSelector: '.j-leave-comment',
                replyIDSelector: '#reply-id',
                openPopupButtonsSelector: '.j-open-popup',
                closePopupBtnSelector: '.j-close-popup',
            });


            let castVote = new CastAVote({
                actionUrl: '<?=$formAction?>',
                artElemID: '<?=$arParams['ART_ELEMENT_ID']?>',
                voteElemsSelector: '.j-vote',
                voteCountSelector: '.j-vote-count',
                voteDisabledClass: 'vote-disabled',
            });

            castVote.init();

            // Копируем url ссылки на комментарий в буфер обмена
            const copyLinksElems = document.querySelectorAll('.j-comm-link');
            copyLinksElems.forEach((copyLinksElem) => {
                copyLinksElem.addEventListener('click', (e) => {
                        const linkVal = copyLinksElem.getAttribute('href');                

                        navigator.clipboard.writeText(linkVal)
                        .then(() => {
                        console.log('Ссылка скопирована в буфер обмена');
                        })
                        .catch(err => {
                        console.log('Ошибка при копировании ссылки', err);
                        });

                        e.preventDefault();
                });

            });
            
            // Добавляем смайлы в комментарии
            const smileItem = $('.j-smile-item');
            const messageTxt = $('#message');

            smileItem.on('click', function(e){
                
                let messLength = messageTxt.val().length;
                let slSt = messageTxt.prop("selectionStart");
                let slEnd = messageTxt.prop("selectionEnd");

                let messTxt = messageTxt.val();        
                let smileTyping = $(this).data('typing');

                let smileTypingLength = smileTyping.length;

                if(slSt < messLength){
                    let startPart = messTxt.substr(0, slSt);            
                    let finishPart = messTxt.substr((slSt + 1));

                    let updMess = startPart.trim() + ' ' + smileTyping + ' ' + finishPart.trim();
                    messageTxt.val(updMess);

                    let newPosCursor = slSt + smileTypingLength + 2;
                    
                    messageTxt.focus();
                    messageTxt[0].setSelectionRange(newPosCursor, newPosCursor);

                }
                else{
                messageTxt.val(messageTxt.val() + ' ' + smileTyping); 
                messageTxt.focus();
                }
            });


        });
        
        formSubmit({
            recaptchaSCK: '<?=$arParams['FORM_CAPTCHA_SCK']?>',
            useRecaptcha: '<?=($arParams['USE_RECAPTCHA']) ? "Y" : "N" ?>'
        });
    </script>
<?endif?>

