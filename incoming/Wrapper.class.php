<?php



/**
 * Клас 'incoming_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'incoming'
 *
 * @category  bgerp
 * @package   incoming
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class incoming_Wrapper extends plg_ProtoWrapper
{
    
    /**
     * Описание на опаковката от табове
     */
    function description()
    {        
        $this->TAB('incoming_Documents', 'Документи');
        $this->TAB('incoming_Types', 'Типове');
    }
}
