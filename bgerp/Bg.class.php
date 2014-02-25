<?php


/**
 * Смяна на езика на български
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_Bg extends core_Mvc
{
    var $protectId = FALSE;

    /**
     * Заглавие
     */
    var $title = 'Смяна на езика на български';
    
    
    /**
     * Екшън по подразбиране, който сменя езика на английски
     */
    function act_Default()
    {
    }

    function on_BeforeAction($mvc, &$res, $act)
    {
        $vid = Request::get('Act');
 
        if($act == 'default') {
            
            // Сменяме езика на външната част на английски
            cms_Content::setLang('bg');

             // Редиректваме
            return redirect(array('Index', 'Default'));
        }

        $res = Request::forward(array('Ctr' => 'cms_Articles', 'Act' => 'Article', 'id' => $vid));

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