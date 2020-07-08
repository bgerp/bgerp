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
 * Ако се зададе maxSearchKeywordLen може да се промени дефолтната дължина на стринга
 * за търсене зададена в PLG_SEARCH_MAX_KEYWORD_LEN
 *
 * @category  bgerp
 * @package   plg
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
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
        
        if (empty($mvc->dbEngine) && !$mvc->dbIndexes['search_keywords']) {
            $mvc->setDbIndex('searchKeywords', null, 'FULLTEXT');
        }
        
        // Как ще се казва полето за търсене, по подразбиране  е 'search'
        setIfNot($mvc->searchInputField, 'search');
    }
    
    
    /**
     * Извиква се преди запис в MVC класа. Генерира ключовите
     * думи за записа, които се допълват в полето searchKeywords
     */
    public static function on_BeforeSave($mvc, $id, $rec, &$fields = null)
    {
        $fieldArr = arr::make($fields, true);
        if (!$fields || arr::haveSection($fields, $mvc->getSearchFields()) || ($fields == 'searchKeywords') || array_key_exists('searchKeywords', $fieldArr)) {
            if ($fields !== null) {
                $fields = arr::make($fields, true);
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
        if ($searchKeywords) {
            
            return;
        }
        
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
            $fieldsArr = $mvc->selectFields('', $searchFields);
            
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
            
            foreach ($fieldsArr as $field => $fieldObj) {
                if (get_class($fieldObj->type) == 'type_Text') {
                    $searchKeywords .= ' ' . static::normalizeText($cRec->{$field});
                } else {
                    Mode::push('text', 'plain');
                    Mode::push('htmlEntity', 'none');
                    Mode::push('forSearch', true);
                    
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
        $data->listFilter->FNC($mvc->searchInputField, 'varchar', 'placeholder=Търсене,caption=Търсене,input,silent,recently,inputmode=search');
        
        $data->listFilter->input(null, 'silent');
        
        $filterRec = $data->listFilter->rec;
        if ($filterRec->{$mvc->searchInputField}) {
            static::applySearch($filterRec->{$mvc->searchInputField}, $data->query);
            
            // Ако ключовата дума е число, търсим и по ид
            if (type_Int::isInt($filterRec->{$mvc->searchInputField}) && ($mvc->searchInId !== false)) {
                $data->query->orWhere($filterRec->{$mvc->searchInputField});
            }
        }
    }
    
    
    /**
     * Помощна функция за сортиране по дължина на думите
     */
    public static function sortLength($a, $b)
    {
        $aT = static::normalizeText($a, array('*'));
        if (stripos($aT, ' ' ) !== false) {
            $aArr = explode(' ', $aT);
            usort($aArr, 'plg_Search::sortLength');
            if (isset($aArr[0])) {
                $a = $aArr[0];
            }
        }
        
        $bT = static::normalizeText($b, array('*'));
        if (stripos($bT, ' ' ) !== false) {
            $bArr = explode(' ', $bT);
            usort($bArr, 'plg_Search::sortLength');
            if (isset($bArr[0])) {
                $b = $bArr[0];
            }
        }
        
        return strlen($b) - strlen($a);
    }
    
    
    /**
     * Прилага търсене по ключови думи
     *
     * @param string     $search
     * @param core_Query $query
     * @param string     $field
     */
    public static function applySearch($search, $query, $field = null, $strict = 2, $limit = null)
    {
        if (!$field) {
            $field = 'searchKeywords';
        }
        
        $wCacheArr = array();
        $words = static::parseQuery($search);
        $query->mvc->invoke('AfterParseSearchQuery', array(&$words));

        if ($words) {
            usort($words, 'plg_Search::sortLength');
            
            $stopWordsCnt = $notStopWordsCnt = $shortWordsCnt = $longWordsCnt = 0;
            $shortWordLen = 4;
            
            foreach ($words as $w) {
                $w = trim($w);
                
                // Предпазване от търсене на повтарящи се думи
                if (isset($wCacheArr[$w])) {
                    continue;
                }
                $wCacheArr[$w] = $w;
                
                if (!$w) {
                    continue;
                }
                
                $wordBegin = ' ';
                $wordEnd = '';
                $wordEndQ = '*';
                
                if ($strict === true || (is_numeric($strict) && $strict > strlen($w))) {
                    $wordEnd = ' ';
                    $wordEndQ = '';
                }
                
                $mode = '+';
                
                $beginMark = false;
                if ($w{0} == '"') {
                    $mode = '"';
                    $w = substr($w, 1);
                    if (!$w) {
                        continue;
                    }
                    $wordEnd = ' ';
                    $beginMark = true;
                }
                
                if ($w{0} == '*') {
                    $wT = substr($w, 1);
                    $wT = trim($wT);
                    if (!$wT) {
                        continue;
                    }
                    $wordBegin = '';
                }
                
                if ($w{0} == '-') {
                    $w = substr($w, 1);
                    $mode = '-';
                    
                    
                    if (!$w) {
                        continue;
                    }
                    $wordEnd = ' ';
                    $like = 'NOT LIKE';
                    $equalTo = ' = 0';
                } else {
                    $like = 'LIKE';
                    $equalTo = '';
                }
                
                $w = trim(static::normalizeText($w, array('*')));
                
                // Ако търсената дума е празен интервал
                $wTrim = trim($w);
                if (!strlen($wTrim)) {
                    continue;
                }
                
                if (strpos($w, ' ')) {
                    $mode = '"';
                }
                
                // Ако няма да се търси точно съвпадение, ограничаваме дължината на думите
                if ($mode != '"') {
                    
                    // Колко е максималната дължина на стринга, гледа се първо в класа на заявката после дефолта за плъгина
                    $maxLen = null;
                    setIfNot($maxLen, $query->mvc->maxSearchKeywordLen, PLG_SEARCH_MAX_KEYWORD_LEN, 10);
                    $w = substr($w, 0, $maxLen);
                }
                
                if (strpos($w, '*') !== false) {
                    $w = str_replace('*', '%', $w);
                    $w = trim($w, '%');
                    $query->where("#{$field} {$like} '%{$wordBegin}{$w}{$wordEnd}%'");
                } else {
                    if (!$beginMark) {
                        // Разделяме думите по интервал и тогава ги преброяваме
                        $wArr = explode(' ', $w);
                    } else {
                        $wArr = array($w);
                    }
                    
                    foreach ($wArr as $wIn) {
                        if ($isStopWord = self::isStopWord($wIn)) {
                            $stopWordsCnt++;
                        } else {
                            $notStopWordsCnt++;
                        }
                        
                        $wLen = strlen($wIn);
                        if ($wLen < $shortWordLen) {
                            $shortWordsCnt++;
                        } else {
                            if (!$isStopWord || $beginMark) {
                                $longWordsCnt++;
                            }
                        }
                    }
                    
                    if (self::isStopWord($w) || !empty($query->mvc->dbEngine) || $limit > 0 || $query->dontUseFts) {
                        if ($limit > 0 && $like == 'LIKE') {
                            $field1 = "LEFT(#{$field}, {$limit})";
                        } else {
                            $field1 = "#{$field}";
                        }
                        $query->where("LOCATE('{$wordBegin}{$w}{$wordEnd}', {$field1}){$equalTo}");
                    } else {
                        if ($mode == '+') {
                            $query->where("MATCH(#{$field}) AGAINST('+{$w}{$wordEndQ}' IN BOOLEAN MODE)");
                        }
                        if ($mode == '"') {
                            $query->where("MATCH(#{$field}) AGAINST('\"{$w}\"' IN BOOLEAN MODE)");
                        }
                        if ($mode == '-') {
                            $query->where("LOCATE('{$w}', #{$field}) = 0");
                        }
                    }
                }
            }
            
            if (!$longWordsCnt && self::isBigTable($query)) {
                $query->isSlowQuery = true;
            }
        }
    }
    
    
    /**
     * Проверява дали таблицата е голяма, за да се използва разделяне на заявката
     *
     * @param core_Query $query
     *
     * @return bool
     */
    public static function isBigTable($query)
    {
        $mvc = $query->mvc;
        
        if (!$mvc) {
            
            return false;
        }
        
        $key = 'tableMaxId|' . $mvc->className;
        
        $maxId = core_Permanent::get($key);
        
        // Намираме максималното id на записа
        if (!isset($maxId) || ($maxId === false)) {
            $q = $mvc->getQuery();
            $q->XPR('maxId', 'int', 'max(#id)');
            $q->show('maxId');
            $qRec = $q->fetch();
            
            $maxId = $qRec->maxId;
            
            if (!isset($maxId)) {
                $maxId = 0;
            }
            
            core_Permanent::set($key, $maxId, 1000);
        }
        
        if ($maxId <= 1000000) {
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Проверява дали думите са изключени от FullText търсене
     *
     * @param string   $word
     * @param bool     $strict
     * @param NULL|int $strict
     *
     * @return bool
     */
    public static function isStopWord($word, $strict = false, $minLenFTS = null)
    {
        $word = trim($word);
        
        if (!isset($minLenFTS)) {
            $minLenFTS = self::getFTSMinWordLen();
        }
        
        if (strlen($word) < $minLenFTS) {
            
            return true;
        }
        
        $type = 'sqlStopWord';
        $handler = 'stopWords';
        $keepMinutes = 10000;
        $stopWordsArr = core_Cache::get($type, $handler, $keepMinutes);
        
        if (!$stopWordsArr) {
            $inc = getFullPath('core/data/sqlStopWords.inc.php');
            
            // Инклудваме го, за да можем да му използваме променливите
            include($inc);
            
            core_Cache::set($type, $handler, $stopWordsArr, $keepMinutes);
        }
        
        if (isset($stopWordsArr[$word])) {
            
            return true;
        }
        
        // Ако има интервал в думите и всички поотделно са stopWords - тогава приемаме целият израз за такъв
        if (strpos($word, ' ')) {
            $wArr = explode(' ', $word);
            $allIsStopWords = true;
            foreach ($wArr as $kWord) {
                if (!self::isStopWord($kWord, $strict, $minLenFTS)) {
                    $allIsStopWords = false;
                    
                    break;
                }
            }
            
            if ($allIsStopWords) {
                
                return true;
            }
        }
        
        // Ако няма да се търси точната дума, гледаме и думите, които започват с подадения стринг
        if (!$strict) {
            $all = false;
            if (strpos($word, '*') !== false) {
                $word = str_replace('*', '%', $word);
                $all = true;
            }
            
            $pattern = '/^' . preg_quote($word, '/') . '/i';
            
            if ($all) {
                $pattern = str_replace('%', '.*', $pattern);
            }
            
            if (preg_grep($pattern, $stopWordsArr)) {
                
                return true;
            }
        }
        
        return false;
    }
    
    
    /**
     * Нормализира текст, който е предмет на претърсване.
     *
     * Замества всички последователности от разделители с един единствен интервал
     * и прави всички букви в долен регистър (lower case).
     *
     * @param string $str
     * @param array  $ignoreParamsArr
     *
     * @return string
     */
    public static function normalizeText($str, $ignoreParamsArr = array())
    {
        $ignoreParamsArr = arr::make($ignoreParamsArr);
        
        if (strlen($str) > 32000) {
            static $maxLen;
            
            if (!$maxLen) {
                $conf = core_Packs::getConfig('core');
                
                // Максимално допустима дължина
                $maxLen = $conf->PLG_SEACH_MAX_TEXT_LEN;
            }
            
            // Ако стринга е над максимума вземаме част от началото и края му
            $str = str::limitLen($str, $maxLen);
        }
        
        $str = preg_replace('/[ ]+/', ' ', $str);
        
        $str = str::utf2ascii($str);
        if ($str) {
            $iConvStr = @iconv('UTF-8', 'ASCII//TRANSLIT', $str);
            if (isset($iConvStr)) {
                $str = $iConvStr;
            }
        }
        
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
    public static function parseQuery($str, $latin = true)
    {
        $str = trim($str);
        
        if (!$str) {
            
            return false;
        }
        
     /*   if ($latin) {
            $str = str::utf2ascii($str);
            $iConvStr = @iconv('UTF-8', 'ASCII//TRANSLIT', $str);
            if (isset($iConvStr)) {
                $str = $iConvStr;
            }
        } */
        
        $str = mb_strtolower($str);
        
        $len = strlen($str);
        
        $quote = false;
        $wordId = 0;
        $isWord = true;
        
        for ($i = 0; $i < $len; $i++) {
            $c = $str{$i};
            
            // Кога трябва да прибавим буквата
            if (($c != ' ' && $c != '"') || ($c == ' ' && $quote)) {
                if (($quote) && empty($words[$wordId])) {
                    $words[$wordId] = '"';
                }
                
                $words[$wordId] .= $c;
                continue;
            }
            
            // Кога трябва да се пробваме да започнем нова дума
            if ($c == ' ' && !$quote) {
                if (strlen($words[$wordId])) {
                    $wordId++;
                    continue;
                }
            }
            
            // Кога трябва да отворим словосъчетание?
            if ($c == '"' && !$quote) {
                $quote = true;
                continue;
            }
            
            // Кога трябва да затворим словосъчетание?
            if ($c == '"' && $quote) {
                $quote = false;
                continue;
            }
        }
        
        return $words;
    }
    
    
    /**
     * Маркира текста, отговарящ на заявката
     */
    public static function highlight($text, $query, $class = 'document')
    {
        $qArr = self::parseQuery($query);
        
        if (is_array($qArr)) {
            foreach ($qArr as $q) {
                if ($q{0} == '-') {
                    continue;
                }
                $q = trim($q);
                $q = json_encode($q);
                jquery_Jquery::run($text, "\n $('.{$class}').highlight({$q});", true);
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
        setIfNot($mvc->fillSearchKeywordsOnSetup, true);
        if ($mvc->fillSearchKeywordsOnSetup !== false && !$mvc->count("#searchKeywords != '' AND #searchKeywords IS NOT NULL")) {
            try {
                $query = $mvc->getQuery();
                while ($rec = $query->fetch()) {
                    try {
                        
                        // Ако има полета от които да се генери ключ за търсене
                        if ($saveFields = $mvc->getSearchFields()) {
                            
                            // Към полетата, които ще се записват, добавяме и полето за търсене
                            $saveFields[] = 'searchKeywords';
                            
                            // Записваме само определени полета, от масива
                            $mvc->save($rec, $saveFields);
                            $i++;
                        }
                    } catch (core_exception_Expect $e) {
                        continue;
                    }
                }
            } catch (Exception $e) {
                reportException($e);
            }
        }
        
        if ($i) {
            $res .= "<li style='color:green;'>Добавени са ключови думи за {$i} записа.</li>";
        }
    }
    
    
    /**
     * Полета, по които да се генерират ключове за търсене
     *
     * @param core_Mvc $mvc
     * @param array    $searchFieldsArr
     */
    public static function on_AfterGetSearchFields($mvc, &$searchFieldsArr)
    {
        $searchFieldsArr = arr::make($mvc->searchFields);
    }
    
    
    /**
     * Функция за проверка на свалените имейли
     * Ако хеша го няма - предизвиква сваляне
     *
     * @param string $emlStatus
     */
    public static function callback_repairSerchKeywords($clsName)
    {
        $clsName = 'lab_Hora';
        $pKey = $clsName . '|repairSearchKeywords';
        
        if (!cls::load($clsName, true)) {
            
            log_System::add(get_called_class(), "Регенериране на ключови думи: липсващ клас {$clsName}", null, 'debug', 1);
            
            return;
        }
        
        $clsInst = cls::get($clsName);
        
        $maxTime = dt::addSecs(40);
        
        $kVal = core_Permanent::get($pKey);
        
        $query = $clsInst->getQuery();
        
        if (isset($kVal)) {
            $query->where(array("#id < '[#1#]'", $kVal));
        }
        
        $cnt = $query->count();
        
        $clsInst->logDebug("Начало на регенериране на ключови думи за {$clsName} за {$cnt} записа преди id<{$kVal}");
        
        if (!$cnt) {
            if (!is_null($kVal)) {
                core_Permanent::set($pKey, $kVal, 200);
            } else {
                core_Permanent::remove($pKey);
            }
            
            $clsInst->logDebug('Приключи регенерирането на ключови думи');
            
            return ;
        }
        $callOn = dt::addSecs(55);
        core_CallOnTime::setCall('plg_Search', 'repairSerchKeywords', $clsName, $callOn);
        
        $query->orderBy('id', 'DESC');
        
        $isFirst = true;
        
        $query->limit(10000);
        
        $lastId = $kVal;
        
        try {
            while ($rec = $query->fetch()) {
                
                if (dt::now() >= $maxTime) {
                    break;
                }
                
                if ($isFirst) {
                    $clsInst->logDebug("Регенериране на ключови думи от {$rec->id}");
                    $isFirst = false;
                }
                
                $lastId = $rec->id;
                
                try {
                    $generatedKeywords = $clsInst->getSearchKeywords($rec);
                    if ($generatedKeywords == $rec->searchKeywords) {
                        
                        continue;
                    }
                    
                    $rec->searchKeywords = $generatedKeywords;
                    
                    $clsInst->save_($rec, 'searchKeywords');
                } catch (Exception $e) {
                    reportException($e);
                } catch (Throwable  $e) {
                    reportException($e);
                }
            }
        } catch (Exception $e) {
            reportException($e);
            if (is_null($lastId)) {
                
                return ;
            }
        } catch (Throwable  $e) {
            reportException($e);
            if (is_null($lastId)) {
                
                return ;
            }
        }
        
        $clsInst->logDebug('Регенерирани ключови думи до id=' . $lastId);
        
        core_Permanent::set($pKey, $lastId, 1000);
    }
    
    
    /**
     * Връща дефолтната стойност на ft_min_word_len
     *
     * @param NULL|core_Query $query
     * @param int             $def
     *
     * @return int
     */
    protected static function getFTSMinWordLen($query = null, $def = 4)
    {
        static $minLenFTS;
        
        if (isset($minLenFTS)) {
            
            return $minLenFTS;
        }
        
        // Ако е зададен в конфига
        if (defined('PLG_SEARCH_MIN_LEN_FTS')) {
            $minLenFTS = PLG_SEARCH_MIN_LEN_FTS;
        }
        
        // Ако не е определен, но е зададен в конфига на mySQL
        if (isset($query) && !$minLenFTS) {
            try {
                if ($query->mvc->db) {
                    $minLenFTS = $query->mvc->db->getVariable('ft_min_word_len');
                }
            } catch (Exception $e) {
                reportException($e);
            }
        }
        
        // Ако все още не може да се определи - дефолтната стойност от документацията
        if (!$minLenFTS) {
            $minLenFTS = $def;
        }
        
        return $minLenFTS;
    }
    
    
    /**
     * Форсира ръчно обновяване на ключовите думи на модела
     * (и на контейнера му ако е документ)
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function forceUpdateKeywords($mvc, $rec)
    {
        $fRec = $mvc->fetch("id = {$rec->id}", '*', false);
        $rec->searchKeywords = $mvc->getSearchKeywords($fRec);
        
        $mvc->save_($fRec, 'searchKeywords');
        if($fRec->containerId){
            doc_Containers::update_($fRec->containerId);
        }
    }
}
