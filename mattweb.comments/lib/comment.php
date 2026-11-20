<?

namespace Mattweb\Comments;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Context;

use Bitrix\Main\Localization\Loc,
Bitrix\Main\ORM\Data\DataManager,
Bitrix\Main\ORM\Fields\DatetimeField,
Bitrix\Main\ORM\Fields\IntegerField,
Bitrix\Main\ORM\Fields\StringField,
Bitrix\Main\ORM\Fields\TextField,
Bitrix\Main\ORM\Fields\Relations\OneToMany,
Bitrix\Main\ORM\Fields\Validators\LengthValidator;

class CommentTable extends DataManager{

    const COMM_ADD_EVENT_TYPE = 'NEW_COMMENT_ADDED';
    const COMM_REPLY_EVENT_TYPE = 'COMMENT_REPLY_ADDED';

    public static function getTableName()
    {
        return 'el_comment';
    }

    public static function getMap()
    {
        return [
            new IntegerField(
                'ID',
                [
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => Loc::getMessage('COMMENT_ENTITY_ID_FIELD')
                ]
            ),
            new IntegerField(
                'IBLOCK_ID',
                [
                    'required' => true,
                    'title' => Loc::getMessage('COMMENT_ENTITY_IBLOCK_ID_FIELD')
                ]
            ),
            new IntegerField(
                'SECT_ID',
                [
                    'title' => Loc::getMessage('COMMENT_ENTITY_SECT_ID_FIELD')
                ]
            ),
            new IntegerField(
                'ELEM_ID',
                [
                    'required' => true,
                    'title' => Loc::getMessage('COMMENT_ENTITY_ELEM_ID_FIELD')
                ]
            ),
            new IntegerField(
                'PARENT_ID',
                [
                    'required' => true,
                    'title' => Loc::getMessage('COMMENT_ENTITY_PARENT_ID_FIELD'),
                    'default_value' => 0
                ]
            ),
            new IntegerField(
                'DEPTH_LEVEL',
                [
                    'required' => true,
                    'title' => Loc::getMessage('COMMENT_ENTITY_DEPTH_LEVEL_FIELD'),
                    'default_value' => 1
                ]
            ),
            new DatetimeField(
                'CREATED_AT',
                [
                    'required' => true,
                    'title' => Loc::getMessage('COMMENT_ENTITY_CREATED_AT_FIELD'),
                    'default_value' => new \Bitrix\Main\Type\DateTime
                ]
            ),
            new IntegerField(
                'PUBLISHED',
                [
                    'required' => true,
                    'title' => Loc::getMessage('COMMENT_ENTITY_PUBLISHED_FIELD'),
                    'default_value' => 0
                ]
            ),
            new IntegerField(
				'DEL_MARKED',
				[
					'required' => true,
					'title' => Loc::getMessage('COMMENT_ENTITY_DEL_MARKED_FIELD'),
					'size' => 1,
                    'default_value' => 0
				]
			),
            new IntegerField(
                'USER_ID',
                [
                    'title' => Loc::getMessage('COMMENT_ENTITY_USER_ID_FIELD'),
                    'default_value' => 0
                ]
            ),
            new StringField(
                'USERNAME',
                [
                    'validation' => [__CLASS__, 'validateUsername'],
                    'title' => Loc::getMessage('COMMENT_ENTITY_USERNAME_FIELD')
                ]
            ),
            new StringField(
                'NAME',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateName'],
                    'title' => Loc::getMessage('COMMENT_ENTITY_NAME_FIELD')
                ]
            ),
            new StringField(
                'HOMEPAGE',
                [
                    'validation' => [__CLASS__, 'validateHomepage'],
                    'title' => Loc::getMessage('COMMENT_ENTITY_HOMEPAGE_FIELD')
                ]
            ),
            new StringField(
                'IP',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateIp'],
                    'title' => Loc::getMessage('COMMENT_ENTITY_IP_FIELD')
                ]
            ),
            new StringField(
                'EMAIL',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateEmail'],
                    'title' => Loc::getMessage('COMMENT_ENTITY_EMAIL_FIELD')
                ]
            ),
            new TextField(
                'COMMENT',
                [
                    'required' => true,
                    'title' => Loc::getMessage('COMMENT_ENTITY_COMMENT_FIELD')
                ]
            ),
            new IntegerField(
                'SUBSCRIBE',
                [
                    'title' => Loc::getMessage('COMMENT_ENTITY_SUBSCRIBE_FIELD'),
                    'default_value' => 0
                ]
            ),
            new IntegerField(
                'JC_ID',
                [
                    'title' => Loc::getMessage('COMMENT_ENTITY_JC_ID_FIELD'),
                    'default_value' => 0
                ]
            ),           
            (new OneToMany('VOTES', VoteTable::class, 'COMMENT'))
        ];
    }


    /*
    * Создание типов почтовых событий и шаблонов:
    * /bitrix/modules/module_id/install/wizards/softserv/piloram/site/services/main/cevent.php
    */
    public static function OnAfterAdd(Entity\Event $event){
        $result = new Entity\EventResult;
        $data = $event->getParameter('fields');
        
        $elemID = intval($data['ELEM_ID']);
        Loader::IncludeModule('iblock');

        $obElem = \CIBlockElement::GetList(array(), array('ID' => $elemID), false, false, array('ID', 'IBLOCK_ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL'));
        $arElem = $obElem->Fetch();
                
        // уведомление модератора о новом комментарии
		$linkStr = self::getCommentLink($elemID);
        $moderEmail = Option::get('mattweb.comments','comm_moder_email');		
		$message = Loc::GetMessage('MODER_EMAIL_MESS_TMPL', ['#LINK_STR#' => $linkStr, '#AR_ELEM_NAME#' => $arElem['NAME'], '#COMM_ID#' => $data['ID']]);
	
        \Bitrix\Main\Mail\Event::send([
            "EVENT_NAME" => self::COMM_ADD_EVENT_TYPE,
            "LID" => "s1",
            "C_FIELDS" => [
                'MODER_EMAIL' => $moderEmail,
                'MESSAGE_TEXT' => $message,
            ]
        ]);        
    }


    public static function OnBeforeUpdate(Entity\Event $event){

        $primary = $event->getParameter('primary');
        $object = $event->getParameter('object');

        // читаем строку до выполнения SQL UPDATE, чтобы в OnAfterUpdate были старые значения
        $old = static::getByPrimary($primary)->fetch();
        $object->customData->set('oldFields', $old ?: []);
    }


    public static function OnAfterUpdate(Entity\Event $event){

        $primary = $event->getParameter('primary');
        $newData = $event->getParameter('fields');
        $object = $event->getParameter('object');

        $oldData = $object->customData->get('oldFields') ?? [];

        // только реально изменившиеся поля
        $changed = [];
        foreach ($newData as $field => $value) {
            if (!array_key_exists($field, $oldData) || $oldData[$field] != $value) {
                $changed[$field] = ['old' => $oldData[$field] ?? null, 'new' => $value];
            }
        }

        if($oldData['PARENT_ID'] > 0 && $newData['PUBLISHED'] == 1 && $newData['PUBLISHED'] != $oldData['PUBLISHED']){
           
            $arrParentComment = static::getByPrimary($oldData['PARENT_ID'])->fetch();

            $messTxt = $arrParentComment['COMMENT'];
            $commAuthEmail = $arrParentComment['EMAIL' ];
            $replyMessTxt = $oldData['COMMENT'];

            $elemID = intval($oldData['ELEM_ID']);
            
			$linkStr = self::getCommentLink($elemID);
			
            $replyMessLink  = "<a href='".$linkStr."#comment-".$oldData['ID']."'>".Loc::GetMessage('MODER_COMM_LINK')."</a>\r\n";

            \Bitrix\Main\Mail\Event::send([
                "EVENT_NAME" => self::COMM_REPLY_EVENT_TYPE,
                "LID" => "s1",
                "C_FIELDS" => [
                    'COMM_AUTHOR_EMAIL' => $commAuthEmail,
                    'MESSAGE_TEXT' => $messTxt,
                    'MESSAGE_REPLY_TEXT' => $replyMessTxt,
                    'MESSAGE_REPLY_LIINK' => $replyMessLink,
                ]
            ]);
        }
    }

	protected static function getCommentLink($elemID){
		Loader::IncludeModule('iblock');

		$obElem = \CIBlockElement::GetList(array(), array('ID' => $elemID), false, false, array('ID', 'IBLOCK_ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL', 'SECTION_CODE'));
		$arElem = $obElem->Fetch();
		
		$obSect = \CIBlockSection::GetByID($arElem['IBLOCK_SECTION_ID']);
		$arSect = $obSect->Fetch();
		
		$httpProtocol = (\CMain::IsHTTPS()) ? "https://" : "http://";
		$server = Context::getCurrent()->getServer();
		$httpHost = $httpProtocol.$server->get('HTTP_HOST');
		
		$linkStr = str_replace(['#SITE_DIR#', '#SECTION_CODE#', '#ELEMENT_CODE#'],[$httpHost, $arSect['CODE'], $arElem['CODE']], $arElem['DETAIL_PAGE_URL']);
		
		return $linkStr;
	}


    /**
     * Returns validators for USERNAME field.
     *
     * @return array
     */
    public static function validateUsername()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for NAME field.
     *
     * @return array
     */
    public static function validateName()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for HOMEPAGE field.
     *
     * @return array
     */
    public static function validateHomepage()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for IP field.
     *
     * @return array
     */
    public static function validateIp()
    {
        return [
            new LengthValidator(null, 39),
        ];
    }

    /**
     * Returns validators for EMAIL field.
     *
     * @return array
     */
    public static function validateEmail()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

}
