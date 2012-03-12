<?php


/**
 * Клас  'tests_Test' - Разни тестове на PHP-to
 *
 *
 * @category  ef
 * @package   test
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class tests_Test extends core_Manager {
    
    /**
     * @todo Чака за документация...
     */
    function act_Regexp()
    {
        preg_match('/(\d+)[ ]*(d|day|days|д|ден|дни|дена)\b/u', "2 дена", $matches);
        
        // работи споредот php 5.3.4+
        // http://stackoverflow.com/questions/8915713/php5-3-preg-match-with-umlaute-utf-8-modifier 
        bp($matches);
    }
}