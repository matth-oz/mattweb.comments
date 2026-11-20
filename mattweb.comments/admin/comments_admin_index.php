<?
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */

use Bitrix\Main\Application,
Bitrix\Main\Localization\Loc,
Bitrix\Iblock\ElementTable,
Bitrix\Main\Loader,
Bitrix\Main\Config\Option,
Mattweb\Comments;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");


IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");

$APPLICATION->SetTitle('Модуль mattweb.comments (комментарии к статьям)');
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>

<h2>Описание модуля</h2>
<p>Модуль «Комментарии к статьям» (mattweb.comments) предназначен для комментирования посетителями сайта элементов инфоблоков (статьи, новости) в публичной части сайта.</p>
<p>Работает в любой редакции 1С-Битрикс, начиная с редакции «Старт».</p>
<p>Для вывода списка комментариев и формы для их добавления используется компонент «Комментарии к статье» (mattweb:artcomm.list)</p>
<p>Компонент работает совместно с комплексным компонентом «Новости» (bitrix:news).</p>
<p>Компонент «Комментарии к статье» (mattweb:artcomm.list) модуля комментариев вызывается в шаблоне детальной страницы комплексного компонента новостей. Он выводит список последних добавленных комментариев в соответствии с параметрами.</p> 
<p>Также возможно использовать компоненты модуля совместно с простым компонентом Битрикс «Новость детально» (news.detail).</p>
<p>В состав модуля также входит и другой компонент «Последние комментарии» (artcomm.last).</p>










