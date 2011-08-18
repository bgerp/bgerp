<?php

/**
 * Клас 'fin_tpl_SinglePrevodnoLayout' -
 *
 * @category   Experta Framework
 * @package    fin
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class fin_tpl_SinglePrevodnoLayout extends core_ET
{
    /**
     *  @todo Чака за документация...
     */
    public function init($params = array())
    {
        $html = "
    <div class=.belejka_container.>
    
        <div class=.belejka_header.>
            <div class=.container.>
                <div class=.label_left.>До</div>
                <div class=.right_block.>
                    <div class=.text border_b.>
                        [#execBank#]
                    </div>
                    <div class=.label_mid.>
                        банка
                    </div>                
                </div>
            </div>
            
            <div class=.container.>
                <div class=.label_left.>&nbsp;</div>
                <div class=.right_block.>
                    <div class=.text border_b.>
                        &nbsp;
                    </div>
                    <div class=.label_mid.>
                        уникален регистрационен номер
                    </div>                
                </div>
            </div>        
            
            <div class=.container.>
                <div class=.label_left.>Клон</div>
                <div class=.right_block.>
                    <div class=.text border_b.>
                        [#execBranch#] 
                    </div>
                </div>
            </div>
            
            <div class=.container.>
                <div class=.label_left.>&nbsp;</div>
                <div class=.right_block.>
                    <div class=.text border_b.>
                        [#issueDate#]
                    </div>
                    <div class=.label_mid.>
                        дата на представяне
                    </div>                
                </div>
            </div>
            
            <div class=.container.>
                <div class=.label_left.>Адрес</div>
                <div class=.right_block.>
                    <div class=.text border_b.>
                        [#execBranchAddress#]
                    </div>
                    <div class=.label_mid.>
                        &nbsp;
                    </div>                
                </div>
            </div>
            
            <div class=.container.>
                <div class=.label_left.>&nbsp;</div>
                <div class=.right_block.>
                    <div class=.text border_b.>
                        &nbsp;
                    </div>
                    <div class=.label_mid.>
                        подпис на наредителя
                    </div>                
                </div>
            </div> 
                                    
        </div>
        <!-- END belejka_header -->

        <div class=.belejka.>
            <div class=.bg_white line.>
                   <div class=.text pos_rel.>
                      [#beneficiaryName#]
                      <span class=.pos_abs small_text_top_left.>Платете на - име на получатела</span>
                   </div>                                        
            </div>
               
            <div class=.bg_blue line.>
                <div class=.text border_r. style=.width: 450px;.>
                   [#beneficiaryIban#]
                   <span class=.pos_abs small_text_top_left.>IBAN на получатела</span>
                </div>                                       
                <div class=.text border_r bg_white. style=.width: 50px;.>
                    &nbsp;
                </div>
                <div class=.text. style=.width: 170px;.>
                   [#beneficiaryBic#]
                   <span class=.pos_abs small_text_top_left.>BIC на банката на получателя</span>
                </div>                                        
            </div>
            
            <div class=.bg_white line.>
                <div class=.text.>
                   [#beneficiaryBank#]
                   <span class=.pos_abs small_text_top_left.>При банка - име на банката на получатела</span>
                </div>                                       
            </div>                              
        
            <div class=.bg_blue line.>
                <div class=.bg_white text a_center border_r. style=.width: 484px; letter-spacing: normal; line-height: 15px; padding-top: 5px; padding-bottom: 5px;.>
                   <span class=.b.>ПРЕВОДНО НАРЕЖДАНЕ</span>
                   <br/>
                   <span style=.text-transform: none;.>за кредитен превод</span>
                </div>
                <div class=.text bg_blue border_r.>
                   [#currencyId#]
                   <span class=.pos_abs small_text_top_left.>Вид валута</span>
                </div>
                <div class=.text bg_blue a_right. style=.width: 148px;.>
                   [#amount#]
                   <span class=.pos_abs small_text_top_left.>Сума</span>
                </div>                                        
            </div>
            
            <div class=.bg_white line.>
                <div class=.text.>
                   [#reason#]
                   <span class=.pos_abs small_text_top_left.>Основание за превод - информация за получатела</span>
                </div>                                       
            </div>                
            
            <div class=.bg_white line.>
                <div class=.text.>
                   [#moreReason#]
                   <span class=.pos_abs small_text_top_left.>Още пояснения</span>
                </div>                                       
            </div>                
            
            <div class=.bg_white line.>
                <div class=.text.>
                   [#ordererName#]
                   <span class=.pos_abs small_text_top_left.>Наредител - име</span>
                </div>                                       
            </div>                
            
            <div class=.bg_blue line.>
                <div class=.text border_r. style=.width: 450px;.>
                   [#ordererIban#]
                   <span class=.pos_abs small_text_top_left.>IBAN на наредителя</span>
                </div>                                       
                <div class=.text border_r bg_white. style=.width: 50px;.>
                    &nbsp;
                </div>
                <div class=.text. style=.width: 170px;.>
                   [#ordererBic#]
                   <span class=.pos_abs small_text_top_left.>BIC на банката на наредителя</span>
                </div>                                        
            </div>
            
            <div class=.bg_white line.>
                <div class=.text border_r. style=.width: 250px;.>
                   [#paymentSystem#]
                   <span class=.pos_abs small_text_top_left.>Платежна система</span>
                </div>                                       
                <div class=.text a_right border_r. style=.width: 80px;.>
                   &nbsp;
                   <span class=.pos_abs small_text_top_left.>Такси</span>
                </div>
                <div class=.text a_right border_r. style=.width: 149px;.>
                   &nbsp;
                </div>
                <div class=.text a_right. style=.width: 80px;.>
                   &nbsp;
                   <span class=.pos_abs small_text_top_left.>Дата на изпълнение</span>
                </div>                                                                                
            </div>
                                            
        </div>
        <!-- END belejka -->
        
    </div>
    <!-- END belejka_container -->";

	    parent::core_ET($html);
	    $this->push("fin/tpl/belejka.css", 'CSS');
	        
	    return $this;
    }
}