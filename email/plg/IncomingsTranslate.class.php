<?php


/**
 * Плъгин за превеждане на входящите имейли
 *
 * Базиран на google_plg_Translate
 *
 * @category  vendors
 * @package   google
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class email_plg_IncomingsTranslate extends core_Plugin
{
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields)
    {
        $translateLg = email_Setup::get('INCOMINGS_TRANSLATE_LG');
        $translateLgArr = type_Keylist::toArray($translateLg);
        
        $translateLgCodeArr = array();
        foreach ($translateLgArr as $lgId) {
            $lgCode = drdata_Languages::fetchField($lgId, 'code');
            $lgCode = strtolower($lgCode);
            $translateLgCodeArr[$lgCode] = $lgCode;
        }
        
        $rLg = strtolower($rec->lg);
        if (empty($translateLgCodeArr) || $translateLgCodeArr[$rLg]) {
            if ($rLg != core_Lg::getCurrent() &&
                !(Mode::is('text', 'xhtml') && !Mode::is('printing')) &&
                !Mode::is('text', 'plain') &&
                $fields['-single'] && trim($row->textPart)
                 ) {
                $row->textPart = new core_ET(
                        google_Translate1::getMarkupTpl($row->textPart)
                    );
                
                if (!Request::get('ajax_mode')) {
                    $row->textPart->push(google_Translate1::getElementJsUrl(), 'JS');
                    $row->textPart->appendOnce(google_Translate1::getInitJs(), 'SCRIPTS');
                    $row->textPart->appendOnce(google_Translate1::getCss(), 'STYLES');
                }
            }
        }
    }
}
