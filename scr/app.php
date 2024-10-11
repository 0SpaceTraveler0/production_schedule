<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/production_schedule/scr/bitrix_request.php');
CModule::IncludeModule('iblock');
CModule::IncludeModule('main');

use Bitrix\Main\Loader;
use Production\Line\QueueProductionLineTable;

$width_conditions = [
    '1050' => 5,
    '840' => 8
];
$allWithMaterials = [840, 1050, 1260, 1400];
app();
function app()
{
    global $allWithMaterials;
    $filter = [
        "IBLOCK_ID" => 17,
        "!==NOMER_VALUE" => null,
        "!==TIP_UPAKOVKI_VALUE" => null, // тип изделия
        "!==DATA_OTGRUZKI_VALUE" => null,
        "!==MATERIAL_INFO_NAME" => null,
        "!==RAZVERTKA_SHIRINA_PO_NOZHAM_VALUE" => 0,
        "!==KOL_VO_NA_SHTAMPE_VALUE" => null,
        "!==DLINA_ZAGOTOVKI_VALUE" => 0,
        "!==KOL_VO_PLAN_SHTUK_VALUE" => null,
        'LOGIC' => 'and',
        [
            ">OSTALOS_SDELAT_VALUE" => 0
        ],
        [
            "!==OSTALOS_SDELAT_VALUE" => null
        ]
    ];

    $arAllOrder = getListOrder($filter);
    $arUnfulfilledOrder = getUnfulfilledOrders(getDeal());
    $arAllOrder += $arUnfulfilledOrder;

    $allCombinations = calculation($arAllOrder, $allWithMaterials);
    $allCombinations = filterArResult($allCombinations, $arAllOrder);

    // $swapCombination = swapCombination($allCombinations, $arAllOrder);
    // $resultAr = array_merge($allCombinations, $swapCombination);

    array_filter($allCombinations, function ($value, $key){
        if($value['countOrder2'] == 0){
            return;
        }
       return $value['countOrder2'] != 0;
    }, ARRAY_FILTER_USE_BOTH);

    $resultAr = $allCombinations;
    $lengthOfRolls = [
        '1400' => 0,
        '1260' => 0,
        '1050' => 0,
        '840' => 0
    ];

    //обновление списка граффик производства
    // $arMadedAndLeft = [];
    // foreach ($resultAr as $key => &$value) {
    //     putValueMadedAndLeft($value, $arMadedAndLeft);
    // }
    //updateListOrder($arMadedAndLeft);

    //deleteAllDeal(getDeal());
    usort($resultAr, function ($a, $b) {
        return ($b['withMaterial'] - $a['withMaterial']) // status ascending
            ?: strcmp($a['material'], $b['material']) // start ascending
            ?: ($b['effectiveness'] - $a['effectiveness']) // mh descending
        ;
    });
    addDeal(array_reverse($resultAr));

    //startingBusinessProcess();

    if (!Loader::includeModule('production.line')) {
        die('Module not installed');
    }

    foreach ($resultAr as $data) {

        $dataToAdd = [
            'NAME_ORDER_MAIN' => $data['order1'],
            // 'EFFICIENCY_PERCENT' => rtrim($data['effectiveness'], '%'), // убираем знак процента и сохраняем число
            'EFFICIENCY_PERCENT' => $data['effectiveness'],
            'MATERIAL_WIDTH' => $data['withMaterial'],
            'MATERIAL' => $data['material'],
            'MAIN_ELEMENT_ID' => $data['order1_id'],
            'COUNT_ORDER_MAIN' => $data['countOrder1'],
            'QUANTITY_WIDTH_MAIN' => $data['dlina_zug1'],
            'REMAINING_MAIN_QUANTITY' => $data['main_left'],
            'USED_MAIN_QUANTITY' => $data['main_made'],
            'PLAN_MAIN_QUANTITY' => $data['main_quantity_plain'],
        ];

        // Проверяем наличие данных для combined и добавляем только если они существуют
        if (!empty($data['order2_id'])) {
            $dataToAdd['COMBINED_ELEMENT_ID'] = $data['order2_id'];
            $dataToAdd['NAME_ORDER_COMBINED'] = $data['order2'];
            $dataToAdd['COUNT_ORDER_COMBINED'] = $data['countOrder2'];
            $dataToAdd['QUANTITY_WIDTH_COMBINED'] = $data['dlina_zug2'];
            $dataToAdd['REMAINING_COMBINED_QUANTITY'] = $data['combined_left'];
            $dataToAdd['USED_COMBINED_QUANTITY'] = $data['combined_made'];
            $dataToAdd['PLAN_COMBINED_QUANTITY'] = $data['combined_quantity_plain'];
        }


        $result = QueueProductionLineTable::add($dataToAdd);

        if ($result->isSuccess()) {
            echo "Record added successfully. ID: " . $result->getId() . "<br>";
        } else {
            echo "Error adding record: " . implode(', ', $result->getErrorMessages()) . "<br>";
        }
    }
    return $resultAr;
}
function calculation(array $arOrder, array $allWithMaterials)
{
    global $width_conditions;
    $allCombinations = [];
    foreach ($arOrder as $id => $order) {
        calculationForOne($allCombinations, $allWithMaterials, $order);
        foreach ($allWithMaterials as $withMaterial) {
            $orderWidth  = (int)$order['RAZVERTKA_SHIRINA_PO_NOZHAM_VALUE'];
            //Если заказ - 4х кл. коробка, то к ширине заготовки прибавляем 10мм
            if ((int)$order['TIP_UPAKOVKI_VALUE'] == 61) {
                $orderWidth += 10;
            }
            for ($i = 1; $i < 3; $i++) {
                foreach ($arOrder as $id2 => $order2) {

                    $order2With  = (int)$order2['RAZVERTKA_SHIRINA_PO_NOZHAM_VALUE'];
                    //Если заказ - 4х кл. коробка, то к ширине заготовки прибавляем 10мм
                    if ((int)$order2['TIP_UPAKOVKI_VALUE'] == 61) {
                        $order2With += 10;
                    }
                    if (
                        $order['NOMER_VALUE'] === $order2['NOMER_VALUE'] or
                        $order['MATERIAL_ID'] !== $order2['MATERIAL_ID'] or
                        $orderWidth > $withMaterial or
                        $orderWidth + $order2With > $withMaterial
                    ) {
                        continue;
                    }
                    $j = 1;

                    while ($j * $order2With < $withMaterial - ($orderWidth * $i)) {
                        $combination = [
                            'order1' => (int)$order['NOMER_VALUE'],
                            'orderName1' => $order['NOMER_VALUE'],
                            'order2' => (int)$order2['NOMER_VALUE'],
                            'orderName2' => $order2['NOMER_VALUE'],
                            'withMaterial' => (int)$withMaterial,
                            'countOrder1' => (int)$i,
                            'countOrder2' => (int)$j,
                            'dlina_zug1' => (int)$order['DLINA_ZAGOTOVKI_VALUE'],
                            'dlina_zug2' => (int)$order2['DLINA_ZAGOTOVKI_VALUE'],
                            'order1_id' => (int)$order['ID'],
                            'order2_id' => (int)$order2['ID'],
                        ];
                        $allCombinations[] = calculatingEfficiency($combination, $orderWidth,  $i,  $withMaterial, true, $order2With, $j);
                        $j++;
                    }
                }
            }
        }
    }
    return $allCombinations;
}
function calculationForOne(&$allCombinations, $allWithMaterials, $order)
{
    global $width_conditions;
    $orderWidth  = (int)$order['RAZVERTKA_SHIRINA_PO_NOZHAM_VALUE'];
    //Если заказ - 4х кл. коробка, то к ширине заготовки прибавляем 10мм
    if ((int)$orderWidth['TIP_UPAKOVKI_VALUE'] == 61) {
        $orderWidth += 10;
    }
    foreach ($allWithMaterials as $withMaterial) {
        $i = 1;
        while ($orderWidth * $i <= $withMaterial) {
            $combination = [
                'order1' => (int)$order['NOMER_VALUE'],
                'orderName1' => $order['NOMER_VALUE'],
                'order2' => null,
                'withMaterial' => (int)$withMaterial,
                'countOrder1' => (int)$i,
                'dlina_zug1' => (int)$order['DLINA_ZAGOTOVKI_VALUE'],
                'order1_id' => (int)$order['ID']
            ];
            $resultCalculatingEfficiency = calculatingEfficiency($combination, $orderWidth, $i, $withMaterial, true);
            if($resultCalculatingEfficiency != 0){
                $allCombinations[] = $resultCalculatingEfficiency;
            }
            $i++;
        }
    }
}
function calculatingEfficiency($combination, $orderWidth,  $countOrder,  $withMaterial, $flag, $order2With = 0, $countOrder2 = 1)
{
    global $width_conditions;
    // flag -  применять вилку эффективности или нет
    $mergeWidth = ($orderWidth * $countOrder) + ($order2With * $countOrder2);
    $effectiveness = ($mergeWidth / $withMaterial) * 100;

    //Если эффективность совмещения на 1400 формате больше 92%, то выбирается этот формат, даже если на других эффективность больше. 
    //Если на 1400 эфф. меньше 92%, смотрим 1260, если на нем больше 92%, берем его, если меньше, берем самый эффективный формат.
    if($withMaterial != 1400){
        $mergeWidth__ = ($orderWidth * $countOrder) + ($order2With * $countOrder2);
        $effectiveness__ = ($mergeWidth__ / 1400) * 100;
        if($effectiveness__ > 92){
            return 0;
        }
    }
    /* if (isset($width_conditions[$withMaterial]) and $flag == true) {
        $eff = $effectiveness - $width_conditions[$withMaterial];
        $combination['effectiveness'] = round($eff, 2);
        $combination['trueEffectiveness'] = round($effectiveness, 2);
    } else {
        $combination['effectiveness'] = round($effectiveness, 2);
    } */
    $combination['effectiveness'] = (int)round($effectiveness, 2);
    return $combination;
}
function completionLengthOfRolls($allCombinations, $lengthOfRolls, &$arOrder)
{
    foreach ($allCombinations as $key => $value) {
        if ($value['order2'] === null) {
            $lengthOrder_main = ceil($value['main_made'] * $value['dlina_zug1'] / ($value['countOrder1'] * $arOrder[$value['order1_id']]['KOL_VO_NA_SHTAMPE_VALUE'] * 1000));
            $arOrder[$value['order1_id']]['RUNNING_METERS'] = $lengthOrder_main;
        } else {
            $lengthOrder_main = ceil($value['main_made'] * $value['dlina_zug1'] / ($value['countOrder1'] * $arOrder[$value['order1_id']]['KOL_VO_NA_SHTAMPE_VALUE'] * 1000));
            $lengthorder2 = ceil($value['main_made'] * $value['dlina_zug2'] / ($value['countOrder2'] * $arOrder[$value['order2_id']]['KOL_VO_NA_SHTAMPE_VALUE'] * 1000));
            $arOrder[$value['order1_id']]['RUNNING_METERS'] = $lengthOrder_main;
            $arOrder[$value['order2_id']]['RUNNING_METERS'] = $lengthOrder_main;
        }

        $q = max($lengthOrder_main, $lengthorder2);
        $lengthOfRolls[$value['withMaterial']] += $q;
    }

    return $lengthOfRolls;
}
function swapCombination(&$allCombinations, $arOrder)
{
    $lengthOfRolls = [
        '1400' => 0,
        '1260' => 0,
        '1050' => 0,
        '840' => 0
    ];
    $lengthOfRolls = completionLengthOfRolls($allCombinations, $lengthOfRolls, $arOrder);

    global $allWithMaterials;
    $lengthOfRolls = getMinRolls($lengthOfRolls); // массив с рулонами/ форматами меньше 1000
    $CombinationMinLendthOfRolls = getCominationByWidth($allCombinations, $lengthOfRolls); // массив с совмещениями рулонов менее 1000
    $arOrderRecalculation = [];
    foreach ($CombinationMinLendthOfRolls as $key => $value) {
        $arOrder[$value['order1_id']]['RUNNING_METERS'] = getRaningMetrs(
            $value['main_made'],
            $arOrder[$value['order1_id']]['KOL_VO_NA_SHTAMPE_VALUE'],
            $arOrder[$value['order1_id']]['DLINA_ZAGOTOVKI_VALUE'],
            $arOrder[$value['order1_id']]['TIP_UPAKOVKI_VALUE']
        );
        $arOrder[$value['order2_id']]['RUNNING_METERS'] = getRaningMetrs(
            $value['combined_made'],
            $arOrder[$value['order2_id']]['KOL_VO_NA_SHTAMPE_VALUE'],
            $arOrder[$value['order2_id']]['DLINA_ZAGOTOVKI_VALUE'],
            $arOrder[$value['order2_id']]['TIP_UPAKOVKI_VALUE']
        );
        unset($allCombinations[$key]);
        $arOrderRecalculation[$value['order1_id']] =  $arOrder[$value['order1_id']];
        $arOrderRecalculation[$value['order2_id']] =  $arOrder[$value['order2_id']];
    }
    $difmaterials = array_diff($allWithMaterials, array_keys($lengthOfRolls));
    $CombinationsRecalculation = calculation($arOrderRecalculation, $difmaterials);
    $CombinationsRecalculation = filterArResult($CombinationsRecalculation, $arOrderRecalculation);

    return $CombinationsRecalculation;
}
function filterArResult(array $allCombinations, array $arOrder): array
{
    global $width_conditions;
    // сортируем, номера заказов от меньшего к большему и для каждого заказа от большей эфективности к меньшей
    //return [$a['order1'], $b['effectiveness']] <=> [$b['order1'], $a['effectiveness']];
    usort($allCombinations, function (array $a, array $b) {
        return [$a['order1'], $b['effectiveness']] <=> [$b['order1'], $a['effectiveness']];
    });

    $totalMileage = 0;
    foreach ($allCombinations as $key => &$value) {
        filterCombination($allCombinations, $arOrder, $key, $value, $totalMileage);
    }

    COption::SetOptionString('production.line', 'totalMileage', $totalMileage);

    return $allCombinations;
}
function getRaningMetrs($KOL_VO_PLAN_SHTUK_VALUE, $KOL_VO_NA_SHTAMPE_VALUE, $DLINA_ZAGOTOVKI_VALUE, $TIP_UPAKOVKI_VALUE)
{
    switch ($TIP_UPAKOVKI_VALUE) {
        case 62:
            return $KOL_VO_PLAN_SHTUK_VALUE / $KOL_VO_NA_SHTAMPE_VALUE * $DLINA_ZAGOTOVKI_VALUE / 1000;
        default:
            return $KOL_VO_PLAN_SHTUK_VALUE / 1 * $DLINA_ZAGOTOVKI_VALUE / 1000;
    }
}
function getMinRolls($lengthOfRolls)
{
    foreach ($lengthOfRolls as $key => $length) {
        if ($length > 1000) {
            unset($lengthOfRolls[$key]);
        }
    }
    return $lengthOfRolls;
}
function getCominationByWidth($allCombinations, $lengthOfRolls)
{
    foreach ($allCombinations as $key => $combination) {
        if ($lengthOfRolls[$combination['withMaterial']]) {
            $arr[$key] = $combination;
        }
    }
    return $arr;
}
function putValueMadedAndLeft($value, &$arr)
{
    if ($value['order2'] === null) {
        if ($value['main_left'] == 0) {
            $value['main_made'] = $value['main_quantity_plain'];
        }
        $arr[$value['order1_id']] = [
            'made' => $value['main_made'],
            'left' => $value['main_left']
        ];
    } else {
        if ($value['main_left'] == 0) {
            $value['main_made'] = $value['main_quantity_plain'];
        }
        if ($value['combined_left'] == 0) {
            $value['combined_made'] = $value['combined_quantity_plain'];
        }
        $arr[$value['order1_id']] = [
            'made' => $value['main_made'],
            'left' => $value['main_left']
        ];
        $arr[$value['order2_id']] = [
            'made' => $value['combined_made'],
            'left' => $value['combined_left']
        ];
    }
}


