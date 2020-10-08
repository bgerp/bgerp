<?php


/**
 * Добавя необходимите атрибути за работа със select2
 *
 * @category  bgerp
 * @package   selec2
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class select2_Adapter
{
    /**
     * Проверка дали може да се изполва select2
     *
     * @return bool
     */
    public static function canUseSelect2()
    {
        if ((strtolower(log_Browsers::getUserAgentOsName()) == 'android') && (strtolower(log_Browsers::getUserAgentBrowserName()) == 'safari')) {
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Добавя необходимите файлове и стартира скирпта
     *
     * @param core_Et           $tpl
     * @param string            $id
     * @param string|NULL       $placeHolder
     * @param bool              $allowClear
     * @param string|NULL|FALSE $lg
     * @param boolean $isTree
     */
    public static function appendAndRun(&$tpl, $id, $placeHolder = null, $allowClear = false, $lg = null, $ajaxUrl = '', $isTree = false)
    {
        if (!($tpl instanceof core_ET)) {
            
            return ;
        }
        
        if (is_null($lg)) {
            $lg = core_Lg::getCurrent();
        }
        
        self::appendFiles($tpl, $lg, $isTree);
        self::run($tpl, $id, $placeHolder, $allowClear, $lg, $ajaxUrl, $isTree);
    }
    
    
    /**
     * Добавя необходимите файлове за работа на select2
     *
     * @param core_Et           $tpl
     * @param string|NULL|FALSE $lg
     * @param boolean $isTree
     */
    public static function appendFiles(&$tpl, $lg = null, $isTree = false)
    {
        if (!($tpl instanceof core_ET)) {
            
            return ;
        }
        
        $select2Version = select2_Setup::get('VERSION');
        $tpl->push('select2/' . $select2Version . '/select2.min.css', 'CSS');
        $tpl->push('select2/' . $select2Version . '/select2.min.js', 'JS');
        
        if ($isTree) {
            $select2ToTreeVersion = select2_Setup::get('TOTREE_VERSION');
            $tpl->push('select2/toTree_' . $select2ToTreeVersion . '/select2totree.css', 'CSS');
            $tpl->push('select2/toTree_' . $select2ToTreeVersion . '/select2totree.js', 'JS');
        }
        
        // custom стилове за плъгина
        $tpl->push('select2/css/select2-custom.css', 'CSS');
        
        if ($lg !== false) {
            if (is_null($lg)) {
                $lg = core_Lg::getCurrent();
            }
            
            if (isset($lg)) {
                $lgPath = 'select2/' . $select2Version . '/i18n/' . $lg . '.js';
                if (getFullPath($lgPath)) {
                    $tpl->push($lgPath, 'JS');
                }
            }
        }
    }
    
    
    /**
     * Добавя скрипт, който да се стартира
     *
     * @param core_Et           $tpl
     * @param string            $id
     * @param string|NULL       $placeHolder
     * @param bool              $allowClear
     * @param string|NULL|FALSE $lg
     * @param boolean $isTree
     */
    public static function run(&$tpl, $id, $placeHolder = null, $allowClear = false, $lg = null, $ajaxUrl = '', $isTree = false)
    {
        if (!($tpl instanceof core_ET)) {
            
            return ;
        }
        
        if ($ajaxUrl) {
            $minimumResultsForSearch = 0;
        } else {
            $minimumResultsForSearch = mode::is('screenMode', 'narrow') ? select2_Setup::get('NARROW_MIN_SEARCH_ITEMS_CNT') : select2_Setup::get('WIDE_MIN_SEARCH_ITEMS_CNT');
        }
        
        if ($isTree) {
            $select2Str = "$('#" . $id . "').addClass('select2-src').select2ToTree({placeholder: '{$placeHolder}', allowClear: '{$allowClear}', language: '{$lg}', minimumResultsForSearch: {$minimumResultsForSearch}";
        } else {
            $select2Str = "$('#" . $id . "').addClass('select2-src').select2({placeholder: '{$placeHolder}', allowClear: '{$allowClear}', language: '{$lg}', minimumResultsForSearch: {$minimumResultsForSearch}";
        }
        
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
        
        $select2Str .= ',templateResult: formatSelect2Data,templateSelection: formatSelect2DataSelection, matcher: modelMatcher';
        
        $select2Str .= '});';
        
        jquery_Jquery::run($tpl, $select2Str, true);
        
        $tpl->push(('select2/js/adapter.js'), 'JS');
    }
    
    
    /**
     * Връща резултат за показване в AJAX формат
     * Показва резултата в JSON формат и вика shutdown()
     *
     * @param string $type
     * @param string $hnd
     * @param string $q
     * @param int    $maxSuggestions
     *
     * @return bool
     */
    public static function getAjaxRes($type, $hnd, $q, $maxSuggestions = 100)
    {
        if (!$hnd || !($sugg = unserialize(core_Cache::get($type, $hnd)))) {
            core_App::outputJson(array(
                (object) array('text' => tr('Липсват допълнителни опции'))
            ));
            
            return ;
        }
        
        $q = plg_Search::normalizeText($q);
        $q = '/[ \"\'\(\[\-\s]' . str_replace(' ', '.* ', $q) . '/';
        
        $resArr = array();
        
        $cnt = 0;
        
        $group = false;
        
        foreach ((array) $sugg as $key => $titleArr) {
            $isGroup = false;
            
            $titleArr = (array) $titleArr;
            
            $title = $titleArr['title'];
            $titleNormalized = $titleArr['id'];
            
            $attr = array();
            
            if ($key == '') {
                continue;
            }
            
            if (!isset($title->group) && $q && (!preg_match($q, ' ' . $titleNormalized))) {
                continue;
            }
            
            $sVal = new stdClass();
            $sVal->id = $key;
            
            if (is_object($title)) {
                $sVal->text = $title->title;
                
                $sVal->gElement = new stdClass();
                
                $sVal->gElement->className = $title->attr['class'];
                
                if ($title->group) {
                    $sVal->gElement->className .= ($sVal->gElement->className) ? ' ' : '';
                    $sVal->gElement->className .= 'group';
                    $sVal->group = true;
                    $sVal->gElement->group = true;
                    
                    $sVal->id = null;
                    $group = $sVal;
                    $isGroup = true;
                }
                
                if ($title->attr) {
                    $sVal->attr = $title->attr;
                }
            } else {
                $sVal->text = $title;
            }
            
            // Предпазва от добавяне на група без елементи в нея
            if ($isGroup && $group) {
                continue;
            }
            if (!$isGroup && $group) {
                $resArr[] = $group;
                $group = false;
            }
            
            $resArr[] = $sVal;
            
            $cnt++;
            
            if ($cnt >= $maxSuggestions) {
                break;
            }
        }
        
        core_App::outputJson($resArr);
    }
}
