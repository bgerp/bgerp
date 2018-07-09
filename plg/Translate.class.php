<?php


/**
 * Клас 'plg_Translate' - Плъгин за превеждане
 *
 * Превежда полето, ако е зададен параметъра 'translate' в типа
 *
 * @category  ef
 * @package   plg
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class plg_Translate extends core_Plugin
{
    /**
     * Преди вземането на вербалната стойност
     *
     * @param core_Mvc $mvc
     * @param int      $num
     * @param mixed    $rec
     * @param string   $part
     */
    public static function on_BeforeGetVerbal($mvc, &$num, &$rec, $part = null)
    {
        // Ако е зададено да се превежда полето и има поле
        if ($part && $mvc->fields[$part]->translate) {
            
            // Ако не е обект
            if (!is_object($rec)) {
                
                // Ако е id на запис
                if (is_numeric($rec)) {
                    
                    // Извличаме записа
                    $rec = $mvc->fetch($rec);
                } elseif (is_numeric($num)) {
                    
                    // Извличаме записа от id' то
                    $rec = $mvc->fetch($num);
                }
            }
            
            // Ако е обект
            if ($rec && $part && is_object($rec)) {
                
                // Превеждаме
                $rec->$part = tr($rec->$part);
            }
        }
    }
}
