<?php



/**
 * Дефолтно общо условие за продажба
 */
defIfNot('TRANSSRV_SALE_DEFAULT_CONDITION', '');


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
	 * Домейн на трансбид
	 */
	const TRANS_BID_DOMAIN = 'http://trans.bid/';
	
	
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'transsrv_TransportModes';
    
    
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
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'transsrv_TransportModes',
            'transsrv_TransportUnits',
        );
    

    /**
     * Роли за достъп до модула
     */
    public $roles = 'transsrv';
    

    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
    		'TRANSSRV_SALE_DEFAULT_CONDITION' => array("text", 'caption=Общо условие за продажба по подразбиране->Условие'),
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
