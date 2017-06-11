<?php


/**
 * Максимална дължина на думата до която ще се търси
 */
defIfNot('PLG_SEARCH_MAX_KEYWORD_LEN', '10');


/**
 * Клас 'plg_Search' - Добавя пълнотекстово търсене в табличния изглед
 *
 * Мениджърът, към който се закача този плъгин трябва да има пропърти
 * searchFields = "field1,field2,..." в които да са описани полетата за търсене
 * По пдоразбиране полето за търсене в филтер формата се казва 'search', 
 * да се смени името му трябва да се дефинира в съответния мениджър searchInputField
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_Search extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(&$mvc)
    {
        // Добавя поле за ключовите думи към обекта
        if (!isset($mvc->fields['searchKeywords'])) {
            $mvc->FLD('searchKeywords', 'text', 'caption=Ключови думи,notNull,column=none,single=none,input=none');
        }
        
        $fType = $mvc->getFieldType('searchKeywords');
        $fType->params['collate'] = 'ascii_bin';

        if(empty($mvc->dbEngine) && !$mvc->dbIndexes['search_keywords']) {
            $mvc->setDbIndex('searchKeywords', NULL, 'FULLTEXT');
        }

        // Как ще се казва полето за търсене, по подразбиране  е 'search'
        setIfNot($mvc->searchInputField, 'search');
    }
    
    
    /**
     * Извиква се преди запис в MVC класа. Генерира ключовите
     * думи за записа, които се допълват в полето searchKeywords
     */
    public static function on_BeforeSave($mvc, $id, $rec, &$fields=NULL)
    {
        if (!$fields || arr::haveSection($fields, $mvc->getSearchFields()) || ($fields == 'searchKeywords')) {
            if ($fields !== NULL) {
                $fields = arr::make($fields, TRUE);
                $fields['searchKeywords'] = 'searchKeywords';
            }
            
            $rec->searchKeywords = $mvc->getSearchKeywords($rec);
        }
    }
    
    
    /**
     * След подготовка на ключовите думи
     */
    public static function on_AfterGetSearchKeywords($mvc, &$searchKeywords, $rec)
    {
        if ($searchKeywords) return;
        
        $searchKeywords = self::getKeywords($mvc, $rec);
    }
    
    
    /**
     * Намира ключови думи
     */
    public static function getKeywords($mvc, $rec)
    {
        $searchKeywords = '';
        $searchFields = $mvc->getSearchFields();
        if (!empty($searchFields)) {
            $fieldsArr = $mvc->selectFields("", $searchFields);
            
            if (is_object($rec)) {
                $cRec = clone $rec;
                if ($cRec->id) {
                    $fullRec = $mvc->fetch($cRec->id);
                    foreach ($fieldsArr as $fieldName => $dummy) {
                        if (!isset($cRec->{$fieldName})) {
                            $cRec->{$fieldName} = $fullRec->{$fieldName};
                        }
                    }
                    
                }
            } elseif (is_numeric($rec)) {
                $cRec = $mvc->fetch($rec);
            }
            
            foreach($fieldsArr as $field => $fieldObj) {
                if(get_class($fieldObj->type) == 'type_Text') {
                    $searchKeywords .= ' ' . static::normalizeText($cRec->{$field});
                } else {
                    Mode::push('text', 'plain');
                    Mode::push('htmlEntity', 'none');
                    Mode::push('forSearch', TRUE);
                    
                    $verbalVal = $mvc->getVerbal($cRec, $field);
                    
                    if (!($fieldObj->type instanceof type_Varchar)) {
                        $verbalVal = type_Richtext::stripTags($verbalVal); 
                    }
            
                    $searchKeywords .= ' ' . static::normalizeText($verbalVal);
                    
                    Mode::pop('forSearch');
                    Mode::pop('htmlEntity');
                    Mode::pop('text');
                }
            }
        }
        
        return $searchKeywords;
    }

    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     * Добавя поле за пълнотекстово търсене
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC($mvc->searchInputField, 'varchar', 'placeholder=Търсене,caption=Търсене,input,silent,recently');
        
    	$data->listFilter->input(NULL, 'silent');
        
        $filterRec = $data->listFilter->rec;
        if ($filterRec->{$mvc->searchInputField}) {
            static::applySearch($filterRec->{$mvc->searchInputField}, $data->query);
            
            // Ако ключовата дума е число, търсим и по ид
            if (type_Int::isInt($filterRec->{$mvc->searchInputField}) && ($mvc->searchInId !== FALSE)) {
            	$data->query->orWhere($filterRec->{$mvc->searchInputField});
            }
        }
    }
    

    /**
     * Помощна функция за сортиране по дължина на думите
     */
    static function sortLength($a, $b)
    {
        return strlen($b)-strlen($a);
    }
   
    
    /**
     * Прилага търсене по ключови думи
     * 
     * @param string $search
     * @param core_Query $query
     * @param string $field
     */
    public static function applySearch($search, $query, $field = NULL, $strict = 2, $limit = NULL)
    {
        if(!$field) {
            $field = 'searchKeywords';
        }
        
        // Ако е зададен в конфига
        if (defined('PLG_SEARCH_MIN_LEN_FTS')) {
            $minLenFTS = PLG_SEARCH_MIN_LEN_FTS;
        }
        
        // Ако не е определен, но е зададен в конфига на mySQL
        if (!$minLenFTS) {
            try {
                if ($query->mvc->db) {
                    $minLenFTS = $query->mvc->db->getVariable('ft_min_word_len');
                }
            } catch (Exception $e) {
                reportException($e);
            }
        }
        
        // Ако все още не може да се определи - дефолтната стойност от документацията
        if(!$minLenFTS) {
            $minLenFTS = 4;
        }
        
        $wCacheArr = array();
        
        if ($words = static::parseQuery($search)) {
            
            usort($words, 'plg_Search::sortLength');
            
            foreach($words as $w) {
                
                $w = trim($w);
                
                // Предпазване от търсене на повтарящи се думи
                if (isset($wCacheArr[$w])) continue;
                $wCacheArr[$w] = $w;
                
                if(!$w) continue;
                
                $wordBegin = ' ';
                $wordEnd = '';
                $wordEndQ = '*';
                
                if($strict === TRUE ||(is_numeric($strict) && $strict > strlen($w))) {
                    $wordEnd = ' ';
                    $wordEndQ = '';
                }

                $mode = '+';

                if($w{0} == '"') {
                    $mode = '"';
                    $w = substr($w, 1);
                    if(!$w) continue;
                    $wordEnd = ' ';
                }  
                
                if($w{0} == '*') {
                    $wТ = substr($w, 1);
                    $wТ = trim($wТ);
                    if(!$wТ) continue;
                    $wordBegin = '';
                } 
                
                if($w{0} == '-') {
                    $w = substr($w, 1);
                    $mode = '-';


                    if(!$w) continue;
                    $wordEnd = ' ';
                    $like = "NOT LIKE";
                    $equalTo = " = 0";
                } else {

                    $like = "LIKE";
                    $equalTo = "";
                }
                
                // Ако няма да се търси точно съвпадение, ограничаваме дължината на думите
                if ($mode != '"') {
                    $maxLen = PLG_SEARCH_MAX_KEYWORD_LEN ? PLG_SEARCH_MAX_KEYWORD_LEN : 10;
                    $w = mb_substr($w, 0, $maxLen);
                }
                
                $w = trim(static::normalizeText($w, array('*')));
                $minWordLen = strlen($w);
                
                // Ако търсената дума е празен интервал
                $wTrim = trim($w);
                if (!strlen($wTrim)) continue;
                
                if(strpos($w, ' ')) {
                    
                    $mode = '"';
            
                    $wArr = explode(' ', $w);
                    $minWordLen = 0;
                    foreach($wArr as $part) {
                        $partLen = strlen($part);
                        $minWordLen = max($minWordLen, $partLen);
                    }
                }

                if(strpos($w, '*') !== FALSE) {
                    $w = str_replace('*', '%', $w);
                    $w = trim($w, '%');
                    $query->where("#{$field} {$like} '%{$wordBegin}{$w}{$wordEnd}%'");
                } else {
                    if($minWordLen <= $minLenFTS || !empty($query->mvc->dbEngine) || $limit > 0) {  
                        if($limit > 0 && $like == 'LIKE') {
                            $field1 =  "LEFT(#{$field}, {$limit})";
                        } else {
                            $field1 =  "#{$field}";
                        }
                        $query->where("LOCATE('{$wordBegin}{$w}{$wordEnd}', {$field1}){$equalTo}");
                    } else {
                        if($mode == '+') {
                            $query->where("match(#{$field}) AGAINST('+{$w}{$wordEndQ}' IN BOOLEAN MODE)");
                        }
                        if($mode == '"') {
                            $query->where("match(#{$field}) AGAINST('\"{$w}\"' IN BOOLEAN MODE)");
                        }
                        if($mode == '-') {
                            $query->where("LOCATE('{$w}', #{$field}) = 0");
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Нормализира текст, който е предмет на претърсване.
     *
     * Замества всички последователности от разделители с един единствен интервал
     * и прави всички букви в долен регистър (lower case).
     *
     * @param string $str
     * @param array $ignoreParamsArr
     * 
     * @return string
     */
    public static function normalizeText($str, $ignoreParamsArr = array())
    {
        $ignoreParamsArr = arr::make($ignoreParamsArr);
        
        if(strlen($str) > 32000) {

            static $maxLen;

            if(!$maxLen) {
                $conf = core_Packs::getConfig('core');
                
                // Максимално допустима дължина
                $maxLen = $conf->PLG_SEACH_MAX_TEXT_LEN;
            }
            
            // Ако стринга е над максимума вземаме част от началото и края му
            $str = str::limitLen($str, $maxLen);
        }
        
        $str = preg_replace('/[ ]+/', ' ', $str);

        $str = str::utf2ascii($str);
        
        $str = strtolower($str);
        $ignoreStr = '';
        
        if (!empty($ignoreParamsArr)) {
            foreach ($ignoreParamsArr as $ignore) {
                $ignoreStr .= preg_quote($ignore, '/');
            } 
        }
        
        $str = preg_replace("/[^a-z0-9{$ignoreStr}]+/", ' ', $str);
        
        return trim($str);
    }
    
    
    /**
     * Парсира заявка за търсене на отделни думи и фрази
     */
    public static function parseQuery($str, $latin = TRUE)
    {
        $str = trim($str);
        
        if(!$str) return FALSE;
        
        if($latin) {
            $str = str::utf2ascii($str);
        }

        $str = strtolower($str);
        
        $len = strlen($str);
        
        $quote = FALSE;
        $wordId = 0;
        $isWord = TRUE;
        
        for($i = 0; $i < $len; $i++) {
            
            $c = $str{$i};
            
            // Кога трябва да прибавим буквата
            if(($c != ' ' && $c != '"') || ($c == ' ' && $quote)) {
                
                if(($quote) && empty($words[$wordId])) {
                    $words[$wordId] = '"';
                }
                
                $words[$wordId] .= $c;
                continue;
            }
            
            // Кога трябва да се пробваме да започнем нова дума
            if($c == ' ' && !$quote) {
                if(strlen($words[$wordId])) {
                    $wordId++;
                    continue;
                }
            }
            
            // Кога трябва да отворим словосъчетание?
            if($c == '"' && !$quote) {
                $quote = TRUE;
                continue;
            }
            
            // Кога трябва да затворим словосъчетание?
            if($c == '"' && $quote) {
                $quote = FALSE;
                continue;
            }
        }

        return $words;
    }


    /**
     * Maркира текста, отговарящ на заявката
     */
    public static function highlight($text, $query, $class = 'document')
    {   
        $qArr = self::parseQuery($query, FALSE);
      
        if(is_array($qArr)) {
            foreach($qArr as $q) {
                if($q{0} == '-') continue;
                $q = trim(str_replace("'", "\\'", $q), '"');
                jquery_Jquery::run($text, "\n $('.{$class}').highlight('{$q}');", TRUE);
            }
        }

        return $text; 
    }


    /**
     * Генериране на searchKeywords когато плъгинът е ново-инсталиран на модел в който е имало записи
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        $i = 0;
        setIfNot($mvc->fillSearchKeywordsOnSetup, TRUE);
    	if($mvc->fillSearchKeywordsOnSetup !== FALSE && !$mvc->count("#searchKeywords != '' AND #searchKeywords IS NOT NULL")) {
            $query = $mvc->getQuery();
            while($rec = $query->fetch()) {
            	try{
            	    
            	    // Ако има полета от които да се генери ключ за търсене
                    if ($saveFields = $mvc->getSearchFields()) {
                        
                        // Към полетата, които ще се записват, добавяме и полето за търсене
                        $saveFields[] = 'searchKeywords';
                        
                        // Записваме само определени полета, от масива
                        $mvc->save($rec, $saveFields);
                        $i++;
                    }
                    
                } catch(core_exception_Expect $e) {
            		continue;
            	}
            }
        }

        if($i) {
            $res .= "<li style='color:green;'>Добавени са ключови думи за {$i} записа.</li>";
        }
    }

    
    /**
     * Полета, по които да се генерират ключове за търсене
     * 
     * @param core_Mvc $mvc
     * @param array $searchFieldsArr
     */   
    public static function on_AfterGetSearchFields($mvc, &$searchFieldsArr)
    {
        $searchFieldsArr = arr::make($mvc->searchFields);
    }
}
