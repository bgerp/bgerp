<?php


/**
 * Плъгин за превеждане на думите в core_Lg
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
class google_plg_LgTranslate extends core_Plugin
{
    public static function on_AfterRenderWrapping($mvc, $res, &$tpl, $data = null)
    {
        // Ако има шаблон
        if ($data->form->tpl) {
            
            // Добавяме го
            $res->append($data->form->tpl);
        }
    }
    
    
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        // Ако текущия език е български, да не се показва
        if ($data->form->rec->lg == 'bg') {
            
            return ;
        }
        
        // Ако не е обейт
        if (!is_object($data->form->tpl)) {
            
            // Създавме шаблона
            $data->form->tpl = new ET();
        }
        
        // Променлива
        $tpl = &$data->form->tpl;
        
        // Вземаме променливите, необходими за превеждане
        $markup = google_Translate1::getMarkupTpl('', true);
        $tpl->push(google_Translate1::getElementJsUrl('en'), 'JS');
        $tpl->appendOnce(google_Translate1::getInitJs(), 'SCRIPTS');
        $tpl->appendOnce(google_Translate1::getCss(), 'STYLES');
        
        // Добавяме скрипта
        jquery_Jquery::run($tpl, "
        	$('.formFields table tbody').append('<tr><td style=\'text-align:right\'>Google:</td><td>${markup}</td>');
        	
        	$('.goog-trans-text').text($('.translated').val());
        ");
    }
}
