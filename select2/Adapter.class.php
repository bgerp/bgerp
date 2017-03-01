<?php


/**
 * Добавя необходимите атрибути за работа със select2
 * 
 * @category  bgerp
 * @package   selec2
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class select2_Adapter
{
    
    
    /**
     * Проверка дали може да се изполва select2
     * 
     * @return boolean
     */
    public static function canUseSelect2()
    {
        if ((strtolower(log_Browsers::getUserAgentOsName()) == 'android' ) && (strtolower(log_Browsers::getUserAgentBrowserName()) == 'safari')) return FALSE;
        
        return TRUE;
    }
    
    
    /**
     * Добавя необходимите файлове и стартира скирпта
     * 
     * @param core_Et $tpl
     * @param string $id
     * @param string|NULL $placeHolder
     * @param boolean $allowClear
     * @param string|NULL|FALSE $lg
     */
    public static function appendAndRun(&$tpl, $id, $placeHolder=NULL, $allowClear=FALSE, $lg=NULL, $ajaxUrl = '')
    {
        if (!($tpl instanceof core_ET)) return ;
        
        if (is_null($lg)) {
            $lg = core_Lg::getCurrent();
        }
        
        self::appendFiles($tpl, $lg);
        self::run($tpl, $id, $placeHolder, $allowClear, $lg, $ajaxUrl);
    }
    
    
    /**
     * Добавя необходимите файлове за работа на select2
     * 
     * @param core_Et $tpl
     * @param string|NULL|FALSE $lg
     */
    public static function appendFiles(&$tpl, $lg=NULL)
    {
        if (!($tpl instanceof core_ET)) return ;
        
        $conf = core_Packs::getConfig('select2');
        
        $tpl->push('select2/' . $conf->SELECT2_VERSION . "/select2.min.css", "CSS");
        $tpl->push('select2/' . $conf->SELECT2_VERSION . "/select2.min.js", "JS");

        // custom стилове за плъгина
        $tpl->push('select2/css/select2-custom.css', "CSS");
        
        if ($lg !== FALSE) {
            if (is_null($lg)) {
                $lg = core_Lg::getCurrent();
            }
            
            if (isset($lg)) {
                $lgPath = 'select2/' . $conf->SELECT2_VERSION . '/i18n/' . $lg . '.js';
                if (getFullPath($lgPath)) {
                    $tpl->push($lgPath, "JS");
                }
            }
        }
    }
    
    
    /**
     * Добавя скрипт, който да се стартира
     * 
     * @param core_Et $tpl
     * @param string $id
     * @param string|NULL $placeHolder
     * @param boolean $allowClear
     * @param string|NULL|FALSE $lg
     */
    public static function run(&$tpl, $id, $placeHolder=NULL, $allowClear=FALSE, $lg=NULL, $ajaxUrl = '')
    {
        if (!($tpl instanceof core_ET)) return ;
        
        if ($ajaxUrl) {
        	$minimumResultsForSearch = 0;
    	} else {
    	    $conf = core_Packs::getConfig('select2');
    	    $minimumResultsForSearch = mode::is('screenMode', 'narrow') ? $conf->SELECT2_NARROW_MIN_SEARCH_ITEMS_CNT : $conf->SELECT2_WIDE_MIN_SEARCH_ITEMS_CNT;
    	}
    	
        $select2Str = "
        
        $('#" . $id . "').addClass('select2-src').select2({placeholder: '{$placeHolder}', allowClear: '{$allowClear}', language: '{$lg}', minimumResultsForSearch: {$minimumResultsForSearch}";
        
        if ($ajaxUrl) {
            $select2Str .= ",ajax: {
    			url: '" . $ajaxUrl . "',
    			
    			delay: 500,
    			
    			dataType: 'json',
    			
    			data: function (params) {
    				return {
    					q: params.term
					};
    			},
    			
    			processResults: function (data, params) {
    				return {
    					results: data
					};
    			},
    			
    			cache: true
    		},
    		
    		minimumInputLength: 0";
        }
        
        $select2Str .= ",templateResult: formatSelect2Data,templateSelection: formatSelect2DataSelection";
        	        
        $select2Str .= "});";
        
        jquery_Jquery::run($tpl, $select2Str, TRUE);
        
        $tpl->push(('select2/js/adapter.js'), 'JS');
    }
    
    
    /**
     * Връща резултат за показване в AJAX формат
     * Показва резултата в JSON формат и вика shutdown()
     * 
     * @param string $type
     * @param string $hnd
     * @param string $q
     * @param integer $maxSuggestions
     * @return boolean
     */
    public static function getAjaxRes($type, $hnd, $q, $maxSuggestions = 100)
    {
        if (!$hnd || !($sugg = unserialize(core_Cache::get($type, $hnd)))) {
        
            core_App::getJson(array(
                (object)array('text' => tr('Липсват допълнителни опции'))
            ));
        	
            return ;
        }
        
        $q = plg_Search::normalizeText($q);
        $q = '/[ \"\'\(\[\-\s]' . str_replace(' ', '.* ', $q) . '/';
        
        $resArr = array();
        
        $cnt = 0;
        
        $group = FALSE;
        
        foreach ((array)$sugg as $key => $titleArr) {
            $isGroup=FALSE;
        	
            $titleArr =  (array) $titleArr;

            $title = $titleArr['title'];
            $titleNormalized = $titleArr['id'];
        	
            $attr = array();
        	
            if ($key == '') continue;
        	
            if (!isset($title->group) && $q && (!preg_match($q, ' ' . $titleNormalized))) continue;
        	
            $sVal = new stdClass();
            $sVal->id = $key;
        	
            if (is_object($title)) {
                $sVal->text = $title->title;
        		
                $sVal->element = new stdClass();
        		
                $sVal->element->className = $title->attr['class'];
        		
                if ($title->group) {
        			
                    $sVal->element->className .= ($sVal->element->className) ? ' ' : '';
                    $sVal->element->className .= 'group';
                    $sVal->group = TRUE;
                    $sVal->element->group = TRUE;
        			
                    $sVal->id = NULL;
                    $group = $sVal;
                    $isGroup = TRUE;
                }
        		
                if ($title->attr) {
                    $sVal->attr = $title->attr;
                }
            } else {
                $sVal->text = $title;
            }
        	
            // Предпазва от добавяне на група без елементи в нея
            if ($isGroup && $group) continue;
            if (!$isGroup && $group) {
                $resArr[] = $group;
                $group = FALSE;
            }
        	
            $resArr[] = $sVal;
        	
            $cnt++;
        	
            if ($cnt >= $maxSuggestions) break;
        }
        
        core_App::getJson($resArr);
    }
}
