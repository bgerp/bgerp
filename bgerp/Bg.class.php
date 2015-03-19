<?php



/**
 * Смяна на езика на български
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_Bg extends core_Mvc
{
    /**
     * Да не се кодират id-тата
     */
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
    
    /**
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param string $action
     */
    function on_BeforeAction($mvc, &$res, $act)
    {
        $vid = urldecode(Request::get('Act'));

        // Сменяме езика на външната част на английски
        cms_Content::setLang('bg');

        switch($act) {
            case 'default' :
                
                
                // Редиректваме към началото
                $res = new redirect(array('Index', 'Default'));
                break;
            
            case 'products' :
                // Вземаме записа, който отговаря на първото меню, сочещо към групите за Bg език
                $cMenuId = cms_Content::getDefaultMenuId('eshop_Groups');
                
                // Връщаме за резултат, породения HTML/ЕТ код от ShowAll метода на eshop_Groups
                $res     = Request::forward(array('Ctr' => 'eshop_Groups', 'Act' => 'ShowAll', 'cMenuId' => $cMenuId));
                break;
            
            default :
            $res = Request::forward(array('Ctr' => 'cms_Articles', 'Act' => 'Article', 'id' => $vid));
        }
        
        return FALSE;
    }
}