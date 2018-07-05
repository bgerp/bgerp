<?php



/**
 * Клас 'frame_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'frame'
 *
 *
 * @category  bgerp
 * @package   frame
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class frame_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('frame_Reports', 'Отчети', 'ceo, report, admin');
    }
}
