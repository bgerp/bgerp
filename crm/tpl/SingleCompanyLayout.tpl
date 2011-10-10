<!-- Шаблон за единичен изглед на компания -->
                 
                 [#SingleToolbar#]

                 <div class='folder-cover'>
                    
		     <div class='clear_l f_left block_title [#STATE_CLASS#]'>
                         [#title#]
                     </div>
                     
                     <!--ET_BEGIN image-->
                        <div style='padding:8px;padding-right:0px;float:right;'>
                         <div class='clear_l f_right pl_0px'>  
                             [#image#]
                         </div>
                         </div>
                     <!--ET_END image-->

                     <!--ET_BEGIN address-->
                     <fieldset class='detail-info'>
                        <legend class='groupTitle'>|Контактни данни|*</legend>
                        <div class='groupList'>
                            <div>[#pCode#] [#place#]</div>
                            <div>[#address#]</div>
                                    <!--ET_BEGIN tel--><div>|Тел.|*: <b>[#tel#]</b></div><!--ET_END tel-->
                                    <!--ET_BEGIN fax--><div>|Факс|*: <b>[#fax#]</b></div><!--ET_END fax--> 
                                    <!--ET_BEGIN email--><div>|E-мейл|*: <b>[#email#]</b></div><!--ET_END email--> 
                                    <!--ET_BEGIN website--><div>|Web сайт|*: <b>[#website#]</b></div><!--ET_END website-->
                                </div>
                         </fieldset>
                        <!--ET_END address-->
                        
                         <!--ET_BEGIN groupList-->
                         <fieldset class='detail-info'>
                             <legend class='groupTitle'>|Групи</legend>
                                <div class='groupList'>
                                    [#groupList#]
                                </div>
                         </fieldset>
                        <!--ET_END groupList-->
                        
                        <!--ET_BEGIN regDecision-->
                         [#regDecision#]
                         <fieldset class='detail-info'>
                            <legend class='groupTitle'>|Решение по регистрцията|*</legend>
                                <div class='groupList'>
                                <!--ET_BEGIN regDecisionNumber--><span>№: <b>[#regDecisionNumber#]</b></span><!--ET_END regDecisionNumber-->
                                <!--ET_BEGIN regDecisionDate--><span>&nbsp;&nbsp;|Дата|*: <b>[#regDecisionDate#]</b></span><!--ET_END regDecisionDate-->
                            </div>
                            <!--ET_BEGIN regCourt--><div><b>[#regCourt#]</b></div><!--ET_END regCourt--> 
                         </fieldset>
                        <!--ET_END regDecision-->

                        <!--ET_BEGIN regCompanyFile-->
                         [#regCompanyFile#] 
                         <fieldset class='detail-info'>
                            <legend class='groupTitle'>|Фирмено дело|*</legend>
                                <div class='groupList'>
                                <!--ET_BEGIN regCompanyFileNumber--><span>№: <b>[#regCompanyFileNumber#]</b></span><!--ET_END regCompanyFileNumber-->
                                <!--ET_BEGIN regCompanyFileYear-->&nbsp;&nbsp;<span>|Година|*: <b>[#regCompanyFileYear#]</b></span><!--ET_END regCompanyFileYear-->
                            </div>
                         </fieldset>
                        <!--ET_END regCompanyFile-->
                        
                        <!--ET_BEGIN info-->
                        <fieldset class='detail-info'>
                             <legend class='groupTitle'>|Друга информация|*</legend>
                             <div class='groupList'>
                                [#info#]
                             </div>
                         </fieldset>
                        <!--ET_END info-->

                    <div style='clear:both;'></div>
 
                 </div>   
                 <!-- END container -->
