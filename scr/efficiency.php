<?php
require_once(__DIR__ . '/bitrix_request.php');
function calculateEfficiency(&$all_order, $widthMaterial)
{
    $all_name_material = getMaterials();
    $result_array = [];

    foreach ($all_order as &$order) {
        
        $max_iterations = 5;
        $iterations = 0;
        $serialNumber = 0;
        $efficiency = 0;
        do {
            //$iterations++;
            $remaining_length = 0;

            foreach ($all_order as $copy_key => $copy_order) {
                
                if ($copy_order['all_length'] == 0 or $order['material'] !== $copy_order['material']) {
                    continue;
                }
                foreach ($widthMaterial as $width_mat) {
                    if ($order['width'] > $width_mat) {
                        continue;
                    }
                    for ($i = 1, $width_order_i = $order['width'] * $i; $i <= 3; $i++) {
                        if ($width_order_i > $width_mat) {
                            continue;
                        }
                        
                        for ($y = 1; $y <= 3; $y++) {
                            if (($width_order_i + $copy_order['width'] * $y) <= $width_mat) {
                                $remainder = ($width_order_i + $copy_order['width'] * $y);
                                $efficiency = (($remainder / $width_mat) * 100);
                                /*$width_conditions = [
                                    1050 => 5,
                                    840 => 8,
                                ];
                                if (isset($width_conditions[$width_mat]) && $order['width_material'] == $width_mat && $order['percent'] - $width_conditions[$width_mat] <= $efficiency) {
                                    $order['percent'] -= $width_conditions[$width_mat];
                                }    */          
                                
                                if ($order['percent'] < $efficiency) {
                                    $order['percent'] = $efficiency;
                                    $order['width_material'] = $width_mat;
                                    $order['combined_id_passport'] = $copy_order['id'];
                                    $order['id_element_list_comibined'] = $copy_order['id_element_list'];
                                    $order['main_order_quantity_widtht'] = $i;
                                    $order['combined_order_quantity_widtht'] = $y;
                                    $remaining_length = ceil(($order['all_length']) / $i - ($copy_order['all_length']) / $y);
                                    $copy_key_cush = $copy_key;                                    
                                }
                            }
                        }
                        if($order['percent'] == 0){
                            $order['percent'] = $efficiency;
                            $order['width_material'] = $width_mat;
                            $order['combined_id_passport'] = $copy_order['id'];
                            $order['id_element_list_comibined'] = $copy_order['id_element_list'];
                            $order['main_order_quantity_widtht'] = 1;
                            $order['combined_order_quantity_widtht'] = 1;
                            $remaining_length = ceil(($order['all_length']) / $i - ($copy_order['all_length']) / $y);
                            $copy_key_cush = $copy_key;
                        }
                    }
                }
            }

            if ($remaining_length == 0){// || $iterations >= $max_iterations) {
                break;
            }
            if ($remaining_length > 0) {
                $metr_pogon_osn = $order['all_length'] - $remaining_length;
                $metr_pogon_sov = $order['all_length'] - $remaining_length;
                $order['all_length'] = $remaining_length;
                $all_order[$copy_key_cush]['all_length'] = 0;
            } else {
                $metr_pogon_osn = $copy_order['all_length'] - $remaining_length;
                $metr_pogon_sov = $copy_order['all_length'] - $remaining_length;
                $order['all_length'] = 0;
                $all_order[$copy_key_cush]['all_length'] = abs($remaining_length);
            }
            $remaining_length = 0;
            $serialNumber++;
        } while ($order['all_length'] > 0);

        if($order['percent'] != 0){
            array_push($result_array, [
                "id" => $order['id'],
                "id_element_list" => $order['id_element_list'],
                "id_element_list_comibined" => $order['id_element_list_comibined'],
                "id_product" => $order['id_product'],
                "name" => $order['name'],
                "combined_id_passport" =>  $order['combined_id_passport'],
                "customer" => getCompany($order['customer']),
                "customer_id" => $order['customer'],
                "shipping_date" => $order['shipping_date'],
                "efficiency" =>  $order['percent'],
                "width" => $order['width_material'],
                "material" => @$all_name_material[$order['material']],
                "items_per_plan" => $order['items_per_plan'],
                "remaining_quantity" => $order['all_length'],
                "remaining_quantity_copy_order" => $all_order[$copy_key_cush]['all_length'],
                "main_order_quantity_widtht" => $order['main_order_quantity_widtht'],
                "combined_order_quantity_widtht" => $order['combined_order_quantity_widtht'],
                "urgent" => $order['urgent'],
                "flag" => 0,
                "metr_pogon_osn" => $metr_pogon_osn,
                "metr_pogon_sov" => $metr_pogon_sov,
            ])
        ;}

        $order['percent'] = 0;
    }

    return $result_array;
}
