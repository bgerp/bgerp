<?php


/**
 * Дефолтно устройство за касов апарат (1)
 */
defIfNot('N18_DEFAULT_FISC_DEVICE_1', '');


/**
 * Дефолтно устройство за касов апарат (1)
 */
defIfNot('N18_DEFAULT_FISC_DEVICE_2', '');


/**
 * До колкото числа след запетаята да се показва цената в коментара на ФУ
 */
defIfNot('N18_PRICE_FU_ROUND', '2');


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с печатане на касови бележки
 *
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
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
        'migrate::removeOldPlugins',
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
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.9, 'Търговия', 'Регистър УНП', 'bgfisc_Register', 'default', 'sales,ceo')
    );
    
    
    /**
     * Описание на конфигурационните константи за този модул
     */
    public $configDescription = array(
        'N18_DEFAULT_FISC_DEVICE_1' => array('varchar', 'caption=Фискално устройство по подразбиране->Първо,optionsFunc=bgfisc_Setup::getFiscDeviceOptins'),
        'N18_DEFAULT_FISC_DEVICE_2' => array('varchar', 'caption=Фискално устройство по подразбиране->Второ,optionsFunc=bgfisc_Setup::getFiscDeviceOptins'),
        'N18_PRICE_FU_ROUND' => array('int', 'caption=Разпечатване на фискален бон от ФУ->Закръгляне (Цена)'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Добавена функционалност от bgfisc към бележките', 'bgfisc_plg_Receipts', 'pos_Receipts', 'private');
        $html .= $Plugins->installPlugin('Добавена функционалност от bgfisc към продажбите', 'bgfisc_plg_Sales', 'sales_Sales', 'private');
        $html .= $Plugins->installPlugin('Добавена на връзка на касите към ФУ', 'bgfisc_plg_CashRegister', 'cash_Cases', 'private');
        $html .= $Plugins->installPlugin('Добавена на връзка на касовите документи към ФУ', 'bgfisc_plg_CashDocument', 'cash_Document', 'family');
        
        $html .= $Plugins->installPlugin('Печат на фискален бон от касовите документи', 'bgfisc_plg_PrintFiscReceipt', 'cash_Document', 'family');
        $html .= $Plugins->installPlugin('Печат на фискален бон от РКО', 'bgfisc_plg_Rko', 'cash_Rko', 'private');
        $html .= $Plugins->installPlugin('Печат на фискален бон от продажбите', 'bgfisc_plg_PrintFiscReceipt', 'sales_Sales', 'private');
        $html .= $Plugins->installPlugin('Добавена на версия към системата', 'bgfisc_plg_Version', 'help_Info', 'private');
        $html .= $Plugins->installPlugin('Име на системата', 'bgfisc_plg_TitlePlg', 'core_ObjectConfiguration', 'private');
        
        $html .= $Plugins->installPlugin('Добавена функционалност от bgfisc към бележките към фактурите', 'bgfisc_plg_SaleDocument', 'sales_Invoices', 'private');
        $html .= $Plugins->installPlugin('Добавена функционалност от bgfisc към бележките към ЕН', 'bgfisc_plg_SaleDocument', 'store_ShipmentOrders', 'private');
        $html .= $Plugins->installPlugin('Добавена функционалност от bgfisc към бележките към СР', 'bgfisc_plg_SaleDocument', 'store_Receipts', 'private');
        $html .= $Plugins->installPlugin('Добавена функционалност от bgfisc към протокола за предаване на услуга', 'bgfisc_plg_SaleDocument', 'sales_Services', 'private');
        $html .= $Plugins->installPlugin('Добавена функционалност от bgfisc към протокола за приемане на услуга', 'bgfisc_plg_SaleDocument', 'purchase_Services', 'private');

        if(!bgfisc_Register::count()){
            pos_Receipts::delete("#state = 'draft'");
        }
        
        return $html;
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
    
    
    /**
     * Изтриване на плъгин
     */
    public function removeOldPlugins()
    {
        $Plugins = cls::get('core_Plugins');
        $Plugins->deinstallPlugin('bgfisc_plg_CashRegister', 'bank_OwnAccounts');
    }


    /**
     * Проверка дали може ръчно да се инсталира пакета
     *
     * @return string|void
     */
    public function checkManualInstall()
    {
        if (defined('EF_PRIVATE_PATH')) {
            $privateRepos = array();
            $privatePath = explode(';', EF_PRIVATE_PATH);
            foreach ($privatePath as $path){
                $baseName = basename($path);
                $privateRepos[$baseName] = $baseName;
            }

            if(isset($privateRepos['bgplus'])){
                if(core_Packs::isInstalled('n18')){

                    return "Не може да се инсталира пакета, докато е инсталиран пакета \"n18\"";
                }
            }
        }
    }
}
