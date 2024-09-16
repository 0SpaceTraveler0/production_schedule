<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Production\Line\QueueProductionLineTable;

Loader::includeModule('production.line');

$backendData = QueueProductionLineTable::getList([
    'select' => [
        'order1' => 'MAIN_ELEMENT_ID',               
        'order2' => 'COMBINED_ELEMENT_ID',          
        'withMaterial' => 'MATERIAL_WIDTH',         
        'countOrder1' => 'COUNT_ORDER_MAIN',        
        'countOrder2' => 'COUNT_ORDER_COMBINED',    
        'dlina_zug1' => 'QUANTITY_WIDTH_MAIN',      
        'dlina_zug2' => 'QUANTITY_WIDTH_COMBINED',  
        'order1_id' => 'MAIN_ELEMENT_ID',           
        'order2_id' => 'COMBINED_ELEMENT_ID',       
        'effectiveness' => 'EFFICIENCY_PERCENT',    
        'main_quantity_plain' => 'PLAN_MAIN_QUANTITY',    
        'combined_quantity_plain' => 'PLAN_COMBINED_QUANTITY', 
        'main_made' => 'USED_MAIN_QUANTITY',        
        'combined_made' => 'USED_COMBINED_QUANTITY',
        'main_left' => 'REMAINING_MAIN_QUANTITY',   
        'combined_left' => 'REMAINING_COMBINED_QUANTITY',
        'material_type' => 'MATERIAL', // Переименованный алиас для поля "MATERIAL"
    ]
])->fetchAll();

$totalMileage = $current_value = COption::GetOptionString('production.line', 'totalMileage');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="style/style.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900|Material+Icons" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/quasar@1.22.10/dist/quasar.min.css" rel="stylesheet">
    <meta charset="UTF-8">
    <title>График</title>
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/vue@^2.0.0/dist/vue.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quasar@1.22.10/dist/quasar.umd.min.js"></script>

<div id="app">
    <div class="q-pa-md">
        <q-table
            title="График"
            :data="rows"
            :columns="columns"
            row-key="name"
            :rows-per-page-options="[20, 50, 0]"
        />
    </div>
</div>

<script>
    const columns = [
        { name: 'order1',                   label: '№', align: 'center', field: 'order1', format: val => `${val}`, sortable: true },
        { name: 'order2',                   label: '№ Сов.', align: 'center', field: 'order2', sortable: true },
        { name: 'effectiveness',            label: 'Эффективность', align: 'center', field: 'effectiveness', sortable: true },
        { name: 'withMaterial',             label: 'Ширина материала (рулона)', align: 'center', field: 'withMaterial', sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        { name: 'material',                 label: 'Материал', align: 'center', field: 'material_type', sortable: true },
        { name: 'main_quantity_plain',      label: 'Кол. план штук осн. Заказа', align: 'center', field: 'main_quantity_plain' },
        { name: 'combined_quantity_plain',  label: 'Кол. План штук сов. Заказа', align: 'center', field: 'combined_quantity_plain' },
        { name: 'countOrder1',              label: 'кол-во в шир1', align: 'center', field: 'countOrder1'},
        { name: 'countOrder2',              label: 'кол-во в шир2', align: 'center', field: 'countOrder2'},
        { name: 'main_left',                label: 'Штук осн. осталось', align: 'center', field: 'main_left'},
        { name: 'combined_left',            label: 'Штук сов. осталось', align: 'center', field: 'combined_left', sortable: true },
        { name: 'main_made',                label: 'Штук осн. сделано', align: 'center', field: 'main_made', sortable: true },
        { name: 'combined_made',            label: 'Штук сов. сделано', align: 'center', field: 'combined_made', sortable: true }
    ];

    const rows = <?= json_encode($backendData, JSON_UNESCAPED_SLASHES) ?>;
    console.log(rows);
    console.log(<?= json_encode($totalMileage, JSON_UNESCAPED_SLASHES) ?>);

    window.app = new Vue({
        el: '#app',
        data: {
            columns: columns,
            rows: rows
        }
    });
</script>
</body>
</html>
