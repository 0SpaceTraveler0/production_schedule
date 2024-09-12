<?php

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class QueueProductionLineTable extends DataManager
{
    public static function getTableName()
    {
        return 'queue_production_line';
    }

    public static function getMap()
    {
        return [
            (new Entity\IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new Entity\IntegerField('EFFICIENCY_PERCENT'))
                ->configureRequired(),

            (new Entity\IntegerField('MATERIAL_WIDTH'))
                ->configureRequired(),

            (new Entity\StringField('MATERIAL'))
                ->configureRequired()
                ->configureSize(255),

            (new Entity\IntegerField('MAIN_ELEMENT_ID'))
                ->configureRequired(),

            (new Entity\IntegerField('COMBINED_ELEMENT_ID'))
                ->configureRequired(),

            (new Entity\IntegerField('COUNT_ORDER_MAIN'))
                ->configureRequired(),

            (new Entity\IntegerField('COUNT_ORDER_COMBINED'))
                ->configureRequired(),

            (new Entity\IntegerField('QUANTITY_WIDTH_MAIN'))
                ->configureRequired(),

            (new Entity\IntegerField('QUANTITY_WIDTH_COMBINED'))
                ->configureRequired(),

            (new Entity\IntegerField('REMAINING_MAIN_QUANTITY'))
                ->configureRequired(),

            (new Entity\IntegerField('REMAINING_COMBINED_QUANTITY'))
                ->configureRequired(),

            (new Entity\IntegerField('USED_MAIN_QUANTITY'))
                ->configureRequired(),

            (new Entity\IntegerField('USED_COMBINED_QUANTITY'))
                ->configureRequired(),

            (new Entity\IntegerField('PLAN_MAIN_QUANTITY'))
                ->configureRequired(),

            (new Entity\IntegerField('PLAN_COMBINED_QUANTITY'))
                ->configureRequired(),
        ];
    }
}
