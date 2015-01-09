<?php



/**
 * Клас 'cond_Wrapper'
 *
 * Поддържа системното меню на пакета trans
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cond_Wrapper extends plg_ProtoWrapper
{
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('cond_DeliveryTerms', 'Доставки', 'cond,ceo');
    	$this->TAB('cond_PaymentMethods', 'Плащания->Методи', 'cond,ceo');    
        $this->TAB('cond_Payments', 'Плащания->Средства', 'ceo,cond');
        $this->TAB('cond_Parameters', 'Параметри', 'cond,ceo');
        
        $this->title = 'Терминология';
    }
}