<?
namespace Mattweb\Comments;

use Bitrix\Main\Entity;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\DatetimeField,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
    Bitrix\Main\ORM\Fields\Relations\Reference,
    Bitrix\Main\ORM\Query\Join,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * Class VoteTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> COMMENT_ID int mandatory
 * <li> USER_ID string(10) mandatory
 * <li> IP string(39) mandatory
 * <li> CREATED_AT datetime mandatory
 * <li> VALUE int mandatory
 * </ul>
 *
 * @package Bitrix\Comment
 **/

 class VoteTable extends DataManager
 {
     /**
      * Returns DB table name for entity.
      *
      * @return string
      */
     public static function getTableName()
     {
         return 'el_comment_vote';
     }
 
     /**
      * Returns entity map definition.
      *
      * @return array
      */
     public static function getMap()
     {
         return [
             new IntegerField(
                 'ID',
                 [
                     'primary' => true,
                     'autocomplete' => true,
                     'title' => Loc::getMessage('VOTE_ENTITY_ID_FIELD')
                 ]
             ),
             new IntegerField(
                 'COMMENT_ID',
                 [
                     'required' => true,
                     'title' => Loc::getMessage('VOTE_ENTITY_COMMENT_ID_FIELD')
                 ]
             ),
             
             (new Reference(
                'COMMENT',
                CommentTable::class,
                Join::on('this.COMMENT_ID', 'ref.ID')
             ))->configureJoinType('inner'),

             new StringField(
                 'USER_ID',
                 [
                     'required' => true,
                     'validation' => [__CLASS__, 'validateUserId'],
                     'title' => Loc::getMessage('VOTE_ENTITY_USER_ID_FIELD')
                 ]
             ),
             new IntegerField(
                'ELEM_ID',
                [
                    'required' => true,
                    'title' => Loc::getMessage('COMMENT_ENTITY_ELEM_ID_FIELD')
                ]
            ),
             new StringField(
                 'IP',
                 [
                     'required' => true,
                     'validation' => [__CLASS__, 'validateIp'],
                     'title' => Loc::getMessage('VOTE_ENTITY_IP_FIELD')
                 ]
             ),
             new DatetimeField(
                 'CREATED_AT',
                 [
                     'required' => true,
                     'title' => Loc::getMessage('VOTE_ENTITY_CREATED_AT_FIELD'),
                     'default_value' => new \Bitrix\Main\Type\DateTime
                 ]
             ),
             new IntegerField(
                 'VALUE',
                 [
                     'required' => true,
                     'title' => Loc::getMessage('VOTE_ENTITY_VALUE_FIELD')
                 ]
             ),
         ];
     }
 
     /**
      * Returns validators for USER_ID field.
      *
      * @return array
      */
     public static function validateUserId()
     {
         return [
             new LengthValidator(null, 10),
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
 }