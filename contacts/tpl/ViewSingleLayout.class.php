<?php


/**
 * Клас 'contacts_tpl_ViewSingleLayout' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    contacts
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class contacts_tpl_ViewSingleLayout extends core_ET
{
    /**
     *  @todo Чака за документация...
     */
    public function init($params = array())
    {
        $html = "<!-- BEGIN container -->
                 <div class='css-lib contacts-single'>

                     <!-- BEGIN Заглавие -->        
                       <div class='clear_l f_left block_title'>
                         <div class='f_left'><b>[#name#]</b></div>
                           
                         <!--ET_BEGIN country--> 
                         <div class='f_right'>
                             <span>[#place#]";
        
        if ($params['data']->row->place) {
            $html .= ", ";
        }
        $html .= "[#country#]</span>
                         </div>
                         <!--ET_END country-->
                     </div>
                     <!-- END Заглавие -->
                     
                     <!-- BEGIN Блок ляво -->
                     <div class='block_left'>
                         <!--ET_BEGIN address--> 
                         <div class='clear_l'>
                             <div class='f_left address'>[#address#]</div>
                         </div>
                         <!--ET_END address-->
                     
                         <!--ET_BEGIN tel--> 
                         <div class='clear_l mb_5px'>
                             <div class='label'>Телефон:</div> 
                             <span>[#tel#]</span>
                         </div>
                         <!--ET_END tel-->

                         <!--ET_BEGIN fax--> 
                         <div class='clear_l mb_5px'>
                             <div class='label'>Факс:</div> 
                             <span>[#fax#]</span>
                         </div>
                         <!--ET_END fax-->                         
    
                         <!--ET_BEGIN mobile--> 
                         <div class='clear_l mb_5px'>
                             <div class='label'>Мобилен:</div> 
                             <span>[#mobile#]</span>
                         </div>
                         <!--ET_END mobile-->
                           
                         <!--ET_BEGIN email--> 
                         <div class='clear_l mb_5px'>
                             <div class='label'>Email:</div> 
                             <a href='mailto:[#email#]'>[#email#]</a>
                         </div>
                         <!--ET_END email-->                     
                     
                         <!--ET_BEGIN website--> 
                         <div class='clear_both'>
                             <div class='label'>Уеб сайт:</div> 
                             <span><a href='[#website#]'>[#website#]</a></span>
                         </div>
                         <!--ET_END website-->";
        
        // BEGIN Листване на групите
        $groups = type_Varchar::toVerbal($params['data']->rec->groupListVerbal);
        
        if (count($groups)) {
            $html .= "<div class='clear_both pt_10px'><b>Групи</b></div>";
            
            $html .= "<div class='clear_l' style='max-width: 250px;'>";
            
            $counter = 0;
            
            foreach ($groups as $group) {
                $counter++;
                
                $html .= "<span>" . substr($group,0,2) . "</span><span style='
                                           text-transform:lowercase;'>" . substr($group,2,strlen($group) - 2) . "</span>";
                
                if ($counter < count($groups)) {
                    $html .= ", ";
                }
            }
            unset($counter);
            
            $html .= "</div>";
        }
        // END Листване на групите                         
        $html .= "</div>
                     <!-- END Блок ляво -->   
                           
                     <!-- BEGIN Блок дясно -->
                     <div class='block_right'>
                     
                         <!--ET_BEGIN image-->
                         <div class='clear_l f_right pl_0px'>  
                             [#image#]
                         </div>   
                         <!--ET_END image-->
                         
                         <!--ET_BEGIN info-->
                         <div class='clear_both' style='padding-top: 20px;'><b>Друга информация</b></div>
                         <div class='clear_l' style='max-width: 250px;'>
                             [#info#]
                         </div>   
                         <!--ET_END info-->                         
                         
                     </div>
                     <!-- BEGIN Блок дясно -->
                     
                 </div>   
                 <!-- END container -->
                     
                 <div style='clear: both;'></div>
                  
                 [#SingleToolbar#]";
        
        return parent::core_ET($html);
    }
}