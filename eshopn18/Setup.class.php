<?php


/**
 * Инсталиране/Деинсталиране на пакета за онлайн магазина по Наредба Н-18 - картови плащания без касов бон
 *
 *
 * @category  bgerp
 * @package   eshopn18
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eshopn18_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';


    /**
     * Необходими пакети
     */
    public $depends = 'eshop=0.1';


    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'eshopn18_SaleRegDocs';


    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';


    /**
     * Описание на модула
     */
    public $info = 'Онлайн магазин по Наредба Н-18 - документ по чл. 52о при плащане с карта и месечен одиторски файл';


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'eshopn18_SaleRegDocs',
    );


    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'eshopn18_reports_N18Audit,eshopn18_driver_TestCardPayment';


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        // Плъгините бяха в bgfisc - старите записи се махат, за да се закачат новите със същите имена
        $Plugins = cls::get('core_Plugins');
        foreach (array('bgfisc_plg_EshopSettings', 'bgfisc_plg_EshopCarts', 'bgfisc_plg_CardPayments') as $oldPlugin) {
            $Plugins->deinstallPlugin($oldPlugin);
        }

        $html .= $Plugins->installPlugin('Настройки на онлайн магазина по Н-18', 'eshopn18_plg_EshopSettings', 'eshop_Settings', 'private');
        $html .= $Plugins->installPlugin('Документ по чл. 52о за платените с карта онлайн поръчки', 'eshopn18_plg_EshopCarts', 'eshop_Carts', 'private');
        $html .= $Plugins->installPlugin('Документ по чл. 52о на страницата за успешно плащане с карта', 'eshopn18_plg_CardPayments', 'cond_CardPayments', 'private');

        // Полетата от плъгините се добавят в таблиците на настройките и количките
        $html .= cls::get('eshop_Settings')->setupMvc();
        $html .= cls::get('eshop_Carts')->setupMvc();

        return $html;
    }


    /**
     * Пакетът не се де-инсталира, докато в онлайн магазина има методи за плащане с карта
     */
    public function canDeinstall()
    {
        if (core_Packs::isInstalled('eshop')) {
            $query = eshop_Settings::getQuery();
            $query->where("#payments IS NOT NULL AND #payments != ''");
            $query->show('payments');
            while ($rec = $query->fetch()) {
                if (countR(eshop_Settings::getCardPaymentMethods(keylist::toArray($rec->payments)))) {

                    return false;
                }
            }
        }

        return parent::canDeinstall();
    }


    /**
     * Защо пакетът не може да се де-инсталира
     */
    public function getCannotDeinstallHint()
    {
        return 'Пакетът не може да бъде де-инсталиран, докато в настройките на онлайн магазина има избрани методи за плащане с карта.';
    }
}
