<?php
namespace Mattweb\Comments\Traits;

trait commonTrait{
    public static function showFieldTitle(string $name, array $arReqFlds, string $title): void
    {
        $fTitle = trim($title);

        if(in_array($name, $arReqFlds,)){
            $fTitle = '<strong>'.$fTitle.'</strong>';
        }

        echo $fTitle;
    }
}

