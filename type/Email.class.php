<?php



/**
 * Клас  'type_Email' - Тип за имейл
 *
 * Има валидираща функция
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Email extends type_Varchar
{
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    public $dbFieldLen = 80;
    
    
    /**
     * Инициализиране на типа
     * Задава, че в базата имейлите ще са case-insensitive
     */
    public function init($params = array())
    {
        setIfNot($params['params']['ci'], 'ci');
        
        parent::init($params);
    }
    
    
    /**
     * Превръща вербална стойност с имейл към вътрешно представяне
     */
    public function fromVerbal($value)
    {
        $value = trim($value);
        
        $value = static::replaceEscaped($value);

        if (empty($value)) {
            return;
        }
                
        if (!$this->isValidEmail($value)) {
            $this->error = 'Некоректен имейл';
            
            return false;
        }
            
        return $value;
    }



    /**
     * Замества низове, които се използват за скриване на ймейл адресите от ботовете
     */
    public static function replaceEscaped($value)
    {
        $from = array('<at>', '[at]', '(at)', '{at}', ' at ', ' <at> ',
            ' [at] ', ' (at) ', ' {at} ');
        $to = array('@', '@', '@', '@', '@', '@', '@', '@', '@');
        
        $value = str_ireplace($from, $to, $value);
        
        $from = array('<dot>', '[dot]', '(dot)', '{dot}', ' dot ',
            ' <dot> ', ' [dot] ', ' (dot) ', ' {dot} ');
        $to = array('.', '.', '.', '.', '.', '.', '.', '.', '.');
        
        $value = str_ireplace($from, $to, $value);

        return $value;
    }
    
    
    /**
     * Добавя атрибут за тип = email, ако изгледа е мобилен
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        if (Mode::is('screenMode', 'narrow') && empty($attr['type'])) {
            $attr['type'] = 'email';
        }
        
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Проверява дали е валиден имейл
     */
    public static function isValidEmail($email)
    {
        if (!strlen($email)) {
            return;
        }
        
        if (preg_match('/[\\000-\\037]/', $email)) {
            
            return false;
        }
        
        $pattern = "/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])" .
        "[-a-z0-9.]+(?<![-.])\.[a-z]{2,}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?$/iD";
        
        if (!preg_match($pattern, $email)) {
            
            return false;
        }
        
        if ((mb_stripos($email, '@fax.man') === false) && (!core_Url::isValidTld($email))) {
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Преобразува имейл-а в човешки вид
     */
    public function toVerbal($email)
    {
        if (empty($email)) {
            return;
        }
        
        $email = self::removeBadPart($email);
        
        $cu = core_Users::getCurrent();
        if (!haveRole('user') && !Mode::is('text', 'plain') && ($cu != -1)) {
            $verbal = str_replace('@', ' [аt] ', $email);
        } else {
            $verbal = $email;
        }

        if (Mode::is('text', 'plain') || Mode::is('htmlEntity', 'none')) {
            $verbal = $email;
        } elseif ($this->params['link'] != 'no') {
            $verbal = $this->addHyperlink($email, $verbal);
        } else {
            $verbal = str_replace('@', '&#64;', $email);
        }

        return $verbal;
    }
    
    
    /**
     * Премахва "лошата" част от имейла
     *
     * @param string $email
     * @param array  $email
     *
     * @return string
     */
    public static function removeBadPart($email, $removeArr = array('+', '='))
    {
        if (!$email) {
            
            return $email;
        }
        
        static $emailsArr = array();
        
        if (isset($emailsArr[$email])) {
            
            return $emailsArr[$email];
        }
        
        list($emailUser, $domain) = explode('@', $email);
        
        foreach ($removeArr as $r) {
            if (($rPos = mb_strpos($emailUser, $r)) !== false) {
                $emailUser = mb_substr($emailUser, 0, $rPos);
            }
        }
        
        $emailsArr[$email] = implode('@', array($emailUser, $domain));
        
        return $emailsArr[$email];
    }
    
    
    /**
     * Превръща имейлите в препратка за изпращане на имейл
     */
    public function addHyperlink_($email, $verbal)
    {
        if (Mode::is('text', 'html') || !Mode::is('text')) {
            list($user, $domain) = explode('@', $email);
            $domain = '&#64;' . $domain;
            
            $attr = array();
            ht::setUniqId($attr);
            $spanId = $attr['id'];
            $value = "<span id='{$spanId}'>{$verbal}</span>";
            $value .= "<script>$('#{$spanId}').html(\"<a href='mailto:{$user}\" + \"{$domain}'><span style='display:none;'>{$verbal}</span>\" + \"{$user}\" + \"{$domain}</a>\");</script>";
        } else {
            $value = $verbal;
        }
        
        return $value;
    }
    
    
    /**
     * Извлича домейна (частта след `@`) от имейл адрес
     *
     * @param  string $value имейл адрес
     * @return string
     */
    public static function domain($value)
    {
        list(, $domain) = explode('@', $value, 2);
        
        $domain = empty($domain) ? false : trim($domain);
        
        $domain = rtrim($domain, '\'"<>;,');
        
        return $domain;
    }

    
    /**
     * Връща масив от всички под-стрингове, които
     * приличат на е-имейл адреси от дадения стринг
     */
    public static function extractEmails($string)
    {
        preg_match_all('/[=\+\/\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i', $string, $matches);
        
        if (is_array($matches[0])) {
            foreach ($matches[0] as $id => $eml) {
                if (!self::isValidEmail($eml)) {
                    unset($matches[0][$id]);
                }
            }
        }

        return $matches[0];
    }
}
