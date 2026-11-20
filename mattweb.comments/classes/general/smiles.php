<?

use \Mattweb\Comments;
use \Bitrix\Main\Localization\Loc;
use \Mattweb\Comments\Traits;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

/*
* Class SmilesActions
*/
class SmilesActions
{
    use Traits\commonTrait;
    
    const IMG_PATH = '/local/images/mattweb.comments/smilies/';
    
    const SMILE_PUBLISHED = 1;
    const SMILE_UNPUBLISHED = 0;
    
    const DEFAULT_SMILES_TYPE_ID = 1;
    const EMOJI_SMILES_TYPE_ID = 2;
    const BX_SMILES_TYPE_ID = 4;

    public static function getSmilesList(){
        $curSmilesType = (int) Option::get("mattweb.comments", 'smiles_type', '');
        $typeId = ($curSmilesType > 0) ? $curSmilesType : self::DEFAULT_SMILES_TYPE_ID;

        $arSmiles = [];

        $rsSmiles = Comments\SmileTable::GetList(array(
            'filter' => ['published' => self::SMILE_PUBLISHED, 'smile_type_id' => $typeId],
            'select' => ['id', 'code', 'image', 'name'],
            'order' => ['ordering' => 'asc'],
        ));

        while($arSmileTmp = $rsSmiles->fetch()){
            $arSmiles[$arSmileTmp['id']]['SHORT_CODE'] = $arSmileTmp['code'];
            $arSmiles[$arSmileTmp['id']]['NAME'] = $arSmileTmp['name'];
            
            if($typeId !== self::EMOJI_SMILES_TYPE_ID){
                $arSmiles[$arSmileTmp['id']]['IMAGE'] = $arSmileTmp['image'];
                $arSmiles[$arSmileTmp['id']]['PATH'] = self::IMG_PATH.$arSmileTmp['image'];
            }            
        }

        return $arSmiles;
    }



    public static function isCurrentTypeEmoji(){
        $curSmilesType = Option::get("mattweb.comments", 'smiles_type', '');

        return $curSmilesType == self::EMOJI_SMILES_TYPE_ID;
    }

    public static function getSmilesSRArray($smilesType = self::DEFAULT_SMILES_TYPE_ID){
        $arSmiles = [];
        $rsSmiles = Comments\SmileTable::GetList(array(
            'filter' => ['published' => '1', 'smile_type_id' => $smilesType],
            'select' => ['code', 'image'],
            'order' => ['ordering'],
        ));
        $arSmilesTmp = $rsSmiles->fetchAll();

        foreach($arSmilesTmp as $key => $arSmileTmp){
            $arSmiles['SEARCH'][$key] = $arSmileTmp['code'];

            if($smilesType != self::EMOJI_SMILES_TYPE_ID){
                $arSmiles['REPLACE'][$key] = '<img src="'.self::IMG_PATH.$arSmileTmp['image'].'" style="width: auto" />';
            }
            
        }

        return $arSmiles;
    }

    public static function replaceSmiles($string, $arSml){

        if((!array_key_exists('SEARCH', $arSml) && !array_key_exists('REPLACE', $arSml))
         || count($arSml['SEARCH']) != count($arSml['REPLACE'])){
        return $string;
        }

        return str_replace($arSml['SEARCH'], $arSml['REPLACE'], $string);
    }

    public static function removeSmiles($string){
       $arSml = self::getSmilesSRArray();

       return str_replace($arSml['SEARCH'], '', $string);
    }

}