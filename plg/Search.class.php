<?php



/**
 * Клас 'plg_Search' - Добавя пълнотекстово търсене в табличния изглед
 *
 * Мениджърът, към който се закача този плъгин трябва да има пропърти
 * searchFields = "field1,field2,..." в които да са описани полетата за търсене
 *
 *
 * @category  all
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
        if (!$mvc->fields['searchKeywords']) {
            $mvc->FLD('searchKeywords', 'text', 'caption=Ключови думи,notNull,column=none, input=none');
        }
    }
    
    
    /**
     * Извиква се преди запис в MVC класа. Генерира ключовите
     * думи за записа, които се допълват в полето searchKeywords
     */
    function on_BeforeSave($mvc, $id, $rec)
    {
        $rec->searchKeywords = static::getKeywords($mvc, $rec);
    }
    
    
    static function getKeywords($mvc, $rec)
    {
        $searchKeywords = '';
        
        if (!empty($mvc->searchFields)) {
            $fieldsArr = $mvc->selectFields("", $mvc->searchFields);
            
            foreach($fieldsArr as $field => $fieldObj) {
                $searchKeywords .= ' ' . static::normalizeText(strip_tags($mvc->getVerbal($rec, $field)));
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
        $data->listFilter->FNC('search', 'varchar', 'placeholder=Търсене,caption=Търсене,input,silent,recently');
    }
    
    
    /**
     * Изпълнява се преди извличането на записите от базата данни
     * Добавя условие в $query за пълнотекстово търсене
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->listFilter->input(null, 'silent');
        
        $filterRec = $data->listFilter->rec;
        
        if($filterRec->search) {
            
            $words = $this->parseQuery($filterRec->search);
            
            if($words) {
                foreach($words as $w) {
                    
                    $w = trim($w);
                    
                    if(!$w) continue;
                    
                    if($w{0} == '"') {
                        $exact = ' ';
                        $w = substr($w, 1);
                        
                        if(!$w) continue;
                    } else {
                        $exact = '';
                    }
                    
                    if($w{0} == '-') {
                        $w = substr($w, 1);
                        
                        if(!$w) continue;
                        $like = "NOT LIKE";
                    } else {
                        $like = "LIKE";
                    }
                    
                    $w = $this->normalizeText($w);
                    
                    $data->query->where("#searchKeywords {$like} '% {$w}{$exact}%'");
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
     * @return string
     */
    static function normalizeText($str)
    {
        $str = str::utf2ascii($str);
        $str = strtolower($str);
        $str = preg_replace('/[^a-zа-я0-9]+/', ' ', " {$str} ");
        
        return trim($str);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function parseQuery($str)
    {
        $str = trim($str);
        
        if(!$str) return FALSE;
        
        $str = str::utf2ascii($str);
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
}