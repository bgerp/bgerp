<?php


/**
 * Клас 'thumbnail_Setup' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    thumbnail
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class thumbnail_Setup extends core_Manager {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Умалени картинки";
    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        // Установяваме папките;
        $Thumbnail = cls::get('thumbnail_Thumbnail');
        $html .= $Thumbnail->setupMVC();
        
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        return "<h4>Пакета thumbnail е деинсталиран</h4>";
    }
}