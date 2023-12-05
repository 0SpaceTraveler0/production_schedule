<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/production_schedule/scr/bitrix_request.php');
CModule::IncludeModule('iblock');
CModule::IncludeModule('main');
$width_conditions = [
    '1050' => 5,
    '840' => 8
];
$allWithMaterials = [840, 1050, 1260, 1400];
function app()
{
    global $allWithMaterials;
    $filter = [
        "IBLOCK_ID" => 17,
        "!==NOMER_VALUE" => null,
        "!==TIP_UPAKOVKI_VALUE" => null,
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
    $swapCombination = swapCombination($allCombinations, $arAllOrder);
    $resultAr = array_merge($allCombinations, $swapCombination);
    $lengthOfRolls = [
        '1400' => 0,
        '1260' => 0,
        '1050' => 0,
        '840' => 0
    ];

    //обновление списка граффик производства
    $arMadedAndLeft = [];
    /* foreach ($resultAr as $key => &$value) {
        putValueMadedAndLeft($value, $arMadedAndLeft);
    } */
    //updateListOrder($arMadedAndLeft);

/*     deleteAllDeal(getDeal());
    addDeal($resultAr);

    usort($resultAr, function ($a, $b) {
        return ($b['withMaterial'] - $a['withMaterial']) // status ascending
            ?: strcmp($a['material'], $b['material']) // start ascending
            //?: ($b['effectiveness'] - $a['effectiveness']) // mh descending
        ;
    }); */
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
            for ($i = 1; $i < 3; $i++) {
                foreach ($arOrder as $id2 => $order2) {
                    $order2With  = (int)$order2['RAZVERTKA_SHIRINA_PO_NOZHAM_VALUE'];

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
                            'order2' => (int)$order2['NOMER_VALUE'],
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
    foreach ($allWithMaterials as $withMaterial) {
        $i = 1;
        while ($orderWidth * $i <= $withMaterial) {
            $combination = [
                'order1' => (int)$order['NOMER_VALUE'],
                'order2' => null,
                'withMaterial' => (int)$withMaterial,
                'countOrder1' => (int)$i,
                'dlina_zug1' => (int)$order['DLINA_ZAGOTOVKI_VALUE'],
                'order1_id' => (int)$order['ID']
            ];
            $allCombinations[] = calculatingEfficiency($combination, $orderWidth, $i, $withMaterial, true);
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
    /* if (isset($width_conditions[$withMaterial]) and $flag == true) {
        $eff = $effectiveness - $width_conditions[$withMaterial];
        $combination['effectiveness'] = round($eff, 2);
        $combination['trueEffectiveness'] = round($effectiveness, 2);
    } else {
        $combination['effectiveness'] = round($effectiveness, 2);
    } */
    $combination['effectiveness'] = round($effectiveness, 2);
    return $combination;
}
function completionLengthOfRolls($allCombinations, $lengthOfRolls, &$arOrder)
{
    foreach ($allCombinations as $key => $value) {
        if ($value['order2'] === null) {
            $lengthorder1 = ceil($value['main_made'] * $value['dlina_zug1'] / ($value['countOrder1'] * $arOrder[$value['order1_id']]['KOL_VO_NA_SHTAMPE_VALUE'] * 1000));
            $arOrder[$value['order1_id']]['RUNNING_METERS'] = $lengthorder1;
        } else {
            $lengthorder1 = ceil($value['main_made'] * $value['dlina_zug1'] / ($value['countOrder1'] * $arOrder[$value['order1_id']]['KOL_VO_NA_SHTAMPE_VALUE'] * 1000));
            $lengthorder2 = ceil($value['main_made'] * $value['dlina_zug2'] / ($value['countOrder2'] * $arOrder[$value['order2_id']]['KOL_VO_NA_SHTAMPE_VALUE'] * 1000));
            $arOrder[$value['order1_id']]['RUNNING_METERS'] = $lengthorder1;
            $arOrder[$value['order2_id']]['RUNNING_METERS'] = $lengthorder1;
        }

        $q = max($lengthorder1, $lengthorder2);
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
    usort($allCombinations, function (array $a, array $b) {
        return [$a['order1'], $b['effectiveness']] <=> [$b['order1'], $a['effectiveness']];
    });

    $totalMileage = 0;

    foreach ($allCombinations as $key => &$value) {
        filter($allCombinations, $arOrder, $key, $value, $totalMileage);
    }
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
function filter(&$allCombinations, &$arOrder, $key, &$value, &$totalMileage)
{
    if ($arOrder[$value['order1_id']]['RUNNING_METERS'] === 0 or $arOrder[$value['order2_id']]['RUNNING_METERS'] === 0) {
        unset($allCombinations[$key]);
        return;
    }
    if ($totalMileage >= 17000) {
        unset($allCombinations[$key]);
        return;
    }
    $value['material'] = $arOrder[$value['order1_id']]['MATERIAL_INFO_NAME'];
    $value['main_quantity_plain'] = $arOrder[$value['order1_id']]['KOL_VO_PLAN_SHTUK_VALUE_COPY'];
    $value['combined_quantity_plain'] = $arOrder[$value['order2_id']]['KOL_VO_PLAN_SHTUK_VALUE_COPY'];

    if (isset($value['trueEffectiveness'])) {
        $value['effectiveness'] = $value['trueEffectiveness'];
    }
    $value['effectiveness'] = $value['effectiveness'] . '%';

    $lengthorder1 = $arOrder[$value['order1_id']]['RUNNING_METERS'] / $value['countOrder1'];

    if ($value['order2'] === null) {
        $alignmentLength = $arOrder[$value['order1_id']]['RUNNING_METERS'];
        $value['running_meters'] = $alignmentLength;        
        $value['sequence_number'] = $arOrder[$value['order1_id']]['SEQUENCE_NUMBER'];
        $arOrder[$value['order1_id']]['SEQUENCE_NUMBER']++;
        $arOrder[$value['order1_id']]['RUNNING_METERS'] = 0;
        $value['main_made'] = $arOrder[$value['order1_id']]['KOL_VO_PLAN_SHTUK_VALUE'];
        $value['main_left'] = 0;
        $value['order1'] = $value['order1']. " " . $value['sequence_number'];
        return;
    }
    $lengthorder2 = $arOrder[$value['order2_id']]['RUNNING_METERS'] / $value['countOrder2'];

    $remaining_length = ceil($lengthorder1 - $lengthorder2);
    if ($remaining_length > 0) {
        $value['main_made'] = floor($lengthorder2 * 1000 / $value['dlina_zug1'] * $value['countOrder1'] * $arOrder[$value['order1_id']]['KOL_VO_NA_SHTAMPE_VALUE']) - 1;
        $value['combined_made'] = $arOrder[$value['order2_id']]['KOL_VO_PLAN_SHTUK_VALUE'];
        // $value['main_left'] = (int)($remaining_length * 1000 / $value['dlina_zug1'] * $value['countOrder1'] * $arOrder[$value['order1_id']]['KOL_VO_NA_SHTAMPE_VALUE']);
        $value['main_left'] = $arOrder[$value['order1_id']]['KOL_VO_PLAN_SHTUK_VALUE'] - $value['main_made'];
        $value['combined_left'] = 0;
        $alignmentLength = $arOrder[$value['order1_id']]['RUNNING_METERS'] - $remaining_length;
        
        
        $value['sequence_number'] = $arOrder[$value['order1_id']]['SEQUENCE_NUMBER'];
        $arOrder[$value['order1_id']]['SEQUENCE_NUMBER']++;
        $value['sequence_number2'] = $arOrder[$value['order2_id']]['SEQUENCE_NUMBER'];
        $arOrder[$value['order2_id']]['SEQUENCE_NUMBER']++;

        $value['order1'] = $value['order1']. " " . $value['sequence_number'];
        $value['order2'] = $value['order2']. " " . $value['sequence_number2'];

        $arOrder[$value['order1_id']]['RUNNING_METERS'] = $remaining_length;
        $arOrder[$value['order2_id']]['RUNNING_METERS'] = 0;
        //$arOrder[$value['order1_id']]['KOL_VO_PLAN_SHTUK_VALUE'] = ceil($arOrder[$value['order1_id']]['RUNNING_METERS'] * 1000 / $value['dlina_zug1'] * $value['countOrder1'] * $arOrder[$value['order1_id']]['KOL_VO_NA_SHTAMPE_VALUE']);
        $arOrder[$value['order1_id']]['KOL_VO_PLAN_SHTUK_VALUE'] = $value['main_left'];
    } elseif ($remaining_length < 0) {
        $value['main_made'] = $arOrder[$value['order1_id']]['KOL_VO_PLAN_SHTUK_VALUE'];
        $value['combined_made'] = floor(($lengthorder1) * 1000 / $value['dlina_zug2'] * $value['countOrder2'] * $arOrder[$value['order2_id']]['KOL_VO_NA_SHTAMPE_VALUE']);

        $remaining_length = $remaining_length * -1;
        $value['main_left'] = 0;
        // $value['combined_left'] = (int)($remaining_length * 1000 / $value['dlina_zug2'] * $value['countOrder2'] * $arOrder[$value['order2_id']]['KOL_VO_NA_SHTAMPE_VALUE']);
        $value['combined_left'] = $arOrder[$value['order2_id']]['KOL_VO_PLAN_SHTUK_VALUE'] - $value['combined_made'];
        $alignmentLength = $arOrder[$value['order2_id']]['RUNNING_METERS'] - $remaining_length;

        $value['sequence_number'] = $arOrder[$value['order1_id']]['SEQUENCE_NUMBER'];
        $arOrder[$value['order1_id']]['SEQUENCE_NUMBER']++;
        $value['sequence_number2'] = $arOrder[$value['order2_id']]['SEQUENCE_NUMBER'];
        $arOrder[$value['order2_id']]['SEQUENCE_NUMBER']++;

        $value['order1'] = $value['order1']. " " . $value['sequence_number'];
        $value['order2'] = $value['order2']. " " . $value['sequence_number2'];

        $arOrder[$value['order2_id']]['RUNNING_METERS'] = $remaining_length;
        $arOrder[$value['order1_id']]['RUNNING_METERS'] = 0;
        //$arOrder[$value['order2_id']]['KOL_VO_PLAN_SHTUK_VALUE'] = ceil($arOrder[$value['order2_id']]['RUNNING_METERS'] * 1000 / $value['dlina_zug2'] * $value['countOrder2'] * $arOrder[$value['order2_id']]['KOL_VO_NA_SHTAMPE_VALUE']);
        $arOrder[$value['order2_id']]['KOL_VO_PLAN_SHTUK_VALUE'] =  $value['combined_left'];
    } else {
        $value['main_left'] = 0;
        $value['combined_left'] = 0;

        $value['main_made'] = $arOrder[$value['order1_id']]['RUNNING_METERS'] / 2 * 1000 / $value['dlina_zug1'] * $arOrder[$value['order1_id']]['KOL_VO_NA_SHTAMPE_VALUE'];
        $value['combined_made'] = $arOrder[$value['order2_id']]['RUNNING_METERS'] / 2 * 1000 / $value['dlina_zug2'] * $arOrder[$value['order2_id']]['KOL_VO_NA_SHTAMPE_VALUE'];

        $alignmentLength = $arOrder[$value['order1_id']]['RUNNING_METERS'] / 2;

        $arOrder[$value['order1_id']]['RUNNING_METERS'] = 0;
        $arOrder[$value['order2_id']]['RUNNING_METERS'] = 0;
    }
    $value['running_meters'] = $alignmentLength;
    if ($alignmentLength != 0) {
        $totalMileage += $alignmentLength;
    }

}
