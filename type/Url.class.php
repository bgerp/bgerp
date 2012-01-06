<?php


/**
 * Клас  'type_Url' - Тип за URL адреси
 *
 * @category   Experta Framework
 * @package    type
 * @author     Yusein Yuseinov
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version	   v 0.1
 */
class type_Url extends type_Varchar {
    
	
	/**
	 * Обработения линк
	 */
	private $url;
	
	
	/**
     *  Дължина на полето в mySql таблица
     */
    var $dbFieldLen = 255;
		
	    
    /**
     *  Преобразуване от вътрешно представяне към вербална стойност за проценти (0 - 100%)
     */
	function toVerbal($value)
	{
        // Когато стойността е празна, трябва да върнем NULL
        $value = trim($value);
        if(empty($value)) return NULL;

		$attr['target'] = '_blank';
		$attr['class'] = 'out';
		$value = HT::createLink($value, $value, FALSE, $attr);
        
		return $value;
	}
		
	
	/**
	 * Проверява и коригира въведения линк.
	 */
	function isValid($value)
	{ 
		$value = trim($value);
		$value = strtolower($value);
		
		if(!$value) return NULL;
		
		$this->findSheme($value);
		$url = $this->url;		
		
		if (!URL::isValidUrl($url)) {
    		$res['error'] = "Невалиден линк.";
    		
    		return $res;
    	}
    	
    	$isFtp = stripos($url, 'ftp://');
    	if ($isFtp !== FALSE) {
    		
    		$parsedFtp = parse_url($url);
    		$ftp = $parsedFtp['scheme'] . '://' . $parsedFtp['host'];
    		$ftpId = @ftp_connect($parsedFtp['host'], FALSE, 3);
    		
    	} else {
    		$arr = array('http' => array( 
		      'timeout' => 2)
	    	);
	    	
	    	stream_context_set_default ($arr);
	    	  
	    	$headers = @get_headers($url);
    	}
    	
    	if ((!$headers) && (!$ftpId)) {
    		$res['warning'] = "Линка, който сте въвели не може да бъде валидиран.";
    		
    		return $res;
    	}
    	
    	if ($headers) {
    		$explode = explode(' ',$headers[0], 3);
    	}
    	
    	$number = substr(trim($explode[1]), 0, 1);
    	if ($number == 4) {
    		$res['warning'] = "Възможен проблем с това URL.";
    		
    		return $res;
    	}
    	
        $res = parent::isValid($url);
        
        return $res;
	}
	
	
	/**
	 * Превръща URL-то от вербално представяне, към вътрешно представяне
	 */
	function fromVerbal($value)
	{	
		$res = self::isValid($value);
		
		if (!count($res)) {

			return $this->url;
		}
		
		return $value;
	}
	
	/**
	 * Връща цялото URL
	 */
	function findSheme($value)
	{
		$pattern = '/^\b[a-z]*\b:\/\//';
		preg_match($pattern, $value, $match);
		
		if (!count($match)) {
			$pattern = '/^\b[a-z]*\b./';
			preg_match($pattern, $value, $matchSub);
			$sheme = 'http';
			if (count($matchSub)) {
				$subDom = $matchSub[0];
				if ($subDom == 'ftp.') {
					$sheme = 'ftp';
				}
			}
			$sheme = $sheme . '://';
			$value = $sheme . $value;
			
		}
		
		$this->url = $value;
	}
	
}