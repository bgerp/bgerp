<?php



/**
 * Клас 'tpl_Info' - Шаблон за ajax резултат
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  ef
 * @package   tpl
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class tpl_DefaultAjax {
    
    
    /**
     * @todo Чака за документация...
     */
    static function output($contentArr)
    {
        echo json_encode($contentArr);
        
        shutdown();
    }
}