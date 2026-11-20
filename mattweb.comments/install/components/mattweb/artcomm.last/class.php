<?php
use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;
use \Mattweb\Comments;


if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class ArtcommLastComponent extends CBitrixComponent
{
    protected function checkModules()
    {
        if (!Loader::includeModule('mattweb.comments'))
        throw new Main\LoaderException(Loc::getMessage('MATTWEB_COMMENTS_MODULE_NOT_INSTALLED'));

        if (!Loader::includeModule('iblock'))
        throw new Main\LoaderException(Loc::getMessage('IBLOCK_MODULE_NOT_INSTALLED'));
    }

    protected function checkIblockId()
    {
        if ($this->arParams["ART_IBLOCK_ID"] <= 0) {
            throw new \Exception(Loc::getMessage('ART_IBLOCK_ID_IS_EMPTY'));
        }
    }
    
	protected function dtStrConv($dt, $dlm = '.'){
		$arMonths = [
			'01' => Loc::getMessage('MONTH_NAME_1_F2'),
			'02' => Loc::getMessage('MONTH_NAME_2_F2'),
			'03' => Loc::getMessage('MONTH_NAME_3_F2'),
			'04' => Loc::getMessage('MONTH_NAME_4_F2'),
			'05' => Loc::getMessage('MONTH_NAME_5_F2'),
			'06' => Loc::getMessage('MONTH_NAME_6_F2'),
			'07' => Loc::getMessage('MONTH_NAME_7_F2'),
			'08' => Loc::getMessage('MONTH_NAME_8_F2'),
			'09' => Loc::getMessage('MONTH_NAME_9_F2'),
			'10' => Loc::getMessage('MONTH_NAME_10_F2'),
			'11' => Loc::getMessage('MONTH_NAME_11_F2'),
			'12' => Loc::getMessage('MONTH_NAME_12_F2')
		];

		$arDate = explode($dlm, $dt);
		
		$dtStr = $arDate[0].' '.$arMonths[$arDate[1]].' '.$arDate[2];
		
		return $dtStr;
	}

    public function onPrepareComponentParams($arParams){

        $arParams["ART_IBLOCK_ID"] = intval($arParams["ART_IBLOCK_ID"]);

        $arParams["ART_PAGE_URL"] = trim($arParams["ART_PAGE_URL"]);
        $arParams["ART_PAGE_URL"] = (!empty($arParams["ART_PAGE_URL"])) ? $arParams["ART_PAGE_URL"] : '/moj-blog/';
        
        $arParams["COMM_DATE_FORMAT"] = trim($arParams["COMM_DATE_FORMAT"]);
        $arParams["COMM_DATE_FORMAT"] = (!empty($arParams["COMM_DATE_FORMAT"])) ? $arParams["COMM_DATE_FORMAT"] : 'F2';

        $arParams["COMM_MAX_LENGTH"] = intval($arParams["COMM_MAX_LENGTH"]);
        $arParams["COMM_MAX_LENGTH"] = ($arParams["COMM_MAX_LENGTH"] > 0) ? $arParams["COMM_MAX_LENGTH"] : 75;

        $arParams["COMM_SHOW_QUANT"] = intval($arParams["COMM_SHOW_QUANT"]);
        $arParams["COMM_SHOW_QUANT"] = ($arParams["COMM_SHOW_QUANT"] > 0) ? $arParams["COMM_SHOW_QUANT"] : 5;

        $arParams["ART_URL_PARAMS"] = trim($arParams["ART_URL_PARAMS"]);        
        $arParams["COMM_URL_TMPL"] = (!empty($arParams["ART_URL_PARAMS"])) ? $arParams["ART_URL_PARAMS"].'#comment-#COMMENT_ID#' : '';

        return $arParams;
    }

    public function executeComponent()
    {
        $arResult = [];
        
        $this->includeComponentLang('class.php');

        try{
            $this->checkModules();
            $this->checkIblockId();
        }
        catch(\Exception $e){
            $this->arResult['ERR_MESSAGES'][] = $e->getMessage();
        }

        if($this->startResultCache()){

            $arOrder = ['ID' => 'desc'];
            $arSelect = ['ID', 'IBLOCK_ID', 'SECT_ID', 'ELEM_ID', 'CREATED_AT', 'NAME', 'COMMENT'];
            $arFilter = ['PUBLISHED' => 1, 'DEPTH_LEVEL'=>1];
            $limit = $this->arParams['COMM_SHOW_QUANT'];

            $rsComms = Comments\CommentTable::getList(array(
                'order' => $arOrder,
                'filter' => $arFilter,
                'select' => $arSelect,
                'limit' => $limit
            ));
            
            $arComms = $rsComms->fetchAll();

            if(count($arComms) > 0){
                $arCERelations = [];

                $arIblockEntities = [];

                $arLatestComms = [];

                $arSmiles = \SmilesActions::getSmilesSRArray();

                foreach($arComms as $arComm){
                    $arLatestComms[$arComm['ID']]['COMMENT_ID'] = $arComm['ID'];

                    // сработает когда все элементы из 1 инфоблока
                    // для разных инфоблоков нужно дорабатывать
                    $arCERelations[$arComm['ELEM_ID']][] = $arComm['ID'];

                    
                    $arIblockEntities['SECTIONS'][$arComm['ID']] = $arComm['SECT_ID'];
                    $arIblockEntities['IBLOCKS'][$arComm['ID']] = $arComm['IBLOCK_ID'];

                    if($this->arParams['COMM_DATE_FORMAT'] == 'F1'){
                        $dateFormatted = $this->dtStrConv($arComm['CREATED_AT'] -> format('d.m.Y'));
                    }
                    elseif($this->arParams['COMM_DATE_FORMAT'] == 'F2'){
                        $dateFormatted = $arComm['CREATED_AT'] -> format('d.m.Y');
                    }
                    else{
                        $dateFormatted = $arComm['CREATED_AT'] -> format('d.m.Y H:i');
                    }
                    $arLatestComms[$arComm['ID']]['CREATED_AT'] = $dateFormatted;
                    $arLatestComms[$arComm['ID']]['AUTHOR_NAME'] = $arComm['NAME'];

                    $arComm['COMMENT'] = \CommentsActions::replaceBBCode($arComm['COMMENT'], true);
                    $arComm['COMMENT'] = \SmilesActions::removeSmiles($arComm['COMMENT']);
                    
                    if(strlen($arComm['COMMENT']) > $this->arParams["COMM_MAX_LENGTH"]){
                        $arComm['COMMENT'] = TruncateText($arComm['COMMENT'], $this->arParams["COMM_MAX_LENGTH"]);
                    }
                    $arLatestComms[$arComm['ID']]['COMMENT'] = $arComm['COMMENT'];
                    
                }          

                if(count($arCERelations) > 0){          
                    $arElems = [];

                    $arIblockEntities['ELEMENTS'] = array_keys($arCERelations);
                    $arIblockElemsFilter = ['IBLOCK_ID' => $this->arParams['ART_IBLOCK_ID'], 'ID' => $arIblockEntities['ELEMENTS']];

                    /*                
                    $dbElems = \Bitrix\Iblock\ElementTable::getList(array(
                        'order' => array('SORT' => 'ASC'),
                        'select' => array('ID', 'NAME', 'CODE'),
                        'filter' => $arIblockElemsFilter,
                        'cache' => array(
                            'ttl' => 3600,
                        ),
                    ));*/

                    // !!! D7 не используется потому что нет возможности получить детальный url статьи !!!
                    $dbElems = \CIBlockElement::GetList(array('SORT' => 'ASC'), $arIblockElemsFilter, false, false, array('ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL'));

                    while($arElem = $dbElems->fetch()) {                 
                    
                        $arElems[$arElem['ID']] = [
                            'NAME' => $arElem['NAME'],
                            'CODE' => $arElem['CODE'],
                        ];                

                        if(!empty($this->arParams["COMM_URL_TMPL"])){
                            $dpu = $this->arParams["ART_PAGE_URL"];
                            $dpu .= \CIBlock::ReplaceDetailUrl($this->arParams["COMM_URL_TMPL"], $arElem, false, 'E');

                            $arElems[$arElem['ID']]['DETAIL_PAGE_URL'] = $dpu;
                        }
                        
                    }

                    foreach($arCERelations as $key => $arCommsID){
                        foreach($arCommsID as $commID){

                            $arSect = \Bitrix\Iblock\SectionTable::getById($arIblockEntities["SECTIONS"][$commID])->fetch();

                            $arLatestComms[$commID]['ELEM_NAME'] = $arElems[$key]['NAME'];

                            if(isset($arElems[$key]['DETAIL_PAGE_URL'])){
                                $arLatestComms[$commID]['ELEM_URL'] = str_replace('#COMMENT_ID#', $commID, $arElems[$key]['DETAIL_PAGE_URL']);
                            }                        
                        }
                    }
                    
                }

                $this->arResult['COMMENTS'] = $arLatestComms;

            }

            $this->includeComponentTemplate();
        }        
    }
};