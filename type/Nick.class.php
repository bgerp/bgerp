<?php



/**
 * Клас  'type_Nick' - Тип за никове
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
class type_Nick extends type_Varchar {
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    var $dbFieldLen = 64;
    

    /**
     * Параметър определящ максималната широчина на полето
     */
    var $maxFieldSize = 10;

    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal($value)
    {
        $value = parent::fromVerbal(trim($value));
        
        if($value === '') return NULL;
        
        $value = self::normalize($value);
        
        if (!$this->isValidNick($value, $this->params['allowEmail'])) {
            $this->error = 'Въвели сте недопустима стойност:|* ' . parent::escape($value);

            return FALSE;
        }
        
        return $value;
    }

    
    /**
     * Нормализира кейса и спец. символи в nika
     */
    public static function normalize($nick)
    {
        if(!strpos($nick, '@')) {
            $nick = trim(str_replace(array('  ', '. ', ' ', '__'), array(' ', '.', '_', '_'), $nick));
            $nick = str::toUpperAfter($nick);
            $nick = str::toUpperAfter($nick, '.');
            $nick = str::toUpperAfter($nick, '_');
        }

        return $nick;
    }
    
    
    /**
     * Проверява дали е валиден
     *
     * @param string $nick
     * @param boolean $allowEmail
     * 
     * @return boolean
     */
    public function isValidNick($value, $allowEmail = FALSE)
    {
        if($allowEmail && type_Email::isValidEmail($value)) {

            return TRUE;
        }

        // Шаблон за потребителско име. 
        // Позволени са малки и големи латински букви, цифри, долни черти и точки.
        // Трябва да започва с буква.
        // Между началото и края може да има букви, цифри и долни черти и точки. 
        // Трябва да завършва с буква или цифра.
        $pattern = "/^[a-z]{1}([a-z0-9\._]*)[a-z0-9]+$/i";

        if(!preg_match($pattern, $value)) {
            
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
     * Преобразува във вербална стойност
     * Прави никовете с първа главна буква и главна буква след точката и долна черта
     */
    function toVerbal($value)
    {
        $value = parent::toVerbal($value);
        
        $value = self::normalize($value);
        
        return $value;
    }
    
    
    /**
     * Конвертира текста във формат за показване на никове
     * Първа главна буква. След точката и долното тире пак главна буква.
     */
    static function convertValueToNick($value)
    {
        $value = trim($value);

        return $value;
        //Дължина на стринга
        $len = strlen($value);
        
        //Ако дължината е 0 връщаме
        if (!$len) return ;
        
        $nick = '';
        
        for ($i = 0; $i<$len; $i++) {
            //Текущата буква
            $char = $value{$i};
            
            //Ако е първата буква, или преди точка и долна черта
            if (($i == 0) || ($value{$i-1} == '.') || ($value{$i-1} == '_')) {
                //Номера в ASCII таблицата
                $lowChar = ord($value{$i});
                
                //Ако е малка латинска буква
                if (($lowChar >= 97) && ($lowChar <= 122)) {
                    //Изваждаме 32 за да получим голяма латинска буква
                    $bigChar = chr ($lowChar - 32);
                    $char = $bigChar;
                }
            }
            $nick .= $char;
        }
        
        return $nick;
    }
    
    
    /**
     * Връща локалната част на имейл-а
     */
    static function parseEmailToNick($value)
    {
        //Ако не е валиден имейл връща FALSE
        if (!type_Email::isValidEmail($value)) {

            return FALSE;
        }

        //Разделяме имейл-а на локална част и домейн
        $arr = explode('@', $value);
        
        //Вземаме локалната част
        $nick = strtolower($arr[0]);
        
        return $nick;
    }
}