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
class eurozone_Migrations extends core_BaseClass
{
    /**
     * Константа за име на безналичния метод за плащане лева
     */
    const BGN_NON_CASH_PAYMENT_NAME = 'BGN';


    /**
     * Мигрира ЦП от BGN в EUR
     */
    public static function updatePriceLists()
    {
        $Lists = cls::get('price_Lists');
        $Discounts = cls::get('price_ListBasicDiscounts');

        $discounts = array();
        $discQuery = $Discounts->getQuery();
        $discQuery->where("#amountFrom IS NOT NULL OR #amountTo IS NOT NULL OR #discountAmount IS NOT NULL");
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

                    if(!empty($dRec1->discountAmount)){
                        $dRec1->discountAmount /= 1.95583;
                        $dRec1->discountAmount = round($dRec1->discountAmount, 2);
                    }

                    $saveDiscounts[$dRec1->id] = $dRec1;
                }
            }
        }

        if(countR($saveDiscounts)){
            $Discounts->saveArray($saveDiscounts, 'id,amountTo,amountFrom,discountAmount');
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
    public static function updateDeltas($test = false)
    {
        $Deltas   = cls::get('sales_PrimeCostByDocument');
        $SaveClass = $Deltas;

        if($test){
            self::prepareCopyTable('sales_PrimeCostByDocument', 'eurozone_PrimeCostByDocumentTest');
            $SaveClass = cls::get('eurozone_PrimeCostByDocumentTest');
        }

        $eurozoneDate = acc_Setup::getEuroZoneDate();
        $valiorCol = str::phpToMysqlName('valior');
        $sellCost  = str::phpToMysqlName('sellCost');                       // sell_cost
        $primeCost = str::phpToMysqlName('primeCost');                      // prime_cost
        $autoDisc  = str::phpToMysqlName('autoDiscountAmount');             // auto_discount_amount
        $scwod     = str::phpToMysqlName('sellCostWithOriginalDiscount');   // sell_cost_with_original_discount

        $tbl = $SaveClass->dbTableName;

        $query = "
UPDATE `{$tbl}`
SET
  `{$sellCost}`  = CASE WHEN `{$sellCost}`  IS NOT NULL THEN `{$sellCost}`  / 1.95583 ELSE NULL END,
  `{$primeCost}` = CASE WHEN `{$primeCost}` IS NOT NULL THEN `{$primeCost}` / 1.95583 ELSE NULL END,
  `{$autoDisc}`  = CASE WHEN `{$autoDisc}`  IS NOT NULL THEN `{$autoDisc}`  / 1.95583 ELSE NULL END,
  `{$scwod}`     = CASE WHEN `{$scwod}`     IS NOT NULL THEN `{$scwod}`     / 1.95583 ELSE NULL END WHERE `{$valiorCol}` < '{$eurozoneDate}'";

        $SaveClass->db->query($query);
    }


    /**
     * Миграция на доставките
     */
    public static function updatePurchases($test = false)
    {
        $PData   = cls::get('purchase_PurchasesData');

        $SaveClass = $PData;
        if($test){
            self::prepareCopyTable('purchase_PurchasesData', 'eurozone_PurchasesDataTest');
            $SaveClass = cls::get('eurozone_PurchasesDataTest');
        }

        $priceCol  = str::phpToMysqlName('price');                       // sell_cost
        $amount = str::phpToMysqlName('amount');                      // prime_cost
        $expensesCol  = str::phpToMysqlName('expenses');             // auto_discount_amount
        $tbl = $SaveClass->dbTableName;

        $query = "
UPDATE `{$tbl}`
SET
  `{$priceCol}`  = CASE WHEN `{$priceCol}`  IS NOT NULL THEN `{$priceCol}`  / 1.95583 ELSE NULL END,
  `{$amount}` = CASE WHEN `{$amount}` IS NOT NULL THEN `{$amount}` / 1.95583 ELSE NULL END,
  `{$expensesCol}`  = CASE WHEN `{$expensesCol}`  IS NOT NULL THEN `{$expensesCol}`  / 1.95583 ELSE NULL END";

        $SaveClass->db->query($query);
    }


    /**
     * Миграция на кешираните цени
     */
    public static function updatePriceCosts($test = false)
    {
        $Costs   = cls::get('price_ProductCosts');
        $SaveClass = $Costs;
        if($test){
            self::prepareCopyTable('price_ProductCosts', 'eurozone_ProductCostsTest');
            $SaveClass = cls::get('eurozone_ProductCostsTest');
        }

        $euroZoneDate = acc_Setup::getEuroZoneDate();
        $priceCol  = str::phpToMysqlName('price');
        $updatedOnCol = str::phpToMysqlName('updatedOn');
        $tbl = $SaveClass->dbTableName;

        $query = "UPDATE `{$tbl}` SET
        `{$priceCol}`  = CASE WHEN `{$priceCol}`  != 0 THEN `{$priceCol}`  / 1.95583 ELSE NULL END
        WHERE `{$updatedOnCol}` <= '{$euroZoneDate}'
        ";

        $SaveClass->db->query($query);
    }


    /**
     * Обновява кешираните складови сб-ст
     */
    public static function updatePricesByDate($test = false)
    {
        $StorePrices = cls::get('acc_ProductPricePerPeriods');
        $SaveClass = $StorePrices;
        if($test){
            self::prepareCopyTable('acc_ProductPricePerPeriods', 'eurozone_ProductPricePerPeriodsTest');
            $SaveClass = cls::get('eurozone_ProductPricePerPeriodsTest');
        }

        $priceCol = str::phpToMysqlName('price');
        $dateCol = str::phpToMysqlName('date');

        $eurozoneDate = acc_Setup::getEuroZoneDate();
        $tbl = $SaveClass->dbTableName;
        $query = "UPDATE `{$tbl}` SET `{$priceCol}`  = (`{$priceCol}`  / 1.95583) WHERE `{$dateCol}` < '{$eurozoneDate}'";
        $SaveClass->db->query($query);
    }

    public $migrations = array('delta' => array('name' => 'Делти', 'function' => 'updateDeltas', 'class' => 'sales_PrimeCostByDocument', 'copy' => 'eurozone_PrimeCostByDocumentTest'),
        'purchases' => array('name' => 'Доставки', 'function' => 'updatePurchases', 'class' => 'purchase_PurchasesData', 'copy' => 'eurozone_PurchasesDataTest'),
        'stocks' => array('name' => 'Складови сб-ст', 'function' => 'updatePricesByDate', 'class' => 'acc_ProductPricePerPeriods', 'copy' => 'eurozone_ProductPricePerPeriodsTest'),
        'prices' => array('name' => 'Цени на артикули', 'function' => 'updatePriceCosts', 'class' => 'price_ProductCosts', 'copy' => 'eurozone_ProductCostsTest'),

    );


    /**
     * Подмяна на периодите след еврозоната да са с евро
     */
    public function updateCreatedPeriods()
    {
        $eurozoneDate = acc_Setup::getEuroZoneDate();
        $prevEndBeforeEurozone = dt::getLastDayOfMonth(dt::addMonths(-1, $eurozoneDate));

        $Periods = cls::get('acc_Periods');
        $euroId = currency_Currencies::getIdByCode('EUR');
        $bgnId = currency_Currencies::getIdByCode('BGN');

        $savePeriods = array();
        $pQuery = $Periods->getQuery();
        $pQuery->where("#baseCurrencyId = {$bgnId} AND #end > '{$prevEndBeforeEurozone}'");
        while($pRec = $pQuery->fetch()){
            $pRec->baseCurrencyId = $euroId;
            $savePeriods[$pRec->id] = $pRec;
        }

        if(countR($savePeriods)){
            $Periods->saveArray($savePeriods, 'id,baseCurrencyId');
        }
    }


    function act_Test()
    {
        requireRole('debug');


        expect($fnc = Request::get('fnc'));
        expect(method_exists($this, $fnc));

        self::$fnc(true);

        followRetUrl(null, 'Успешно минал тест');

        self::addBgnPayment();
        self::updateCreatedPeriods();
        self::updatePriceLists();
        self::updateDeltas();
        self::updatePurchases();
        self::updateEshopSettings();
        self::updatePriceCosts();
        self::updatePricesByDate();
    }


    /**
     * Копиране на данните на една таблица в друга - копие
     *
     * @param mixed $class - клас от който да се копира
     * @param mixed $copyClass   - в таблицата на кой клас да се копира
     * @return void
     */
    private static function prepareCopyTable($class, $copyClass)
    {
        $Class = cls::get($class);
        $Copy = cls::get($copyClass);

        $originalTable = $Class->dbTableName;
        $copyTable = $Copy->dbTableName;

        $selectFields = array();
        foreach ($Class->fields as $fieldName => $field) {
            if($field->kind == 'FLD'){
                $selectFields[] = str::phpToMysqlName($fieldName);
            }
        }
        $selectFieldsStr = implode(',', $selectFields);

        $query = "TRUNCATE TABLE {$copyTable};";
        $Copy->db->query($query);

        $query = "INSERT INTO {$copyTable} SELECT {$selectFieldsStr} FROM {$originalTable};";
        $Copy->db->query($query);
    }


    /**
     * Екшън реконтиращ всички документи където участва дадена сметка
     * в даден интервал
     */
    public function act_testMigrations()
    {
        requireRole('debug');
        $tpl = getTplFromFile('eurozone/tpl/testMigrationsLayout.shtml');

        foreach ($this->migrations as $data){
            $blockTpl = clone $tpl->getBlock('BLOCK');
            $blockTpl->replace($data['name'], 'name');

            $testUrl = array($this, 'test', "fnc" => $data['function'], 'ret_url' => true);
            $testBtn = ht::createBtn("Тествай", $testUrl);
            $blockTpl->replace($testBtn, 'testBtn');

            $listUrl = $copyUrl = array();
            if($data['class']::haveRightFor('list')){
                $listUrl = array($data['class'], 'list');
            }
            if($data['copy']::haveRightFor('list')){
                $copyUrl = array($data['copy'], 'list');
            }

            if($data['class'] == 'acc_ProductPricePerPeriods'){
                $copyUrl['type'] = $listUrl = 'stores';
            }

            $blockTpl->replace(ht::createLink('Оригинал', $listUrl, false, array('target' => '_blank')), 'tableLink');
            $blockTpl->replace(ht::createLink('Копие', $copyUrl, false, array('target' => '_blank')), 'copyLink');
            $blockTpl->removeBlocksAndPlaces();
            $tpl->append($blockTpl, 'MIGRATIONS');
        }

        return $tpl;
    }


    /**
     * Миграция на полетата за финансовите сделки
     */
    public static function updateFinDeals($class)
    {
        $Deals = cls::get($class);
        $eurozoneDate = acc_Setup::getEuroZoneDate();
        $query = $Deals->getQuery();
        $query->where("#currencyId = 'BGN' AND #state = 'active'");

        while($rec = $query->fetch()) {
            $rec->currencyId = 'EUR';
            $rec->oldCurrencyId = 'BGN';
            if ($rec->valior >= $eurozoneDate) {
                $rec->currencyRate = 1;
            } else {
                $rec->currencyRate = 1.95583;
            }
            $rec->amountDeal = 0;

            if (!empty($rec->baseAmount) && $rec->currencyRate != 1) {
                $rec->baseAmount /= $rec->currencyRate;
                $rec->baseAmount = round($rec->baseAmount, 2);
            }

            $Deals->save($rec, 'currencyId,currencyRate,oldCurrencyId,baseAmount,amountDeal');
            $Deals->logWrite('Мигриране от лева в евро след еврозоната', $rec->id);
            acc_Journal::reconto($rec->containerId);

            $itemRec = acc_Items::fetchItem($Deals, $rec->id);
            $Deals->invoke('AfterJournalItemAffect', array($rec, $itemRec));
        }
    }


    function act_Test2()
    {
        requireRole('debug');

        self::addBgnPayment();
    }


    /**
     * Добавя безналичен метод за плащане - ЛЕВА
     */
    public function addBgnPayment()
    {
        core_Users::forceSystemUser();
        $rec = (object)array('title' => self::BGN_NON_CASH_PAYMENT_NAME, 'change' => 'yes', 'currencyCode' => 'BGN', 'synonym' => 'bgn');
        cond_Payments::save($rec);
        core_Users::cancelSystemUser();
    }
}
