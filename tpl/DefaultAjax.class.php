<?php

/**
 * Клас 'tpl_Info' - Шаблон за ajax резултат
 *
 * Файлът може да се подмени с друг
 *
 * @category   Experta Framework
 * @package    tpl
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class tpl_DefaultAjax {
    
    
    /**
     *  @todo Чака за документация...
     */
    function output($contentArr)
    {
        echo json_encode($contentArr);

        shutdown();
    }
}