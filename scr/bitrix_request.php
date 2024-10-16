<?php

use Bitrix\Crm\DealTable;
use Bitrix\Crm\Deal;
use Bitrix\Seo\Engine\Bitrix;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Crm\Service;
use Bitrix\Crm\Item;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('iblock');
CModule::IncludeModule('main');
CModule::IncludeModule('crm');

function groupedDeal($aRCombinations)
{
    
    $grouped = array_reduce($aRCombinations, function ($acc, $item) {
        $key = $item['order1_id'] . '-' . $item['countOrder1'] . '-' . $item['withMaterial'];
        if (!isset($acc[$key])) {
            $acc[$key] = [];
        }

        $acc[$key][] = $item;

        return $acc;
    }, []);

    // Проходим по группам и добавляем цвет только тем группам, где больше одного элемента
    $result = [];
    foreach ($grouped as $key => $group) {
        if (count($group) > 1) {
            $color = getRandomColor(rand_color(), 0.8);
            foreach ($group as &$item) {
                $item['color'] = $color;
            }
        }
        $result = array_merge($result, $group);
    }
    return $result;
}

function addDeal($aRCombinations): void
{
    $entityTypeId = \CCrmOwnerType::Deal;
    $factory = Container::getInstance()->getFactory($entityTypeId);
    $groupedTransactions = groupedDeal($aRCombinations);
    foreach ($groupedTransactions as $value) {

        $new_item = $factory->createItem([
            'TITLE' => $value['order1'] . '/' . $value['order2'],
            // 'STAGE_ID' => 'C9:NEW',
            'STAGE_ID' => 'C9:UC_5O2IAX',
            'CATEGORY_ID' => 9,
            'UF_CRM_1680089010545' => $value['withMaterial'], //ширина рулона
            'UF_CRM_1680087136' => $value['order1_id'], // id паспорт
            'UF_CRM_1674156116' => $value['order2_id'], // id паспорт сов
            //'UF_CRM_1675555129' => 0, //погонные метры
            //'UF_CRM_1675558516' => 0, //погонные метры сов
            'UF_CRM_1680087517854' => $value['countOrder1'], //Количество основного заказа в ширину
            'UF_CRM_1680088113635' => $value['countOrder2'], //Количество совмещенного заказа в ширину
            'UF_CRM_1702558329461' => $value['main_made'], //Штук на запуск ОСН
            'UF_CRM_1702558337821' => $value['combined_made'], //Штук на запуск СОВ
            'UF_CRM_1702558362582' => $value['main_left'], //Остается не сделано ОСН
            'UF_CRM_1702558368462' => $value['combined_left'], //Остается несделано СОВ
            'UF_CRM_1675555129' => $value['running_meters'], //меры погонные заказа
            'UF_CRM_1685005404730' => 1,
            'UF_CRM_1703658554' => $value['color']
        ]);

        $context = new \Bitrix\Crm\Service\Context();
        $context->setUserId(9);
        $operation = $factory->getAddOperation($new_item, $context);
        $res = $operation->launch();
    }
}
function rand_color()
{
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}
/**
 * Increases or decreases the brightness of a color by a percentage of the current brightness.
 *
 * @param   string  $hexCode        Supported formats: `#FFF`, `#FFFFFF`, `FFF`, `FFFFFF`
 * @param   float   $adjustPercent  A number between -1 and 1. E.g. 0.3 = 30% lighter; -0.4 = 40% darker.
 *
 * @return  string
 *
 * @author  maliayas
 */
function getRandomColor($hexCode, $adjustPercent)
{
    $hexCode = ltrim($hexCode, '#');

    if (strlen($hexCode) == 3) {
        $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
    }

    $hexCode = array_map('hexdec', str_split($hexCode, 2));

    foreach ($hexCode as &$color) {
        $adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
        $adjustAmount = ceil($adjustableLimit * $adjustPercent);

        $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
    }

    return '#' . implode($hexCode);
}
function startingBusinessProcess()
{
    CModule::IncludeModule('bizproc');
    $deals = getDeal();
    foreach ($deals as $deal) {
        CBPDocument::StartWorkflow(
            54,  //ID робота
            array("crm", "CCrmDocumentDeal", "DEAL_" . $deal['ID']),
            array("TargetUser" => "user_1"),
            $arErrorsTmp
        );
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
        // "NOMER_VALUE" => $arr,
        "IBLOCK_ID" => 17,
        "!NOMER_VALUE" => $arr,
        "!TIP_UPAKOVKI_VALUE" => false, // тип изделия
        [
            'LOGIC' => 'OR',
            "!DATA_OTGRUZKI_VALUE" => false,
            ">DATA_OTGRUZKI_VALUE" => 0
        ],
        "!MATERIAL_INFO_NAME" => false,
        [
            'LOGIC' => 'OR',
            ">RAZVERTKA_SHIRINA_PO_NOZHAM_VALUE" => 0,
            "!RAZVERTKA_SHIRINA_PO_NOZHAM_VALUE" => false
        ],
        [
            'LOGIC' => 'OR',
            ">DLINA_ZAGOTOVKI_VALUE" => 0,
            "!DLINA_ZAGOTOVKI_VALUE" => false
        ],
        [
            'LOGIC' => 'OR',
            ">KOL_VO_NA_SHTAMPE_VALUE" => 0,
            "!KOL_VO_NA_SHTAMPE_VALUE" => false
        ],
        [
            'LOGIC' => 'OR',
            ">KOL_VO_PLAN_SHTUK_VALUE" => 0,
            "!KOL_VO_PLAN_SHTUK_VALUE" => false
        ],
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
        $result[$key]['SEQUENCE_NUMBER'] = 1;

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
