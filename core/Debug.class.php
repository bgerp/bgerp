<?php



/**
 * Клас 'core_Debug' ['Debug'] - Функции за дебъг и настройка на приложения
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Debug
{
    
    
    /**
     * Функция - флаг, че обектите от този клас са Singleton
     */
    function _Singleton() {}
    
    
    /**
     * Инициализираме таймерите
     */
    function core_Debug()
    {
        $this->lastMicroTime = 0;
        $this->debugTime[] = "0.00000: Begin";
        
        if (!$this->startMicroTime) {
            $this->startMicroTime = dt::getMicrotime();
        }
    }
    
    
    /**
     * Пускаме хронометъра за посоченото име
     */
    static function startTimer($name)
    {
        // Функцията работи само в режим DEBUG
        if(!isDebug()) return;
        
        //static $Debug;
        
        if (!$Debug)
        $Debug = & cls::get('core_Debug');
        $Debug->timers[$name] = new stdClass();
        $Debug->timers[$name]->start = dt::getMicrotime();
    }
    
    
    /**
     * Спираме хронометъра за посоченото име
     */
    static function stopTimer($name)
    {
        // Функцията работи само в режим DEBUG
        if(!isDebug()) return;
        
        //static $Debug;
        
        if (!$Debug)
        $Debug = & cls::get('core_Debug');
        
        if ($Debug->timers[$name]->start) {
            $workingTime = dt::getMicrotime() - $Debug->timers[$name]->start;
            $Debug->timers[$name]->workingTime += $workingTime;
            $Debug->timers[$name]->start = NULL;
        }
    }
    
    
    /**
     * Лог записи за текущия хит
     */
    static function log($name)
    {
        // Функцията работи само в режим DEBUG
        if(EF_DEBUG !== TRUE) return;
        
        static $Debug;
        
        if (!$Debug)
        $Debug = & cls::get('core_Debug');
        
        $Debug->debugTime[] = number_format((dt::getMicrotime() - $Debug->startMicroTime), 5) . ": " . $name;
    }
    
    
    /**
     * Колко време е записано на това име?
     */
    static function getExecutionTime()
    {
        static $Debug;
        
        if (!$Debug)
        $Debug = & cls::get('core_Debug');
        
        return number_format((dt::getMicrotime() - $Debug->startMicroTime), 5);
    }
    
    
    /**
     * Връща лога за текущия хит
     */
    static function getLog()
    {
        static $Debug;
        
        if (!$Debug) $Debug = & cls::get('core_Debug');
        
        if (count($Debug->debugTime) > 1) {
            $Debug->log('End');
            $html .= "\n<div style='padding:5px; margin:10px; border:solid 1px #777; background-color:#FFFF99; display:table;color:black;'>" .
            "\n<div style='background-color:#FFFF33; padding:5px; color:black;'>Debug log</div><ol>";
            
            foreach ($Debug->debugTime as $rec) {
                $html .= "\n<li style='padding:15px 0px 15px 0px;border-top:solid 1px #cc3;'>" . htmlentities($rec, ENT_QUOTES, 'UTF-8');
            }
            
            $html .= "\n</ol></div>";
        }
        
        if ($Debug->timers) {
            $html .= "\n<div style='padding:5px; margin:10px; border:solid 1px #777; background-color:#FFFF99; display:table;color:black;'>" .
            "\n<div style='background-color:#FFFF33; padding:5px;color:black;'>Timers info</div><ol>";
            
            foreach ($Debug->timers as $name => $t) {
                $html .= "\n<li> '{$name}' => " . number_format($t->workingTime, 5) . ' sec.';
            }
            
            $html .= "\n</ol></div>";
        }
        
        return $html;
    }
}