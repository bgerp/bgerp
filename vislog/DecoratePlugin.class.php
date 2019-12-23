<?php


/**
 * Декорира ip адреса с html връзки
 *
 * @category  bgerp
 * @package   vislog
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class vislog_DecoratePlugin extends core_Plugin
{
    /**
     * Декорира ip адреса с html връзки
     */
    public static function on_AfterDecorateIp($type, &$res, $ip, $time = null, $coloring = false)
    {
        static $cntArr = array();
        
        // Ако показваме чист текст или подготвяме HTML за навън - лишаваме се от декорациите
        if (Mode::is('text', 'plain') || Mode::is('text', 'xhtml')) {
            
            return $ip;
        }
        
        if (!strtolower(trim($ip))) {
            
            return $ip;
        }
        
        $cnt = $old = 0;
        if (!($cnt = $cntArr[$ip])) {
            $cnt = vislog_History::count(array("#ip = '[#1#]'", $ip));
            if ($cnt && $time) {
                $old = $cnt == 1 ? 1 : vislog_History::count(array("#ip = '[#1#]' AND #createdOn <= '[#2#]'", $ip, $time));
            }
        }
        
        $res = log_Ips::decorateIp($ip, $coloring, $cnt, $old);
    }
}
