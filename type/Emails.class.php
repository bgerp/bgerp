<?php



/**
 * Клас  'type_Emails' - Тип за много имейли
 *
 * Тип, който ще позволява въвеждането на много имейл-а в едно поле
 *
 *
 * @category  all
 * @package   type
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Emails extends type_Varchar {
    
    /**
     * Шаблон за разделяне на имейлите
     */
    static $pattern = '/[\s,:;\\\[\]\(\)\>\<]/';
    
    
    /**
     * Проверява зададената стойност дали е допустима за този тип.
     */
    function isValid($value)
    {
        //Ако няма въведено нищо връщаме резултата
        if (!str::trim($value)) return NULL;
        
        //Проверяваме за грешки
        $res = parent::isValid($value);
        
        //Ако има грешки връщаме резултатa
        if (count($res)) return $res;
        
        //Намираме всички имейли въведени в полето
        $emails = static::parse($value);
        
        if (empty($emails['valid'])) {
            $res['error'] = "Няма нито един валиден имейл";
        } elseif (!empty($emails['invalid'])) {
            //Ако сме открили сгрешени имейли ги визуализираме
            $res['warning'] = "Стойността не е валиден имейл: " . implode(', ', $emails['invalid']);
        }
        
        return $res;
    }
    
    
    /**
     * Преобразува полетата за многов човешки вид
     */
    function toVerbal_($str) {
        
        $char = '##';
        
        $str = trim($str);
        
        if (empty($str)) return NULL;
        
        $emails = static::parse($str);
        $TypeEmail = cls::get('type_Email');
        
        foreach ($emails['valid'] as $email) {
            $val[$email] = $TypeEmail->addHyperlink($email);
        }
        
        //Ако съществува поне един валиден имейл
        if (isset($val)) {
            $keys = array_map('mb_strlen', array_keys($val));
            array_multisort($keys, SORT_DESC, $val);
            $i = 0;
            
            foreach ($val as $key => $v) {
                $str = str_ireplace($key, $char . $i . $char, $str);
                $new[$i] = $v;
                ++$i;
            }
            $str = parent::escape($str);
            $length = count($new);
            
            for ($s = 0; $s < $length; $s++) {
                $str = str_ireplace($char . $s . $char, $new[$s], $str);
            }
            
            return $str;
        } else {
            $str = parent::escape($str);
            
            return "<font color='red'>{$str}</font>";
        }
    }
    
    
    /**
     * Преобразува стринг, съдържащ имейли към масив от валидни имейли.
     *
     * @param string $str
     * @return array масив от валидни имейли (възможно празен)
     */
    static function toArray($str)
    {
        $emails = static::parse($str);
        
        return $emails['valid'];
    }
    
    /**
     * Парсира стринг, съдържащ имейли
     *
     * @param string $str
     * @return array масив с два елемента-масиви:
     *     [valid] - масив от валидните имейли, съдържащи се в $str
     *  [invalid] - масив низове, които приличат на имейли, но не са валидни имейли
     */
    protected static function parse($str)
    {
        $str    = strtolower($str);
        $tokens = preg_split(self::$pattern, $str, NULL, PREG_SPLIT_NO_EMPTY);
        
        $result = array(
            'valid' => array(),
            'invalid' => array(),
        );
        
        // Инспектираме само частите, които приличат на имейл (т.е. - съдържат '@')
        foreach ($tokens as $tok) {
            if (strpos($tok, '@') !== FALSE) {
                $result[type_Email::isValidEmail($tok) ? 'valid' : 'invalid'][$tok] = $tok;
            }
        }
        
        return $result;
    }
}