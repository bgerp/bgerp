<?php

/**
 * Мениджър на отчети за налични количества
 *
 * @category  bgerp
 * @package   store
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Склад » Артикули налични количества
 */

class store_reports_ProductAvailableQuantity extends frame2_driver_TableData
{

    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,manager,store';

    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Артикул,mandatory');
        $fieldset->FLD('store', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,mandatory');
        $fieldset->FLD('quantityMin', 'double(decimals=2)', 'caption=Минимално количество');
        $fieldset->FLD('quantityMax', 'double(decimals=2)', 'caption=Максимално количество');
    }

    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     * @return array
     */
    protected function prepareRecs($rec, &$data = NULL)
    {
        $recs = array();
        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec   - записа
     * @param boolean $export - таблицата за експорт ли е
     * @return core_FieldSet  - полетата
     */
    protected function getTableFieldSet($rec, $export = FALSE)
    {
        $fld = cls::get('core_FieldSet');

        if($export === FALSE){
            $fld->FLD('kod', 'varchar','caption=Код');
            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            $fld->FLD('measure', 'varchar', 'caption=Мярка');
            $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'caption=Наличност');
            $fld->FLD('reservedQuantity', 'double', 'caption=Запазено');
            $fld->FLD('freeQuantity', 'double', 'caption=Разполагаемо');
            $fld->FLD('changeQuantity', 'double', 'caption=Промяна');

        } else {
            $fld->FLD('kod', 'varchar','caption=Код');
            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            $fld->FLD('measure', 'varchar', 'caption=Мярка');
            $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'caption=Наличност');
            $fld->FLD('reservedQuantity', 'double', 'caption=Запазено');
            $fld->FLD('freeQuantity', 'double', 'caption=Разполагаемо');
            $fld->FLD('changeQuantity', 'double', 'caption=Промяна');
        }

        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec  - записа
     * @param stdClass $dRec - чистия запис
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {

        $row = new stdClass();

        return $row;
    }

}