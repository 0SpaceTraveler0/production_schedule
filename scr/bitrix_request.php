<?php

use Bitrix\Crm\DealTable;
use Bitrix\Crm\Deal;
use Bitrix\Seo\Engine\Bitrix;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('iblock');
CModule::IncludeModule('main');
CModule::IncludeModule('crm');

function addDeal($aRCombinations): void
{
  foreach ($aRCombinations as $value) {
    //$unixtime_arrival = FormatDate("d.m.y",strtotime($data['arrival']));

    $arFields = array(
      'TITLE' => $value['order1'] . '/' . $value['order2'],
      'STAGE_ID' => 'C9:NEW',
      'CATEGORY_ID' => 9,
      'UF_CRM_1674181372' => $value['material'], //материал @[getIdValueUserFields(275,$data['actual_price']['room_type_name'])], 
      'UF_CRM_1680089010545' => $value['withMaterial'], //ширина рулона
      'UF_CRM_1680087136' => $value['order1'], // паспорт
      'UF_CRM_1674156116' => $value['order2'], // паспорт сов
      'UF_CRM_1675555129' => 0, //погонные метры
      'UF_CRM_1675558516' => 0, //погонные метры сов
      'UF_CRM_1680087517854' => $value['countOrder1'], //Количество основного заказа в ширину
      'UF_CRM_1680088113635' => $value['countOrder2'], //Количество совмещенного заказа в ширину
    );

    $CCrmDeal = new CCrmDeal(false);
    $res = $CCrmDeal->Add($arFields);
  }
}

function deleteAllDeal($deals): void
{
  foreach ($deals as $deal) {
    $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
    $item = $factory->getItem($deal['ID']);
    $item->delete();
  }
}

function getDeal(): array
{
  $ibClass = 'Bitrix\Crm\DealTable';
  $obResult = $ibClass::getList([
    'filter' => [
      'STAGE_ID' => 'C9:NEW'
    ],
    'select' => [
      'ID',
      'UF_CRM_1680087136', // основной паспорт
      'UF_CRM_1674156116' // совмещенный паспорт
    ]
  ]);
  return $obResult->fetchAll();
}

function getUnfulfilledOrders($deals)
{
  $arr = [];
  foreach ($deals as $key => $value) {
    $arr[] = $value['UF_CRM_1680087136'];
    $arr[] = $value['UF_CRM_1674156116'];
  }
  $arr = array_unique($arr);
  $filter = [
    "IBLOCK_ID" => 17,
    "NOMER_VALUE" => $arr,
    "!==TIP_UPAKOVKI_VALUE" => null,
    "!==DATA_OTGRUZKI_VALUE" => null,
    "!==MATERIAL_INFO_NAME" => null,
    "!==RAZVERTKA_SHIRINA_PO_NOZHAM_VALUE" => 0,
    "!==KOL_VO_NA_SHTAMPE_VALUE" => null,
    "!==DLINA_ZAGOTOVKI_VALUE" => 0,
    "!==KOL_VO_PLAN_SHTUK_VALUE" => null,
  ];
  $arAllOrder = getListOrder($filter);
  return $arAllOrder;
}


function updateListOrder($list)
{
  foreach ($list as $key => $value) {
    CIBlockElement::SetPropertyValueCode($key, "SDELANO", $value['made']);
    CIBlockElement::SetPropertyValueCode($key, "OSTALOS_SDELAT", $value['left']);
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
      'NAME',
      'NOMER_VALUE' => 'NOMER.IBLOCK_GENERIC_VALUE',
      'DATA_OTGRUZKI_VALUE' => 'DATA_OTGRUZKI.VALUE',
      'MATERIAL_ID' => 'MATERIAL.VALUE',
      'MATERIAL_INFO_NAME' => 'MATERIAL_INFO.VALUE',
      'TIP_UPAKOVKI_VALUE' => 'TIP_UPAKOVKI.VALUE',
      'RAZVERTKA_SHIRINA_PO_NOZHAM_VALUE' => 'RAZVERTKA_SHIRINA_PO_NOZHAM.VALUE',
      'KOL_VO_NA_SHTAMPE_VALUE' => 'KOL_VO_NA_SHTAMPE.VALUE',
      'DLINA_ZAGOTOVKI_VALUE' => 'DLINA_ZAGOTOVKI.VALUE',
      'KOL_VO_PLAN_SHTUK_VALUE' => 'KOL_VO_PLAN_SHTUK.VALUE',
      'KOL_VO_PLAN_SHTUK_VALUE_COPY' => 'KOL_VO_PLAN_SHTUK.VALUE',
      'SROCHNYY_VALUE' => 'SROCHNYY.IBLOCK_GENERIC_VALUE',
      'OSTALOS_SDELAT_VALUE' => 'OSTALOS_SDELAT.VALUE',
    ],
    'order' => [
      'DATA_OTGRUZKI_VALUE' => 'ASC',
    ],
    //Materials
    'runtime' => array(
      'MATERIAL_INFO' => [
        'data_type' => \Bitrix\Iblock\PropertyEnumerationTable::class,
        'reference' => ['this.MATERIAL_ID' => 'ref.ID'],
        'join_type' => 'LEFT'
      ]
    )
  ]);

  $result = $obResult->fetchAll();
  foreach ($result as $key => $value) {

    $result[$key]['RUNNING_METERS'] = getRaningMetrs(
      $result[$key]['KOL_VO_PLAN_SHTUK_VALUE'],
      $result[$key]['KOL_VO_NA_SHTAMPE_VALUE'],
      $result[$key]['DLINA_ZAGOTOVKI_VALUE'],
      $result[$key]['TIP_UPAKOVKI_VALUE']
    );

/*     switch ($result[$key]['TIP_UPAKOVKI_VALUE']) {
      case 62:
        $result[$key]['RUNNING_METERS'] = $result[$key]['KOL_VO_PLAN_SHTUK_VALUE'] / $result[$key]['KOL_VO_NA_SHTAMPE_VALUE'] * $result[$key]['DLINA_ZAGOTOVKI_VALUE'] / 1000;
        break;
      default:
        $result[$key]['RUNNING_METERS'] = $result[$key]['KOL_VO_PLAN_SHTUK_VALUE'] / 1 * $result[$key]['DLINA_ZAGOTOVKI_VALUE'] / 1000;
        break;
    } */

    change_key($key, $result[$key]['ID'], $result);
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
