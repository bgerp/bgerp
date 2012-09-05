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
    
    
    /*
     * Константи за филтриране на списък с имейли
     */
    
    /**
     * Само валидни имейли
     * 
     * @var int
     */
    const VALID   = 1;
    
    
    /**
     * Само невалидни имейли
     * 
     * @var int
     */
    const INVALID = 2;
    
    
    /**
     * Всички "имейли" - валидни + невалидни
     * 
     * @var int
     */
    const ALL     = 0;
    
    
    /**
     * Превръща вербална стойност на списък имейли към вътрешно представяне
     */
    function fromVerbal($value)
    {
        $value = trim($value);
        
        $value = type_Email::replaceEscaped($value);

        if(empty($value)) return NULL;
  
        return $value;
    }


    /**
     * Проверява зададената стойност дали е допустима за този тип.
     */
    function isValid($value)
    {
        //Ако няма въведено нищо връщаме резултата
        if (!trim($value)) return NULL;
        
        //Проверяваме за грешки
        $res = parent::isValid($value);
        
        //Ако има грешки връщаме резултатa
        if (count($res)) return $res;

        //
        if (count($invalidEmails = self::getInvalidEmails($value))) {
            $res['error'] = parent::escape("Стойността не е валиден имейл: " . implode(', ', $invalidEmails));
        }
        
        return $res;
    }
    
    
    /**
     * Преобразува полетата за много имейли в човешки вид
     */
    function toVerbal_($str) 
    {
        //Тримваме полето
        $str = trim($str);
        
        //Ескейпваме стринга
        $str = parent::escape($str);
        
        //ако е празен, връщаме NULL
        if (empty($str)) return NULL;
        
        //Вземаме всички имейли
        $emails = self::toArray($str, self::ALL);
        
        //Инстанция към type_Email
        $TypeEmail = cls::get('type_Email');
        
        $links = array();
        
        foreach ($emails as $email) {
            
            $verbal = str_replace('@', " [аt] ", $email);
            
            if ((type_Email::isValidEmail($email)) || ($this->params['link'] != 'no')) {
                $links[] = $TypeEmail->addHyperlink($email, $verbal);
            } else {
                $links[] = $verbal;
            }
        }
        
        return implode(', ', $links);
    }
    
    
    /**
     * Преобразува стринг, съдържащ имейли към масив от имейли.
     *
     * @param string $str
     * @param int $only - кои "имейли" да върне:
     *         o ALL     - всички; 
     *         o VALID   - само валидните;
     *         o INVALID - само невалидните 
     * @return array масив от валидни имейли
     */
    static function toArray($str, $only = self::VALID)
    {
        //Масив с всички имейли
        $emailsArr = preg_split(self::$pattern, $str, NULL, PREG_SPLIT_NO_EMPTY);
        
        if ($only != self::ALL) {
            foreach ($emailsArr as $i=>$email) {
                if (type_Email::isValidEmail($email) != ($only == self::VALID)) {
                    unset($emailsArr[$i]);
                }
            }
            
            $emailsArr = array_values($emailsArr);
        }
                
        return $emailsArr;
    }
    
    
    /**
     * Връща всички невалидни имейли в стринга
     */
    static function getInvalidEmails($str)
    {
        return self::toArray($str, self::INVALID);
    }
}