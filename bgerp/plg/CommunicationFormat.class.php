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
    static $tel = array("тел", "телефон", "tel" , "telephone", "phone", "mobile", "mob",
			    	 	"тел:", "телефон:", "tel:" , "telephone:", "phone:", "mobile:", "mob:",
			    	 	"тел.", "телефон.", "tel." , "telephone.", "phone.", "mobile.", "mob.");
    static $email = array("company email", "e-mail", "e-mayl", "e-meyl", "e-post", "e", 
    				   	  "emai" , "email msn", "email to", "email", "emne", "epost", "mail to",
    				      "mailto", "mail", "personal e-mail", "@",
    				      "company email:", "e-mail:", "e-mayl:", "e-meyl:", "e-post:", "e:", 
    				      "emai:" , "email msn:", "email to:", "email:", "emne:", "epost:", "mail to:",
    				      "mailto:", "mail:", "personal e-mail:", "@:",
    				      "company email.", "e-mail.", "e-mayl.", "e-meyl.", "e-post.", "e.", 
    				      "emai." , "email msn.", "email to.", "email.", "emne.", "epost.", "mail to.",
    				      "mailto.", "mail.", "personal e-mail.", "@.");
	/**
     * 
     */
    function on_AfterCatchRichElements($mvc, &$html)
    {
     
       $this->mvc = $mvc;

       //Ако намери съвпадение на регулярния израз изпълнява функцията
       // Обработваме елементите, който са от вида услига : или . или интервал последвана от email 
       $html = preg_replace_callback("/([\r\n]{0,2}[a-zа-я\-]{3,32}[ .:]?)([ ]?[-_a-z0-9\'+*$^&%=~!?{}]+(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?)/is", array($this, 'catchCommunicationFormat'), $html);
       
       //Ако намери съвпадение на регулярния израз изпълнява функцията
       // Обработваме елементите, който са всичко останало
       $html = preg_replace_callback("/([\r\n]{0,2}[a-zа-я\-]{3,32}[ .:]{1})([ ]?[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*)/is", array($this, 'catchCommunicationFormat'), $html);

    }
    
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $match
     */
    function catchCommunicationFormat($match)
    {
        $place = $this->mvc->getPlace();
    
        if(in_array(strtolower(trim($match[1])), static::$email)){
        	
        }
        
        // Ако услугата е телефон
        elseif(in_array(trim(strtolower($match[1])), static::$tel)){ 
        	
        	$this->mvc->_htmlBoard[$place] = "<div>{$match[1]}<a href='tel:{$match[2]}' title='phone'>{$match[2]}</a></div>";

          // Ако услугата е skype
        } elseif(trim(strtolower($match[1])) == "skype" || trim(strtolower($match[1])) == "skype." || trim(strtolower($match[1])) == "skype:") {
        	
        	$skypeUser = trim($match[2]);
        	
        	$this->mvc->_htmlBoard[$place] = "<div>{$match[1]}<a class='url' href='skype:{$skypeUser}?call'>{$match[2]}</a></div>";
        }
        
        // ако не удовлетворява другите условия връщаме тестов див
        // за да знаем, че отговаря на регулярните изрази
        else{
      
			$title = htmlentities($match[2], ENT_COMPAT, 'UTF-8');
				        
			$this->mvc->_htmlBoard[$place] = "<div><span class='tel'><span class='value'>$match[1]{$title}</span></span></span></div>";
        }
        
    	return "[#{$place}#]";
      
    }
}