<?php

/**
 * Версия на JS компонента
 */
defIfNot('ESCPOS_HASH_LEN', '6');


/**
 * "Подправка" за кодиране
 */
defIfNot('ESCPOS_SALT', md5(EF_SALT . '_ESCPOS'));


/**
 * 
 * 
 * @category  bgerp
 * @package   escpos
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class escpos_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Escpos принтиране";
    


    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
            'ESCPOS_HASH_LEN' => array ('int', 'caption=Дължина на хеша за линковете->Стойност'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        //
        // Закачаме плъгина 
        //
        $html .= core_Plugins::installPlugin('Мобилно принтиране на продажби', 'escpos_PrintPlg', 'sales_Sales', 'private');
        $html .= core_Plugins::installPlugin('Мобилно принтиране на ЕН', 'escpos_PrintPlg', 'store_ShipmentOrders', 'private');
        $html .= core_Plugins::installPlugin('Мобилно принтиране на фактури', 'escpos_PrintPlg', 'sales_Invoices', 'private');
        
        return $html;
    }
}
