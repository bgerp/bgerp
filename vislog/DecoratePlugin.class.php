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
    public static function on_AfterDecorateIp($type, &$res, $ip, $time = null, $coloring = false, $showNames = false)
    {
        static $cntArr = array();

        // Ако показваме чист текст или подготвяме HTML за навън - лишаваме се от декорациите
        if (Mode::is('text', 'plain') || Mode::is('text', 'xhtml')) {
            
            return $ip;
        }
        
        if (!strtolower(trim($ip))) {
            
            return $ip;
        }

        if(!($cnt = $cntArr[$ip])) {
            $cnt = vislog_History::count(array("#ip = '[#1#]'", $ip));
        }

        if ($cnt) {
            if ($time) {
                $old = $cnt == 1 ? 1 : vislog_History::count(array("#ip = '[#1#]' AND #createdOn <= '[#2#]'", $ip, $time));
                $style = 'color:#' . sprintf('%02X%02X%02X', min(($old / $cnt) * ($old / $cnt) * ($old / $cnt) * 255, 255), 0, 0) . ';';
                $titleCnt = "{$old}/{$cnt}";
            } else {
                $style = '';
                $titleCnt = "{$cnt}";
            }
            if (vislog_History::haveRightFor('list')) {
                $count = ht::createLink(
                    $titleCnt,
                            array('vislog_History', 'ip' => $ip),
                            null,
                            array('class' => 'vislog-cnt', 'style' => $style)
                );
            } else {
                $count = $titleCnt;
            }
        }
        
        $country2 = drdata_IpToCountry::get($ip);
        if(!$country2) {
            $country2 = '??';
        }

        $countryName = drdata_Countries::fetchField("#letterCode2 = '" . strtoupper($country2) . "'", 'commonName' . (core_Lg::getCurrent() == 'bg' ? 'Bg' : ''));
        
        $country = ht::createLink($country2, 'http://bgwhois.com/?query=' . $ip, null, array('target' => '_blank', 'class' => 'vislog-country', 'title' => $countryName));
        
        if ($showNames) {
            $ipRec = vislog_IpNames::fetch(array("#ip = '[#1#]'", $ip));
        }
        
        $fullName = $ip;

        if ($ipRec) {
            $fullName .=  ' ' .vislog_IpNames::getVerbal($ipRec, 'name');
        }
        
        $name = drdata_IpToHosts::getHostByIP($ip); 
        
        if ($coloring) {
            $name = str::coloring($name, $ip);
        }
        
        if ($fullName) {
            $fullName = ht::escapeAttr($fullName);
            $res = new ET("<div class='vislog'>[#1#]&nbsp;<span class='vislog-ip' title='{$fullName}'>{$name}</span>&nbsp;[#2#]</div>", $country, $count);
        } else {
            $res = new ET("<div class='vislog'>[#1#]&nbsp;<span class='vislog-ip'>{$name}</span>&nbsp;[#2#]</div>", $country, $count);
        }
    }


}
