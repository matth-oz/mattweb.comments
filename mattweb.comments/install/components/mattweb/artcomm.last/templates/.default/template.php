<?
use \Bitrix\Main\Localization\Loc;
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
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

//dump($arResult);
?>
<div class="single-widget-area mb-30">
	<!-- Title -->
	<div class="widget-title">
		<h6><?=Loc::GetMessage('LAST_COMMENTS_BLOCK_TITLE');?></h6>
	</div>
    <?if(isset($arResult["ERR_MESSAGES"])):?>
        <?foreach($arResult["ERR_MESSAGES"] as $errMess):?>
            <p class="comment-text error"><?=$errMess?></p>
        <?endforeach?>
    <?else:?>
        <?if(count($arResult["COMMENTS"]) > 0):?>
            <?foreach($arResult["COMMENTS"] as $arComment):?>
            <div class="latest-post-comment">
                <p class="comment-adt">
                    <span class="comment-author"><?=$arComment["AUTHOR_NAME"]?></span> / <span class="comment-date"><?=$arComment["CREATED_AT"]?></span>
                </p>
                <p class="comment-text"><?=$arComment["COMMENT"]?></p>
                <p class="comment-art">
                    <?if(isset($arComment["ELEM_URL"])):?>
                    <a href="<?=$arComment["ELEM_URL"]?>"><?=$arComment["ELEM_NAME"]?></a>
                    <?else:?>
                    <span><?=$arComment["ELEM_NAME"]?></span>
                    <?endif?>
                </p>
            </div>
            <?endforeach;?>
        <?else:?>
            <p class="comment-text error"><?=Loc::GetMessage('LAST_COMMENTS_EMPTY_LIST');?></p>
        <?endif?>
    <?endif?>
</div>