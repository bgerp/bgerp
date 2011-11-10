<?php
/**
 * Плъгин за създаване на линкове от полета, които са ключове на fileman
 * 
 * @category   Experta Framework
 * @package    plg
 * @author     Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 */
class plg_KeyToLink extends core_Plugin
{
	
	
	/**
	 * Създава линк от инстанции на кейлист, ако имат параметър hyperlink
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		foreach ($mvc->fields as $key => $value) {
	        if (($value->type instanceof type_Keylist) OR ($value->type instanceof type_Key)) {
	        	if ($value->hyperlink) {
	        		$keys[] = $value->name;
	        	}
	        	
	        }
		}
		
		if (count($keys)) {
			foreach ($keys as $value) {
				if ($row->$value) {
					$val = $row->$value;
					if (stristr($val, '<a') === FALSE) {
						if (method_exists($mvc,'getSingleLink')) {
							$row->$value = '';
							$vals = type_Keylist::toArray($rec->$value);
							if (count($vals)) {
								foreach ($vals as $keyD) {
									$row->$value .= $mvc->getSingleLink($keyD);
								}
							}
						}
					}
				}
			}
		}
	}
}