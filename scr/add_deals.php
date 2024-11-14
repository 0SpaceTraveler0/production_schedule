<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Production\Line\QueueProductionLineTable;

Loader::includeModule('production.line');
$id = $_REQUEST['id'] ?? null;
file_put_contents(__DIR__ . '/test.txt', print_r($_REQUEST,true), FILE_APPEND);
if (!$id) die();
file_put_contents(__DIR__ . '/test.txt', $id, FILE_APPEND);
$data = QueueProductionLineTable::getList([
    'select' => [
        'NAME_ORDER_MAIN',
        'NAME_ORDER_COMBINED',
        'MATERIAL_WIDTH',
        'COUNT_ORDER_MAIN',
        'COUNT_ORDER_COMBINED',
        'MAIN_ELEMENT_ID',
        'COMBINED_ELEMENT_ID',
        'EFFICIENCY_PERCENT',
        'PLAN_MAIN_QUANTITY',
        'PLAN_COMBINED_QUANTITY',
        'USED_MAIN_QUANTITY',
        'USED_COMBINED_QUANTITY',
        'REMAINING_MAIN_QUANTITY',
        'REMAINING_COMBINED_QUANTITY',
        'RUNNING_METERS',
        'COLOR',
    ],
    'filter' => [
        '>ID' => $id
    ],
    'limit' => 1
])->fetch();


$entityTypeId = \CCrmOwnerType::Deal;
$factory = Container::getInstance()->getFactory($entityTypeId);
$new_item = $factory->createItem([
    'TITLE' => $data['NAME_ORDER_MAIN'] . '/' . $data['NAME_ORDER_COMBINED'],
    'STAGE_ID' => 'C9:UC_5O2IAX',
    'CATEGORY_ID' => 9,
    'UF_CRM_1680089010545' => $data['MATERIAL_WIDTH'], //ширина рулона
    'UF_CRM_1680087136' => $data['MAIN_ELEMENT_ID'], // id паспорт
    'UF_CRM_1674156116' => $data['COMBINED_ELEMENT_ID'], // id паспорт сов
    'UF_CRM_1680087517854' => $data['COUNT_ORDER_MAIN'], //Количество основного заказа в ширину
    'UF_CRM_1680088113635' => $data['COUNT_ORDER_COMBINED'], //Количество совмещенного заказа в ширину
    'UF_CRM_1702558329461' => $data['USED_MAIN_QUANTITY'], //Штук на запуск ОСН
    'UF_CRM_1702558337821' => $data['USED_COMBINED_QUANTITY'], //Штук на запуск СОВ
    'UF_CRM_1702558362582' => $data['REMAINING_MAIN_QUANTITY'], //Остается не сделано ОСН
    'UF_CRM_1702558368462' => $data['REMAINING_COMBINED_QUANTITY'], //Остается несделано СОВ
    'UF_CRM_1675555129' => $data['RUNNING_METERS'], //меры погонные заказа
    'UF_CRM_1685005404730' => 1,
    'UF_CRM_1703658554' => $data['COLOR']
]);

$operation = $factory->getAddOperation($new_item, (new \Bitrix\Crm\Service\Context())->setUserId(9));
$operation->launch();
