<?php



/**
 * Клас 'trans_Wrapper'
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
class trans_Wrapper extends plg_ProtoWrapper
{
    
    /**
     * Описание на табовете
     */
    function description()
    {
               
        $this->TAB('trans_DeliveryTerms', 'Условия на доставка');
   
        $this->title = 'Логистика';
    }
}