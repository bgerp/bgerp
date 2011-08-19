<?php

/**
 * Клас 'fin_tpl_SingleRazpiskaLayout' -
 *
 * @category   Experta Framework
 * @package    fin
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class fin_tpl_SingleRazpiskaLayout extends core_ET
{
    /**
     *  @todo Чака за документация...
     */
    public function init($params = array())
    {
        $html = "
    <div class='belejka_container'>
    
	    <div class='belejka_header'>
	        <div class='container'>
	            <div class='label_left'>До</div>
	            <div class='right_block'>
	                <div class='text border_b'>
	                    [#execBank#]
	                </div>
	                <div class='label_mid'>
	                    банка
	                </div>                
	            </div>
	        </div>
	        
	        <div class='container'>
	            <div class='label_left'>&nbsp;</div>
	            <div class='right_block'>
	                <div class='text border_b'>
	                    [#issuePlaceAndDate#]
	                </div>
	                <div class='label_mid'>
	                    място и дата на подаване
	                </div>                
	            </div>
	        </div>        
	        
	        <div class='container'>
	            <div class='label_left'>Клон</div>
	            <div class='right_block'>
	                <div class='text border_b'>
	                    [#execBranch#] 
	                </div>
	                <div class='label_mid'>
	                    банка
	                </div>                
	            </div>
	        </div>
	        
	        <div class='container'>
	            <div class='label_left'>&nbsp;</div>
	            <div class='right_block'>
	                <div class='text border_b_white'>
	                    &nbsp;
	                </div>
	                <div class='label_mid'>
	                    &nbsp;
	                </div>                
	            </div>
	        </div>
	        
	        <div class='container'>
	            <div class='label_left'>Адрес</div>
	            <div class='right_block'>
	                <div class='text border_b'>
	                    [#execBranchAddress#]
	                </div>
	                <div class='label_mid'>
	                    &nbsp;
	                </div>                
	            </div>
	        </div>
	        
	        <div class='container'>
	            <div class='label_left'>&nbsp;</div>
	            <div class='right_block'>
	                <div class='text border_b'>
	                    &nbsp;
	                </div>
	                <div class='label_mid'>
	                    подписи на лицата, които могат да се разпореждат
	                </div>                
	            </div>
	        </div>
	                                
	    </div>
        <!-- END belejka_header -->
	    
	    <div class='belejka'>
	        <div class='bg_white line'>
	               <div class='text pos_rel'>
	                  [#ordererName#] 
	                  <span class='pos_abs small_text_top_left'>Наредител - име</span>
	               </div>                                        
	        </div>
	           
	        <div class='bg_red line'>
	            <div class='text border_r' style='width: 450px;'>
	               [#ordererIban#]
	               <span class='pos_abs small_text_top_left'>IBAN на наредителя</span>
	            </div>                                       
	            <div class='text bg_white' style='width: 257px;'>
	                &nbsp;
	            </div>
	        </div>
	        
	        <div class='bg_white line'>
	            <div class='text'>
	               [#ordererBank#]
	               <span class='pos_abs small_text_top_left'>При банка (банка, клон)</span>
	            </div>                                       
	        </div>
	        
	        <div class='bg_white line'>
	            <div class='text a_center border_r' style='width: 200px; letter-spacing: normal; line-height: 15px; padding-top: 5px; padding-bottom: 5px;'>
	               <span class='b'>НАРЕЖДАНЕ<br/>РАЗПИСКА</span>
	            </div>
	            <div class='text a_center border_r' style='width: 100px; letter-spacing: normal; line-height: 15px; padding-top: 5px; padding-bottom: 5px;'>
	               <span class='b'>Платете<br/>в брой</span>
	            </div>          
	            
	            <div class='text bg_red border_r'>
	               [#currencyId#]
	               <span class='pos_abs small_text_top_left'>Вид валута</span>
	            </div>
	            <div class='text bg_red a_right' style='width: 313px;'>
	               [#amount#]
	               <span class='pos_abs small_text_top_left'>Сума</span>
	            </div>                                        
	        </div>        
	        
	        <div class='bg_white line'>
	            <div class='text'>
	               [#sayWords#]
	               <span class='pos_abs small_text_top_left'>С думи</span>
	            </div>                                       
	        </div>        
	        
	        <div class='bg_white line'>
	            <div class='text'>
	               [#proxyName#]
	               <span class='pos_abs small_text_top_left'>Упълномощавам - трите имена на лицето, упълномощено да получи стоката</span>
	            </div>                                       
	        </div>                
	        
	        <div class='bg_red line'>
	            <div class='text border_r' style='width: 250px;'>
	               [#proxyIdentityCardNumber#]
	               <span class='pos_abs small_text_top_left'>Документ за самоличност №</span>
	            </div>
	            <div class='text border_r' style='width: 250px;'>
	               [#proxyEgn#]
	               <span class='pos_abs small_text_top_left'>ЕГН</span>
	            </div>          
	            <div class='text' style='width: 186px;'>
	               &nbsp;
	               <span class='pos_abs small_text_top_left'>Получател</span>
	            </div>
	        </div>
	        
	        <div class='bg_red line'>
	            <div class='text border_r b' style='width: 250px;'>
	               <span style='text-transform: none; letter-spacing: normal;'>Получих сумата</span>
	            </div>
	            <div class='text border_r' style='width: 114px;'>
	               &nbsp;
	               <span class='pos_abs small_text_top_left'>Контролен подпис</span>
	            </div>          
	            <div class='text border_r' style='width: 115px;'>
	               &nbsp;
	               <span class='pos_abs small_text_top_left'>Банков служител</span>
	            </div>
	            <div class='text' style='width: 186px;'>
	               &nbsp;
	               <span class='pos_abs small_text_top_left'>Касиер</span>
	            </div>            
	        </div>

	    </div>    
	    <!-- END belejka -->    
	
	</div>
    <!-- END belejka_container -->";

	    parent::core_ET($html);
	    $this->push("fin/tpl/belevka.css", 'CSS');
	        
	    return $this;
    }
}