<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
    'NAME' => Loc::getMessage('MCART_WCFC_DESCR_NAME'),
    'DESCRIPTION' => Loc::getMessage('MCART_WCFC_DESCR_DESCR'),
    'TYPE' => 'activity',
    'CLASS' => 'McartWaitingCrmFieldChange',
    'JSCLASS' => 'BizProcActivity',
    'CATEGORY' => [
        'ID' => 'other',
    ]
];
