<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/production_schedule/scr/bitrix_request.php');

use Bitrix\Main\Loader;
use Production\Line\QueueProductionLineTable;
if (!Loader::includeModule('production.line')) {
    die('Module not installed');
}
deleteAllDeal(getDeal());

QueueProductionLineTable::deleteAllRecords();
COption::SetOptionString('production.line', 'totalMileage', 0);