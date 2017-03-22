<?php


/**
 * Плъгин за хифинация
 * 
 * @category  vendors
 * @package   hyphen
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hyphen_Plugin extends core_Plugin
{
    
    
	/**
     * Минималната дължина на стринга, над която ще се хифенира стринга
     */
    const TRANSFER_WORD_MIN_LENGTH = 32;
    
    
	/**
     * Минималната дължина след която ще се добавя знак за хифенация
     */
    const MIN_LENGTH_HYPHEN = 12;
    
    
    /**
     * Максималната дължина след която ще се добавя (задължително) знак за хифенация
     */
    const MAX_LENGTH_HYPHEN = 28;
    
    
    /**
     * Прихваща извикването на hyphenText
     * Хифенира подадения текст, като добавя <wbr>
     */
    function on_AfterToHtml($mvc, &$html)
    {
        // Ако сме в текстов режим, връщаме
        if (Mode::is('text', 'plain')) return ;
        
        // Ако сме в широк режим и не сме в дебъг, връщаме
        if (!Mode::is('screenMode', 'narrow')) return ;
        
        // Шаблона, за намиране, на думите, които ще хифинираме
        $pattern = "/(?'html'\<[^\>]*\>)|(?'space'[\s]+)|(?'words'[^\s\<]{" . static::TRANSFER_WORD_MIN_LENGTH .",})/iu";
        
        // Хифенираме
        $html = preg_replace_callback($pattern, array($this, '_catchHyphen'), $html);
    }
    
    
    /**
     * Прихваща извикването на _catchHyphen
     * 
     * @param array $match
     */
    static function _catchHyphen($match)
    {
        // Ако хваната чиста дума за хифениране
        if ($match['words']) {
            
            // Хифенираме
            return static::getHyphenWord($match['words']);
        }
        
        // Връщаме целия текст, без обработка
        return $match[0];
    }
    
    
	/**
     * Хифенира стринговете
     *
	 * @param string $string
	 * @param integer $minLen
	 * @param integer $maxLen
	 * @param string $hyphenStr
	 * 
	 * @return string
	 */
    static function getHyphenWord($string, $minLen = self::MIN_LENGTH_HYPHEN, $maxLen = self::MAX_LENGTH_HYPHEN, $hyphenStr = "<wbr>")
    {
        // Брояча за сивмовилите
        $i = 0;
        
        // За циклене по стринга
        $p = 0;
        
        // Резултатния стринг
        $resStr = '';
        
        // Текущия символ в итерацията
        $currChar = '';
        
        // Предишния символ
        $prevChar = '';
        
        // Дължината на стринга
        $len = strlen($string);
        
        // Срещане на ентити 
        $entity = 0;
        
        // Обхождаме всички символи
        while('' != ($char = core_String::nextChar($string, $p))) {
            
            // Вземаме предишния символ
            $prevChar = $currChar;
            
            // Вземаме текущия символ
            $currChar = $char;
            
            // Флаг, дали да се добавя знак за хифенация
            $addHyphen = FALSE;
            
            // Увеличаваме брояча
            $i++;
            
            // Ако предишния символ е начало на ентити
            if (($prevChar == '&') || ($entity)) {
                
                // Увеличаваме му брояча
                $entity++;
            }
            
            // Ако предишния символ е край на entity
            if ($prevChar == ';') {
                
                // Нулираме му брояча
                $entity = 0;
                
                // Вдигаме влага за добавяне на хифенация
                $addHyphen = TRUE;
            }
            
            // Ако брояча е под първия минимум или сме в края или сме вътре в ентити
            if (($i <= $minLen) || ($p == $len) || ($entity && $entity < 10)) {
                
                // Добавяме символа
                $resStr .= $currChar;
                
                continue;
            }
            
            // Нулираме брояча за ентити
            $entity = 0;
            
            // Ако текущия символ е начало на ентити
            if ($currChar == '&') {
                
                // Вдигаме влага за добавяне на хифенация
                $addHyphen = TRUE;
            }
            
            // Ако сегашния символ е съгласна, а предишния не е съгласна
            if (core_String::isConsonent($currChar) && !core_String::isConsonent($prevChar)) {
                
                // Вдигаме влага за добавяне на хифенация
                $addHyphen = TRUE;
            }
            
            // Ако предишния символ не е съгласна и не е гласна - не е буква
            // Текущия символ трябва също да е буква
            if ((!core_String::isConsonent($prevChar)) && (!core_String::isVowel($prevChar))
                            && ((core_String::isConsonent($char)) || (core_String::isVowel($char)))) {
                
                // Вдигаме влага за добавяне на хифенация
                $addHyphen = TRUE;
            }
            
            // Ако флага все още не е вдигнат
            if (!$addHyphen) {
                    
                // Ако брояча е над втория допустим праг, задължително вдигаме флага
                if ($i > $maxLen) {
                    
                    // Вдигаме влага за добавяне на хифенация
                    $addHyphen = TRUE;
                    
                }
            }
            
            // Ако флага е вдигнат
            if ($addHyphen) {
//                $resStr .= "&#173;" . $char; // Знак за softHyphne
                $resStr .= $hyphenStr . $char;
                
                // Нулираме брояча
                $i = 0;
            } else {
                
                // Добавяме символа
                $resStr .= $currChar;
            }
            
            continue;
        }
        
        return $resStr;
    }
}
