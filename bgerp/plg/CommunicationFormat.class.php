<?php


/**
 * Форматиране на ISQ, Scype, tel. и други
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_CommunicationFormat extends core_Plugin
{
    
	/**
     * Обработваме елементите линковете, които сочат към ISQ, Scype, tel
     */
    function on_AfterCatchRichElements($mvc, &$html)
    {

       $this->mvc = $mvc;
       $html = preg_replace_callback("/^\s*((Тел|Телефон|Tel|Telephone|Phone|Mobile|Mob)\.?\:? *)([0-9\-\\\\\/\+\(\)]{5,15})/umi", array($this, 'catchCommunicationFormat'), $html);
       $html = preg_replace_callback("/^\s*((AIM|YIM|MSNIM|MSN|XMPP|Jabber|Skype|ICQ)\.?\:? *)([a-zA-Z0-9_\-\@\.]{3,64})/umi", array($this, 'catchCommunicationFormat'), $html);
       //Ако намери съвпадение на регулярния израз изпълнява функцията
       //$html = preg_replace_callback("/^\s*((Тел|Телефон|Tel|Telephone|Phone|Mobile|Mob|AIM|YIM|MSNIM|MSN|XMPP|Jabber|Skype|ICQ)\.?\:? *)([a-zA-Z0-9_\-\@\.\+]{3,64})/umi", array($this, 'catchCommunicationFormat'), $html);
    
    }
    
    
    /**
     * Обработваме всички елементи в richText-а,
     * които са от вида на "Skype: скайп_име" или "ICQ номер на icq потребител"
     * и започват на нов ред.
     * Заместваме ги с линк към съответната услуга
     * 
     * @param array $match
     */
    function catchCommunicationFormat($match)
    {
        // намираме мястото, което ще заместваме
        $place = $this->mvc->getPlace();
        
        // елемент съдържащ: телефонен номер или потребителско име/номер
        $matchElement = trim(strtolower($match[2]));

        // в зависимост от услугата, правим различни линкове
        switch ($matchElement) {
        	
        	case 'тел' :
        	case 'телефон' :
        	case 'tel' :
        	case 'telephone' :
        	case 'phone' :
        	case 'mobile' :
        	case 'mob' :
        		
        		$PhonesVerbal = cls::get('drdata_PhoneType');
        		
        		if($PhonesVerbal->toVerbal($match[3])){
        			
        			$this->mvc->_htmlBoard[$place] = $PhonesVerbal->toVerbal($match[3]);
        		}
        	    break;
        	    
        	case 'msnim' :
        	case 'msn' :
        		$this->mvc->_htmlBoard[$place] = "<span class='communication'><a class='url' href='msnim:chat?contact={$match[3]}' title='MSN'>{$match[3]}</a>";
        		break;

        	case 'xmpp' :
        	case 'jabber' :
        		 $this->mvc->_htmlBoard[$place] = "<span class='communication'><a class='url' href='xmpp:{$match[3]}' title='{$match[2]}'>{$match[3]}</a>";
        		 break;
        		 
	        case 'skype' : 
		        $skypeUser = trim($match[3]);
        	
        		$this->mvc->_htmlBoard[$place] = "<span class='linkWithIcon'><a class='url' href='skype:{$skypeUser}?call' title='Skype'>{$match[3]}</a>";
		        break;
		        
	        case 'aim' : 
		        $this->mvc->_htmlBoard[$place] = "<span class='communication'><a class='url' href='aim:goim?screenname={$match[3]}' title='AOL Instant Messenger (AIM)'>{$match[3]}</a>";
		        break;
		        
	        case 'yim' :
		        $this->mvc->_htmlBoard[$place] = "<span class='communication'><a class='url' href='ymsgr:sendIM?{$match[3]}' title='Yahoo! Messenger'>{$match[3]}</a>";
		        break;
		 		        
		    case 'icq' :
		        $this->mvc->_htmlBoard[$place] = "<span class='communication'><a class='url' type='application/x-icq' 
		         																			href='http://www.icq.com/people/cmd.php?uin={$match[3]}&action=message'>{$match[3]}</a>";
		        break;
        }
        
        // посочваме мястото където ще за заменят линковете
        $communicationFormat = str_replace($match[3], "[#{$place}#]", $match[0]);
    	
        return $communicationFormat;
    }
}