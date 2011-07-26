<?php


/**
 * Клас 'lab_tpl_ViewSingleLayoutTests' -
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
class lab_tpl_ViewSingleLayoutTests extends core_ET
{
    /**
     *  @todo Чака за документация...
     */
    public function init($params = array())
    {
        // bp($params['data']->rec);
        
        $html = "<div class='f_left'>[#SingleToolbar#]</div>
                 <!-- BEGIN container -->
                 <div class='clear_l css-lib lab-tests-single mt_10px'>

                     <!-- BEGIN Заглавие -->        
                     <div class='clear_l f_left block_title'>
                         <div class='title'>" . BGERP_OWN_COMPANY_NAME . "</div>
                     </div>
                     <!-- END Заглавие -->
                     
                     <!-- BEGIN Заглавие -->        
                     <div class='clear_l f_left block_subtitle'>
                        <div class='line1'><b>СВИДЕТЕЛСТВО ЗА ЛАБОРАТОРЕН АНАЛИЗ</b></div>
                        <div class='line2'>No 212 / " . date('d.m.Y' ,dt::mysql2timestamp($params['data']->rec->activatedOn)) . "&nbsp;г.</div>
                        
                        <div class='f_left test-name'>
                            <div class='label'>Име на тест:</div> 
                            <div class='f_left'>[#handler#]</div>
                        </div>
                     </div>
                     <!-- END Заглавие -->                     
                     
                     <!-- BEGIN Блок ляво -->
                     <div class='block_left'>
                         <table cellspacing='1' class='top-table'>
                            <!--ET_BEGIN type-->
                            <tr>
                                <td>Вид:&nbsp;&nbsp;</td>
                                <td>[#type#]</td>
                            </tr>
                            <!--ET_END type-->
                            
                            <!--ET_BEGIN origin-->
                            <tr>
                                <td>Произход:&nbsp;&nbsp;</td>
                                <td>[#origin#]</td>
                            </tr>
                            <!--ET_END origin-->

                            <!--ET_BEGIN assignor-->
                            <tr>
                                <td>Възложител:&nbsp;&nbsp;</td>
                                <td>[#assignor#]</td>
                            </tr>
                            <!--ET_END assignor-->                            
                            
                            <!--ET_BEGIN madeBy-->
                            <tr>
                                <td>Изпълнител:&nbsp;&nbsp;</td>
                                <td>[#madeBy#]</td>
                            </tr>
                            <!--ET_END madeBy-->
                         </table>
<div style='display: none'>                     
                         <!--ET_BEGIN type--> 
                         <div class='clear_l mb_5px'>
                             <div class='label'>Вид:</div> 
                             <div>[#type#]</div>
                         </div>
                         <!--ET_END type-->
                                              
                         <!--ET_BEGIN origin--> 
                         <div class='clear_l mb_5px'>
                             <div class='label'>Произход:</div> 
                             <div>[#origin#]</div>
                         </div>
                         <!--ET_END origin-->                         
                         
                         <!--ET_BEGIN assignor--> 
                         <div class='clear_l mb_5px'>
                             <div class='label'>Възложител:</div> 
                             <div>[#assignor#]</div>
                         </div>
                         <!--ET_END assignor-->
                         
                         <!--ET_BEGIN madeBy--> 
                         <div class='clear_l mb_5px'>
                             <div class='label'>Изпълнител:</div> 
                             <div>[#madeBy#]</div>
                         </div>
                         <!--ET_END madeBy-->                         
</div>                         
                     </div>    
                     <!-- END Блок ляво -->   
                           
                     <!-- BEGIN Блок дясно -->
                     <div class='block_right'>
                         <!--ET_BEGIN note-->
                         <div class='clear_both'><b>Описание</b></div>
                         <div class='clear_l note'>
                             [#note#]
                         </div>   
                         <!--ET_END note-->                         
                         
                     </div>
                     <!-- END Блок дясно -->
                     
                     <!-- BEGIN Блок долу --> 
                     <div class='clear_l f_left'>
                        <p class='mb_5px'>Параметри:</p>
                        <div>[#detailsTpl#]</div>
                     </div>
                     <!-- END Блок долу -->
                     
                     <div style='clear: both;'></div>
                     
                     <!-- BEGIN footer -->        
                     <div class='clear_l f_left block_subtitle mt_50px'>
                        <div class='f_left w_100'>
                            <div class='w_100'>
                               <div class='f_right'>Изготвил:&nbsp;&nbsp;_____________</div>
                            </div>
                            <div class='clear_both f_right mt_10px'>
                               <div>/&nbsp;[#madeBy#]&nbsp;/</div>
                            </div>
                        </div>
                     </div>
                     <!-- END footer -->
                 </div>   
                 <!-- END container -->
                     
                 <div style='clear: both;'></div>";
        
        return parent::core_ET($html);
    }
}