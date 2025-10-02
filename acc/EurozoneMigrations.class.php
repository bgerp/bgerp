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
        $Discounts = cls::get('price_ListBasicDiscounts');

        $discounts = array();
        $discQuery = $Discounts->getQuery();
        $discQuery->where("#amountFrom IS NOT NULL OR #amountTo IS NOT NULL");
        while($dRec = $discQuery->fetch()){
            $discounts[$dRec->listId][$dRec->id] = $dRec;
        }

        $saveDiscounts = array();
        $query = $Lists->getQuery();
        $query->where("#currency = 'BGN'");
        while($rec = $query->fetch()){
            $rec->currency = 'EUR';
            $Lists->save_($rec, 'currency');

            if(isset($discounts[$rec->id])){
                foreach ($discounts[$rec->id] as $dRec1){
                    if($dRec1->amountFrom !== null){
                        $dRec1->amountFrom /= 1.95583;
                        $dRec1->amountFrom = round($dRec1->amountFrom, 2);
                    }

                    if(!empty($dRec1->amountTo)){
                        $dRec1->amountTo /= 1.95583;
                        $dRec1->amountTo = round($dRec1->amountTo, 2);
                    }

                    $saveDiscounts[$dRec1->id] = $dRec1;
                }
            }
        }

        if(countR($saveDiscounts)){
            $Discounts->saveArray($saveDiscounts, 'id,amountTo,amountFrom');
        }
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


    /**
     * Миграция на делтите
     */
    public static function updateDeltas()
    {
        $Deltas   = cls::get('sales_PrimeCostByDocument');

        $sellCost  = str::phpToMysqlName('sellCost');                       // sell_cost
        $primeCost = str::phpToMysqlName('primeCost');                      // prime_cost
        $autoDisc  = str::phpToMysqlName('autoDiscountAmount');             // auto_discount_amount
        $scwod     = str::phpToMysqlName('sellCostWithOriginalDiscount');   // sell_cost_with_original_discount
        $tbl = $Deltas->dbTableName;

        $query = "
UPDATE `{$tbl}`
SET
  `{$sellCost}`  = CASE WHEN `{$sellCost}`  IS NOT NULL THEN `{$sellCost}`  / 1.95583 ELSE NULL END,
  `{$primeCost}` = CASE WHEN `{$primeCost}` IS NOT NULL THEN `{$primeCost}` / 1.95583 ELSE NULL END,
  `{$autoDisc}`  = CASE WHEN `{$autoDisc}`  IS NOT NULL THEN `{$autoDisc}`  / 1.95583 ELSE NULL END,
  `{$scwod}`     = CASE WHEN `{$scwod}`     IS NOT NULL THEN `{$scwod}`     / 1.95583 ELSE NULL END
";
        $Deltas->db->query($query);
    }


    /**
     * Миграция на делтите
     */
    public static function updatePriceCosts()
    {
        $Deltas   = cls::get('price_ProductCosts');

        $euroZoneDate = acc_Setup::getEuroZoneDate();
        $priceCol  = str::phpToMysqlName('price');
        $updatedOnCol = str::phpToMysqlName('updatedOn');
        $tbl = $Deltas->dbTableName;

        $query = "UPDATE `{$tbl}` SET
        `{$priceCol}`  = CASE WHEN `{$priceCol}`  != 0 THEN `{$priceCol}`  / 1.95583 ELSE NULL END
        WHERE `{$updatedOnCol}` <= '{$euroZoneDate}'
        ";

        $Deltas->db->query($query);
    }



    function act_Test()
    {
        requireRole('debug');

        self::updatePriceCosts();
    }
}
