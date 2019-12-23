<?php


/**
 * Заменя линковете за телефоните с линк към централата
 *
 * @category  bgerp
 * @package   callcenter
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class callcenter_LinkPlg extends core_Plugin
{
    /**
     * Прихваща извикването на getLink
     * Замества линковете към централата
     *
     * @param core_Mvc $mvc
     * @param string   $resUrl
     * @param string   $verbal
     * @param string   $canonical
     * @param bool     $isFax
     * @param array    $attr
     */
    public static function on_AfterGetLink($mvc, &$res, $verbal, $canonical, $isFax = false, $attr = array())
    {
        // Ако пакета не е инсталиран - не би трябвало да се стига до тук, ако не е инсталиран
//        if (!core_Packs::isInstalled('callcenter')) return ;
        
        $canonical = '+' . $canonical;
        
        // Ако е факс
        if ($isFax) {
            
            // Ако има права залистовия изглед на факсовете
            if (!callcenter_Fax::haveRightFor('list')) {
                
                return ;
            }
            
            // URL към факсовет изпратени към този номер
            $url = array('callcenter_Fax', 'number' => $canonical);
        } else {
            // Ако има права залистовия изглед на централата
            if (!callcenter_Talks::haveRightFor('list')) {
                
                return ;
            }
            
            // URL към разговорите с този номер
            $url = array('callcenter_Talks', 'number' => $canonical);
        }
        
        // Резултатния линк
        $res = ht::createLink($verbal, $url, null, $attr);
    }
}
