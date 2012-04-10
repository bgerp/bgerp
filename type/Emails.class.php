<?php



/**
 * Клас  'type_Emails' - Тип за много имейли
 *
 * Тип, който ще позволява въвеждането на много имейл-а в едно поле
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
     * Шаблон за разделяне на имейлите
     */
    static $pattern = '/[\s,;]/';
    
    
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

        //
        if (count($invalidEmails = self::getInvalidEmails($value))) {
            
            $res['error'] = "Стойността не е валиден имейл: " . implode(', ', $invalidEmails);
        }
        
        return $res;
    }
    
    
    /**
     * Преобразува полетата за много имейли в човешки вид
     */
    function toVerbal_($str) 
    {
        //Тримваме полето
        $str = str::trim($str);
        
        //Ескейпваме стринга
        $str = parent::escape($str);
        
        //ако е празен, връщаме NULL
        if (empty($str)) return NULL;
        
        //Вземаме всички имейли
        $emails = self::splitEmails($str);
        
        //Инстанция към type_Email
        $TypeEmail = cls::get('type_Email');
        
        foreach ($emails as $email) {
            
            //Линковете на имейлите
            $link = $TypeEmail->addHyperlink($email);
            
            //Резултата, който ще се върне
            $res .= ($res) ? ', '. $link : $link;
            
        }
        
        return $res;
    }
    
    
    /**
     * Преобразува стринг, съдържащ имейли към масив от валидни имейли.
     *
     * @param string $str
     * @return array масив от валидни имейли
     */
    static function toArray($str)
    {
        //Масив с всички имейли
        $emailsArr = self::splitEmails($str);
        
        return $emailsArr;
    }
    
    
    /**
     * Разделяме имейлите в масив
     */
    static function splitEmails($str)
    {
        //Всички имейли в малък регистър
        //$str = strtolower($str);  
        
        //Масив с всикчи имейли
        $emailsArr = preg_split(self::$pattern, $str, NULL, PREG_SPLIT_NO_EMPTY);
        
        return  $emailsArr;
    }
    
    
    /**
     * Връща всички невалидни имейли в стринга
     */
    static function getInvalidEmails($str) {
        
        //Масив с всички имейли
        $emailsArr = self::splitEmails($str);
        
        //Невалидни имейли
        $invalidEmailsArr = array(); 
        
        foreach ($emailsArr as $email) {
            
            //Ако стойността не е валиден имейл, тогава го добавяме в масива с невалидни имейли
            if (!type_Email::isValidEmail($email)) {
                $invalidEmailsArr[] = $email;
            }
        }  
        
        return $invalidEmailsArr;
    }
}