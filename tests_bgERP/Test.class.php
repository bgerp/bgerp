<?php


/**
 * Клас  'tests_Test' - Разни тестове на PHP-to
 *
 *
 * @category  bgerp
 * @package   tests
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class tests_Test extends core_Manager
{
    /**
     * @todo Чака за документация...
     */
    public function act_Regexp()
    {
        preg_match('/(\d+)[ ]*(d|day|days|д|ден|дни|дена)\b/u', '2 дена', $matches);
        
        // работи според очакванията от php 5.3.4+
        // http://stackoverflow.com/questions/8915713/php5-3-preg-match-with-umlaute-utf-8-modifier
        bp($matches);
    }
    
    
    public function act_Date()
    {
        $this->date = 'Tue, 17 Dec 2013 13:49:05 -0800';
        
        $this->getSendingTime();
        
        bp($this->sendingTime);
    }
    
    
    /**
     * Определяне на датата на писмото, когато е изпратено
     */
    public function getSendingTime()
    {
        if (!isset($this->sendingTime)) {
            // Определяме датата на писмото
            $d = date_parse($this->date);
            
            //  bp($d);
            if (count($d)) {
                $time = mktime($d['hour'], $d['minute'], $d['second'], $d['month'], $d['day'], $d['year']);
                
                if ($d['is_localtime']) {
                    $time = $time - ($d['zone'] * 60) + (date('O') / 100) * 60 * 60;
                }
                
                $this->sendingTime = dt::timestamp2Mysql($time);
            }
        }
        
        return $this->sendingTime;
    }
}