function filterCombination(&$allCombinations, &$arOrder, $key, &$value, &$totalMileage)
{
    if (isInvalidOrder($arOrder, $value) || isMileageExceeded($totalMileage)) {
        unset($allCombinations[$key]);
        return;
    }


    // processMaterialAndQuantity($arOrder, $value); // развернул функцию
    //заполняем данные, для выводаа, можно перенести перед возвратом результата
    $value['material'] = $arOrder[$value['order1_id']]['MATERIAL_INFO_NAME'];
    $value['main_quantity_plain'] = $arOrder[$value['order1_id']]['KOL_VO_PLAN_SHTUK_VALUE_COPY'];
    $value['combined_quantity_plain'] = $arOrder[$value['order2_id']]['KOL_VO_PLAN_SHTUK_VALUE_COPY'] ?? 0;
    $value['effectiveness'] = $value['trueEffectiveness'] ?? '';
    // $value['effectiveness'] .= '%'; // более не требуется


    $lengthOrder_main = getRunningMeters($arOrder, $value['order1_id'], $value['countOrder1']);

    if (empty($value['order2_id'])) {
        processSingleOrder($arOrder, $value, $lengthOrder_main);
    } else {
        processDoubleOrder($arOrder, $value, $lengthOrder_main, $totalMileage);
    }
}

