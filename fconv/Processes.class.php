<?php

defIfNot('FCONV_HANDLER_LEN', 8);
/**
 * Показва стартираните процеси
 * @category   Experta Framework
 * @package    fconv
 * @author     Yusein Yuseinov
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n
 * @since      v 0.1
 */
class fconv_Processes extends core_Manager
{
	/**
	 * Заглавие на модула
	 */
	var $title = "Стартирани процеси";
	
	
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
        $this->FLD( "processId", "varchar(" . FCONV_HANDLER_LEN . ")",
        	array('notNull' => TRUE, 'caption' => 'Манипулатор'));
        	
        $this->FLD("start", "type_Datetime", 'notNull, caption=Време на стартиране');
        
        $this->FLD("script", "blob(70000)", 'caption=Скрипт');

        $this->FLD("timeOut", "int", array('notNull' => TRUE, 'caption' => 'Продължителност'));
        
		$this->FLD("callBack", "varchar(128)", array('caption' => 'Функция'));
	}
	
	
	/**
	 * Екшън за http callback от ОС скрипт
	 * Получава управлението от шел скрипта и взема резултата от зададената функция.
	 * Ако е TRUE тогава изтрива записите от таблицата за текущото конвертиране и
	 * съответната директория.
	 */
	function act_CallBack()
	{	
		if (!(gethostbyname($_SERVER['SERVER_NAME']) == '127.0.0.1') || !(isDebug())) {
			exit(1);
		}
		$pid = Request::get('pid');
		$func = Request::get('func');
		$rec = self::fetch(array("#processId = '[#1#]'", $pid));
		if (!is_object($rec)) {
			exit (1); 
		}
		$script = unserialize($rec->script);
		$funcArr = explode('::', $func);
		$object = cls::get($funcArr[0]);
		$method = $funcArr[1];
		$result = call_user_func_array(array($object, $method), array($script));
		if ($result) {
			if ($this->deleteDir($script->tempDir)) {
				fconv_Processes::delete("#processId = '{$pid}'");
				
				return TRUE;
			}
		}
	}
	
	
	/**
	 * Изтрива директорията
	 */
	function deleteDir($dir) 
	{ 
   		if (substr($dir, strlen($dir)-1, 1) != '/') {
   			$dir .= '/'; 	
   		}
    	
		if ($handle = opendir($dir)) { 
			while ($obj = readdir($handle)) { 
				if ($obj != '.' && $obj != '..') { 
					if (is_dir($dir.$obj)) { 
						if (!deleteDir($dir.$obj))
						
							return false; 
						} elseif (is_file($dir.$obj)) { 
							if (!unlink($dir.$obj)) {
								
								return false;	
							}
						} 
					} 
				} 
			closedir($handle); 
	
			if (!@rmdir($dir)) {
				
				return false; 	
			}
	        
			return true; 
		} 
		
		return false; 
	}
	
}