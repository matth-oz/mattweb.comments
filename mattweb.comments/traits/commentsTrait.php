<?
namespace Mattweb\Comments\Traits;

trait commentsTrait{

    const COOKIE_USER_ID = 'USER_VOTE_ID';
    const COOKIE_USER_ID_LENGTH = 6;

    // getUserVoteID
    public static function getCommentUserID($decode = false){
         
        if(isset($_COOKIE[self::COOKIE_USER_ID]) && strlen($_COOKIE[self::COOKIE_USER_ID]) > 0){

            if($decode) return base64_decode($_COOKIE[self::COOKIE_USER_ID]);

            return $_COOKIE[self::COOKIE_USER_ID];
        }

        return false;
    }

    // setUserVoteID
    public static function setCommentUserID($uidLenght = self::COOKIE_USER_ID_LENGTH, $encode = false){
        $cookieUserMark = self::generateRandomString($uidLenght);

        if($encode) $cookieUserMark = base64_encode($cookieUserMark);

        $yearTS = time() + 60 * 60 * 24 * 365;
        setcookie(self::COOKIE_USER_ID, $cookieUserMark, $yearTS, '/');
    }

     // generateRandomString
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