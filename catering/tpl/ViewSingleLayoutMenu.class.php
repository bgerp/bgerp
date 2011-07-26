<?php


/**
 * Клас 'catering_tpl_ViewSingleLayoutMenu' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    catering
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class catering_tpl_ViewSingleLayoutMenu extends core_ET
{
    /**
     *  @todo Чака за документация...
     */
    public function init($params = array())
    {
        // bp($params['data']->rec);
        
        $html = "<div class='f_left'>[#SingleToolbar#]</div>
                 <!-- BEGIN container -->
                 <div class='clear_l css-lib catering-menu-single mt_10px'>

                     <!-- BEGIN Заглавие -->        
                     <div class='clear_l f_left block_title'>
                         <div class='title'>Меню на фирма \"{$params['data']->row->companyId}\"</div>
                     </div>
                     <!-- END Заглавие -->
                     
                     <!-- BEGIN Заглавие -->        
                     <div class='clear_l f_left block_subtitle'>
                        <div class='line1'>";
        
        if ($params['data']->rec->repeatDay == '0.OnlyOnThisDate') {
            $html .= "Дата: <b>{$params['data']->row->date}</b>";
        } else {
            $html .= "<b>{$params['data']->row->repeatDay}</b>";
        }
        $html .= "
                        </div>
                     </div>
                     <!-- END Заглавие -->                     
                     
                     <!-- BEGIN Блок долу --> 
                     <div class='clear_l f_left'>
                        <br/>
                        <div>[#detailsTpl#]</div>
                     </div>
                     <!-- END Блок долу -->
                     
                     <div style='clear: both;'></div>
                     
                 </div>   
                 <!-- END container -->
                     
                 <div style='clear: both;'></div>";
        
        return parent::core_ET($html);
    }
}