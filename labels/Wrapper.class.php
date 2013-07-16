<?php


/**
 * Поддържа системното меню и табове-те на пакета 'inks'
 *
 * @category  bgerp
 * @package   inks
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class labels_Wrapper extends plg_ProtoWrapper
{
    
    /**
     * Описание на опаковката от табове
     */
    function description()
    {        
        $this->TAB('labels_Default', 'Етикети', 'labels');
       
        $this->title = 'Етикети « Търговия';
        Mode::set('menuPage', 'Търговия:Етикети');
    }
}
