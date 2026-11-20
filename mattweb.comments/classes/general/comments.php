<?

use \Mattweb\Comments;
use \Mattweb\Comments\Traits;
use Bitrix\Main\Entity;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/*
* Class CommentsActions
*/
class CommentsActions
{
 
    public static $pComms = [];

    use Traits\commentsTrait;
    use Traits\commonTrait;

    /**
	 * Returns tree of comments.
	 *
     * https://www.php.net/manual/ru/functions.anonymous.php
     * https://myrusakov.ru/php-builds-comments-tree.html
     * @return array
	 */
    public static function getCommentsTree(array &$list)
    {
        $tree = [];

        // делаем группировку по идентификатору родителя
        foreach ($list as $node) {
            $tree[$node['PARENT_ID']][$node['COMMENT_ID']] = $node;
        }

        // рекурсивная функция, создающая вложенную (древовидную) структуру
        $recursiveBuilder = function ($children) use (&$recursiveBuilder, $tree) {

            foreach ($children as $key => $child)
            {
                $child_id = $child['COMMENT_ID'];

                if (isset($tree[$child_id])) {
                    $child['IS_PARENT'] = '1';
                    $child['ANSWERS'] = $recursiveBuilder($tree[$child_id]);
                }

                $children[$key] = $child;
            }
            
            ksort($children);
            return $children;
        };

        return $recursiveBuilder($tree[0]);
    }


    public static function GetCommentTree($cpid, $cdl = 1, $mode = 'down', $cid = 0){
        $arSelect = ['ID', 'PARENT_ID', 'DEPTH_LEVEL'];
        $arFilter = [];

        switch ($mode){
            case 'down':

                if($cdl == 1){
                    $arFilter['ID'] = $cpid;    
                }

                if($cdl > 1){
                    $arFilter['PARENT_ID'] = $cpid;    
                }

                $filtSelect = [
                    'select' => $arSelect,
                    'filter' => $arFilter,
                ];

                $prComms = Comments\CommentTable::getList($filtSelect)->fetchAll();
                
                if(empty($prComms)) return self::$pComms;

                // обходим КАЖДОГО потомка, иначе теряются все ветки, кроме последней
                foreach($prComms as $prComm){
                    self::$pComms[$prComm['DEPTH_LEVEL']][] = $prComm['ID'];

                    self::GetCommentTree($prComm['ID'], $prComm['DEPTH_LEVEL'] + 1);
                }

                break;
            case 'up':
                // вверх по ветке всегда выбираем сам комментарий по ID,
                // а на следующий шаг передаём его PARENT_ID
                $arFilter['ID'] = $cpid;

                $filtSelect = [
                    'select' => $arSelect,
                    'filter' => $arFilter,
                ];

                $prComm = Comments\CommentTable::getList($filtSelect)->fetch();

                if(empty($prComm)) return self::$pComms;

                self::$pComms[$prComm['DEPTH_LEVEL']][] = $prComm['ID'];

                // дошли до корня ветки - подниматься больше некуда
                if((int)$prComm['PARENT_ID'] <= 0) return self::$pComms;

                self::GetCommentTree($prComm['PARENT_ID'], $prComm['DEPTH_LEVEL'] - 1, 'up', $cid);

                break;
        }

        return self::$pComms;
    }

    public static function countUserComments($artId, $commentsUID = '', $userId = 0){

        // Если id или куки есть пытаемся получить все комментарии пользователя к текущей статье
        $filtSelect['select'] = ['ID', 'PUBLISHED', 'CREATED_AT'];
        $filtSelect['filter'] = ['ELEM_ID' => $artId, '!DEL_MARKED' => 1];
        
        if($userId > 0){
            $filtSelect['filter']['USER_ID'] = $userId;            
        }
        
        if(!empty($commentsUID)){
            $filtSelect['filter']['USERNAME'] = $commentsUID;
        }

        $prComms = Comments\CommentTable::getList($filtSelect)->fetchAll();
        
        // считаем количество комментариев и возвращаем его
        return count($prComms);
    }


    /**
     * Сбрасывает накопленное дерево.
     * Свойство статическое, поэтому вызывать перед каждым новым обходом.
     */
    public static function ResetCommentTree()
    {
        self::$pComms = [];
    }


