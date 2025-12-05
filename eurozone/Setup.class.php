<?php


/**
 * Мигриране на системата за еврозоната на
 */
defIfNot('EUROZONE_SET_MIGRATIONS', 'no');


/**
 * Мигрирана ли е системата към еврозоната
 */
defIfNot('EUROZONE_MIGRATE_SYSTEM', 'no');


/**
 * Мигрирани ли са ЦП
 */
defIfNot('EUROZONE_MIGRATE_PRICE_LISTS', 'no');


/**
 * Мигрирани ли са делтите
 */
defIfNot('EUROZONE_MIGRATE_DELTAS', 'no');


/**
 * Мигрирани ли са покупките
 */
defIfNot('EUROZONE_MIGRATE_PURCHASES', 'no');


/**
 * Мигриранили са кешираните цени
 */
defIfNot('EUROZONE_MIGRATE_COSTS', 'no');


/**
 * Мигрирани ли са складовите цени
 */
defIfNot('EUROZONE_MIGRATE_STORE_PRICES', 'no');


/**
 * Мигриран ли е HR пакета
 */
defIfNot('EUROZONE_MIGRATE_HR', 'no');


/**
 * Мигрирани ли са банковите сметки
 */
defIfNot('EUROZONE_MIGRATE_ACCOUNTS', 'no');


/**
 * class eurozone_Setup
 *
 * Инсталиране/Деинсталиране на
 * пакети свързани с преминаването към еврозоната
 *
 *
 * @category  bgerp
 * @package   eurozone
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eurozone_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';


    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';


    /**
     * Описание на модула
     */
    public $info = 'Еднократно преминаване на системата към еврозоната';


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'eurozone_PrimeCostByDocumentTest',
        'eurozone_PurchasesDataTest',
        'eurozone_ProductPricePerPeriodsTest',
        'eurozone_ProductCostsTest',
    );


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'EUROZONE_SET_MIGRATIONS' => array('enum(no=Не е настроено,yes=Настроено е)', 'caption=Мигриране на системата за еврозоната на|* 01.01.2026->Моля свържете се с наш представител да зададе обновяването->Избор'),
    );


    /**
     * Роли за достъп до модула
     */
    public $roles = 'euro';


    /**
     * Описание на системните действия
     */
    public $systemActions = array(
        array(
            'title' => 'Тест миграция',
            'url' => array(
                'eurozone_Migrations',
                'testMigrations',
                'ret_url' => true
            ),
            'params' => array(
                'title' => 'Тестове на миграции',
                'ef_icon' => 'img/16/arrow_refresh.png'
            ),
            'roles' => 'euro',
        ),
    );

    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        if(self::get('MIGRATE_SYSTEM') == 'no'){
            $rec = new stdClass();
            $rec->systemId = 'MigrateToEuro';
            $rec->description = 'Изчакване за мигриране към еврозоната';
            $rec->controller = 'eurozone_Migrations';
            $rec->action = 'migrateToEuro';
            $rec->period = 60;
            $rec->offset = 0;
            $rec->delay = 0;
            $rec->timeLimit = 300;
            $html .= core_Cron::addOnce($rec);
        } else {
            $html .= "Системата вече е обновена за еврозоната";
        }

        return $html;
    }


    /**
     * Връща ИД-то на безналичния метод за плащане - лева
     * @return mixed
     */
    public static function getBgnPaymentId()
    {
        $bgnPaymentName = eurozone_Migrations::BGN_NON_CASH_PAYMENT_NAME;
        $paymentId = cond_Payments::fetchField("#title = '{$bgnPaymentName}'");

        return $paymentId;
    }


    /**
     * Менижиране на формата формата за настройките
     *
     * @param core_Form $configForm
     * @return void
     */
    public function manageConfigDescriptionForm(&$configForm)
    {
        if (!haveRole('euro')) {
            $configForm->setReadOnly('EUROZONE_SET_MIGRATIONS');
        }
    }
}
