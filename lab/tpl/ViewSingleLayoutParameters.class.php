<?php


/**
 * Клас 'lab_tpl_ViewSingleLayoutParameters' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    lab
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class lab_tpl_ViewSingleLayoutParameters extends core_ET
{
    /**
     *  @todo Чака за документация...
     */
    public function init($params = array())
    {
        // bp($params['recParameters']->name);
        
        $html = "<div class='f_left'>[#SingleToolbar#]</div>
                 <!-- BEGIN container -->
                 <div class='clear_l lab-tests-single mt_10px'>

                     <!-- BEGIN Заглавие -->        
                     <div class='clear_l f_left block_title'>
                         <div class='f_left'><b>[#name#]</b></div>
                     </div>
                     <!-- END Заглавие -->
                     
                     <!-- BEGIN Блок ляво -->
                     <div class='block_left'>
                     
                         <!--ET_BEGIN type--> 
                         <div class='clear_l mb_5px'>
                             <div class='label'>Тип:</div> 
                             <div>[#type#]</div>
                         </div>
                         <!--ET_END type-->                         
                         
                         <!--ET_BEGIN dimention--> 
                         <div class='clear_l mb_5px'>
                             <div class='label'>Размерност:</div> 
                             <div>[#dimention#]</div>
                         </div>
                         <!--ET_END dimention-->
                         
                         <!--ET_BEGIN precision--> 
                         <div class='clear_l mb_5px'>
                             <div class='label'>Прецизност:</div> 
                             <div>[#precision#]</div>
                         </div>
                         <!--ET_END precision-->                         
                     </div>    
                     <!-- END Блок ляво -->   
                           
                     <!-- BEGIN Блок дясно -->
                     <div class='block_right'>
                     </div>
                     <!-- END Блок дясно -->
                     
                     <!-- BEGIN Блок долу --> 
                     <div class='clear_l f_left'>
                         <!--ET_BEGIN description-->
                         <div class='clear_both'><b>Описание</b></div>
                         <div class='clear_l' style='max-width: 600px;'>
                             [#description#]
                         </div>   
                         <!--ET_END description--> 
                     </div>
                     <!-- END Блок долу -->
                     
                     <div style='clear: both;'></div>
                     
                 </div>   
                 <!-- END container -->
                     
                 <div style='clear: both;'></div>";
        
        return parent::core_ET($html);
    }
}