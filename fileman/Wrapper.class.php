<?php



/**
 * Клас 'fileman_Wrapper' -
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class fileman_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('fileman_Files', 'Файлове');
        $this->TAB('fileman_Versions', 'Версии');
        $this->TAB('fileman_Buckets', 'Кофи');
        $this->TAB('fileman_Download', 'Сваляния');
        $this->TAB('fileman_Data', 'Данни');
        
        
        $this->title = 'Файлове';
       
    }
}