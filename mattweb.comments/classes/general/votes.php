<?php

use \Mattweb\Comments;
use \Mattweb\Comments\Traits;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/*
 * class VotesActions
*/
class VotesActions
{
    use Traits\commentsTrait;
    use Traits\commonTrait;
  
    /**
     * Нужно получить массив лайков и дизлайков
     * по каждому комментарию текущей статьи
     */
    public static function getVotesList($arCommIDs){
        $arVotes = [];      

        $rsVotes = Comments\VoteTable::GetList(array(
            'filter' => ['COMMENT_ID' => $arCommIDs],
            'select' => ['COMMENT_ID', 'USER_ID', 'ELEM_ID', 'IP', 'CREATED_AT', 'VALUE'],
            'order' => ['COMMENT_ID'],
        ));
        $arVotesTmp = $rsVotes->fetchAll();

        foreach($arVotesTmp as $arVoteTmp){
            if($arVoteTmp['VALUE'] == 1){
                $arVotes[$arVoteTmp['COMMENT_ID']]['LIKES'] = $arVotes[$arVoteTmp['COMMENT_ID']]['LIKES'] + 1;
            }
            else{
                $arVotes[$arVoteTmp['COMMENT_ID']]['DISLIKES'] = $arVotes[$arVoteTmp['COMMENT_ID']]['DISLIKES'] + 1;
            }            
        }       

        return $arVotes;        
    }

    /**
     * Нужно получить массив ID комментариев
     * у которых есть лайки текущего пользователя
     * для того чтобы не давать ему голосовать повторно
     */
    public static function getVotesUserList($userID, $arCommIDs){
        
        $arVotes = [];
        $rsVotes = Comments\VoteTable::GetList(array(
            'filter' => ['USER_ID' => $userID, 'COMMENT_ID' => $arCommIDs],
            'select' => ['COMMENT_ID', 'VALUE'],
            'order' => ['COMMENT_ID'],
        ));

        $arVotesTmp = $rsVotes->fetchAll();

        foreach($arVotesTmp as $arVoteTmp){ 
            $valueTxt = ($arVoteTmp['VALUE'] == 1) ? 'LIKE' : 'DISLIKE';
            $arVotes[$arVoteTmp['COMMENT_ID']] = $valueTxt;
        }

        return $arVotes;
    }

    protected static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }







}
