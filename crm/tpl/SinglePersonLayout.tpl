[#SingleToolbar#]
<div class='folder-cover'>
                    
                     <div class='block_title [#STATE_CLASS#]'>
                         [#title#]
                     </div>
                     
                     <!--ET_BEGIN image-->
                        <div style='padding:8px;padding-right:0px;float:right;'>
                         <div class='clear_l f_right pl_0px'>  
                             [#image#]
                         </div>
                         </div>
                     <!--ET_END image-->
                     
                     <!--ET_BEGIN contacts-->
                         [#contacts#]
                         <fieldset class='detail-info'>
                            <legend class='groupTitle'>|Данни|*</legend>
                            <div class='groupList'>
                                <!--ET_BEGIN place--><div>[#pCode#] [#place#]</div><!--ET_END place-->
                                <!--ET_BEGIN address--><div>[#address#]</div><!--ET_END address-->
                                <!--ET_BEGIN mobile--><div>|Моб.|*: <b>[#mobile#]</b></div><!--ET_END mobile-->
                                <!--ET_BEGIN tel--><div>|Тел.|*: <b>[#tel#]</b></div><!--ET_END tel-->
                                <!--ET_BEGIN fax--><div>|Факс|*: <b>[#fax#]</b></div><!--ET_END fax--> 
                                <!--ET_BEGIN email--><div>|E-мейл|*: <b>[#email#]</b></div><!--ET_END email--> 
                                <!--ET_BEGIN website--><div>|Сайт/Блог|*: <b>[#website#]</b></div><!--ET_END website-->
                            </div>
                         </fieldset>
                        <!--ET_END contacts-->

                        
                        <!--ET_BEGIN groupList-->
                         <fieldset class='detail-info'>
                             <legend class='groupTitle'>|Групи|*</legend>
                             <div class='groupList'>
                                [#groupList#]
                             </div>
                         </fieldset>
                        <!--ET_END groupList-->

                                             
                        <!--ET_BEGIN business-->
                         [#business#]
                         <fieldset class='detail-info'>
                            <legend class='groupTitle'>|Служебна информация|*</legend>
                            <div class='groupList'>
                                <!--ET_BEGIN buzCompanyId--><div><b>[#buzCompanyId#]</b></div><!--ET_END buzCompanyId-->
                                <!--ET_BEGIN buzTel--><div>|Тел.|*:&nbsp;<b>[#buzTel#]</b></div><!--ET_END buzTel-->
                                <!--ET_BEGIN buzFax--><div>|Факс|*:&nbsp;<b>[#buzFax#]</b></div><!--ET_END buzFax--> 
                                <!--ET_BEGIN buzEmail--><div>|E-мейл|*:&nbsp;<b>[#buzEmail#]</b></div><!--ET_END buzEmail--> 
                                <!--ET_BEGIN buzAddress--><div>|Адрес|*:&nbsp;<b>[#buzAddress#]</b></div><!--ET_END buzAddress--> 
                            </div>
                         </fieldset>
                        <!--ET_END business-->
                      

                        
                         
                        <!--ET_BEGIN idCard-->
                         [#idCard#]
                         <fieldset class='detail-info'>
                            <legend class='groupTitle'>|Лична карта|*</legend>
                            <div class='groupList'>
                                <!--ET_BEGIN idCardNumber--><span>№: <b>[#idCardNumber#]</b></span><!--ET_END idCardNumber-->
                                <!--ET_BEGIN idCardIssuedOn--><span>&nbsp;&nbsp; |Издаване|*:&nbsp;<b>[#idCardIssuedOn#]</b></span><!--ET_END idCardIssuedOn-->
                                <!--ET_BEGIN idCardExpiredOn--><span>&nbsp;&nbsp; |Валидност|*:&nbsp;<b>[#idCardExpiredOn#]</b></span><!--ET_END idCardExpiredOn-->
                            </div>
                            <!--ET_BEGIN idCardIssuedBy--><div><b>[#idCardIssuedBy#]</b></div><!--ET_END idCardIssuedBy--> 
                         </fieldset>
                        <!--ET_END idCard-->

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