<?php



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
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_Search extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        // Добавя поле за ключовите думи към обекта
        if (!isset($mvc->fields['searchKeywords'])) {
            $mvc->FLD('searchKeywords', 'text', 'caption=Ключови думи,notNull,column=none, input=none');
        }
        
        // Как ще се казва полето за търсене, по подразбиране  е 'search'
        setIfNot($mvc->searchInputField, 'search');
    }
    
    
    /**
     * Извиква се преди запис в MVC класа. Генерира ключовите
     * думи за записа, които се допълват в полето searchKeywords
     */
    function on_BeforeSave($mvc, $id, $rec, $fields=NULL)
    {
        if(!$fields || arr::haveSection($fields, $mvc->getSearchFields())) {

            $rec->searchKeywords = $mvc->getSearchKeywords($rec);
        }
    }
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterGetSearchKeywords($mvc, &$searchKeywords, $rec)
    {
        if($searchKeywords) return;
        
        $searchKeywords = self::getKeywords($mvc, $rec);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getKeywords($mvc, $rec)
    {
        $searchKeywords = '';
        $searchFields = $mvc->getSearchFields();
        if (!empty($searchFields)) {
            $fieldsArr = $mvc->selectFields("", $searchFields);
            
            if (is_object($rec)) {
                $cRec = clone $rec;
            } elseif (is_numeric($rec)) {
                $cRec = $mvc->fetch($rec);
            }
            
            foreach($fieldsArr as $field => $fieldObj) {
                if(get_class($fieldObj->type) == 'type_Text') {
                    $searchKeywords .= ' ' . static::normalizeText($cRec->{$field});
                } else {
                    Mode::push('text', 'plain');
                    $searchKeywords .= ' ' . static::normalizeText(strip_tags($mvc->getVerbal($cRec, $field)));
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
    function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC($mvc->searchInputField, 'varchar', 'placeholder=Търсене,caption=Търсене,input,silent,recently');
        
    	$data->listFilter->input(null, 'silent');
        
        $filterRec = $data->listFilter->rec;
        if ($filterRec->{$mvc->searchInputField}) {
            static::applySearch($filterRec->{$mvc->searchInputField}, $data->query);
        }
    }
       
    
    static function applySearch($search, $query, $field = 'searchKeywords')
    {
        if ($words = static::parseQuery($search)) {
            foreach($words as $w) {
                
                $w = trim($w);
                
                if(!$w) continue;
                
                $wordBegin = ' ';
                $wordEnd = '';

                if($w{0} == '"') {
                    $w = substr($w, 1);
                    if(!$w) continue;
                    $wordEnd = ' ';
                }  
                
                if($w{0} == '*') {
                    $w = substr($w, 1);
                    if(!$w) continue;
                    $wordBegin = '';
                } 
                
                if($w{0} == '-') {
                    $w = substr($w, 1);
                    
                    if(!$w) continue;
                    $like = "NOT LIKE";
                } else {
                    $like = "LIKE";
                }
                
                $w = static::normalizeText($w);
                $w = str_replace('*', '%', $w);
               
                $query->where("#{$field} {$like} '%{$wordBegin}{$w}{$wordEnd}%'");
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
     * @return string
     */
    static function normalizeText($str)
    {
        $conf = core_Packs::getConfig('core');
        
        // Максимално допустима дължина
        $maxLen = $conf->PLG_SEACH_MAX_TEXT_LEN;
        
        // Ако стринга е над максимума вземаме част от началото и края му
        $str = str::limitLen($str, $maxLen);
        
        // Ако стринга е над максимума
//        if (mb_strlen($str) > $maxLen) {
//            
//            // Вземаме 
//            $str = mb_substr($str, 0, $maxLen);
//        }
        
        $str = str::utf2ascii($str);
        
        $str = strtolower($str);
        $str = preg_replace('/[^a-z0-9\*]+/', ' ', $str);
        
        return trim($str);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function parseQuery($str, $latin = TRUE)
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
    static function highlight($text, $query)
    {  
    	jquery_Jquery::run($text, "\n $('.document').highlight('{$query}');", TRUE);
    	
        return $text; 
    }


    /**
     * Генериране на searchKeywords когато плъгинът е ново-инсталиран на модел в който е имало записи
     */
    function on_AfterSetupMVC($mvc, &$res)
    {
        $i = 0;
    	if(!$mvc->count("#searchKeywords != '' AND #searchKeywords IS NOT NULL")) {
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
    function on_AfterGetSearchFields($mvc, &$searchFieldsArr)
    {
        $searchFieldsArr = arr::make($mvc->searchFields);
    }
}
