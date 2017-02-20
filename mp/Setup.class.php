<?php


/**
 * 
 * 
 * @category  bgerp
 * @package   mp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mp_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Тестване на bluetooth принтер";
        
    
    /**
     *
     */
    public $deprecated = TRUE;
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        //
        // Закачаме плъгина 
        //
        $html .= core_Plugins::installPlugin('Sales Print Mockup', 'mp_PrintMockupPlg', 'sales_Sales', 'private');
        $html .= core_Plugins::installPlugin('EN Print Mockup', 'mp_PrintMockupPlg', 'store_ShipmentOrders', 'private');
        
        return $html;
    }
}
