<?php


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
     * Описание на системните действия
     */
    public $systemActions = array(
        array(
            'title' => 'Тест миграция',
            'url' => array(
                'eurozone_Setup',
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
     * Описание на конфигурационните константи
     */
    public $configDescription = array();


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
}
