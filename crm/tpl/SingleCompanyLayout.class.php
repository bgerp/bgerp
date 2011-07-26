<?php


/**
 * Клас 'crm_tpl_SingleCompanyLayout' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    crm
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class crm_tpl_SingleCompanyLayout extends core_ET
{
    /**
     *  @todo Чака за документация...
     */
    public function init($params = array())
    {
        $html = "<!-- BEGIN container -->
                 
                 <div style='padding-bottom:10px;padding-top:5px;'>[#SingleToolbar#]</div>

                 <div class='css-lib contacts-single'>
                    
                       <div class='clear_l f_left block_title'>
                         [#title#]
                     </div>
                     
                     <!--ET_BEGIN image-->
                         <div class='clear_l f_right pl_0px'>  
                             [#image#]
                         </div>   
                     <!--ET_END image-->

                          <!--ET_BEGIN address-->
                            <div>[#pCode#] [#place#]</div>
                            <div>[#address#]</div>
                         <!--ET_END address-->
                     
                         <!--ET_BEGIN contacts-->
                         [#contacts#]
                         <div style='margin-top:5px;'>
                            <div style='color:#999;'><u><b>" . tr("Контакти"). "</b></u></div>
                            <!--ET_BEGIN tel--><div>Тел.: <b>[#tel#]</b></div><!--ET_END tel-->
                            <!--ET_BEGIN fax--><div>Факс: <b>[#fax#]</b></div><!--ET_END fax--> 
                            <!--ET_BEGIN email--><div>E-мейл: <b>[#email#]</b></div><!--ET_END email--> 
                            <!--ET_BEGIN website--><div>Web сайт: <b>[#website#]</b></div><!--ET_END website--> 
                         </div>
                        <!--ET_END contacts-->
                        
                         <!--ET_BEGIN groupList-->
                         <div style='margin-top:10px;'>
                             <div style='color:#999;'><u><b>" . tr("Групи"). "</b></u></div>
                             [#groupList#]
                         </div>
                        <!--ET_END groupList-->
                        
                        <!--ET_BEGIN regDecision-->
                         [#regDecision#]
                         <div style='margin-top:5px;'>
                            <div style='color:#999;'><u><b>" . tr("Решение по регистрцията"). "</b></u></div>
                            <div>
                                <!--ET_BEGIN regDecisionNumber--><span>№: <b>[#regDecisionNumber#]</b></span><!--ET_END regDecisionNumber-->
                                <!--ET_BEGIN regDecisionDate--><span>&nbsp;&nbsp;Дата: <b>[#regDecisionDate#]</b></span><!--ET_END regDecisionDate-->
                            </div>
                            <!--ET_BEGIN regCourt--><div><b>[#regCourt#]</b></div><!--ET_END regCourt--> 
                         </div>
                        <!--ET_END regDecision-->

                        <!--ET_BEGIN regCompanyFile-->
                         [#regCompanyFile#] 
                         <div style='margin-top:5px;'>
                            <div style='color:#999;'><u><b>" . tr("Фирмено дело"). "</b></u></div>
                            <div>
                                <!--ET_BEGIN regCompanyFileNumber--><span>№: <b>[#regCompanyFileNumber#]</b></span><!--ET_END regCompanyFileNumber-->
                                <!--ET_BEGIN regCompanyFileYear-->&nbsp;&nbsp;<span>Година: <b>[#regCompanyFileYear#]</b></span><!--ET_END regCompanyFileYear-->
                            </div>
                         </div>
                        <!--ET_END regCompanyFile-->
                        
                        <!--ET_BEGIN info-->
                         <div style='margin-top:10px;'>
                             <div style='color:#999;'><u><b>" . tr("Информация"). "</b></u></div>
                             [#info#]
                         </div>
                        <!--ET_END info-->

                    <div style='clear:both;'></div>
 
                 </div>   
                 <!-- END container -->
                   
                 ";
        
        return parent::core_ET($html);
    }
}