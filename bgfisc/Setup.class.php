<?php


/**
 * Дефолтно устройство за касов апарат (1)
 */
defIfNot('BGFISC_DEFAULT_FISC_DEVICE_1', '');


/**
 * Дефолтно устройство за касов апарат (1)
 */
defIfNot('BGFISC_DEFAULT_FISC_DEVICE_2', '');


/**
 * До колкото числа след запетаята да се показва цената в коментара на ФУ
 */
defIfNot('BGFISC_PRICE_FU_ROUND', '2');


/**
 * Да се проверява ли серийния номер на ФУ, дали отговаря на този на бележката, която ще се печата
 */
defIfNot('BGFISC_CHECK_SERIAL_NUMBER', 'no');


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с печатане на касови бележки
 *
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgfisc_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'peripheral=0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'bgfisc_Register';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Фискализиране на търговски документи';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array('bgfisc_Register',
        'bgfisc_PrintedReceipts',
        'migrate::deletePlugins2449',
        'migrate::updateWebConstants2349',
    );
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'delete_not_finished_receipts',
            'description' => 'Изтриване на незавършените бележки',
            'controller' => 'bgfisc_PrintedReceipts',
            'action' => 'DeleteUnfinishedReceipts',
            'period' => 1,
            'timeLimit' => 1
        )
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'bgfisc_reports_SalesPayments,bgfisc_reports_AggregateSalesData,bgfisc_reports_DetailedSalesData,bgfisc_reports_ReversedSales,
                          bgfisc_reports_CanceledSales,bgfisc_reports_SummaryPurchasesData,bgfisc_reports_DetailedPurchasesData,bgfisc_reports_MovementOfGoodsForAPeriod';

    /**
     * Описание на конфигурационните константи за този модул
     */
    public $configDescription = array(
        'BGFISC_DEFAULT_FISC_DEVICE_1' => array('varchar', 'caption=Фискално устройство по подразбиране->Първо,optionsFunc=bgfisc_Setup::getFiscDeviceOptins'),
        'BGFISC_DEFAULT_FISC_DEVICE_2' => array('varchar', 'caption=Фискално устройство по подразбиране->Второ,optionsFunc=bgfisc_Setup::getFiscDeviceOptins'),
        'BGFISC_PRICE_FU_ROUND' => array('int', 'caption=Разпечатване на фискален бон от ФУ->Закръгляне (Цена)'),
        'BGFISC_CHECK_SERIAL_NUMBER' => array('enum(yes=Включено,no=Изключено)', 'caption=Разпечатване на фискален бон от ФУ->Проверка на сер. номер'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = '';
        if (core_Packs::isInstalled('n18')) {
            $html .= cls::get('core_Packs')->deinstall('n18');
        }
        $html .= parent::install();
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Добавена функционалност от bgfisc към бележките', 'bgfisc_plg_Receipts', 'pos_Receipts', 'private');
        $html .= $Plugins->installPlugin('Добавена функционалност от bgfisc към продажбите', 'bgfisc_plg_Sales', 'sales_Sales', 'private');
        $html .= $Plugins->installPlugin('Добавена на връзка на касите към ФУ', 'bgfisc_plg_CashRegister', 'cash_Cases', 'private');
        $html .= $Plugins->installPlugin('Добавена на връзка на касовите документи към ФУ', 'bgfisc_plg_CashDocument', 'cash_Document', 'family');
        
        $html .= $Plugins->installPlugin('Печат на фискален бон от касовите документи', 'bgfisc_plg_PrintFiscReceipt', 'cash_Document', 'family');
        $html .= $Plugins->installPlugin('Печат на фискален бон от РКО', 'bgfisc_plg_Rko', 'cash_Rko', 'private');
        $html .= $Plugins->installPlugin('Печат на фискален бон от продажбите', 'bgfisc_plg_PrintFiscReceipt', 'sales_Sales', 'private');

        $html .= $Plugins->installPlugin('Добавена функционалност от bgfisc към бележките към фактурите', 'bgfisc_plg_SaleDocument', 'sales_Invoices', 'private');
        $html .= $Plugins->installPlugin('Добавена функционалност от bgfisc към бележките към ЕН', 'bgfisc_plg_SaleDocument', 'store_ShipmentOrders', 'private');
        $html .= $Plugins->installPlugin('Добавена функционалност от bgfisc към бележките към СР', 'bgfisc_plg_SaleDocument', 'store_Receipts', 'private');
        $html .= $Plugins->installPlugin('Добавена функционалност от bgfisc към протокола за предаване на услуга', 'bgfisc_plg_SaleDocument', 'sales_Services', 'private');
        $html .= $Plugins->installPlugin('Добавена функционалност от bgfisc към протокола за приемане на услуга', 'bgfisc_plg_SaleDocument', 'purchase_Services', 'private');

        if(!bgfisc_Register::count()){
            pos_Receipts::delete("#state = 'draft'");
        }

        // Сетъпване на модели в които са се добавили нови полета
        cls::get('cash_Cases')->setupMvc();
        cls::get('pos_Receipts')->setupMvc();
        cls::get('cash_Pko')->setupMvc();
        cls::get('cash_Rko')->setupMvc();

        return $html;
    }


    /**
     * Миграция на уеб константите
     */
    public function updateWebConstants2349()
    {
        if(core_Packs::fetch("#name = 'n18'")){
            if(cls::load('n18_Setup', true)){
                foreach (array('DEFAULT_FISC_DEVICE_1', 'DEFAULT_FISC_DEVICE_2', 'PRICE_FU_ROUND') as $name){
                    $val = n18_Setup::get($name);
                    if(!empty($val)){
                        core_Packs::setConfig('bgfisc', array("BGFISC_{$name}" => $val));
                    }
                }
            }
        }
    }


    /**
     * Изтриване на стар плъгин
     */
    public function deletePlugins2449()
    {
        $Plugins = cls::get('core_Plugins');
        $Plugins->deinstallPlugin('bgfisc_plg_TitlePlg', 'core_ObjectConfiguration');
        $Plugins->deinstallPlugin('bgfisc_plg_Version', 'help_Info');
    }


    /**
     * Връща наличните за избор фискални устройства
     *
     * @return array $cashRegOptions;
     */
    public static function getFiscDeviceOptins()
    {
        $options = array();
        $cashRegOptions = peripheral_Devices::getDevicesArrByField('peripheral_FiscPrinterIntf', 'serialNumber', false);
        foreach ($cashRegOptions as $serial => $name) {
            $options[$serial] = "{$name} ( {$serial} )";
        }
        
        return array('' => '') + $options;
    }
}
