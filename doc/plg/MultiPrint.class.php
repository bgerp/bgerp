<?php


/**
 * Клас 'doc_plg_MultiPrint'
 *
 * Плъгин за  принтиране на няколко копия на даден документ
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_plg_MultiPrint extends core_Plugin
{
	/**
     * След инициализирането на модела
     * 
     * @param core_Mvc $mvc
     * @param core_Mvc $data
     */
    public static function on_AfterDescription($mvc)
    {
        // Проверка за приложимост на плъгина към зададения $mvc
        static::checkApplicability($mvc);
    }
    
    
	/**
     * Проверява дали този плъгин е приложим към зададен мениджър
     * 
     * @param core_Mvc $mvc
     * @return boolean
     */
    protected static function checkApplicability($mvc)
    {
        // Прикачане е допустимо само към наследник на core_Manager ...
        if (!$mvc instanceof core_Manager) {
            return FALSE;
        }
        
        // ... към който е прикачен doc_DocumentPlg
        $plugins = arr::make($mvc->loadList);

        if (isset($plugins['doc_DocumentPlg'])) {
            return FALSE;
        } 
        
        return TRUE;
    }
    
    
	/**
     * Дупликираме шаблона, колкото пъти е зададено, в режим принтиране
     */
	static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	if(Mode::get('printing')) {
    		$originalTpl = clone($tpl);
    		$tpl=new ET('');
    		$copiesNum = count($mvc->printParams);
    		if(!$copiesNum)
    			$copiesNum = '2';
	    	for($i = 1; $i <= $copiesNum; $i++) {
	    			
	    			// Ако сме в режим принтиране, добавяме копие на ордера
		    		$clone = clone($originalTpl);
		    		
		    		// Добавяме зададените параметри в $mvc->printParams, най отгоре на документа
		    		if(count($mvc->printParams) > 0) {
		    			$paramET = '';
		    			foreach($mvc->printParams[$i-1] as $param) {
		    				$paramET .= $param . " &nbsp;&nbsp;&nbsp;";
		    			}
		    			$clone->prepend(new ET("<span style='margin-left:24px'>[#paramET#]</span>"));
		    			$clone->replace($paramET, 'paramET');
		    		}
		    		
		    		// Контейнер в който ще вкараме документа + шаблона с параметрите му
		    		$container= new ET("<div style='margin:0px;float:left;padding:0px;min-width:0px;border-bottom:1px dotted black'>[#clone#]</div>");
		    		$container->replace($clone,'clone');
		    		$tpl->append($container);
		    		
		    		// добавяме print-page-break на всеки  2 документа
		    		//@TODO да се добавя автоматино с Jquery при нужда
		    		if($i%2==0)
	    				$tpl->append("<div class='print-page-break'></div>");
	    			$tpl->removeBlocks();
	    	}
    	}
    }
}