<?php



/**
 * Клас 'bgerp_ProtoWrapper' - Плъгин прототин на всички wrapper-и
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class bgerp_protoWrapper  extends core_Plugin
{
	
	
	/**
	 * Преобразува var $tabMenu от Wrappera в масив
	 * 
	 * @param unknown_type $invoker
	 */
    function prepareArr($invoker)
    {
		//За всеки плъгин намерен в класа, взимаме var $tabMenu
		foreach ($invoker->_plugins as $plgName=>$plgProparty){
			$menu = $plgProparty->tabMenu;
		}
	
		//От стринга съдържащ менюто правим един един 
		//тримерен масив[името на таба][името на класа][екшън]
        foreach (arr::make($menu) as $tabName => $cName){
    		list ($className, $actName) = explode('::', $cName);
    		$wrapperArr[$tabName][$className][$actName] = "Php модул";
    		
    	}
  	return $wrapperArr;
	}
	
	
	/**
	 * Рендира опаковката
	 * 
	 * @param unknown_type $invoker
	 * @param unknown_type $tplPlg
	 * @param unknown_type $arr
	 */
    function rend($invoker, &$tplPlg, $arr)
    {
    	  //Зареждаме класа на табовете 
  	      $tabs = cls::get('core_Tabs');
  	      
  	      //Взимаме масива
  	      $wrapperArr = $this->prepareArr($invoker);
  	
  	    //Обикаляме по масива и правим табовете  
  	   foreach($wrapperArr as $tabName=>$class){
  		   foreach($class as $cName=>$actName){
  			   foreach($actName as $aName=>$modeName){
  			      $tabs->TAB(trim($cName), trim($tabName));
  			   /*if(method_exists($cName, 'act_'.$aName)){
  			      $tabs->TAB(trim($cName), trim($tabName), $aName);
  			   	}
  			    else $tabs->TAB(trim($cName), trim($tabName));*/
  			   	
  			   }
  		   }
  	   }
  	
  	$tplPlg = $tabs->renderHtml($tplPlg, $invoker->currentTab ?  $invoker->tabName : $invoker->className);
  	
  	
  	$tplPlg->append(tr($invoker->title) .tr($modeName). " « ", 'PAGE_TITLE');
 
    }
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, $tpl)
    {
	 
 	  self::rend($invoker, $tpl, $wrapperArr);
    }
}