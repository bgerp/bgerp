<?php



/**
 * Клас 'gallery_Wrapper'
 *
 * Поддържа табове-те на пакета 'gallery'
 *
 *
 * @category  vendors
 * @package   vislog
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class gallery_Wrapper extends plg_ProtoWrapper
{    
    /**
     * Описание на табовете
     */
    function description()
    {
        
        $this->TAB('gallery_Groups', 'Групи');
        $this->TAB('gallery_Images', 'Картинки');
       
        $this->title = 'Галерия';
    }
}