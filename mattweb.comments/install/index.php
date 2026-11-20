<?

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config as Conf;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Entity\Base;
use \Bitrix\Main\Application;
use \Bitrix\Main\EventManager;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\TypeTable;
use Bitrix\Iblock\ElementTable;
use \Bitrix\Main\IO\Directory;
use \Bitrix\Main\IO\File;

use Mattweb\Comments;

Loc::loadMessages(__FILE__);
Class mattweb_comments extends CModule
{
    public $warnings = [];
    public $errors = [];
    public $success = [];

    public $siteId;

    public $iblockType; 
    public $iblockCode;

    const EVENT_TYPES = [
        'COMMENT_REPLY_EVENT' => 'COMMENT_REPLY_ADDED',
        'COMMENT_ADDED_EVENT' => 'NEW_COMMENT_ADDED',
        'COMPLAINT_ADDED_EVENT' => 'NEW_COMPLAINT_ADDED'
    ];
    private $exclusionAdminFiles;
    private $arComponentsTargetFolders;
    private $arCommentsDemoData;
    private $showCommentsPropCode;


    function __construct(){
        $arModuleVersion = array();
        include(__DIR__."/version.php");
        
        $this->MODULE_ID = 'mattweb.comments';        
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('MATTWEB_COMMENTS_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('MATTWEB_COMMENTS_MODULE_DESC');

        $this->PARTNER_NAME = Loc::getMessage('MATTWEB_COMMENTS_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('MATTWEB_COMMENTS_PARTNER_URI');

        $this->MODULE_SORT = 1;
        $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS = 'Y';
        $this->MODULE_GROUP_RIGHTS = 'Y';

        $this->siteId = $this->GetCurSiteId();

        $this->exclusionAdminFiles=array(
            '..',
            '.',
            'menu.php',
            'operation_description.php',
            'task_description.php'
        );

        $this->arComponentsTargetFolders = [    
           'artcomm.last' => $_SERVER["DOCUMENT_ROOT"]."/local/components/mattweb/artcomm.last",
           'artcomm.list' => $_SERVER["DOCUMENT_ROOT"]."/local/components/mattweb/artcomm.list",
        ];

        $this->iblockType = 'demo_comments';
        $this->iblockCode = 'comments_demo';

        $this->arCommentsDemoData = [];
        $this->showCommentsPropCode = 'SHOW_COMMENTS';
    }

    //Проверяем что система поддерживает D7
    public function isVersionD7()
    {
        return CheckVersion(\Bitrix\Main\ModuleManager::getVersion('main'), '14.00.00');
    }

    //Определяем место размещения модуля
    public function GetPath($notDocumentRoot=false)
    {
        if($notDocumentRoot)
            return str_ireplace(Application::getDocumentRoot(),'',dirname(__DIR__));
        else
            return dirname(__DIR__);
    }

    function GetModuleRightList()
    {
        return array(
            "reference_id" => array("D","K","S","W"),
            "reference" => array(
                "[D] ".Loc::getMessage("MATTWEB_COMMENTS_DENIED"),
                "[K] ".Loc::getMessage("MATTWEB_COMMENTS_READ_COMPONENT"),
                "[S] ".Loc::getMessage("MATTWEB_COMMENTS_WRITE_SETTINGS"),
                "[W] ".Loc::getMessage("MATTWEB_COMMENTS_FULL"))
        );
    }

    function InstallDB(){

       global $DB;

        // 1) структура таблиц
        $errors = $DB->RunSQLBatch($this->GetPath() . '/install/db/install.sql');
        if ($errors !== false) {
            $this->errors = array_merge($this->errors, $errors);
            return false;
        }

        return true;
    
    }

    function UnInstallDB(){

        global $DB, $APPLICATION;
	    $this->errors = [];
        
        $this->errors = $DB->RunSQLBatch($this->GetPath() . '/install/db/uninstall.sql');

        if(!empty($this->errors)){
            $APPLICATION->ThrowException(implode('', $this->errors));
            return false;
        }

        return true;
    }


    function InstallEventsType(){
    
        $et = new \CEventType;

        $arEventTypes = [];

        $existsCommentReply = \CEventType::GetList(['TYPE_ID' => 'COMMENT_REPLY_ADDED'])->Fetch();
        if(!$existsCommentReply){
            $et->Add([
                'LID'         => 'ru',
                'EVENT_NAME'  => 'COMMENT_REPLY_ADDED',
                'NAME'        => 'Добавлен ответ на комментарий',
                'DESCRIPTION' => "#COMM_AUTHOR_EMAIL# - Электронная почта автора комментария\n#MESSAGE_TEXT# - Текст сообщения\n#MESSAGE_REPLY_TEXT# - Текст ответного сообщения\n#MESSAGE_REPLY_LIINK# - Ссылка на комментарий",
            ]);

            $arEventTypes[] = 'COMMENT_REPLY_ADDED';
        }

        $existsCommentAdded = \CEventType::GetList(['TYPE_ID' => 'NEW_COMMENT_ADDED'])->Fetch();    
        if(!$existsCommentAdded){
            $et->Add([
                'LID'         => 'ru',
                'EVENT_NAME'  => 'NEW_COMMENT_ADDED',
                'NAME'        => 'Добавлен новый комментарий',
                'DESCRIPTION' => "#MODER_EMAIL# - Электронная почта модератора\n#MESSAGE_TEXT# - Текст сообщения",
            ]);

            $arEventTypes[] = 'NEW_COMMENT_ADDED';
        }

        $existsComplaintAdded = \CEventType::GetList(['TYPE_ID' => 'NEW_COMPLAINT_ADDED'])->Fetch();
        if(!$existsComplaintAdded){
            $et->Add([
                'LID'         => 'ru',
                'EVENT_NAME'  => 'NEW_COMPLAINT_ADDED',
                'NAME'        => 'Добавлена новая жалоба',
                'DESCRIPTION' => "#MODERATOR_EMAIL# - Email модератора\n#COMMENT_ID# - Идентификатор комментария\n#COMPLAINT_REASON# - Причина жалобы",
            ]);

            $arEventTypes[] = 'NEW_COMPLAINT_ADDED';
        }

        return $arEventTypes;        
    }
    
    function UnInstallEventsType(){
        $et = new \CEventType;

        foreach(self::EVENT_TYPES as $eventType){
            $existCurEventType = \CEventType::GetList(['TYPE_ID' => $eventType])->Fetch();
            if($existCurEventType){
                $et->Delete($existCurEventType);
            }
        }
    }


    private function getEventTemplateExists($eventName){
        $existingTemplates = [];

        $rs = \CEventMessage::GetList($by = 'id', $order = 'asc', ['TYPE_ID' => $eventName]);
        while ($tpl = $rs->Fetch()) {
            $existingTemplates[] = (int)$tpl['ID'];
        }

        return $existingTemplates;
    }


    function InstallCommentReplyTemplates(){
        // 'COMMENT_REPLY_ADDED'

        $existing = [
          'TYPE_ID' => 'COMMENT_REPLY_ADDED',
          'TEMPLATES' => $this->getEventTemplateExists('COMMENT_REPLY_ADDED'),  
        ];

        if (count($existing['TEMPLATES']) >= 1) {
            // шаблон уже есть — берём существующие, ничего не создаём
            return $existing; // $existing['TEMPLATES']
        }

        $emess = new \CEventMessage;

        // Добавлен ответ на комментарий
        $tplCommentReply = (int)$emess->Add([
            'ACTIVE'     => 'Y',
            'EVENT_NAME' => 'COMMENT_REPLY_ADDED',
            'LID'        => [$this->siteId],
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO'   => '#COMM_AUTHOR_EMAIL#',
            'SUBJECT'    => 'На ваш комментарий добавлен ответ',
            'BODY_TYPE'  => 'text',
            'MESSAGE'    => "Добрый день! \nНа сайте #SITE_NAME# появился ответ на ваш комментарий.\n
            Вы писали:\n#MESSAGE_TEXT#\n
            Вам ответили:\n#MESSAGE_REPLY_TEXT#\n
            Ссылка на комментарий:\n#MESSAGE_REPLY_LIINK#\n
            ------\n
            Письмо отравлено автоматически",
        ]);

        $existing['TEMPLATES'][] = $tplCommentReply;

        return $existing; // $existing['TEMPLATES']
    }


    function InstallCommentAddedTemplates(){
        // 'NEW_COMMENT_ADDED'
        $existing = [
          'TYPE_ID' => 'NEW_COMMENT_ADDED',
          'TEMPLATES' => $this->getEventTemplateExists('NEW_COMMENT_ADDED'),  
        ];

        if (count($existing['TEMPLATES']) >= 1) {
            // шаблон уже есть — берём существующие, ничего не создаём
            return $existing; // $existing['TEMPLATES']
        }

        $emess = new \CEventMessage;
        $tplCommentAdded = (int)$emess->Add([
            'ACTIVE'     => 'Y',
            'EVENT_NAME' => 'NEW_COMMENT_ADDED',
            'LID'        => [$this->siteId],
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO'   => '#MODER_EMAIL#',
            'SUBJECT'    => 'На сайте #SITE_NAME# добавлен новый коментарий',
            'BODY_TYPE'  => 'text',
            'MESSAGE'    => "Добрый день! \nНа сайте #SITE_NAME# добавлен новый комментарий:.\n
            \n#MESSAGE_TEXT#\n
            Ознакомьтесь с ним в панели управления сайтом и активируйте его для отображения в статье.\n
            Ссылка на комментарий:\n#MESSAGE_REPLY_LIINK#\n
            ------\n
            Письмо отравлено автоматически",
        ]);

        $existing['TEMPLATES'][] = $tplCommentAdded;
        
        return $existing; // $existing['TEMPLATES']
    }

    function InstallComplaintAddedTemplates(){
        // 'NEW_COMPLAINT_ADDED'
        $existing = [
          'TYPE_ID' => 'NEW_COMPLAINT_ADDED',
          'TEMPLATES' => $this->getEventTemplateExists('NEW_COMPLAINT_ADDED'),  
        ];

        if (count($existing['TEMPLATES']) >= 1) {
            // шаблон уже есть — берём существующие, ничего не создаём
            return $existing; // $existing['TEMPLATES']
        }

        $emess = new \CEventMessage;

        $tplComplaintAdded = (int)$emess->Add([
            'ACTIVE'     => 'Y',
            'EVENT_NAME' => 'NEW_COMPLAINT_ADDED',
            'LID'        => [$this->siteId],
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO'   => '#MODERATOR_EMAIL#',
            'SUBJECT'    => 'На сайте #SITE_NAME# добавлена новая жалоба',
            'BODY_TYPE'  => 'text',
            'MESSAGE'    => "Добрый день! \nНа сайте #SITE_NAME# добавлена новая жалоба.:.\n
            \nКомментарий: Ссылка на комментарий [#COMMENT_ID#]\n
            Причина жалобы: #COMPLAINT_REASON#.\n                    
            ------\n
            Письмо отравлено автоматически",
        ]);
        
        $existing['TEMPLATES'][] = $tplComplaintAdded;
        
        return $existing; // $existing['TEMPLATES']
    }

    function UnInstallEventTemplates($eventName){
        $existing = [];
        $rs = \CEventMessage::GetList($by = 'id', $order = 'asc', ['TYPE_ID' => $eventName]);
        while ($tpl = $rs->Fetch()) {
            $existing[] = (int)$tpl['ID'];
        }

        if (count($existing) > 0) {
            
            $emess = new \CEventMessage;
            
            foreach($existing as $tmplId){
                $emess->Delete($tmplId);
            }            
        }
    }

    function InstallFiles($arParams = array()){

        // компоненты
        foreach($this->arComponentsTargetFolders as $compName => $compPath){
            
            if(Directory::isDirectoryExists($compPath)){
                 $this->warnings[] = 'Папка компонента /'.$compName.'/ уже существует — пропущено';
            }
            else{
                Directory::createDirectory($compPath);
                CopyDirFiles(
                    $_SERVER["DOCUMENT_ROOT"]."/local/modules/mattweb.comments/install/components/mattweb/".$compName.'/', 
                    $_SERVER["DOCUMENT_ROOT"]."/local/components/mattweb/".$compName.'/', 
                    true, 
                    true
                );
            }
        }

        // Смайлы
        $smilesFolder = $_SERVER["DOCUMENT_ROOT"]."/local/images/mattweb.comments/smilies";
        if(Directory::isDirectoryExists($smilesFolder)){
            $this->warnings[] = 'Папка для смайлов /'.$smilesFolder.'/ уже существует — пропущено';
        }
        else{
            Directory::createDirectory($smilesFolder);
            CopyDirFiles(
                $_SERVER["DOCUMENT_ROOT"]."/local/modules/mattweb.comments/install/images/smilies/", 
                $_SERVER["DOCUMENT_ROOT"]."/local/images/mattweb.comments/smilies/", 
                true, 
                true
            );
        }
        
        // шаблоны news и news.detail
        $templateTargetFolder = $_SERVER["DOCUMENT_ROOT"]."/local/templates/.default";
        if(!Directory::isDirectoryExists($templateTargetFolder)){
            Directory::createDirectory($templateTargetFolder);            
        }
        
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/local/modules/mattweb.comments/install/templates/.default/",
            $_SERVER["DOCUMENT_ROOT"]."/local/templates/.default/",
            true,
            true
        );

        // демо страница
        $demoContentPath = $_SERVER["DOCUMENT_ROOT"]."/comments_demo";
        if(Directory::isDirectoryExists($demoContentPath)){
                $this->warnings[] = 'Папка компонента /'.$demoContentPath.'/ уже существует — пропущено';
        }
        else{
            Directory::createDirectory($demoContentPath);
            CopyDirFiles(
                $_SERVER["DOCUMENT_ROOT"]."/local/modules/mattweb.comments/install/public/comments_demo/", 
                $_SERVER["DOCUMENT_ROOT"]."/comments_demo/", 
                true, 
                true
            );
        }
    
        // Админ-страницы
        if (Directory::isDirectoryExists($path = $this->GetPath() . '/admin'))
        {
            if ($dir = opendir($path))
            {
                while (false !== $item = readdir($dir))
                {
                    if (in_array($item,$this->exclusionAdminFiles))
                        continue;
                    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.$item,
                        '<'.'? require("'.$this->GetPath(false).'/admin/'.$item.'");?'.'>');
                }
                closedir($dir);
            }
        }

        return true;
    }

    function UnInstallFiles(){

        // проверяем существуют ли папки компонентов и шаблона сайта 
        // если да, то удаляем их
        foreach($this->arComponentsTargetFolders as $compName => $compPath){           
            if(Directory::isDirectoryExists($compPath)){
                DeleteDirFilesEx($compPath);
            }            
        }

        // Смайлы
        $smilesFolder = $_SERVER["DOCUMENT_ROOT"]."/local/images/mattweb.comments/smilies";
        if(file_exists($smilesFolder)){
            DeleteDirFilesEx($smilesFolder);
        }

        // шаблоны news и news.detail
        if(file_exists($_SERVER["DOCUMENT_ROOT"]."/local/templates/.default/components/bitrix/news/blog_demo/")){
            DeleteDirFilesEx("/local/templates/.default/components/bitrix/news/blog_demo/");
        }

        // шаблоны news и news.detail
        if(file_exists($_SERVER["DOCUMENT_ROOT"]."/local/templates/.default/components/bitrix/news.detail/blog_demo/")){
            DeleteDirFilesEx("/local/templates/.default/components/bitrix/news.detail/blog_demo/");
        }

        // демо страница
        $demoTargetFolder = $_SERVER['DOCUMENT_ROOT'] . '/comments_demo/';
        if(Directory::isDirectoryExists($demoTargetFolder)){
            DeleteDirFilesEx($demoTargetFolder);
        }

        // Админ-страницы
        if (Directory::isDirectoryExists($path = $this->GetPath() . '/admin')) {             
             if ($dir = opendir($path)) {
                 while (false !== $item = readdir($dir)) {
                     if (in_array($item, $this->exclusionAdminFiles))
                         continue;
                     File::deleteFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/' . $item);
                 }
                 closedir($dir);
             }
        }

        return true;
    }

    function DoInstall(){
        global $USER, $APPLICATION;
        
        if ($USER->IsAdmin()){
            
            $context = Application::getInstance()->getContext();
            $request = $context->getRequest();

            if($request['step'] < 2){
                $APPLICATION->IncludeAdminFile(Loc::getMessage("MATTWEB_COMMENTS_INSTALL_TITLE"), $this->GetPath()."/install/step1.php");
            }
            elseif ($request['step'] == 2){
                $arErrors = $optionsData = [];

                $session = Application::getInstance()->getSession();
                
                $installParams = $request->getPostList();

                // создание нового инфоблока
                if(isset($installParams['create_new_iblock'])){
                    if(strlen($installParams['source_iblock_type']) == 0 && !isset($installParams['source_iblock_name']) && !isset($installParams['source_iblock_code'])){
                        $arErrors[] = 'Не указано название и код инфоблока. Создание нового инфоблока невозможно';
                    }
                }
                else{
                    if(strlen($installParams['source_iblock']) == 0 && !isset($installParams['add_new_iblock']) && !isset($installParams['add_demo_data'])){
                        $arErrors[] = 'Не выбран существующий инфоблок и не выбрано создание нового инфоблока';
                    }

                    if(isset($installParams['use_captcha']) && (strlen(trim($installParams['captcha_sitekey'])) == 0 || strlen(trim($installParams['captcha_secret'])) == 0)){
                        $arErrors[] = 'Не переданы ключи для Google reCaptcha';
                    }

                    if (strlen(trim($installParams['comm_moder_email'])) > 0 && !filter_var(trim($installParams['comm_moder_email']), FILTER_VALIDATE_EMAIL)) {
                        $arErrors[] =  'Некорректный формат email';
                    }  
                }

                if(!empty($arErrors)){
                    self::ErrorRedirect($arErrors, $session);
                }
                else{

                   Loader::includeModule('iblock');

                   if(!isset($installParams['create_new_iblock'])){
 
                        $optionsData['source_iblock_types'] =  $installParams['source_iblock_types'];
                        
                        if(strlen($installParams['source_iblock']) != 0){
                            $optionsData['source_iblock'] =  $installParams['source_iblock'];
                        }                   

                        $optionsData['use_captcha'] = $installParams['use_captcha'];
                        $optionsData['captcha_sitekey'] = trim($installParams['captcha_sitekey']);
                        $optionsData['captcha_secret'] = trim($installParams['captcha_secret']);

                        if(!empty($installParams['comm_moder_email'])){
                                $optionsData['comm_moder_email'] = trim($installParams['comm_moder_email']);
                        }

                        $optionsData['comm_max_quant'] = 10;
                        if((int)$installParams['comm_max_quant'] > 0){
                                $optionsData['comm_max_quant'] = $installParams['comm_max_quant'];
                        }

                        // перед показом формы с параметрами для создания инфоблока
                        if(isset($installParams['add_new_iblock'])){
                                $optionsData['add_new_iblock'] = trim($installParams['add_new_iblock']);
                        }

                        // перед созданием инфоблока с демо-данными
                        if(isset($installParams['add_demo_data'])){
                                $optionsData['add_demo_data'] = $installParams['add_demo_data'];                        
                        }  

                        $session->set('OPTIONS_DATA', $optionsData);
                   }
                   else{
                        // создание нового инфоблока
                        $optionsData = $session->get('OPTIONS_DATA');

                        if(array_key_exists('add_new_iblock', $optionsData)){
                            unset($optionsData['add_new_iblock']);
                        }

                        $optionsData['source_iblock_types'] =  $installParams['source_iblock_types'];
                        $optionsData['create_new_iblock'] =  $installParams['create_new_iblock'];

                        $optionsData['source_iblock_name'] =  trim($installParams['source_iblock_name']);
                        $optionsData['source_iblock_code'] =  trim($installParams['source_iblock_code']);
                        $optionsData['list_page_url'] =  trim($installParams['list_page_url']);
                        $optionsData['detail_page_url'] =  trim($installParams['detail_page_url']);

                        $iblock = $this->GetDataIblockList($optionsData['source_iblock_code']);
                        
                        if(sizeof($iblock) > 0){
                            // ReplaceMacrosRecursive
                            // $this->warnings
                            $arErrors[] =  'Инфоблок с символьным кодом '.$optionsData['source_iblock_code'].' уже существует';
                        }
                        else{
                            // создаем новый инфоблок на основе данных из шага 1_1:

                            $iblockIdRes = $this->AddDataIblock($optionsData);
                            $optionsData['source_iblock'] = $iblockIdRes;
                       
                            if(!is_array($iblockIdRes) && intval($iblockIdRes) > 0){
                                //$propValID = $this->AddShowCommentProperty($iblockIdRes);
                                $this->AddShowCommentProperty($iblockIdRes);
                            }
                        }

                        if(!empty($arErrors)){
                            $session->set('ERRORS', $arErrors);
                            $APPLICATION->IncludeAdminFile(Loc::getMessage("MATTWEB_COMMENTS_INSTALL_TITLE"), $this->GetPath()."/install/step1_1.php");                               
                        }
                        else{
                            $session->set('OPTIONS_DATA', $optionsData);
                        }
                        
                   }
                      
                   // создаем инфоблок с демо-данными
                   if(array_key_exists('add_demo_data', $optionsData)){

                        $optionsData = $session->get('OPTIONS_DATA');

                        if(array_key_exists('add_new_iblock', $optionsData)){
                            unset($optionsData['add_new_iblock']);
                        }

                        $arType = $this->GetDataIblockTypeList($this->iblockType);                                

                        if(sizeof($arType) == 0){
                            $iblockRes = $this->AddDataIblockType($this->iblockType);                        
                        }
                        
                        if(!is_array($iblockRes)){
                            $optionsData['source_iblock_types'] = $this->iblockType;

                            $iblockXMLFile = $_SERVER["DOCUMENT_ROOT"].'/local/modules/mattweb.comments/install/iblock_data/comments_demo.xml';
                        
                            // проверяем существует ли инфоблок по полученным данным из шага 1_1;
                            $result = IblockTable::getList([
                                'select' => ['ID', 'NAME', 'CODE'],
                                'filter' => [
                                    '=CODE' => $this->iblockCode,
                                ],
                            ]);

                            $iblock = $result->fetch();

                            if(is_array($iblock)){
                                // $this->warnings
                                $arErrors[] =  'Инфоблок с символьным кодом '.$this->iblockCode.' уже существует';
                            }
                            else{
                                $permissions = Array(
                                        "1" => "X",
                                        "2" => "R"
                                    );
                                
                                // создаем ИБ Новости из xml файла, привязываем его к типу demo_comments
                                $iblockId = self::ImportIBlockFromXML(
                                    $iblockXMLFile,
                                    $this->iblockCode,
                                    $this->iblockType,
                                    $this->siteId,
                                    $permissions
                                );

                                if ($iblockId < 1){
                                    $arErrors[] =  'Ошибка при импорте инфоблока с символьным кодом '.$this->iblockCode;
                                }
                                else{
                                    /*$session->set('source_iblock', $iblockId);*/

                                    $optionsData['source_iblock'] = $iblockId;
                                    $session->set('OPTIONS_DATA', $optionsData);
                                    
                                    // Добавляем демо комменты. Они находятся в массиве в файле comments_demo_data.php
                                    $commentsDemoDataFile = $_SERVER["DOCUMENT_ROOT"].'/local/modules/mattweb.comments/install/iblock_data/comments_demo_data.php';
                                    $this->arCommentsDemoData = require($commentsDemoDataFile);

                                    $arFilterCodes = $arCodesIds = [];

                                    foreach($this->arCommentsDemoData as $key=>$arCommTmp){                                        
                                        if(!in_array($arCommTmp['ELEM_CODE'], $arCodesIds)){
                                            $arCodesIds[$arCommTmp['ELEM_CODE']][] = $key;
                                        }                                        
                                    }

                                    $arFilterCodes = array_keys($arCodesIds);
                                    
                                    Loader::IncludeModule('iblock');

                                    $arIblockElems = [];

                                    $result = ElementTable::getList([
                                        'select' => ['ID', 'NAME', 'CODE'],
                                        'filter' => [
                                            'IBLOCK_ID' => $iblockId,
                                            'ACTIVE' => 'Y',
                                            '=CODE' => $arFilterCodes,
                                        ],
                                    ]);

                                    while($arIblockItem = $result->fetch()){
                                        $arIblockElems[$arIblockItem['CODE']] = $arIblockItem;
                                    }

                                    foreach($arCodesIds as $code=>$arCommentsDemoDataKeys){
                                        foreach ($arCommentsDemoDataKeys as $k){
                                            $this->arCommentsDemoData[$k]['ELEM_ID'] = $arIblockElems[$code]['ID'];
                                            $this->arCommentsDemoData[$k]['IBLOCK_ID'] = $iblockId;
                                        }
                                    }

                                    // устанавливаем значение показывать комментарии у демо статей в инфоблоке
                                    $property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$iblockId, "CODE" => $this->showCommentsPropCode));
                                    while($enum_field = $property_enums->GetNext())
                                    {
                                        if($enum_field['PROPERTY_CODE'] == $this->showCommentsPropCode && $enum_field['XML_ID'] == 'Y'){
                                            $valueId = $enum_field['ID'];
                                            break;
                                        }
                                    }

                                    foreach($arIblockElems as $arIblockElem){
                                        $elemId = $arIblockElem['ID'];

                                        $res = CIBlockElement::SetPropertyValuesEx(
                                            $elemId,
                                            $iblockId,
                                            [$this->showCommentsPropCode => $valueId]
                                        );
                                    }

                                    // $arIblockElems[$code]['ID'];

                                }
                            }
                        }
                        else{
                           $arErrors[] = $iblockRes;
                        }

                        if(!empty($arErrors)){
                            self::ErrorRedirect($arErrors, $session);
                        }                        
                   }

                   if(array_key_exists('add_new_iblock', $optionsData)){
                        // показываем форму для добавления нового инфоблока
                        $APPLICATION->IncludeAdminFile(Loc::getMessage("MATTWEB_COMMENTS_INSTALL_TITLE"), $this->GetPath()."/install/step1_1.php");
                   }

                   if(array_key_exists('source_iblock_types', $optionsData) && array_key_exists('source_iblock', $optionsData)){

                       if ($this->isVersionD7()){
                            $this->InstallDB();

                            $addCommDemo = $this->AddCommentDemoData();
                            
                            if(is_array($addCommDemo)){
                                $APPLICATION->ThrowException(implode('', $addCommDemo));
                            }
                            
                            $arEventTypesTemplates = [];
                            $arEventTypes = $this->InstallEventsType();

                            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/installLog.txt', "arEventTypes\r\n", FILE_APPEND | LOCK_EX);
                            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/installLog.txt', var_export($arEventTypes, true), FILE_APPEND | LOCK_EX);
                            
                            // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/installLog.txt', "session\r\n", FILE_APPEND | LOCK_EX);
                            // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/installLog.txt', var_export($_SESSION['OPTIONS_DATA'], true), FILE_APPEND | LOCK_EX);

                            if(in_array('COMMENT_REPLY_ADDED', $arEventTypes)){
                                $arEventTypesTemplates[] = $this->InstallCommentReplyTemplates();
                            }

                            if(in_array('NEW_COMMENT_ADDED', $arEventTypes)){
                                $arEventTypesTemplates[] = $this->InstallCommentAddedTemplates();
                            }

                            if(in_array('NEW_COMPLAINT_ADDED', $arEventTypes)){
                                $arEventTypesTemplates[] = $this->InstallComplaintAddedTemplates();
                            }

                            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/installLog.txt', "arEventTypesTemplates\r\n", FILE_APPEND | LOCK_EX);
                            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/installLog.txt', var_export($arEventTypesTemplates, true), FILE_APPEND | LOCK_EX);

                            $this->InstallFiles();

                            require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/wizard.php");
                            
                            CWizardUtil::ReplaceMacros(
                                $_SERVER["DOCUMENT_ROOT"]."/comments_demo/index.php",
                                [
                                    '#DEMO_IBLOCK_ID#' => $optionsData['source_iblock'],
                                    '#DEMO_IBLOCK_TYPE#' => $optionsData['source_iblock_types'],
                                ],
                                true
                            );

                            // Добавляем правило обработки
                            CUrlRewriter::Add(array(
                                "SITE_ID" => $this->GetCurSiteId(),
                                "CONDITION" => '#^/comments_demo/#',
                                'RULE' => '',
                                "ID" => 'bitrix:news',
                                "PATH" => '/comments_demo/index.php',
                                'SORT' => 100,
                            ));
                        
                            $this->SaveOptions($optionsData);


                            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
                        }
                        else
                        {
                            $APPLICATION-ThrowException(Loc::getMessage('MATTWEB_COMMENTS_INSTALL_ERROR_VERSION'));
                        }
                        
                        $APPLICATION->IncludeAdminFile(Loc::getMessage("MATTWEB_COMMENTS_INSTALL_TITLE"), $this->GetPath()."/install/step2.php");
                   }
                   
                }


            }
        }
    }

    function DoUninstall(){
        global $APPLICATION;

        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        if($request['step'] < 2){
            $APPLICATION->IncludeAdminFile(Loc::getMessage("MATTWEB_COMMENTS_UNINSTALL_TITLE"), $this->GetPath()."/install/unstep1.php");
        }
        elseif ($request['step'] == 2){

            $this->UnInstallFiles();
            $this->UnInstallEventsType();

            foreach(self::EVENT_TYPES as $eventType){
                $this->UnInstallEventTemplates($eventType);
            }

            if($request['savedata'] !=  'Y'){
                $this->UnInstallDB();

                // удалить демо-инфоблок
                $dataIblock = $this->GetDataIblockList($this->iblockCode);

                if(!empty($dataIblock)){
                    IblockTable::delete($dataIblock['ID']);
                }

                // удалить тип демо-инфоблока
                $dataIblockType = $this->GetDataIblockTypeList($this->iblockType);
                if(!empty($dataIblockType)){
                    TypeTable::delete($this->iblockType);
                }
            }                

            \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile(Loc::getMessage("MATTWEB_COMMENTS_UNINSTALL_TITLE"), $this->GetPath()."/install/unstep2.php");
        }
    }


    public static function ErrorRedirect($arErrors, $sessionObj){
        global $APPLICATION;

        $returnUrl = $APPLICATION->GetCurPageParam('', ['step']);
        $sessionObj->set('ERRORS', $arErrors);
        LocalRedirect($returnUrl);
    }
    

    public static function ImportIBlockFromXML($xmlFile, $iblockCode, $iblockType, $siteID, $permissions = Array())
	{
		if (!CModule::IncludeModule("iblock"))
			return false;

		$rsIBlock = CIBlock::GetList(array(), array("CODE" => $iblockCode, "TYPE" => $iblockType, "SITE_ID" => $siteID));
		if ($arIBlock = $rsIBlock->Fetch())
			return false;

		if (!is_array($siteID))
			$siteID = Array($siteID);

		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/classes/mysql/cml2.php");
		$iblockID = ImportXMLFile(
			$xmlFile,
			$iblockType,
			$siteID,
			$section_action = "N",
			$element_action = "N",
			$use_crc = false,
			$preview = false,
			$sync = false,
			$return_last_error = false,
			$return_iblock_id = true
		);
		//Import was not able to return iblock_id by error
		if ((!is_integer($iblockID)) || ($iblockID <= 0))
		{
			//try to find iblock
			$rsIBlock = CIBlock::GetList(array(), array("CODE" => $iblockCode, "TYPE" => $iblockType, "SITE_ID" => $siteID));
			if ($arIBlock = $rsIBlock->Fetch())
				$iblockID = $arIBlock["ID"];
			else
				$iblockID = false;
		}
		//Set iblock permissions
		if ($iblockID > 0)
		{
			if (empty($permissions))
				$permissions = Array(1 => "X", 2 => "R");

			CIBlock::SetPermission($iblockID, $permissions);
		}
		return $iblockID;
	}

    private function GetCurSiteId(): string
    {
        $rsSites = \CSite::GetList($by="sort", $order="desc", array("DOMAIN" => $_SERVER['SERVER_NAME']));
        $arSite = $rsSites->Fetch();
        $siteId = !empty($arSite["LID"]) ? $arSite["LID"] : 's1';

        return $siteId;
    }
    
    /**
     * Сохраняет опцию в том же формате, в котором её читает getOptionValue():
     * имя = "{name}_{siteId}", плюс привязка к сайту.
     */
    private function setCommentOption(string $name, string $value, string $siteId = 's1'): void
    {
        //Option::set($this->MODULE_ID, $name, $value, $siteId);
        Option::set($this->MODULE_ID, $name, $value);
    }

    private function SaveOptions(array $optionsData): void
    {
        $defaults = [
            'source_iblock_types' => $optionsData['source_iblock_types'],
            'source_iblock' => $optionsData['source_iblock'],
            'comm_max_quant' => $optionsData['comm_max_quant'],
        ];

        if(!empty($optionsData['comm_moder_email'])){
            $defaults['comm_moder_email'] = (string)$optionsData['comm_moder_email'];
        }

        if(!empty($optionsData['use_captcha']) && !empty($optionsData['captcha_sitekey']) && !empty($optionsData['captcha_secret'])){
            $defaults['use_captcha'] = (string)$optionsData['use_captcha'];
            $defaults['captcha_sitekey'] = (string)$optionsData['captcha_sitekey'];
            $defaults['captcha_secret'] = (string)$optionsData['captcha_secret'];
        }

        $logDir = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/pfm/';
        if (!Directory::isDirectoryExists($logDir)) {
            Directory::createDirectory($logDir);
        }
        
        foreach ($defaults as $name => $value) {
            $this->setCommentOption($name, $value);   // обёртка из 01-option-keys.md
        }
    }

    private function GetDataIblockTypeList(string $dataIblockTypeId): array
    {
        $result = TypeTable::getList([
            'select' => [
                'ID',
                'SECTIONS',
            ],
            'filter' => [
                '=ID' => $dataIblockTypeId,
            ],
        ]);

        $type = $result->fetch();
        return (!$type) ? [] : $type;
    }

    private function AddDataIblockType($iblockTypeId){
        $arErrors = [];
        $arFields = [
            'ID' => $iblockTypeId,
            'SECTIONS'=>'Y',
            'IN_RSS'=>'N',
            'SORT'=>100,
            'LANG'=> [
                'en'=> [
                    'NAME'=>'Demo-comments',
                    'SECTION_NAME'=>'Sections',
                    'ELEMENT_NAME'=>'Elements'
                ],
                'ru'=> [
                    'NAME'=>'Демо-комменты',
                    'SECTION_NAME'=>'Разделы',
                    'ELEMENT_NAME'=>'Элементы'
                ]
            ]
        ];

        $obBlocktype = new CIBlockType();
        $resId = $obBlocktype->Add($arFields);
        if(!$resId){
            $arErrors[] =  'Ошибка при создании типа инфоблока: '.$obBlocktype->LAST_ERROR;
            return $arErrors;
        }

        return $resId;
    }

    private function GetDataIblockList($dataIblockCode){
        // проверяем существует ли инфоблок по полученным данным из шага 1_1;
        $result = IblockTable::getList([
            'select' => ['ID', 'NAME', 'CODE'],
            'filter' => [
                '=CODE' => $dataIblockCode,
            ],
        ]);

        $iblock = $result->fetch();

        return (!$iblock) ? [] : $iblock;
    }

    private function AddDataIblock($dataIblockFields){
        $arErrors = [];

        $iblockObj = new CIBlock();

        $iblockId = $iblockObj->Add([
            'IBLOCK_TYPE_ID' => $dataIblockFields['source_iblock_types'],
            'LID' => [$this->siteId], // Привязка к сайту (массив ID)
            'CODE' => $dataIblockFields['source_iblock_code'], // Символьный код
            'NAME' => $dataIblockFields['source_iblock_name'], // Название
            'ACTIVE' => 'Y', // Активность
            'SORT' => 100, // Сортировка
            'LIST_PAGE_URL' => $dataIblockFields['list_page_url'], // URL списка
            'DETAIL_PAGE_URL' => $dataIblockFields['detail_page_url'], // URL детальной страницы
        ]);

        if ($iblockId <= 0) {
            // Обработка ошибок                               
            $arErrors[] =  'Ошибки при создании инфоблока: '.$iblockObj->LAST_ERROR;
            return $arErrors;
        }

        return $iblockId;
    }

    private function AddShowCommentProperty($iblockId){
        $arErrors = [];
        $property = new \CIBlockProperty();
        $propertyId = $property->Add([
            'IBLOCK_ID' => $iblockId, // ID нужного инфоблока
            'NAME' => 'Показать комментарии к элементу', // Название свойства
            'ACTIVE' => 'Y',
            'SORT' => 100,
            'CODE' => $this->showCommentsPropCode, // Код свойства (латиница, snake_case)
            'PROPERTY_TYPE' => 'L', // Тип: L — список
            'LIST_TYPE' => 'L' // Внешний вид: C — флажки
        ]);

        if (!$propertyId) {
            // Обработка ошибки
            $arErrors[] = 'Ошибка: ' . $property->LAST_ERROR;
            return $arErrors;
        }
        else{
            $ibpEnum = new \CIBlockPropertyEnum();
            $propValID = $ibpEnum->Add(['PROPERTY_ID'=>$propertyId, 'XML_ID' => 'Y', 'VALUE'=>'Показать', 'SORT' => 100]);
            
            if (!$propValID) {
                // Обработка ошибки
                $arErrors[] = 'Ошибка: ' . $ibpEnum->LAST_ERROR;
                return $arErrors;                                       
            }
        }                          

        return $propValID;
    }

    private function AddCommentDemoData(){
        $arErrors = [];

        if(!empty($this->arCommentsDemoData)){
            $db = \Bitrix\Main\Application::getConnection();
            foreach($this->arCommentsDemoData as $key => &$arDemoItem){
                try
                {                    
                    unset($arDemoItem['ELEM_CODE']);
                    $commentId = $db->add('el_comment', $arDemoItem);

                    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/installLog.txt', "Добавлен новый тестовый комментарий\r\n", FILE_APPEND | LOCK_EX);
                    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/installLog.txt', "ID нового комментария ".$commentId."\r\n", FILE_APPEND | LOCK_EX);
                    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/installLog.txt', "===+++====\r\n", FILE_APPEND | LOCK_EX);
                    
                }
                catch (SqlQueryException $e)
                {
                    $arErrors[$key] = [
                        'ID' => $key,
                        'ERROR_TXT' => $e->getDatabaseMessage(),
                        'ERROR_SQL' => $e->getQuery()
                    ];
                }
            }
        }

        return !empty($arErrors) ? $arErrors : true;
    }
}