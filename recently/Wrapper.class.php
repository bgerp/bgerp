<?php



/**
 * Клас 'plg_SystemWrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Core'
 *
 *
 * @category  vendors
 * @package   recently
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class recently_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('recently_Values', 'Последно използвани');
        
        $this->title = 'Подсказки за инпут полетата';
    }
}
