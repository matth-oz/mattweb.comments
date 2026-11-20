<?
if (!check_bitrix_sessid())
    return;

use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Iblock\TypeTable;
use Bitrix\Iblock\IblockTable;
use \Bitrix\Main\Application;

Loc::loadMessages(__FILE__);
Loader::includeModule('iblock');


$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$installParams = $request->getPostList();

$arTypesLangList = [];

$arTypes = ['' => Loc::getMessage('EMPTY_OPTION_VALUE')];

$arTypesLang = \Bitrix\Iblock\TypeLanguageTable::getList()->fetchAll();
foreach($arTypesLang as $arTypeLang){
    if($arTypeLang['LANGUAGE_ID'] !== 'ru') continue;
    $arTypesLangList[$arTypeLang['IBLOCK_TYPE_ID']] = $arTypeLang['NAME'];
}

$iblockType = $installParams['source_iblock_types'] ?? '';


$rsTypes = TypeTable::getList();

while($arType = $rsTypes->fetch()){
    $arTypes[$arType['ID']] = $arTypesLangList[$arType['ID']];
}

$iblockTypeChoosen = (array_key_exists($iblockType, $arTypes)); 


$session = Application::getInstance()->getSession();
$arErrors = $session->get('ERRORS');
if(!empty($arErrors)){
    $errMess = join("\r\n", $arErrors);

    echo CAdminMessage::ShowMessage(array(
        "TYPE" => "ERROR",
        "MESSAGE" => Loc::getMessage("MOD_INST_ERR"),
        "DETAILS" => $errMess,
        "HTML" => true,
    ));
}

$session->remove('ERRORS');
?>
<link rel="stylesheet" type="text/css" href="/local/modules/mattweb.comments/install/install.css" />
<div class="inst-step-wrap">
    <form action="<?echo $APPLICATION->GetCurPageParam('step=2'); ?>" name="install_step1_1" method="POST">
        <?=bitrix_sessid_post()?>
        <input type="hidden" name="lang" value="<?echo LANGUAGE_ID ?>">
        <input type="hidden" name="id" value="mattweb.comments">
        <input type="hidden" name="install" value="Y">
        <input type="hidden" name="step" value="2">
        <input type="hidden" name="create_new_iblock" value="Y">

        <div class="option-wrap"><!--source_iblock_types-->
            <div class="option-wrap-inner">
                <label for="sourceIblockTypes"><?=Loc::GetMessage('SOURCE_IBLOCK_TYPES_TITLE')?></label>
            </div>
            <div class="option-wrap-inner">
                <select name="source_iblock_types" id="sourceIblockTypes">
                    <?foreach($arTypes as $key => $value):?>                    
                    <option value="<?=$key?>"<?if($key == $installParams['source_iblock_types']):?> selected<?endif?>><?=$value?></option>
                    <?endforeach?>
                </select>
            </div>    
        </div>

        <div class="option-wrap"><!--source_iblock_name-->
            <div class="option-wrap-inner">
                <label for="sourceIblockName"><?=Loc::GetMessage('SOURCE_IBLOCK_NAME_TTILE')?></label>
            </div>
            <div class="option-wrap-inner">
                <input<?if(!$iblockTypeChoosen):?> disabled<?endif?> type="text" name="source_iblock_name" value="<?=$installParams['source_iblock_name']?>" id="sourceIblockName" />
            </div>    
        </div>

        <div class="option-wrap"><!--source_iblock_code-->
            <div class="option-wrap-inner">
                <label for="sourceIblockCode"><?=Loc::GetMessage('SOURCE_IBLOCK_CODE_TTILE')?></label>
            </div>
            <div class="option-wrap-inner">
                <input<?if(!$iblockTypeChoosen):?> disabled<?endif?> type="text" name="source_iblock_code" value="<?=$installParams['source_iblock_code']?>" id="sourceIblockName" />
            </div>    
        </div>
        <div class="option-wrap"><!--list_page_url-->
            <div class="option-wrap-inner">
                <label for="listPageUrl"><?=Loc::GetMessage('LIST_PAGE_URL_TITLE')?></label>
            </div>
            <div class="option-wrap-inner">
                <?$listPageUrl = $installParams['list_page_url'] ?? '#SITE_DIR#/comments_demo/'?>
                <input<?if(!$iblockTypeChoosen):?> disabled<?endif?> type="text" name="list_page_url" value="<?=$listPageUrl?>" id="listPageUrl" />
            </div>    
        </div>
        <div class="option-wrap"><!--detail_page_url-->
            <div class="option-wrap-inner">
                <label for="detailPageUrl"><?=Loc::GetMessage('DETAIL_PAGE_URL_TITLE')?></label>
            </div>
            <div class="option-wrap-inner">
                <?$detailPageUrl = $installParams['detail_page_url'] ?? '#SITE_DIR#/comments_demo/#ID#/'?>
                <input<?if(!$iblockTypeChoosen):?> disabled<?endif?> type="text" name="detail_page_url" value="<?=$detailPageUrl?>" id="detailPageUrl" />
            </div>    
        </div>

        <a href="<?=$APPLICATION->GetCurPageParam('', ['step']);?>"><?=Loc::GetMessage('MOD_STEP_BACK')?></a>
        &nbsp;
        <input type="submit" name="" value="<?echo Loc::getMessage("MOD_CONTINUE"); ?>">
    </form>
</div>
<script>
(function(){
    var form = document.forms['install_step1_1'];
    if(!form) return;

    var typeSelect = form.elements['source_iblock_types'],
        iblockNameSelect = form.elements['source_iblock_name'],
        iblockCodeSelect = form.elements['source_iblock_code'],
        listPageUrl = form.elements['list_page_url'],
        detailPageUrl = form.elements['detail_page_url'];
    
    typeSelect.addEventListener('change', function(){
    
        if(this.value !== ''){
            iblockNameSelect.disabled = false;
            iblockCodeSelect.disabled = false;
            listPageUrl.disabled = false;
            detailPageUrl.disabled = false;

        }
        else{
            iblockNameSelect.disabled = true;
            iblockCodeSelect.disabled = true;
            listPageUrl.disabled = true;
            detailPageUrl.disabled = true;
        }

    });
})()
</script>



