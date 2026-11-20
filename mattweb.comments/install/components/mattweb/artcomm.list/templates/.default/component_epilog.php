<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$this->getTemplate()->addExternalJS($templateFolder.'/js/jquery-2.2.4.min.js');
$this->getTemplate()->addExternalJS($templateFolder.'/js/popup.js');
$this->getTemplate()->addExternalJS($templateFolder.'/js/validate.js');
$this->getTemplate()->addExternalJS($templateFolder.'/js/submit.js');