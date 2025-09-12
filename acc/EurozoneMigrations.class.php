<?php


/**
 * Миграции свързани с еврозоната
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acc_EurozoneMigrations extends core_BaseClass
{
    /**
     * Мигрира ЦП от BGN в EUR
     */
    public static function updatePriceLists()
    {
        $Lists = cls::get('price_Lists');

        $currencyColName = str::phpToMysqlName('currency');
        $query = "UPDATE {$Lists->dbTableName} SET {$currencyColName} = 'EUR' WHERE {$currencyColName} = 'BGN'";
        $Lists->db->query($query);
    }


    /**
     * Мигрира настройките в онлайн магазина
     */
    public static function updateEshopSettings()
    {
        $Settings = cls::get('eshop_Settings');

        $Carts = cls::get('eshop_Carts');
        $Carts->setupMvc();

        $sQuery = $Settings->getQuery();
        $sQuery->where("#currencyId = 'BGN' AND #classId = " . cms_Domains::getClassId());
        $sQuery->show('objectId');
        $settingIds = arr::extractValuesFromArray($sQuery->fetchAll(), 'objectId');

        if(countR($settingIds)){
            $currencyColName = str::phpToMysqlName('currencyId');
            $domainColName = str::phpToMysqlName('domainId');
            $query = "UPDATE {$Carts->dbTableName} SET {$currencyColName} = 'BGN' WHERE {$domainColName} IN (" . implode(',', $settingIds) . ") AND {$currencyColName} IS NULL";
            $Carts->db->query($query);
        }

        $eQuery = eshop_Settings::getQuery();
        $eQuery->where("#currencyId = 'BGN'");
        while($eRec = $eQuery->fetch()){
            $eRec->currencyId = 'EUR';
            if(!empty($eRec->freeDelivery)){
                $eRec->freeDelivery /= 1.95583;
                $eRec->freeDelivery = round($eRec->freeDelivery, 2);
            }
            if(!empty($eRec->minOrderAmount)){
                $eRec->minOrderAmount /= 1.95583;
                $eRec->minOrderAmount = round($eRec->minOrderAmount, 2);
            }

            if(!empty($eRec->freeDeliveryByBus)){
                $eRec->freeDeliveryByBus /= 1.95583;
                $eRec->freeDeliveryByBus = round($eRec->freeDeliveryByBus, 2);
            }

            eshop_Settings::save($eRec, 'currencyId,freeDeliveryByBus,minOrderAmount,freeDelivery');
        }
    }

    function act_Test()
    {
        requireRole('debug');

        self::updateEshopSettings();
    }
}
