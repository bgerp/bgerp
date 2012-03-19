<?php



/**
 * Клас 'thumbnail_Setup' -
 *
 *
 * @category  all
 * @package   thumbnail
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class thumbnail_Setup extends core_Manager {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Умалени картинки";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Установяваме папките;
        $Thumbnail = cls::get('thumbnail_Thumbnail');
        $html .= $Thumbnail->setupMVC();
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        return "<h4>Пакета thumbnail е деинсталиран</h4>";
    }
}