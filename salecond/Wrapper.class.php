<?php



/**
 * Клас 'salecond_Wrapper'
 *
 * Поддържа системното меню на пакета trans
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class salecond_Wrapper extends plg_ProtoWrapper
{
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('salecond_DeliveryTerms', 'Условия на доставка');
    	$this->TAB('salecond_PaymentMethods', 'Начини на плащане');    
        $this->TAB('salecond_Others', 'Други');
   		
        $this->title = 'Логистика';
    }
}