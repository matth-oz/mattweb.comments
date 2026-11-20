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

$arTypesLangList = [];

$arTypes = ['' => Loc::getMessage('EMPTY_OPTION_VALUE')];

$arTypesLang = \Bitrix\Iblock\TypeLanguageTable::getList()->fetchAll();
foreach($arTypesLang as $arTypeLang){
    if($arTypeLang['LANGUAGE_ID'] !== 'ru') continue;
    $arTypesLangList[$arTypeLang['IBLOCK_TYPE_ID']] = $arTypeLang['NAME'];
}

$rsTypes = TypeTable::getList();

while($arType = $rsTypes->fetch()){
    $arTypes[$arType['ID']] = $arTypesLangList[$arType['ID']];
}

// Инфоблоки, сгруппированные по типу - для зависимого списка "Инфоблок источник"
$arIblocksByType = ['' => Loc::getMessage('EMPTY_OPTION_VALUE')];

$rsIblocks = IblockTable::getList([
    'select' => ['ID', 'NAME', 'IBLOCK_TYPE_ID', 'LID'],
    'order' => ['IBLOCK_TYPE_ID' => 'ASC', 'SORT' => 'ASC', 'NAME' => 'ASC'],
    'filter' => ['ACTIVE' => 'Y'],
]);

while($arIblock = $rsIblocks->fetch()){
    $arIblocksByType[$arIblock['IBLOCK_TYPE_ID']][$arIblock['ID']] =
        '[' . $arIblock['ID'] . '] ' . $arIblock['NAME'] . ' (' . $arIblock['LID'] . ')';
}

// Оставляем только типы, в которых есть активные инфоблоки
$arTypes = array_intersect_key($arTypes, $arIblocksByType);

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

if($session->has('OPTIONS_DATA')){
    $session->remove('OPTIONS_DATA');
}
?>
<link rel="stylesheet" type="text/css" href="/local/modules/mattweb.comments/install/install.css" />
<div class="inst-step-wrap">
    <form action="<?echo $APPLICATION->GetCurPageParam('step=2'); ?>" name="install_step1" method="POST">
    <?=bitrix_sessid_post()?>
    <input type="hidden" name="lang" value="<?echo LANGUAGE_ID?>">
    <input type="hidden" name="id" value="mattweb.comments">
    <input type="hidden" name="install" value="Y">
    <input type="hidden" name="step" value="2">

    <div class="option-wrap"><!--source_iblock_types-->
        <div class="option-wrap-inner">
            <label for="sourceIblockTypes"><?=Loc::GetMessage('SOURCE_IBLOCK_TYPES_TITLE')?></label>
        </div>
        <div class="option-wrap-inner">
            <select name="source_iblock_types" id="sourceIblockTypes">
                <?foreach($arTypes as $key => $value):?>
                <option value="<?=$key?>"><?=$value?></option>
                <?endforeach?>
            </select>
        </div>    
    </div>
    <div class="option-wrap"><!--source_iblock-->
        <div class="option-wrap-inner">
            <label for="sourceIblock"><?=Loc::GetMessage('SOURCE_IBLOCK_TITLE')?></label>
        </div>
        <div class="option-wrap-inner">
            <select disabled name="source_iblock" id="sourceIblock">
                <option value=""><?=Loc::GetMessage('EMPTY_OPTION_VALUE')?></option>
            </select>  
        </div>  
    </div>
    <div class="option-wrap"><!--add_new_iblock-->
        <div class="option-wrap-inner inp-chk">
            <input type="checkbox" name="add_new_iblock" disabled value="Y" id="addNewIblock" />
        </div>    
        <div class="option-wrap-inner">
            <label for="addNewIblock"><?=Loc::GetMessage('ADD_NEW_IBLOCK_TITLE')?></label>            
        </div>        
    </div>
    <div class="option-wrap"><!--add_demo_data-->
        <div class="option-wrap-inner inp-chk">
            <input type="checkbox" name="add_demo_data" value="Y" id="addDemoData" />
        </div>
        <div class="option-wrap-inner">
            <label for="addDemoData"><?=Loc::GetMessage('ADD_DEMO_DATA_TITLE')?>. <span><?=Loc::GetMessage('ADD_DEMO_DATA_NOTICE_TITLE')?></span></label>
        </div>        
    </div>
    <div class="option-wrap"><!--use_captcha-->
        <div class="option-wrap-inner inp-chk">
            <input type="checkbox" name="use_captcha" value="Y" id="useCaptcha" />
        </div>    
        <div class="option-wrap-inner">
            <label for="useCaptcha"><?=Loc::GetMessage('USE_CAPTCHA_TITLE')?></label>
        </div>        
    </div>
    <div class="option-wrap"><!--captcha_sitekey-->
        <div class="option-wrap-inner">
            <label for="captchaSitekey"><?=Loc::GetMessage('CAPTCHA_SITEKEY_TITLE')?></label>
        </div>
        <div class="option-wrap-inner">
            <input disabled type="text" name="captcha_sitekey" value="" id="captchaSitekey" />
        </div>    
    </div>
    <div class="option-wrap"><!--captcha_secret-->
        <div class="option-wrap-inner">
            <label for="captchaSecret"><?=Loc::GetMessage('CAPTCHA_SECRET_TTILE')?></label>
        </div>
        <div class="option-wrap-inner">
            <input disabled type="text" name="captcha_secret" value="" id="captchaSecret" />
        </div>    
    </div>
    <div class="option-wrap"><!--comm_moder_email-->
        <div class="option-wrap-inner">
            <label for=""><?=Loc::GetMessage('COMM_MODER_EMAIL_TITLE')?></label>
        </div>    
        <div class="option-wrap-inner">
            <input type="text" name="comm_moder_email" value="" id="" />
        </div>    
    </div>
    <div class="option-wrap"><!--comm_max_quant-->
        <div class="option-wrap-inner">
            <label for="commMaxQuant"><?=Loc::GetMessage('COMM_MAX_QUANT_TITLE')?></label>
        </div>
        <div class="option-wrap-inner">
            <input type="text" name="comm_max_quant" value="10" id="commMaxQuant" />
        </div>    
    </div>
    <input type="submit" name="" value="<?echo Loc::getMessage("MOD_CONTINUE"); ?>">
    <form>

