<?php


/**
 * Плъгин за превеждане на входящите имейли
 *
 * Базиран на google_plg_Translate
 *
 * @category  vendors
 * @package   google
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_plg_IncomingsTranslate extends core_Plugin
{
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields)
    {
        if ($rec->lg != core_Lg::getCurrent() &&
            !(Mode::is('text', 'xhtml') && !Mode::is('printing')) &&
            !Mode::is('text', 'plain') &&
            $fields['-single'] && trim($row->textPart)
             ) {
            $row->textPart = new core_ET(
                google_Translate1::getMarkupTpl($row->textPart)
            );
            
            $row->textPart->push(google_Translate1::getElementJsUrl(), 'JS');
            $row->textPart->appendOnce(google_Translate1::getInitJs(), 'SCRIPTS');
            $row->textPart->appendOnce(google_Translate1::getCss(), 'STYLES');
        }
    }
}
