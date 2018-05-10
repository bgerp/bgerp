<?php


/**
 * Дефолтно общо условие за продажба
 */
defIfNot('TRANSSRV_SALE_DEFAULT_CONDITION', '');


/**
 * 
 */
defIfNot('TRANSSRV_BID_DOMAIN', '//trans.bid');


/**
 * Дали за транспортни услуги могат да се правят запитвания от партньори
 */
defIfNot('TRANSSRV_AVIABLE_FOR_PARTNERS', 'no');


/**
 * Клас 'transsrv_Setup' 
 *
 *
 * @category  bgerp
 * @package   transsrv
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   Property
 * @since     v 0.1
 */
class transsrv_Setup extends core_ProtoSetup
{
	
	
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = "Интеграция с trans.bid";
    

    /**
     * Роли за достъп до модула
     */
    public $roles = 'transsrv';
    

    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
    		'TRANSSRV_SALE_DEFAULT_CONDITION' => array("text", 'caption=Общо условие за продажба по подразбиране->Условие'),
    		'TRANSSRV_BID_DOMAIN' => array("varchar", 'caption=Отдалечена системно за създаване на търг->URL'),
            'TRANSSRV_AVIABLE_FOR_PARTNERS' => array("enum(no=Не,yes=Да)", 'caption=Допускат ли се запитвания от партньори за тези услуги->Избор'),

    );

    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = "transsrv_ProductDrv";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    
    	$Plugins = cls::get('core_Plugins');
    	$html .= $Plugins->installPlugin('Създаване на търгове от ЕН', 'transsrv_plg_CreateAuction', 'store_ShipmentOrders', 'private');
    	$html .= $Plugins->installPlugin('Създаване на търгове от СР', 'transsrv_plg_CreateAuction', 'store_Receipts', 'private');
    	$html .= $Plugins->installPlugin('Създаване на търгове от МС', 'transsrv_plg_CreateAuction', 'store_Transfers', 'private');
    
    	return $html;
    }
}
