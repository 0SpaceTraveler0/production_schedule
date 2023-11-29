<?php

use Bitrix\Crm\DealTable;
use Bitrix\Crm\Deal;
use Bitrix\Seo\Engine\Bitrix;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('iblock');
CModule::IncludeModule('main');
CModule::IncludeModule('crm');

$ar = getListOrder([]);
updateListOrder($ar);

function updateListOrder($list)
{
  foreach ($list as $key => $value) {
    CIBlockElement::SetPropertyValueCode($key, "SDELANO", 0);
    CIBlockElement::SetPropertyValueCode($key, "OSTALOS_SDELAT", 0);
  }
}


function getListOrder($filter): array
{
    $iblockCode = "ProductionSchedule";
    $ibClass = '\Bitrix\Iblock\Elements\Element' . $iblockCode . 'Table';
    $obResult = $ibClass::getList([
        'filter' => $filter,
        'select' => [
            'ID',
            'NAME'
        ]
    ]);

    $result = $obResult->fetchAll();
    foreach ($result as $key => $value) {
        change_key($key, $result[$key]['NOMER_VALUE'], $result);
    }

    return $result;
}

function change_key($key, $new_key, &$arr, $rewrite = true)
{
    if (!array_key_exists($new_key, $arr) || $rewrite) {
        $arr[$new_key] = $arr[$key];
        unset($arr[$key]);
        return true;
    }
    return false;
}
