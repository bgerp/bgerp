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
     * Класове, които да се бекъпнат
     */
    private static $backupClasses = array('eshop_Settings', 'sales_PrimeCostByDocument', 'purchase_PurchasesData', 'price_ProductCosts', 'acc_ProductPricePerPeriods', 'price_Updates', 'hr_Positions');


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
        $valiorCol = str::phpToMysqlName('valior');
        $eurozoneDate = acc_Setup::getEuroZoneDate();

        $query = "
UPDATE `{$tbl}`
SET
  `{$priceCol}`  = CASE WHEN `{$priceCol}`  IS NOT NULL THEN `{$priceCol}`  / 1.95583 ELSE NULL END,
  `{$amount}` = CASE WHEN `{$amount}` IS NOT NULL THEN `{$amount}` / 1.95583 ELSE NULL END,
  `{$expensesCol}`  = CASE WHEN `{$expensesCol}`  IS NOT NULL THEN `{$expensesCol}`  / 1.95583 ELSE NULL END WHERE `{$valiorCol}` < '{$eurozoneDate}'";

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


    /**
     * Екшън за тестове
     */
    function act_Test()
    {
        requireRole('euro');

        sleep(15);

        expect($fnc = Request::get('fnc'));
        expect(method_exists($this, $fnc));

        self::$fnc(true);

        followRetUrl(null, 'Успешно минал тест');
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
        requireRole('euro');
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
                $copyUrl['type'] = $listUrl['type'] = 'stores';
            }

            $blockTpl->replace(ht::createLink('Оригинал', $listUrl, false, array('target' => '_blank')), 'tableLink');
            $blockTpl->replace(ht::createLink('Копие', $copyUrl, false, array('target' => '_blank')), 'copyLink');
            $blockTpl->removeBlocksAndPlaces();
            $tpl->append($blockTpl, 'MIGRATIONS');
        }

        $conf = core_Packs::getConfig('eurozone');

        $data = $conf->_data;
        if($data['EUROZONE_MIGRATE_SYSTEM'] == 'yes'){
            $tpl->append("<h2>Системата е мигрирана</h2>", 'RESULT');

            $const = array('EUROZONE_MIGRATE_PRICE_LISTS' => 'Ценови политики',
                           'EUROZONE_MIGRATE_DELTAS' => 'Делти',
                           'EUROZONE_MIGRATE_PURCHASES' => 'Доставки',
                           'EUROZONE_MIGRATE_COSTS' => 'Кеширани продуктови цени',
                           'EUROZONE_MIGRATE_STORE_PRICES' => 'Складови цени',
                           'EUROZONE_MIGRATE_HR' => 'Позиции във фирмата',
                           'EUROZONE_MIGRATE_ACCOUNTS' => 'Банкови сметки',
                           'EUROZONE_MIGRATE_FINDEALS' => 'Финансови сделки',
                           'EUROZONE_MIGRATE_ADVANCE_FINDEALS' => 'Служебни аванси');

            $tpl->append("<li style='color:green;'>Сч. периоди са мигрирани</li>", 'RESULT');
            $tpl->append("<li style='color:green;'>ЕШОП настройки са мигрирани</li>", 'RESULT');
            foreach ($const as $constName => $constValue){
                if($data[$constName] == 'yes'){
                    $tpl->append("<li style='color:green;'>{$constValue} са мигрирани</li>", 'RESULT');
                } else {
                    $tpl->append("<li style='color:red;'>{$constValue} НЕ са мигрирани</li>", 'RESULT');
                }
            }
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
        $query->where("#currencyId = 'BGN' AND #state NOT IN ('closed', 'rejected')");

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

            if($rec->state == 'active'){
                acc_Journal::reconto($rec->containerId);
                $itemRec = acc_Items::fetchItem($Deals, $rec->id);
                $Deals->invoke('AfterJournalItemAffect', array($rec, $itemRec));
            }
        }
    }


    /**
     * Добавя безналичен метод за плащане - ЛЕВА
     */
    public static function addBgnPayment()
    {
        $rec = cond_Payments::fetch(array("#title = '[#1#]'", self::BGN_NON_CASH_PAYMENT_NAME));
        if(!is_object($rec)){
            $rec = (object)array('title' => self::BGN_NON_CASH_PAYMENT_NAME, 'change' => 'yes', 'currencyCode' => 'BGN', 'synonym' => 'bgn');

            core_Users::forceSystemUser();
            cond_Payments::save($rec);
            core_Users::cancelSystemUser();
        }
    }


    /**
     * Миграция на твърдите надценки за обновяване на себестойностите
     */
    public static function updatePriceUpdates()
    {
        $Updates = cls::get('price_Updates');
        $costAddAmountCol = str::phpToMysqlName('costAddAmount');

        $tbl = $Updates->dbTableName;
        $query = "UPDATE `{$tbl}` SET `{$costAddAmountCol}`  = (`{$costAddAmountCol}`  / 1.95583) WHERE `{$costAddAmountCol}` IS NOT NULL";
        $Updates->db->query($query);
    }


    /**
     * Конвертиране на левовите сметки в еврови
     */
    public static function convertBgnAccounts2Euro()
    {
        $bQuery = bank_OwnAccounts::getQuery();
        $bQuery->EXT('currencyId', 'bank_Accounts', "externalName=currencyId,externalKey=bankAccountId");
        $bQuery->where("#state NOT IN ('closed', 'rejected')");
        $bgnCurrencyId = currency_Currencies::getIdByCode('BGN');
        $bQuery->where("#currencyId = '{$bgnCurrencyId}'");
        while($bRec = $bQuery->fetch()){
            if($exChangeRec = bank_OwnAccounts::createBgnExchangeDocument($bRec)){
                try{
                    cls::get('bank_ExchangeDocument')->conto($exChangeRec);
                } catch(acc_journal_RejectRedirect $e){
                    wp('Проблем при мигриране на салдо', $e, $bRec, $exChangeRec);
                }
            }
        }
    }


    /**
     * Обновяване на сумите на позициите в моята фирма
     * @return void
     */
    public static function updateHr()
    {
        $Positions = cls::get('hr_Positions');
        $Positions->setupMvc();

        $query = $Positions->getQuery();
        $query->where("#salaryBase IS NOT NULL || #compensations IS NOT NULL");
        while($rec = $query->fetch()) {
            if(isset($rec->salaryBase)) {
                $rec->salaryBase = round($rec->salaryBase / 1.95583, 2);
            }

            if(isset($rec->compensations)) {
                $rec->compensations = round($rec->compensations / 1.95583, 2);
            }

            $Positions->save($rec, 'salaryBase,compensations');
        }
    }


    /**
     * Изчистване на константите
     */
    function act_ClearConf()
    {
        requireRole('debug');
        $conf = core_Packs::getConfig('eurozone');

        foreach ($conf->_data as $const => $val) {
            if ($const != 'EUROZONE_SET_MIGRATIONS') {
                core_Packs::setConfig('eurozone', array($const => 'no'));
            }
        }
    }


    /**
     * Нагласяне за мигриране на системата към еврозоната
     */
    function cron_migrateToEuro()
    {
        // Ако системата не е подготвена - нищо не се прави
        $isSet = eurozone_Setup::get('SET_MIGRATIONS');
        if($isSet != 'yes') {

            return 'Системата още не е нагласена';
        }

        // Ако системата вече е мигрирана да не се прави нищо
        $isMigrated = eurozone_Setup::get('MIGRATE_SYSTEM');
        if($isMigrated == 'yes') {

            return 'Системата е вече мигрирана';
        }

        // Ако сме преди еврозоната също
        if(dt::today() < acc_Setup::getEurozoneDate()) {

            return 'Не е настъпила датата на еврозоната';
        }

        // Заключване на системата
        core_SystemLock::block('Migration For Eurozone...', 55);
        core_App::setTimeLimit(800);

        // Създаване на копие на чувствителните таблици
        $html = '';
        foreach (self::$backupClasses as $class){
            $Class = cls::get($class);
            $Class->copyTable();
            $html .= "<li>Създаване на копие на: {$Class->className}</li>";
        }
        $errors = array();

        // Мигриране на сч. периоди
        try {
            self::updateCreatedPeriods();
            $html .= "<li>Мигриране на СЧ. Периоди успешно</li>";
        } catch(Exception $e){
            wp($e);
            $errors[] = "при периодите:" . $e->getMessage();
            $html .= "<li>Мигриране на СЧ. Периоди ГРЕШКА: {$e->getMessage()}</li>";
        }

        // Добавяне на безналично плащане БГН
        try {
            self::addBgnPayment();
            $html .= "<li>Мигриране на безналично плащане лева Успешно</li>";
        } catch(Exception $e){
            wp($e);
            $errors[] = "при безн. плащане:" . $e->getMessage();
            $html .= "<li>Мигриране на безналично плащане лева ГРЕШКА {$e->getMessage()}</li>";
        }

        // Миграция на финансовите сделки
        if(eurozone_Setup::get('MIGRATE_FINDEALS') != 'yes'){
            try {
                self::updateFinDeals('findeals_Deals');
                core_Packs::setConfig('eurozone', array('EUROZONE_MIGRATE_FINDEALS' => 'yes'));
                $html .= "<li>Мигриране на финансови сделки Успешно</li>";
            } catch(Exception $e){
                wp($e);
                $errors[] = "при ФС:" . $e->getMessage();
                $html .= "<li>Мигриране на ФС ГРЕШКА {$e->getMessage()}</li>";
            }
        }

        // Миграция на служебните аванси
        if(eurozone_Setup::get('MIGRATE_ADVANCE_FINDEALS') != 'yes'){
            try {
                self::updateFinDeals('findeals_AdvanceDeals');
                core_Packs::setConfig('eurozone', array('EUROZONE_MIGRATE_ADVANCE_FINDEALS' => 'yes'));
                $html .= "<li>Мигриране на СА Успешно</li>";
            } catch(Exception $e){
                wp($e);
                $errors[] = "при СА:" . $e->getMessage();
                $html .= "<li>Мигриране на СА ГРЕШКА {$e->getMessage()}</li>";
            }
        }

        // Мигриране на ЦП, ако не са мигрирани вече
        if(eurozone_Setup::get('MIGRATE_PRICE_LISTS') != 'yes'){
            try {
                self::updatePriceLists();
                core_Packs::setConfig('eurozone', array('EUROZONE_MIGRATE_PRICE_LISTS' => 'yes'));
                $html .= "<li>Мигриране на ЦП Успешно</li>";
            } catch(Exception $e){
                wp($e);
                $errors[] = "при ЦП:" . $e->getMessage();
                $html .= "<li>Мигриране на ЦП ГРЕШКА {$e->getMessage()}</li>";
            }
        } else {
            $html .= "<li>ЦП са вече мигрирани</li>";
        }

        // Мигриране на Делтите, ако не са мигрирани вече
        if(eurozone_Setup::get('MIGRATE_DELTAS') != 'yes'){
            try {
                self::updateDeltas();
                $html .= "<li>Мигриране на Делти Успешно</li>";
                core_Packs::setConfig('eurozone', array('EUROZONE_MIGRATE_DELTAS' => 'yes'));
            } catch(Exception $e){
                wp($e);
                $html .= "<li>Мигриране на Делти ГРЕШКА {$e->getMessage()}</li>";
                $errors[] = "при делти:" . $e->getMessage();
            }
        } else {
            $html .= "<li>Делтите са вече мигрирани</li>";
        }

        // Мигриране на Покупките, ако не са мигрирани вече
        if(eurozone_Setup::get('MIGRATE_PURCHASES') != 'yes'){
            try {
                self::updatePurchases();
                core_Packs::setConfig('eurozone', array('EUROZONE_MIGRATE_PURCHASES' => 'yes'));
                $html .= "<li>Мигриране на покупки Успешно</li>";
            } catch(Exception $e){
                wp($e);
                $errors[] = "при покупки:" . $e->getMessage();
                $html .= "<li>Мигриране на покупки ГРЕШКА {$e->getMessage()}</li>";
            }
        } else {
            $html .= "<li>Покупки са вече мигрирани</li>";
        }

        // Мигриране на онлайн магазина, ако не е
        try {
            self::updateEshopSettings();
            $html .= "<li>Мигриране на ешоп Успешно";
        } catch(Exception $e){
            wp($e);
            $errors[] = "при ешопа:" . $e->getMessage();
            $html .= "<li>Мигриране на ешоп ГРЕШКА {$e->getMessage()}</li>";
        }

        // Мигриране на кеш цените, ако не е
        if(eurozone_Setup::get('MIGRATE_COSTS') != 'yes'){
            try {
                self::updatePriceCosts();
                core_Packs::setConfig('eurozone', array('EUROZONE_MIGRATE_COSTS' => 'yes'));
                $html .= "<li>Мигриране на кеш. цени Успешно</li>";
            } catch(Exception $e){
                wp($e);
                $errors[] = "при кеш. цени:" . $e->getMessage();
                $html .= "<li>Мигриране на кеш. цени ГРЕШКА {$e->getMessage()}</li>";
            }
        } else {
            $html .= "<li>Кеш цените са вече мигрирани</li>";
        }

        // Мигриране на складовите цени, ако не е
        if(eurozone_Setup::get('MIGRATE_STORE_PRICES') != 'yes'){
            try {
                self::updatePricesByDate();
                core_Packs::setConfig('eurozone', array('EUROZONE_MIGRATE_STORE_PRICES' => 'yes'));
                $html .= "<li>Мигриране на складови цени Успешно</li>";
            } catch(Exception $e){
                wp($e);
                $errors[] = "при складови цени:" . $e->getMessage();
                $html .= "<li>Мигриране на складови цени ГРЕШКА {$e->getMessage()}</li>";
            }
        } else {
            $html .= "<li>Складови цени са вече мигрирани</li>";
        }

        // Мигриране на HR, ако не е
        if(eurozone_Setup::get('MIGRATE_HR') != 'yes'){
            try {
                self::updateHr();
                core_Packs::setConfig('eurozone', array('EUROZONE_MIGRATE_HR' => 'yes'));
                $html .= "<li>Мигриране на HR Успешно</li>";
            } catch(Exception $e){
                wp($e);
                $errors[] = "при HR:" . $e->getMessage();
                $html .= "<li>Мигриране на HR ГРЕШКА {$e->getMessage()}</li>";
            }
        } else {
            $html .= "<li>HR са вече мигрирани</li>";
        }

        // Мигриране на сметките, ако не е
        if(eurozone_Setup::get('MIGRATE_ACCOUNTS') != 'yes'){
            try {
                self::convertBgnAccounts2Euro();
                core_Packs::setConfig('eurozone', array('EUROZONE_MIGRATE_ACCOUNTS' => 'yes'));
                $html .= "<li>Мигриране на б. сметки Успешно</li>";
            } catch(Exception $e){
                wp($e);
                $errors[] = "при б. сметки:" . $e->getMessage();
                $html .= "<li>Мигриране на б. сметки {$e->getMessage()}</li>";
            }
        } else {
            $html .= "<li>Б. сметки са вече мигрирани</li>";
        }

        if(countR($errors)){
            wp("ЕВРО миграция", $errors);
            foreach ($errors as $error) {
                log_System::add('eurozone_Migrations', "ЕВРО мигр.: {$error}", null, 'err');
            }

            $admins = core_Users::getByRole('admin');
            foreach ($admins as $adminId) {
                bgerp_Notifications::add('Имаше проблем при преминаване към Евро', array('log_System', 'list', 'type' => 'err', 'search' => 'ЕВРО мигр'), $adminId, 'critical');
            }

            return $html;
        }

        // Ако не е имало грешки изтрива се крон процеса
        core_Cron::delete("#systemId = 'MigrateToEuro'");

        // Админите се нотифицират
        $admins = core_Users::getByRole('admin');
        foreach ($admins as $adminId) {
            bgerp_Notifications::add('Системата е мигрирана към евро успешно', array(), $adminId, 'critical');
        }

        // Записва се, че системата е вече мигрирана
        core_Packs::setConfig('eurozone', array('EUROZONE_MIGRATE_SYSTEM' => 'yes'));

        $html .= "<li>Системата е мигрирана успешно</li>";

        return $html;
    }
}
