<?php



/**
 * Клас 'cash_Wrapper'
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
       
        
        $this->TAB('cash_Cases', 'Каси', 'cash, admin');
        $this->TAB('cash_Documents', 'Документи', 'cash, admin');
        
        $this->title = 'Фирмени каси';
    }
}