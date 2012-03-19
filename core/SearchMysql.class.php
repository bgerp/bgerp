<?php



/**
 * Клас 'core_SearchMysql' - Генератор на MySQL заявка за пълнотекстово търсене
 *
 * Предполага, че таблицата в която се прави търсенето има две
 * полета 'searcht' и 'searchd', в които има само думи на латински, разделени
 * с интервал. Думите в 'searcht' имат по-висока тежест от тези в 'searchd'
 *
 *
 * @category  all
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>, SStefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_SearchMysql extends core_BaseClass
{
    
    
    /**
     * Парсираната заявка.
     *
     * Асоциативен масив - индексите са думите от заявката, стойностите са
     * техните тегла при определяне на рейтинга.
     */
    var $_query;
    
    
    /**
     * Парсира заявка за търсене и я записва във вътрешни за класа структури.
     *
     * Синтаксис на заявка за търсене
     * ==============================
     * Последователност от думи, някои от които могат да имат знак ^ (чавка)
     * отпред, което е маркиране на изключена дума.
     * ДУМА* е всяка последователност от букви на латиница, кирилица и/или цифри.
     *
     * @param string $str Заявката, както я пише потребителя
     */
    function init($params = array())
    {
        parent::init($params);
        
        // Обработка на кавичките - искаме да ги третираме като думи,
        // за това подсигуряваме, че са обградени от разделители.
        
        
        $str = strtolower(str_replace('"', ' " ', $this->filter));
        
        $this->_query = array();
        
        // Разбиваме заявката на думи
        if (!preg_match_all("/[a-zа-я0-9]+|\"|\^[a-zа-я0-9]+/", $str, $matches)) {
            return;
        }
        
        // Премахваме изключените думи
        $this->_excludedWords = array();
        
        foreach ($matches[0] as $i => $word) {
            if ($word{0} == '^') {
                $this->_excludedWords[] = substr($word, 1);
                unset($matches[0][$i]);
            }
        }
        
        $prevWord = '';
        
        foreach ($matches[0] as $i => $word) {
            if ($word != '"') {
                if ($weight = $this->getWordWeight($word)) {
                    if ((strlen($word) > 5 && $this->isLatin($word) || strlen($word) > 7 && $this->isCyrillic($word))) {
                        $this->_query["{$word}"] = $weight * 0.7;
                        $this->_query["{$word} "] = $weight * 0.3;
                    } else {
                        $this->_query["{$word} "] = $weight;
                    }
                }
                
                if ($prevWord && $prevWord != '"') {
                    if ($weight + $prevWeight) {
                        $this->_query["{$prevWord} {$word}"] = ($weight + $prevWeight) * 1.5;
                    }
                    
                    if ($this->isLatin($word) && $this->isLatin($prevWord)) {
                        $this->_query["{$prevWord}{$word}"] = 3;
                    }
                }
            }
            $prevWord = $word;
            $prevWeight = $weight;
        }
    }
    
    
    /**
     * Генерира SQL WHERE клауза, отговаряща на заявката за търсене.
     */
    function prepareSql($prefix = '')
    {
        $sqlRating = '0';
        
        if ($prefix) {
            $prefix = "`{$prefix}`.";
        }
        
        if ($this->_query["notitle"]) {
            foreach ($this->_query as $word => $weight) {
                if ($word != "notitle") {
                    $sqlRating .= "+ {$weight}*(CONCAT(' ', {$prefix}`searchD`, ' ') LIKE binary('% {$word}%'))";
                }
            }
        } else {
            foreach ($this->_query as $word => $weight) {
                $sqlRating .= "+ 1.2*{$weight}*(CONCAT(' ', {$prefix}`searchT`, ' ') LIKE binary('% {$word}%')) + " . "\n{$weight}*(CONCAT(' ', {$prefix}`searchD`, ' ') LIKE binary('% {$word}%'))";
            }
        }
        
        foreach ($this->_excludedWords as $word) {
            $sqlWhere .= ($sqlWhere ? " AND " : "") . "CONCAT(' ', {$prefix}`searchD`, ' ') NOT LIKE binary('% {$word} %') AND CONCAT(' ', {$prefix}`searchT`, ' ') NOT LIKE binary('% {$word} %')\n";
        }
        
        return array(
            $sqlRating,
            $sqlWhere
        );
    }
    
    
    /**
     * Проверява дали думата е изцяло съставена от букви на латиница
     *
     * @param string $word
     * @return bool
     */
    function isLatin($word)
    {
        return preg_match("/^[a-z]+$/i", $word);
    }
    
    
    /**
     * Проверява дали думата е изцяло съставена от букви на кирилица
     *
     * @param string $word
     * @return bool
     */
    function isCyrillic($word)
    {
        return preg_match("/^[а-я]+$/i", $word);
    }
    
    
    /**
     * Проверява дали заявката е от български думи, написани на латиница.
     *
     * @return bool
     */
    function isLatinBg()
    {
        /*
        
        Detect na bulgarski na latinica.
        
        SELECT *
        FROM `q_log`
        WHERE query
        LIKE '% za %' OR query
        LIKE "% na %" OR query
        LIKE "%programa%" OR query
        LIKE "%sait%" OR query
        LIKE "% s %" OR query
        LIKE "%filmi%" OR query
        LIKE "%snimki%" OR query
        LIKE "% ot %" OR query
        LIKE "% w %" OR query
        LIKE "% v %" OR query
        LIKE "% vav %"
        
        */
        
        /**
         * TODO: ...
         */
        
        return FALSE;
    }
    
    
    /**
     * Връща теглото на отделна дума - реално число между 0 и 1.
     *
     * Почти всички думи имат тегло 1. Според това, каква е вероятността
     * да се срещне дадена дума в произволен текст в сайта, някои думи могат
     * да имат и по-малко тегло. Най-често срещаните думи дори имат тегло
     * 0 (нула), което означава, че наличието им не променя резултата от
     * търсенето - той би бил същия дори тези думи да не се срещаха в текста
     * и/или в заявката.
     * Честотата на срещане на различните думи (респ. вероятността за срещането
     * им в произволен текст от сайта) е определена емпирично и точно тези данни
     * определят какви резултати връща този метод.
     *
     * @param string $word
     * @return float
     */
    function getWordWeight($word)
    {
        $weights = array(
            'за' => 0,
            'на' => 0,
            'в' => 0,
            'и' => 0,
            'програма' => 0,
            'download' => 0.6
        );
        
        if (isset($weights[$word])) {
            return $weights[$word];
        }
        
        if (strlen($word) < 3) return 0.4;
        
        if (strlen($word) < 4) return 0.6;
        
        if (strlen($word) < 6) return 0.8;
        
        return 1;
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
    function normalizeText($str)
    {
        $str = str::utf2ascii($str);
        $str = strtolower($str);
        $str = preg_replace('/[^a-z0-9]+/', ' ', " {$str} ");
        
        return $str;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getHiliteRegexp()
    {
        if (!isset($this->_hiliteRegexp)) {
            $this->_hiliteRegexp = "";
            
            foreach ($this->_query as $word => $_) {
                $this->_hiliteRegexp .= $pipe . trim($word);
                $pipe = "|";
            }
            $this->_hiliteRegexp = str_replace('_', '[a-zа-я0-9]', $this->_hiliteRegexp);
            $this->_hiliteRegexp = str_replace(' ', '[^a-zа-я0-9]+', $this->_hiliteRegexp);
            $this->_hiliteRegexp = "/({$this->_hiliteRegexp})/iU";
        }
        
        return $this->_hiliteRegexp;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function hiliteText($str, $prefix, $suffix)
    {
        if ($this->_query) {
            $str = substr(preg_replace($this->getHiliteRegexp(), "{$prefix}\\1{$suffix}", " {$str} "), 1, -1);
        }
        
        return $str;
        
        foreach ($this->_query as $word => $dd) {
            $word = str_replace('_', '.', trim($word));
            
            if (!$used[$word] && strpos(' ', $word) === FALSE) {
                $str = preg_replace("/([^a-zа-я0-9])({$word})([^a-zа-я0-9])/i", "\\1{$prefix}\\2{$suffix}\\3", $str);
                $used[$word] = TRUE;
            }
        }
        
        return substr($str, 1, -1);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function hiliteHtml($html, $prefix, $suffix)
    {
        // Извличаме от HTML кода парчета прост текст
        $textChunks = preg_split("/\s*?(<script.*>.*<\/script>|<textarea.*>.*<\/textarea>|" .
            "<style.*>.*<\/style>|<.*>|&[a-z]{2,};)\s*?/Usi",
            $html,
            -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);
        
        /**
         * $textChunk[$i][0] - фрагмент чист текст
         * $textChunk[$i][1] - началото на фрагмента в оригиналния текст
         */
        
        $offset = 0;
        
        for ($i = 0; $i < count($textChunks); $i++) {
            $origLength = strlen($textChunks[$i][0]);
            $textChunks[$i][0] = $this->hiliteText($textChunks[$i][0], $prefix, $suffix);
            $newLength = strlen($textChunks[$i][0]);
            
            if ($newLength != $origLength) {
                $html = substr($html, 0, $textChunks[$i][1] + $offset) .
                $textChunks[$i][0] .
                substr($html, $textChunks[$i][1] + $offset + $origLength);
                $offset += $newLength - $origLength;
            }
        }
        
        return $html;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getChar($str, $pos)
    {
        if ($pos < 0 || $pos >= strlen($str)) return "";
        
        return substr($str, $pos, 1);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getSubStr($str, $begin, $end)
    {
        $len = strlen($str);
        
        if ($begin < 0)
        $begin = 0;
        
        if ($end >= $len)
        $end = $len - 1;
        
        if ($begin >= $end)
        return "";
        
        return substr($str, $begin, $end - $begin + 1);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function hiliteHtml1($html, $prefix, $suffix, $maxLen = 0)
    {
        $lenHtml = strlen($html);
        
        $startHtml = -5;
        $startText = 0;
        $endHtml = -5;
        $endText = -5;
        
        for ($i = 0; $i < $lenHtml; $i++) {
            $c = $html{$i};
            
            //Начало на ХТМЛ или край на текста
            if ($c == "<") {
                $startHtml = $i;
                $endText = $i - 1;
                
                $c5 = strtolower($this->getSubStr($html, $i + 1, $i + 6));
                
                if ($c5 == "scrip") {
                    $paragraphBegin = $i;
                    
                    for (; $i < $lenHtml && (strtolower($this->getSubStr($html, $i + 1, $i + 10)) != "</script>"); $i++);
                    $i = $i + 9;
                }
                
                if ($c5 == "texta") {
                    $paragraphBegin = $i;
                    
                    for (; $i < $lenHtml && (strtolower($this->getSubStr($html, $i + 1, $i + 12)) != "</textarea>"); $i++);
                    $i = $i + 11;
                }
                
                if ($c5 == "style") {
                    $paragraphBegin = $i;
                    
                    for (; $i < $lenHtml && (strtolower($this->getSubStr($html, $i + 1, $i + 9)) != "</style>"); $i++);
                    $i = $i + 8;
                }
            }
            
            if ($c == ">") {
                $endHtml = $i;
                $startText = $i + 1;
            }
            
            if ($i == $lenHtml - 1) {
                $endHtml = $i;
                $endText = $i;
            }
            
            if ($startText >= 0 && $endText > 0) {
                $sub = $this->getSubStr($html, $startText, $endText);
                $hilite = $this->hiliteText($sub, $prefix, $suffix);
                $newHtml .= $hilite;
                $startText = -5;
                $endText = -5;
            }
            
            if ($startHtml >= 0 && $endHtml > 0) {
                $newHtml .= $this->getSubStr($html, $startHtml, $endHtml);
                $startHtml = -5;
                $endHtml = -5;
            }
        }
        
        return $newHtml;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function translateGoogleQuery($str)
    {
        $search = array(
            '/\s-([^\s])/'
        );
        $replace = array(
            ' ^\\1'
        );
        
        $str = preg_replace($search, $replace, $str);
        
        return $str;
    }
}