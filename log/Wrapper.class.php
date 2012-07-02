<?php



/**
 * Поддържа системното меню и табове-те на пакета 'log'
 *
 *
 * @category  bgerp
 * @package   log
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class log_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
       
        
        $this->TAB('log_Documents', 'Документи', 'admin, doc');
        
        $this->title = 'История';
    }
}