function isInvalidOrder($arOrder, $value)
{
    return $arOrder[$value['order1_id']]['RUNNING_METERS'] === 0 ||
        (!empty($value['order2']) && $arOrder[$value['order2_id']]['RUNNING_METERS'] === 0);
}

function isMileageExceeded($totalMileage)
{
    return $totalMileage >= 15000;
}

function getRunningMeters($arOrder, $orderId, $countOrder)
{
    return $arOrder[$orderId]['RUNNING_METERS'] / $countOrder;
}

function processMaterialAndQuantity(&$arOrder, &$value)
{
    $value['material'] = $arOrder[$value['order1_id']]['MATERIAL_INFO_NAME'];
    $value['main_quantity_plain'] = $arOrder[$value['order1_id']]['KOL_VO_PLAN_SHTUK_VALUE_COPY'];
    $value['combined_quantity_plain'] = $arOrder[$value['order2_id']]['KOL_VO_PLAN_SHTUK_VALUE_COPY'] ?? 0;

    $value['effectiveness'] = $value['trueEffectiveness'] ?? '';
    $value['effectiveness'] .= '%';
}

function processSingleOrder(&$arOrder, &$value, $lengthOrder_main)
{
    assignSingleOrderValues($arOrder, $value, $lengthOrder_main);
    updateSequenceNumbers($arOrder, $value);
}

