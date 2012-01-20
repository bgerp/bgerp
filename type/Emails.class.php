<?php



/**
 * Клас  'type_Emails' - Тип за много имейли
 *
 * Тип, който ще позволява въвеждането на много имейла в едно поле
 *
 *
 * @category  ef
 * @package   type
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Emails extends type_Varchar {
    
    
    /**
     * Преобразува полетата за много мейли в човешки вид
     */
    function toVerbal_($str) {
        
        $char = '##';
        
        $str = trim($str);
        
        if (empty($str)) return NULL;
        $pattern = '/[\s,:;\\\[\]\(\)\>\<]/';
        $values = preg_split($pattern, $str, NULL, PREG_SPLIT_NO_EMPTY);
        
        foreach ($values as $value) {
            if (type_Email::isValidEmail($value)) {
                $typeEmail = cls::get('type_Email');
                $val[$value] = $typeEmail->addHyperlink($value);
            }
        }
        //Ако съществува поне един валиден меил
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
}