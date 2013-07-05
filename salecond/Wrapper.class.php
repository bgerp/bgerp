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
 * @copyright 2006 - 2013 Experta OOD
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
        $this->TAB('salecond_DeliveryTerms', 'Доставки', 'salecond,ceo');
    	$this->TAB('salecond_PaymentMethods', 'Плащания', 'salecond,ceo');    
        $this->TAB('salecond_Parameters', 'Параметри', 'salecond,ceo');
   		
        $this->title = 'Терминология';
    }
}