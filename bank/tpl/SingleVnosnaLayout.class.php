<?php

/**
 * Клас 'ank_tpl_SingleVnosnaLayout' -
 *
 * @category   Experta Framework
 * @package    bank
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class bank_tpl_SingleVnosnaLayout extends core_ET
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
	                    подпис на вносителя
	                </div>                
	            </div>
	        </div> 
	                               
	    </div>
	    <!-- END belejka_header -->
	
		<div class='belejka'>
		    <div class='bg_white line'>
		           <div class='text pos_rel'>
		              [#beneficiaryName#]
		              <span class='pos_abs small_text_top_left'>В полза на - име</span>
		           </div>	                                     
		    </div>
		       
		    <div class='bg_white line'>
		        <div class='bg_green text border_r' style='width: 450px;'>
		           [#beneficiaryIban#]
		           <span class='pos_abs small_text_top_left'>IBAN на получателя</span>
		        </div>                                       
		    </div>
		    
		    <div class='bg_white line'>
		        <div class='text'>
		           [#beneficiaryBank#]
		           <span class='pos_abs small_text_top_left'>При банка (банка, клон)</span>
		        </div>                                       
		    </div>                	            
		
		    <div class='bg_white line'>
		        <div class='text a_center border_r' style='width: 150px; letter-spacing: normal; line-height: 15px; padding-top: 5px; padding-bottom: 5px;'>
		           <span class='b'>ВНОСНА<br/>БЕЛЕЖКА</span>
		        </div>
	            <div class='text a_center border_r' style='width: 150px; letter-spacing: normal; line-height: 15px; padding-top: 5px; padding-bottom: 5px;'>
	               <span>Внесохме<br/>в брой</span>
	            </div>	        
		        
		        <div class='text bg_green border_r'>
		           [#currencyId#]
		           <span class='pos_abs small_text_top_left'>Вид валута</span>
		        </div>
		        <div class='text bg_green a_right' style='width: 310px;'>
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
		           [#depositorName#]
		           <span class='pos_abs small_text_top_left'>Вносител - име</span>
		        </div>                                       
		    </div>                
		    
	        <div class='bg_white line'>
	            <div class='text'>
	               [#reason#]
	               <span class='pos_abs small_text_top_left'>Основание за внасяне</span>
	            </div>                                       
	        </div>
	        
	        <div class='bg_green line'>
	            <div class='text border_r' style='width: 353px;'>
	               &nbsp;
	               <span class='pos_abs small_text_top_left'>Счетоводител</span>
	            </div>                                       
	            <div class='text' style='width: 352px;'>
	               &nbsp;
	               <span class='pos_abs small_text_top_left'>Касиер</span>
	            </div>
	        </div>        	    
		    
	    </div>
	    <!-- END belejka -->
	    
    </div>
    <!-- END belejka_container -->
    
    <div style='clear: both;'></div>";

	    parent::core_ET($html);
	    $this->push("bank/tpl/css/belejka.css", 'CSS');
	        
	    return $this;
    }
}