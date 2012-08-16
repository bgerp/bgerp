<?php



/**
 * Клас 'acc_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Acc'
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class legalact_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
       
        
        $this->TAB('legalact_Acts', 'Актове', 'ceo,admin,legal');
         
        $this->title = 'Актове « Производство';
    }
}