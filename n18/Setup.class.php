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
 * class n18_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с Наредба 18
 *
 *
 * @category  bgplus
 * @package   n18
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class n18_Setup extends core_ProtoSetup
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
    public $startCtr = 'n18_Register';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Еднопосочно конвертиране на системата към bgERP-N18, за изпълнение изискванията на Наредба 18';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array('n18_Register',
        'n18_PrintedReceipts',
        'migrate::removeOldPlugins',
        'migrate::fixNapUsers'
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(array('napodit', 'cashMaster, posMaster', 'external'), array('n18'));
    
    
    /**
     * Дали пакета е системен
     */
//     public $isSystem = true;
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'delete_not_finished_receipts',
            'description' => 'Изтриване на незавършените бележки',
            'controller' => 'n18_PrintedReceipts',
            'action' => 'DeleteUnfinishedReceipts',
            'period' => 1,
            'timeLimit' => 1
        )
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'n18_reports_SalesPayments,n18_reports_AggregateSalesData,n18_reports_DetailedSalesData,n18_reports_ReversedSales,
                          n18_reports_CanceledSales,n18_reports_SummaryPurchasesData,n18_reports_DetailedPurchasesData,n18_reports_MovementOfGoodsForAPeriod';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.9, 'Търговия', 'Регистър УНП', 'n18_Register', 'default', 'sales,napodit,ceo')
    );
    
    
    /**
     * Описание на конфигурационните константи за този модул
     */
    public $configDescription = array(
        'N18_DEFAULT_FISC_DEVICE_1' => array('varchar', 'caption=Фискално устройство по подразбиране->Първо,optionsFunc=n18_Setup::getFiscDeviceOptins'),
        'N18_DEFAULT_FISC_DEVICE_2' => array('varchar', 'caption=Фискално устройство по подразбиране->Второ,optionsFunc=n18_Setup::getFiscDeviceOptins'),
        'N18_PRICE_FU_ROUND' => array('int', 'caption=Разпечатване на фискален бон от ФУ->Закръгляне (Цена)'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Добавена функционалност от наредба 18 към бележките', 'n18_plg_Receipts', 'pos_Receipts', 'private');
        $html .= $Plugins->installPlugin('Добавена функционалност от наредба 18 към продажбите', 'n18_plg_Sales', 'sales_Sales', 'private');
        $html .= $Plugins->installPlugin('Добавена на връзка на касите към ФУ', 'n18_plg_CashRegister', 'cash_Cases', 'private');
        $html .= $Plugins->installPlugin('Добавена на връзка на касовите документи към ФУ', 'n18_plg_CashDocument', 'cash_Document', 'family');
        
        $html .= $Plugins->installPlugin('Печат на фискален бон от касовите документи', 'n18_plg_PrintFiscReceipt', 'cash_Document', 'family');
        $html .= $Plugins->installPlugin('Печат на фискален бон от РКО', 'n18_plg_Rko', 'cash_Rko', 'private');
        $html .= $Plugins->installPlugin('Печат на фискален бон от продажбите', 'n18_plg_PrintFiscReceipt', 'sales_Sales', 'private');
        $html .= $Plugins->installPlugin('Добавена на версия към системата', 'n18_plg_Version', 'help_Info', 'private');
        $html .= $Plugins->installPlugin('Премахване на правата за добавяне и редакция на napodit ролята', 'n18_plg_NapOdit', 'core_Manager', 'family');

        $html .= $Plugins->installPlugin('Име на системата', 'n18_plg_TitlePlg', 'core_ObjectConfiguration', 'private');
        
        $html .= $Plugins->installPlugin('Добавена функционалност от наредба 18 към бележките към фактурите', 'n18_plg_SaleDocument', 'sales_Invoices', 'private');
        $html .= $Plugins->installPlugin('Добавена функционалност от наредба 18 към бележките към ЕН', 'n18_plg_SaleDocument', 'store_ShipmentOrders', 'private');
        $html .= $Plugins->installPlugin('Добавена функционалност от наредба 18 към бележките към СР', 'n18_plg_SaleDocument', 'store_Receipts', 'private');
        $html .= $Plugins->installPlugin('Добавена функционалност от наредба 18 към протокола за предаване на услуга', 'n18_plg_SaleDocument', 'sales_Services', 'private');
        $html .= $Plugins->installPlugin('Добавена функционалност от наредба 18 към протокола за приемане на услуга', 'n18_plg_SaleDocument', 'purchase_Services', 'private');
        
        $nick = 'N18';
        $napUser = core_Users::fetch(array("LOWER(#nick) = '[#1#]'", mb_strtolower($nick)));
        if (!$napUser) {
            $rolesArr = array(core_Roles::fetchByName('napodit'), core_Roles::fetchByName('ceo'), core_Roles::fetchByName('admin'), core_Roles::fetchByName(doc_Setup::get('BGERP_ROLE_HEADQUARTER', true)));
            
            $napUser = new stdClass();
            $napUser->nick = $nick;
            $napUser->names = 'НАП Одиторски Профил';
            $napUser->rolesInput = type_Keylist::fromArray(arr::make($rolesArr, true));
            $napUser->ps5enc = core_Users::encodePwd(str::getRand(), $nick);
            $napUser->state = 'closed';
            
            core_Users::save($napUser);
            
            $html .= "<li class=\"green\">Добавен потребител: {$nick}</li>";
        } else {
            $html .= "<li style=\"color: #660000;\">Съществуващ потребител: {$nick}</li>";
        }
        
        if(!n18_Register::count()){
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
        $Plugins->deinstallPlugin('n18_plg_CashRegister', 'bank_OwnAccounts');
    }
    
    
    /**
     * Миграция за изчистване на грешно добавените записи
     */
    public function fixNapUsers()
    {
        $pQuery = crm_Persons::getQuery();
        $pQuery->where("#name = 'НАП Одиторски Профил'");
        while ($pRec = $pQuery->fetch()) {
            $inst = cls::get('crm_Profiles');
            foreach ($inst->fields as $fName => $fKey) {
                if ($fKey->kind != 'FLD') {
                    unset($inst->fields[$fName]);
                }
            }
            
            $prRec = crm_Profiles::fetch(array("#personId = '[#1#]'", $pRec->id));
            
            if (!$prRec->userId || !core_Users::fetch($prRec->userId)) {
                if ($pRec->id) {
                    crm_Persons::delete($pRec->id);
                }
                if ($prRec->id) {
                    crm_Profiles::delete($prRec->id);
                }
            }
        }
    }
}
