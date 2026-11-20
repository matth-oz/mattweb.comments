<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$setId = 2;
$smilesPathDef = '/bitrix/images/main/smiles/'.$setId.'/';

$smilesPath = '/upload/main/smiles/'.$setId.'/';
$iconsPath = '/upload/main/icons/'.$setId.'/';

$arSmiles = CSmile::getList([
    'SELECT' => ['ID', 'SET_ID', 'TYPE', 'NAME', 'TYPING', 'IMAGE'],
    'FILTER' => [],
    'ORDER' => ['ID' => 'ASC'],
    'NAV_PARAMS' => ["nPageSize" => 50],
    'RETURN_SQL' => 'N',
    'RETURN_RES' => 'N', 
],
    //$lang = LANGUAGE_ID
);


$arSmilesSimbols = $arSmilesPaths = [];

foreach($arSmiles as $arSmile){
    
    $smilePath = ($arSmile['TYPE'] == CSmile::TYPE_ICON) ? $iconsPath.$arSmile['IMAGE'] : $smilesPath.$arSmile['IMAGE'];
    $smilePictPath = '<img src="'.$smilePath.'" style="max-width: 18px;" />';

    $arTypingTmp = explode(' ', $arSmile['TYPING']);

    foreach($arTypingTmp as $typingTmp){
    array_push($arSmilesSimbols, $typingTmp);
    array_push($arSmilesPaths, $smilePictPath);
    }

    $arResult['SMILES'][$arSmile['ID']] = [
        'TYPING' => $arTypingTmp,
        'IMAGE' => $arSmile['IMAGE'],
        'PATH' => $smilePath
    ];
}

$arResult['SMILES_REPLACE'] = [
    'SEARCH' => $arSmilesSimbols,
    'REPLACE' => $arSmilesPaths,
];
