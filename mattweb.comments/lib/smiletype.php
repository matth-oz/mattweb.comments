<?
namespace Mattweb\Comments;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

use Bitrix\Main\ORM\Fields\Relations\OneToMany;

/**
 * Class SmileTypeTable
 * 
 * Fields:
 * <ul>
 * <li> id int mandatory
 * <li> name string(255) mandatory
 * <li> published int mandatory
 * </ul>
 *
 * @package Bitrix\Comment
 **/

class SmileTypeTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'el_comment_smile_type';
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
					'title' => Loc::getMessage('SMILE_TYPE_ENTITY_ID_FIELD'),
				]
			),
			new StringField(
				'name',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 255),
						];
					},
					'title' => Loc::getMessage('SMILE_TYPE_ENTITY_NAME_FIELD'),
				]
			),
			new StringField(
				'code',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 255),
						];
					},
					'title' => Loc::getMessage('SMILE_TYPE_ENTITY_CODE_FIELD'),
				]
			),
			new IntegerField(
				'published',
				[
					'required' => true,
					'title' => Loc::getMessage('SMILE_TYPE_ENTITY_PUBLISHED_FIELD'),
					'size' => 1,
				]
			),
			(new OneToMany('smiles', SmileTable::class, 'smile_type'))
                ->configureJoinType('inner') // Установка типа соединения: INNER JOIN
		];
	}
}
