<?php


/**
 * Смяна на езика на английски
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_En extends core_Mvc
{
    var $protectId = FALSE;
    
    /**
     * Заглавие
     */
    var $title = 'Смяна на езика на английски';
    
    
    /**
     * Екшън по подразбиране, който сменя езика на английски
     */
    function act_Default()
    {
    }

    function on_BeforeAction($mvc, &$res, $act)
    {
        $vid = urldecode(Request::get('Act'));
 
        
        switch($act) {
            case 'default':
                // Сменяме езика на външната част на английски
                cms_Content::setLang('en');

                // Редиректваме към началото
                $res = new redirect(array('Index', 'Default'));
                break;

            case 'products':
                // Вземаме записа, който отговаря на първото меню, сочещо към групите за En език
                $cMenuId = cms_Content::fetchField(array("#source = [#1#] AND #lang = 'en' AND #state = 'active'" , eshop_Groups::getClassId()));
                
                // Връщаме за резултат, породения HTML/ЕТ код от ShowAll метода на eshop_Groups
                $res     = Request::forward(array('Ctr' => 'eshop_Groups', 'Act' => 'ShowAll', 'cMenuId' => $cMenuId));
                break;

            default:
                $res = Request::forward(array('Ctr' => 'cms_Articles', 'Act' => 'Article', 'id' => $vid));
        }

        return FALSE;
    }


    /**
     * Връща кратко URL към съдържание, което се линква чрез този редиректор
     */
    function getShortUrl($url)
    {
        if($url['Act']) { 

            $vid = urldecode($url['Act']);

            $id = cms_VerbalId::fetchId($vid, 'cms_Articles');
 
            if(!$id) {
                $id = cms_Articles::fetchField(array("#vid = '[#1#]'", $vid), 'id');
            }
            
            if($id) {
                $url['Act'] = $id;
            }
        }
        
        unset($url['PU']);

        return $url;
    }
}