<?php
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
    $docRoot = \Bitrix\Main\Application::getDocumentRoot();
    require_once(__DIR__."/scr/app.php");
    $backendData = app();
    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="style/style.css">
    <!-- Latest compiled and minified CSS -->
    <!-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900|Material+Icons" rel="stylesheet" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/quasar@1.22.10/dist/quasar.min.css" rel="stylesheet" type="text/css">
    <meta charset="UTF-8">
    <title>График</title>
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/vue@^2.0.0/dist/vue.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quasar@1.22.10/dist/quasar.umd.min.js"></script>

<div id="app">
    <template>
        <div class="q-pa-md">
          <q-table
            title="График"
            :data="rows"
            :columns="columns"
            row-key="name"
            :rows-per-page-options="[20,50,0]"
          />
        </div>
    </template>
</div>
<script>
    const columns = [
        { name: 'order1',                   label: '№', align: 'center', field: 'order1', format: val => `${val}`, sortable: true },
        { name: 'order2',                   label: '№ Сов.', align: 'center', field: 'order2', sortable: true },
        { name: 'effectiveness',            label: 'Эффективность', align: 'center', field: 'effectiveness', sortable: true },
        { name: 'withMaterial',             label: 'Ширина материала (рулона)', align: 'center', field: 'withMaterial', sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10)},
        { name: 'material',                 label: 'Материал', align: 'center', field: 'material', sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10)},
        { name: 'main_quantity_plain',      label: 'Кол. план штук осн. Заказа', align: 'center', field: 'main_quantity_plain' },
        { name: 'combined_quantity_plain',  label: 'Кол. План штук сов. Заказа', align: 'center', field: 'combined_quantity_plain' },
        { name: 'countOrder1',                label: 'кол-во в шир1', align: 'center', field: 'countOrder1'},
        { name: 'countOrder2',                label: 'кол-во в шир2', align: 'center', field: 'countOrder2'},
        { name: 'main_left',                label: 'Штук осн. осталось', align: 'center', field: 'main_left'},
        { name: 'combined_left',            label: 'Штук сов. осталось', align: 'center', field: 'combined_left', sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        { name: 'main_made',                label: 'Штук осн. сделано', align: 'center', field: 'main_made', sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        { name: 'combined_made',            label: 'Штук сов. сделано', align: 'center', field: 'combined_made', sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) }
    ]

    const rows = <?= json_encode(array_values($backendData), JSON_UNESCAPED_SLASHES) ?>;
    console.log(rows)
    window.app = new Vue({
        el: '#app',
        data: {
            columns: columns,
            rows: rows
        }
    })

</script>
