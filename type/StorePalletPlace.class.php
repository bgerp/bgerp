<?php
/**
 * Клас 'type_StorePalletPlace' - тип за палет място в 'Логистика'
 *
 * @category   Experta Framework
 * @package    type
 * @author     Milen Georgiev
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 3
 * @since      v 0.1
 */
class type_StorePalletPlace extends type_Varchar {
	
	function toVerbal_($position)
	{
	    $position = trim($position);
	      
	    if(empty($position)) return FALSE;
	        
	    $rowLettersArr = array('a', 'b', 'c', 'd', 'e', 'f', 'g',
	                           'A', 'B', 'C', 'D', 'E', 'F', 'G');
	        
	    foreach ($rowLettersArr as $rowLetter) {
	        if ($letterPos = strpos($position, $rowLetter)) {
	            break; 
	        }
	    }
	    
	    if (empty($letterPos)) return FALSE;
	    
	    /* process $rackId */
	        $rackId = substr($position, 0, $letterPos);
	
	        /* test pattern */
	            $pattern = "/^[1-9]{1}[0-9]{0,2}[\s]{0,1}[\-]{0,1}[\s]{0,1}$/";
	            
	            if (!preg_match($pattern, $rackId, $match)) {
	                // test failed
	                unset($match);
	                return FALSE;
	            } else {
	                // test passed
	                
	                // extract pattern
	                $pattern = "/^[0-9]+/";
	                preg_match($pattern, $rackId, $match);
	                $rackId = $match[0];
	                unset($match);
	            }
	        /* ENDOF test pattern */
	    /* ENDOF process $rackId */
	        
	    /* process $rackRow */
	        $rackRow = substr($position, $letterPos, 1);
	        $rackRow = strtoupper($rackRow);
	    /* ENDOF process $rackRow */
	
	    /* process $rackColumn */
	        $rackColumn = substr($position, $letterPos + 1, strlen($position) - $letterPos - 1);
	                
	        /* test pattern */
	            $pattern = "/^[\s]{0,1}[\-]{0,1}[\s]{0,1}[1-9]{1}[0-9]{0,1}$/";
	            
	            if (!preg_match($pattern, $rackColumn, $match)) {
	                // test failed
	                unset($match);
	                return FALSE;
	            } else {
	                // test passed
	                
	                // extract pattern
	                $pattern = "/[0-9]+$/";
	                preg_match($pattern, $rackColumn, $match);
	                $rackColumn = $match[0];
	                unset($match);
	            }
	        /* test pattern */                  
	    /* ENDOF process $rackColumn */
	
	    $position = $rackId . "-" . $rackRow . "-" . $rackColumn;
	    
	    return $position;    
	}
	
}