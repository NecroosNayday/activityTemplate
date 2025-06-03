<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
    'NAME' => Loc::getMessage('MCART_GTRS_DESCR_NAME'),
    'DESCRIPTION' => Loc::getMessage('MCART_GTRS_DESCR_DESCR'),
    'TYPE' => 'activity',
    'CLASS' => 'McartGetTaskResultString',
    'JSCLASS' => 'BizProcActivity',
    'CATEGORY' => [
        'ID' => 'other',
    ],
    'RETURN' => [
        'resultString' => [
            'NAME' => Loc::getMessage('MCART_GTRS_RESULT_STR'),
            'TYPE' => 'string',
        ]
    ]
];
