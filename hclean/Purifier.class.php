<?php


/**
 * Вкарваме файловете необходими за работа с програмата.
 */
require_once 'purifier4.3.0/HTMLPurifier.standalone.php';


/**
 * Папка за съхранение на временните файлове
 */
defIfNot('PURIFIER_TEMP_PATH', EF_TEMP_PATH . '/purifer');


/**
 * Клас 'hclean_Purifier' - Пречистване на HTML
 *
 * @category   Experta Framework
 * @package    hclean
 * @author	   Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 * @link	   https://github.com/bgerp/vendors/issues/10
 * 
 */
class hclean_Purifier
{
	
	
	/**
	 * Изпълнява се при създаване на инстанция на класа.
	 */
	function init()
	{
		$this->mkdir();
	}
	
	
	/**
	 * Изчиства HTML кода от зловреден код (против XSS атаки)
	 */
	function clean($html)
	{
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache', 'SerializerPath', PURIFIER_TEMP_PATH);
		$config->set('Core', 'Encoding', 'UTF-8');
		
		$purifier = new HTMLPurifier($config);
		
		$clear = $purifier->purify($html);
		
		return $clear;
	}
	
	
	/**
	 * Създава директорията нужна за работа на системата
	 */
	function mkdir()
	{
		
		if(!is_dir(PURIFIER_TEMP_PATH)) {
            if( !mkdir(PURIFIER_TEMP_PATH, 0777, TRUE) ) {
              	expect('Не може да се създаде директорията необходима за работа на HTML Purifier');
            } 
		}
	}
	
}