function processDoubleOrder(&$arOrder, &$value, $lengthOrder_main, &$totalMileage)
{
    $lengthOrder2 = getRunningMeters($arOrder, $value['order2_id'], $value['countOrder2']);
    $remainingLength = round($lengthOrder_main - $lengthOrder2, 2);

    if ($remainingLength > 0) {
        calculateOrderQuantities($arOrder, $value, $lengthOrder2, $remainingLength, 'main');
    } elseif ($remainingLength < 0) {
        calculateOrderQuantities($arOrder, $value, $lengthOrder_main, $remainingLength, 'combined');
    } else {
        calculateEqualOrderQuantities($arOrder, $value);
    }

    $value['running_meters'] = $arOrder[$value['order1_id']]['RUNNING_METERS'];
    if ($value['running_meters'] != 0) {
        $totalMileage += $value['running_meters'];
    }
}

function assignSingleOrderValues(&$arOrder, &$value, $lengthOrder_main)
{
    $value['running_meters'] = $arOrder[$value['order1_id']]['RUNNING_METERS'];
    $value['main_made'] = $arOrder[$value['order1_id']]['KOL_VO_PLAN_SHTUK_VALUE'];
    $value['main_left'] = 0;
    $arOrder[$value['order1_id']]['RUNNING_METERS'] = 0;
    
}