    /**
     * Плоский список ID из self::$pComms, отсортированный по уровню вложенности.
     *
     * @return array
     */
    public static function GetPlainCommentIds()
    {
        $tree = self::$pComms;
        ksort($tree);

        $ids = [];
        foreach($tree as $levelIds){
            foreach($levelIds as $id){
                $ids[] = (int)$id;
            }
        }

        return array_values(array_unique($ids));
    }


    /**
     * Return list of comments
     * @param array $order
     * @param array $filter
     * @param array $select
     *
     * @return array
     */
    public static function getCommentsList($order=[], $filter = [], $select = [])
    {
        $arComms = [];

        $rsComms = \Mattweb\Comments\CommentTable::getList(array(
            'filter' => $filter,
            'select' => $select,
            'order' => $order,
        ));

        $arCommsTmp = $rsComms->fetchAll();
        
        if (count($arCommsTmp) > 0) {
            foreach($arCommsTmp as $arCommTmp){
                $arComms[$arCommTmp['ID']] = [
                    'COMMENT_ID' => $arCommTmp['ID'],
                    'ELEM_ID' => $arCommTmp['ELEM_ID'],
                    'PARENT_ID' => $arCommTmp['PARENT_ID'],
                    'DEPTH_LEVEL' => $arCommTmp['DEPTH_LEVEL'],
                    'CREATED_AT' => $arCommTmp['CREATED_AT'] -> format('d.m.Y H:i'),
                    'NAME' => $arCommTmp['NAME'],
                    'COMMENT' => $arCommTmp['COMMENT'],
                    'IP' => $arCommTmp['IP'],
                ];
            }
        }
        
        return $arComms;
    }

    /**
     * Возвращает правильную форму слова в зависимости от числительного
     * @param integer $num
     * @param string $form_for_1 ex: Товар
     * @param string $form_for_2 ex: Товара
     * @param string $form_for_3 ex: Товаров
     *
     * @return string
    */
    public static function getWordform($num, $form_for_1, $form_for_2, $form_for_5){
        // берем число по модулю и сбрасываем сотни (делим на 100, а остаток присваиваем переменной $num)
        $num = abs($num) % 100;
        
        // сбрасываем десятки и записываем в новую переменную
        $num_x = $num % 10;

        // если число принадлежит отрезку [11;19]
        if ($num > 10 && $num < 20)
            return $num." ".$form_for_5;
        
        // иначе если число оканчивается на 2,3,4
        if ($num_x > 1 && $num_x < 5)
            return $num." ".$form_for_2;
        
        // иначе если оканчивается на 1
        if ($num_x == 1)
            return $num." ".$form_for_1;

        return $num." ".$form_for_5;
    }

    public static function replaceBBCode($string, $remove = false){
        $arPattens = [
            '/\[code\]/', '/\[\/code\]/',
            '/\[quote name="(.*?)"\]/', '/\[\/quote\]/',
            '/\[url\](https?:\/\/.+)\[\/url\]/'
        ];

        if(!$remove){
            $arReplacements = [
                '<span class="code">', '</span>',
                '<div class="quote-wrap"><span class="qc-author">$1</span>', '</div>',
                '<a class="comment-link" href="$1">$1</a>'
            ];
        }
        else{
            $arReplacements = '';
        }
        
    
        return preg_replace($arPattens, $arReplacements, $string);
    }

    public static function getCountElemComments($elemID, $dLevel = 1){
        // SELECT COUNT(id) FROM `el_comment` WHERE ELEM_ID=10 AND PUBLISHED=1 AND DEPTH_LEVEL=1;
        
        $filter = ['ELEM_ID' => $elemID, 'PUBLISHED' => 1];
        if($dLevel != 0){
            $filter['DEPTH_LEVEL'] = $dLevel;
        }

        $res = Comments\CommentTable::getList(array(
            'filter' => $filter,
            'runtime'=> array(                
                new Entity\ExpressionField('COMM_QUANT', 'COUNT(id)')
            ),
            'select' => array('COMM_QUANT'),
        ));

        $arQRes = $res->fetch();

        return $arQRes['COMM_QUANT'];
    }

}