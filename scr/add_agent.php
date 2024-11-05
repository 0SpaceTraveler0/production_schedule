<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
use Production\Line\QueueProductionLineTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;

Loader::includeModule('production.line');
CModule::IncludeModule('crm');
CModule::IncludeModule('main');
\Bitrix\Main\Loader::includeModule('crm');
// echo $_SERVER['DOCUMENT_ROOT'];
// Симуляция web-контекста
// $_SERVER['DOCUMENT_ROOT'] = '/bitrix';

define("NO_AGENT_CHECK", true);    // Отключение агентов
define("NO_KEEP_STATISTIC", true); // Отключение статистики
define("NOT_CHECK_PERMISSIONS", true); // Отключение проверки прав


file_put_contents(__DIR__ . '/file.txt', 'Проверка работоспособности');

/* $data = QueueProductionLineTable::getList([
    'select' => [
        'order1' => 'NAME_ORDER_MAIN',
        'order2' => 'NAME_ORDER_COMBINED',
        'withMaterial' => 'MATERIAL_WIDTH',
        'countOrder1' => 'COUNT_ORDER_MAIN',
        'countOrder2' => 'COUNT_ORDER_COMBINED',
        'order1_id' => 'MAIN_ELEMENT_ID',
        'order2_id' => 'COMBINED_ELEMENT_ID',
        'main_made' => 'USED_MAIN_QUANTITY',
        'combined_made' => 'USED_COMBINED_QUANTITY',
        'main_left' => 'REMAINING_MAIN_QUANTITY',
        'combined_left' => 'REMAINING_COMBINED_QUANTITY',
        'running_meter' => 'RUNNING_METERS',
    ]
])->fetchAll();

$entityTypeId = \CCrmOwnerType::Deal;
$factory = Container::getInstance()->getFactory($entityTypeId);
$groupedTransactions = groupedDeal($data);
foreach ($data as $value) {

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
        'UF_CRM_1675555129' => $value['running_meter'], //меры погонные заказа
        'UF_CRM_1685005404730' => 1,
        'UF_CRM_1703658554' => $value['color']
    ]);

    $context = new \Bitrix\Crm\Service\Context();
    $context->setUserId(9);
    $operation = $factory->getAddOperation($new_item, $context);
    $res = $operation->launch();
} */