function calculateOrderQuantities(&$arOrder, &$value, $lengthOrder, $remainingLength, $type)
{
    if ($type === 'main') {
        $value['main_made'] = calculateMadeQuantity($lengthOrder, $value['dlina_zug1'], $value['countOrder1'], $arOrder[$value['order1_id']]['KOL_VO_NA_SHTAMPE_VALUE']);
        $value['combined_made'] = $arOrder[$value['order2_id']]['KOL_VO_PLAN_SHTUK_VALUE'];
        $value['main_left'] = $arOrder[$value['order1_id']]['KOL_VO_PLAN_SHTUK_VALUE'] - $value['main_made'];
        $value['combined_left'] = 0;
        $arOrder[$value['order1_id']]['RUNNING_METERS'] = $remainingLength;
        $arOrder[$value['order2_id']]['RUNNING_METERS'] = 0;
    } else {
        $value['main_made'] = $arOrder[$value['order1_id']]['KOL_VO_PLAN_SHTUK_VALUE'];
        $value['combined_made'] = calculateMadeQuantity($lengthOrder, $value['dlina_zug2'], $value['countOrder2'], $arOrder[$value['order2_id']]['KOL_VO_NA_SHTAMPE_VALUE']);
        $value['main_left'] = 0;
        $value['combined_left'] = $arOrder[$value['order2_id']]['KOL_VO_PLAN_SHTUK_VALUE'] - $value['combined_made'];
        $arOrder[$value['order2_id']]['RUNNING_METERS'] = abs($remainingLength);
        $arOrder[$value['order1_id']]['RUNNING_METERS'] = 0;
    }

    updateSequenceNumbers($arOrder, $value);
}

