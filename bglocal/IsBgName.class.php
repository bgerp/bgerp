<?php



/**
 * Клас 'bglocal_IsBgName' -
 *
 *
 * @category  vendors
 * @package   bglocal
 * @author    Gabriela Petrova 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class bglocal_IsBgName extends core_Manager
{
    
    
    
    /**
     * Статично зареждане на 
     * @var string
     */
    static $bgNames = "";
    
    function act_Test()
    {
    	$name = "ahah";
    	self:: foundName($name);
    }
    
    /**
     * По зададен стринг, проверява в списък с имена
     * дали стринга не е българско име
     */
    function foundName($name)
    {

      if (static::$bgNames == ""){
      	static::$bgNames = file_get_contents('/var/www/ef_root/vendors/bglocal/data/bgLadiesNamesLatin.txt') . file_get_contents('/var/www/ef_root/vendors/bglocal/data/bgSurNamesLatin.txt'); 
      }
      
      $isName = strpos(static::$bgNames, "\n$name\n");

      if ($isName === FALSE) {
      	$res = $name. " не е българско име";
      } else {
      	$res = $name. " е българско име";
      }

      return $res;
    }

}