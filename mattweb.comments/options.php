<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Iblock\TypeTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Config\Option;
use Mattweb\Comments;

$module_id = 'mattweb.comments'; //обязательно, иначе права доступа не работают!

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
Loc::loadMessages(__FILE__);

if ($APPLICATION->GetGroupRight($module_id) < "S") {
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

Loader::includeModule('iblock');

$arTypesLangList = [];

$arTypes = ['' => Loc::getMessage('MATTWEB_COMMENTS_IBLOCK_NOT_SELECTED')];

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
$arIblocksByType = [];

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

$fGetIblockList = function($sType) use ($arIblocksByType){
    return ['' => Loc::getMessage('MATTWEB_COMMENTS_IBLOCK_NOT_SELECTED')]
        + ($arIblocksByType[$sType] ?? []);
};

// Тип, выбранный в данный момент. Если ничего не сохранено - селект покажет первый пункт списка
$fGetCurrentType = function() use ($arTypes, $module_id){
    $sType = Option::get($module_id, 'source_iblock_types', '');

    return isset($arTypes[$sType]) ? $sType : key($arTypes);
};

$sCurrentType = $fGetCurrentType();
$arIblocksOfType = $fGetIblockList($sCurrentType);

Loader::includeModule($module_id);

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$allowUseSmiles = Option::get($module_id, 'allow_use_smiles', '');

$obSmTypes = Comments\SmileTypeTable::getList([
    'select' => ['*'],
    'filter' => ['published' => '1'],
    'order' => ['id' => 'asc']
]);

$arSmTypesTmp = $obSmTypes->fetchAll();

$arSmTypes[0] = Loc::getMessage('MATTWEB_COMMENTS_IBLOCK_NOT_SELECTED');

foreach($arSmTypesTmp as $arSmType){
    $arSmTypes[$arSmType['id']] = $arSmType['name'];
}

// Описание опций
$aTabs = array(
    array(
        'DIV'=>'edit1',
        'TAB' => Loc::getMessage('MATTWEB_COMMENTS_TAB_SETTINGS'),
        'OPTIONS' => array(
            ['source_iblock_types', Loc::getMessage('MATTWEB_COMMENTS_SOURCE_IBLOCK_TYPES'), 'P', ['selectbox', $arTypes]],
            ['source_iblock', Loc::getMessage('MATTWEB_COMMENTS_SOURCE_IBLOCK'), '', ['selectbox', $arIblocksOfType]],
            array('av_max_size', Loc::getMessage('MATTWEB_COMMENTS_AV_MAX_SIZE'),
                '10000',
                array('text', 10)),
            
            array("allow_use_smiles", Loc::getMessage('MATTWEB_COMMENTS_ALLOW_USE_SMILES'), "N", array("checkbox", "Y")),
            ['smiles_type', Loc::getMessage('MATTWEB_COMMENTS_SMILES_TYPE'), '', ['selectbox', $arSmTypes]],
            
            array("allow_comm_subscr", Loc::getMessage('MATTWEB_COMMENTS_ALLOW_ANSW_SUBSCR'), "N", array("checkbox", "Y")),
            array("use_captcha", Loc::getMessage('MATTWEB_COMMENTS_USE_CAPTCHA'), "N", array("checkbox", "Y")),
            array('captcha_sitekey', Loc::getMessage('MATTWEB_COMMENTS_CAPTCHA_SITEKEY'), '', array('text', 30)),
            array('captcha_secret', Loc::getMessage('MATTWEB_COMMENTS_CAPTCHA_SECRET'), '', array('text', 30)),
            array('comm_del_mode', Loc::getMessage('MATTWEB_COMMENTS_DEL_MODE'),
            '',
            array('selectbox', array(
                'dm_cascade'=>Loc::getMessage('MATTWEB_COMMENTS_DM_CASCADE'),
                'dm_mark'=>Loc::getMessage('MATTWEB_COMMENTS_DM_MARK'),))
            ),
            array("comm_inform_moder", Loc::getMessage('MATTWEB_COMMENTS_INFORM_MODERATOR'), "N", array("checkbox", "Y")),
            array('comm_moder_email', Loc::getMessage('MATTWEB_COMMENTS_MODERATOR_EMAIL'),
            '',
            array('text', 20)),

            array('comm_max_quant', Loc::getMessage('MATTWEB_COMMENTS_MAX_QUANT_COMMENTS'),
            '10',
            array('text', 10)),

            array('comm_not_auth_max_rights', Loc::getMessage('MATTWEB_COMMENTS_NOT_AUTH_MAX_RIGHTS'),
            'P',
            array('selectbox', $GLOBALS['NOT_AUTH_MAX_RIGHTS'])),

            array('comm_auth_max_rights', Loc::getMessage('MATTWEB_COMMENTS_AUTH_MAX_RIGHTS'),
            'T',
            array('selectbox', $GLOBALS['AUTH_MAX_RIGHTS'])),
            

        ),
    ),
    array(
        "DIV" => "edit2",
        "TAB" => Loc::getMessage("MATTWEB_COMMENTS_TAB_RIGHTS"),
        "TITLE" => Loc::getMessage("MATTWEB_COMMENTS_TAB_TITLE_RIGHTS")
    ),
);

// Сохранение
if ($request->isPost() && $request['Update'] && check_bitrix_sessid())
{
    
    foreach ($aTabs as $aTab)
    {
        $arOptions = $aTab['OPTIONS'];

        if(is_array($arOptions)){
           foreach($arOptions as $arOption){
                if (!is_array($arOption)) //Строка с подсветкой. Используется для разделения настроек в одной вкладке
                    continue;

                if ($arOption['note']) //Уведомление с подсветкой
                    continue;

                $optionName = $arOption[0];

                $optionValue = $request->getPost($optionName);

                Option::set($module_id, $optionName, is_array($optionValue) ? implode(",", $optionValue):$optionValue);
           }
        }
    }

    // $aTabs собран до сохранения, поэтому список инфоблоков всё ещё соответствует
    // прежнему типу. Пересобираем его под только что сохранённый тип, иначе
    // renderInput() не найдёт сохранённый ИБ среди пунктов и покажет пустой селект.
    $sCurrentType = $fGetCurrentType();

    foreach($aTabs as &$aTab){
        if(!is_array($aTab['OPTIONS'] ?? null)) continue;

        foreach($aTab['OPTIONS'] as &$arOption){
            if(is_array($arOption) && ($arOption[0] ?? '') === 'source_iblock'){
                $arOption[3][1] = $fGetIblockList($sCurrentType);
            }
        }
        unset($arOption);
    }
    unset($aTab);
}

// Визуальный вывод
$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>
<? $tabControl->Begin(); ?>
<form method='post' action='<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($request['mid'])?>&amp;lang=<?=$request['lang']?>' name='mattweb_comments_settings'>
    <?=bitrix_sessid_post();?>
    <? foreach ($aTabs as $aTab):
            if($aTab['OPTIONS']):?>
        <? $tabControl->BeginNextTab(); ?>
        <? __AdmSettingsDrawList($module_id, $aTab['OPTIONS']); ?>

    <?      endif;
        endforeach; ?>

    <?
    $tabControl->BeginNextTab();?>

    <?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
    
    <?$tabControl->Buttons(); ?>

    <input type="submit" name="Update" value="<?echo GetMessage('MAIN_SAVE')?>">
    <input type="reset" name="reset" value="<?echo GetMessage('MAIN_RESET')?>">
    
</form>
<? $tabControl->End(); ?>

<script>
(function(){
    var arIblocksByType = <?=\Bitrix\Main\Web\Json::encode($arIblocksByType)?>;
    var sEmptyText = '<?=CUtil::JSEscape(Loc::getMessage('MATTWEB_COMMENTS_IBLOCK_NOT_SELECTED'))?>';

    var form = document.forms['mattweb_comments_settings'];
    if(!form) return;

    var typeSelect = form.elements['source_iblock_types'],
        iblockSelect = form.elements['source_iblock'];

    if(!typeSelect || !iblockSelect) return;

    // Первичный список отрисован на стороне PHP, поэтому перезаполняем только при смене типа
    typeSelect.addEventListener('change', function(){
        var arList = arIblocksByType[this.value] || {};

        iblockSelect.options.length = 0;
        iblockSelect.options.add(new Option(sEmptyText, ''));

        for(var sId in arList){
            if(Object.prototype.hasOwnProperty.call(arList, sId)){
                iblockSelect.options.add(new Option(arList[sId], sId));
            }
        }
    });

    var useSmilesCheck = form.elements['allow_use_smiles'],
    smilesTypeSelect = form.elements['smiles_type'];
    
    if(!useSmilesCheck.checked){
        smilesTypeSelect.parentElement.parentElement.style.display = 'none';
    }

    useSmilesCheck.addEventListener('change', (e) => {
        let useSmilesCheckChecked = useSmilesCheck.checked;
        if(useSmilesCheckChecked){
            smilesTypeSelect.parentElement.parentElement.style.display = 'table-row';
        }
        else{
            smilesTypeSelect.parentElement.parentElement.style.display = 'none';
            smilesTypeSelect.selectedIndex = 0;
        }       

        console.log(useSmilesCheck.checked);
    });

    //console.log(useSmilesCheck.checked);


})();
</script>