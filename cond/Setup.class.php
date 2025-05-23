<?php


/**
 *  Начин за плащане с карта
 */
defIfNot('COND_CARD_PAYMENT_METHOD_ID', '');


/**
 * class cond_Setup
 *
 * Инсталиране/Деинсталиране на
 * админ. мениджъри с общо предназначение
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'cond_DeliveryTerms';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'crm=0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Дефиниции на фирмата (пасажи, диапазони, плащания и др.)';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'cond_Texts',
        'cond_Groups',
        'cond_PaymentMethods',
        'cond_DeliveryTerms',
        'cond_Parameters',
        'cond_ConditionsToCustomers',
        'cond_Payments',
        'cond_Countries',
        'cond_TaxAndFees',
        'cond_Colors',
        'cond_Ranges',
        'cond_Allergens',
        'cond_TariffCodes',
        'cond_VatExceptions'
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.9, 'Система', 'Дефиниции', 'cond_DeliveryTerms', 'default', 'ceo, admin'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'cond_type_Double,cond_type_Text,cond_type_Varchar,cond_type_Time,cond_type_Date,cond_type_Component,cond_type_Enum,cond_type_Set,cond_type_Percent,cond_type_Int,cond_type_Delivery,cond_type_PaymentMethod,cond_type_Image,cond_type_File,cond_type_Store,cond_type_PriceList,cond_type_PurchaseListings,cond_type_SaleListings,cond_type_Url,cond_type_YesOrNo,cond_type_Color, cond_type_Egn, cond_type_Email, cond_type_Keylist, cond_type_Files, cond_type_Html, cond_type_Product, cond_type_Formula, cond_type_Key,cond_type_DocTemplate,cond_iface_AllergensTemplateRendered';


    /**
     * Описание на конфигурационните константи за този модул
     */
    public $configDescription = array(
        'COND_CARD_PAYMENT_METHOD_ID' => array('key(mvc=cond_Payments,select=title)', 'caption=Метод за плащане с карта->Избор'),
    );


    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'CloseExceptions',
            'description' => 'Затваряне на невалидни изключения',
            'controller' => 'cond_VatExceptions',
            'action' => 'CloseExceptions',
            'period' => 1440,
            'offset' => 60,
        ),
    );


    /**
     * Инсталиране на пакета
     *
     * @TODO Да се премахне след като кода се разнесе до всички бранчове
     * и старата роля 'salecond' бъде изтрита
     */
    public function install()
    {
        $html = parent::install();
        
        $Plugins = cls::get('core_Plugins');
        
        // Замества handle' ите на документите с линк към документа
        $html .= $Plugins->installPlugin('Плъгин за пасажи в RichEdit', 'cond_RichTextPlg', 'type_Richtext', 'private');
        
        // Кофа за файлове от тип параметър
        $Bucket = cls::get('fileman_Buckets');
        $Bucket->createBucket('paramFiles', 'Прикачени файлови параметри', null, '1GB', 'user', 'user');

        return $html;
    }


    /**
     * Зареждане на данните
     */
    public function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);
        $config = core_Packs::getConfig('cond');
        if (strlen($config->COND_CARD_PAYMENT_METHOD_ID) === 0) {
            $cardPaymentId = cond_Payments::fetchField("#code = 7 AND #state = 'active'");
            core_Packs::setConfig('cond', array('COND_CARD_PAYMENT_METHOD_ID' => $cardPaymentId));
            $res .= "<li style='color:green'>Задаване на начин за плащане с карта</li>";
        }

        return $res;
    }
}