function calculateMadeQuantity($lengthOrder, $dlinaZug, $countOrder, $kolVoNaShtampe)
{
    return floor($lengthOrder * 1000 / $dlinaZug * $countOrder * $kolVoNaShtampe);
}

function calculateEqualOrderQuantities(&$arOrder, &$value)
{
    $value['main_left'] = 0;
    $value['combined_left'] = 0;
    $value['main_made'] = calculateMadeQuantityForEqual($arOrder[$value['order1_id']], $value['dlina_zug1']);
    $value['combined_made'] = calculateMadeQuantityForEqual($arOrder[$value['order2_id']], $value['dlina_zug2']);
    $arOrder[$value['order1_id']]['RUNNING_METERS'] = 0;
    $arOrder[$value['order2_id']]['RUNNING_METERS'] = 0;



    
    updateSequenceNumbers($arOrder, $value);
}

function calculateMadeQuantityForEqual($order, $dlinaZug)
{
    return $order['RUNNING_METERS'] / 2 * 1000 / $dlinaZug * $order['KOL_VO_NA_SHTAMPE_VALUE'];
}

function updateSequenceNumbers(&$arOrder, &$value)
{
    $value['sequence_number'] = $arOrder[$value['order1_id']]['SEQUENCE_NUMBER']++;
    $value['sequence_number2'] = $arOrder[$value['order2_id']]['SEQUENCE_NUMBER']++;
    $value['order1'] .= " " . $value['sequence_number'];
    $value['order2'] .= " " . $value['sequence_number2'];

    //из предыдущего кода .. другая ветка
    // $value['sequence_number'] = $arOrder[$value['order1_id']]['SEQUENCE_NUMBER'];
    // $arOrder[$value['order1_id']]['SEQUENCE_NUMBER']++;
    // $value['sequence_number2'] = $arOrder[$value['order2_id']]['SEQUENCE_NUMBER'];
    // $arOrder[$value['order2_id']]['SEQUENCE_NUMBER']++;
}