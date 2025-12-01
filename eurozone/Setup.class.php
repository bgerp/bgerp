<?php


/**
 * Мигриране на системата за еврозоната на
 */
defIfNot('EUROZONE_SET_MIGRATIONS', 'no');


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
        'EUROZONE_SET_MIGRATIONS' => array('enum(no=Не е настроено,yes=Настроено е)', 'caption=Мигриране на системата за еврозоната на|* 01.01.2026->Моля свържете се с наш представител да зададе обновяването->Избор,callOnChange=eurozone_Setup::setCallOnTimeMigrations'),
    );


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
            'roles' => 'debug',
        ),
    );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

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
        if (!haveRole('debug')) {
            $configForm->setReadOnly('EUROZONE_SET_MIGRATIONS');
        }
    }


    /**
     * Изпълнява се при промяна на стойноста на миграцията
     *
     * @param $Type
     * @param $oldValue
     * @param $newValue
     * @return void
     */
    public static function setCallOnTimeMigrations($Type, $oldValue, $newValue)
    {
        if($newValue == 'yes'){
            core_CallOnTime::setCall('eurozone_Migrations', 'updatePeriods', null, '2025-12-31 23:00:00');
            core_CallOnTime::setCall('eurozone_Migrations', 'migrateAll', null, '2026-01-01 00:00:00');
        }
    }
}
