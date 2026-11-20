<?
namespace Mattweb\Comments;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

Loc::loadMessages(__FILE__);

/**
 * Class SmileTable
 * 
 * Fields:
 * <ul>
 * <li> id int mandatory
 * <li> code string(39) mandatory
 * <li> image string(255) mandatory
 * <li> name string(255) mandatory
 * <li> published int mandatory
 * <li> ordering int mandatory
 * </ul>
 *
 * @package Bitrix\Comment
 **/

class SmileTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'el_comment_smile';
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
				'id',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('SMILE_ENTITY_ID_FIELD')
				]
			),
			new StringField(
				'code',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateCode'],
					'title' => Loc::getMessage('SMILE_ENTITY_CODE_FIELD')
				]
			),
			new StringField(
				'image',
				[
					//'required' => true,
					'validation' => [__CLASS__, 'validateImage'],
					'title' => Loc::getMessage('SMILE_ENTITY_IMAGE_FIELD')
				]
			),
			new StringField(
				'name',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateName'],
					'title' => Loc::getMessage('SMILE_ENTITY_NAME_FIELD')
				]
			),
			new IntegerField(
				'published',
				[
					'required' => true,
					'title' => Loc::getMessage('SMILE_ENTITY_PUBLISHED_FIELD')
				]
			),
			new IntegerField(
				'ordering',
				[
					'required' => true,
					'title' => Loc::getMessage('SMILE_ENTITY_ORDERING_FIELD')
				]
			),
			new IntegerField(
				'smile_type_id',
				[
					'required' => true,
					'title' => Loc::getMessage('SMILE_ENTITY_SMILE_TYPE_FIELD'),					 
				]
			),
			(new Reference(
				'smile_type', 
				SmileTypeTable::class, 
				Join::on('this.smile_type_id', 'ref.id'))
			)->configureJoinType('inner'),			 
		];
	}

	/**
	 * Returns validators for code field.
	 *
	 * @return array
	 */
	public static function validateCode()
	{
		return [
			new LengthValidator(null, 39),
		];
	}

	/**
	 * Returns validators for image field.
	 *
	 * @return array
	 */
	public static function validateImage()
	{
		return [
			new LengthValidator(null, 255),
		];
	}

	/**
	 * Returns validators for name field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return [
			new LengthValidator(null, 255),
		];
	}
}