</div>

<script>
(function(){
    var arIblocksByType = <?=\Bitrix\Main\Web\Json::encode($arIblocksByType)?>;
    var sEmptyText = '<?=CUtil::JSEscape(Loc::getMessage('EMPTY_OPTION_VALUE'))?>';
    
    var form = document.forms['install_step1'];
    if(!form) return;

    var typeSelect = form.elements['source_iblock_types'],
        iblockSelect = form.elements['source_iblock'],        
        addNewIblock = form.elements['add_new_iblock'],
        addDemoData = form.elements['add_demo_data'];
    
    if(!typeSelect || !iblockSelect) return;
    
    addDemoData.addEventListener('change', function(event) {
           if(addDemoData.checked){
                typeSelect.disabled = true;
           }
           else{
                typeSelect.disabled = false;
           }
    });
       
    var useCaptcha = form.elements['use_captcha'],
        captcahSiteKey = form.elements['captcha_sitekey'],
        captchaSecretKey = form.elements['captcha_secret'];
        
    useCaptcha.addEventListener('change', function(event) {
        // Код выполнится при изменении состояния чекбокса
        if(useCaptcha.checked){
            captcahSiteKey.disabled = false;
            captchaSecretKey.disabled = false;
        }
        else{
            captcahSiteKey.value = '';
            captcahSiteKey.disabled = true;
            captchaSecretKey.value = '';
            captchaSecretKey.disabled = true;
        }
        console.log(useCaptcha.checked);

    });    

    typeSelect.addEventListener('change', function(){
        var arList = arIblocksByType[this.value] || {};
        iblockSelect.options.length = 0;
        iblockSelect.options.add(new Option(sEmptyText, ''));

        if(this.value !== ''){
            iblockSelect.disabled = false;
            addNewIblock.disabled = false;
            addDemoData.disabled = true;

            for(var sId in arList){
                if(Object.prototype.hasOwnProperty.call(arList, sId)){
                    iblockSelect.options.add(new Option(arList[sId], sId));
                }
            }
        }
        else{
            iblockSelect.disabled = true;
            addNewIblock.disabled = true;
            addDemoData.disabled = false;
        }
    });

    iblockSelect.addEventListener('change', function(){
        if(this.value !== ''){
           addNewIblock.disabled = true; 
        }
        else{
            addNewIblock.disabled = false;
        }
    });

})();
</script>