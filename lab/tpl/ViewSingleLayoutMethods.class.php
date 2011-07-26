<?php


/**
 * Клас 'lab_tpl_ViewSingleLayoutMethods' -
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
class lab_tpl_ViewSingleLayoutMethods extends core_ET
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
                         <div class='f_left'>Метод:&nbsp;&nbsp;&nbsp;<b>[#name#]</b></div>
                     </div>
                     <!-- END Заглавие -->
                     
                     <!-- BEGIN Блок ляво -->
                     <div class='block_left'>
                         <!--ET_BEGIN equipment--> 
                         <div class='clear_l mb_5px'>
                             <div class='label'>Оборудване:</div> 
                             <div>[#equipment#]</div>
                         </div>
                         <!--ET_END equipment-->
                         
                         <!--ET_BEGIN param--> 
                         <div class='clear_l mb_5px'>
                             <div class='label'>Параметър:</div> 
                             <div>[#param#]</div>
                         </div>
                         <!--ET_END param-->                         
                     </div>    
                     <!-- END Блок ляво -->   
                           
                     <!-- BEGIN Блок дясно -->
                     <div class='block_right'>
                         <!--ET_BEGIN minVal--> 
                         <div class='clear_l mb_5px' style='width: 150px;'>
                             <div class='label'>Мин. ст-ст:</div> 
                             <div class='f_right'>[#minVal#]</div>
                         </div>
                         <!--ET_END minVal-->
                                                  
                         <!--ET_BEGIN maxVal--> 
                         <div class='clear_l mb_5px' style='padding-top: 3px; width: 150px;'>
                             <div class='label'>Макс. ст-ст:</div> 
                             <div class='f_right'>[#maxVal#]</div>
                         </div>
                         <!--ET_END maxVal-->                         
                      </div>
                     <!-- END Блок дясно -->
                     
                     <!-- BEGIN Блок долу --> 
                     <div class='clear_l f_left'>
                        <b>Описание:</b>
                        <div class='clear_l' style='max-width: 500px;'>[#description#]</div>
                     </div>
                     <!-- END Блок долу -->
                     
                     <div style='clear: both;'></div>
                     
                 </div>   
                 <!-- END container -->
                     
                 <div style='clear: both;'></div>";
        
        return parent::core_ET($html);
    }
}