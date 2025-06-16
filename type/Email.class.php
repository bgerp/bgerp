<?php


/**
 * Клас  'type_Email' - Тип за имейл
 *
 * Има валидираща функция
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
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
        setIfNot($params['params']['inputmode'], 'email');
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

        if (!$this->params['showOriginal']) {
            $value = email_AddressesInfo::getEmail($value);
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

        if (!$this->params['showOriginal']) {
            $value = email_AddressesInfo::getEmail($value);
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

        if (!$this->params['showOriginal']) {
            $emailOrig = $email;
            $email = email_AddressesInfo::getEmail($email);
            if (trim($email) != trim($emailOrig)) {
                $email .= " ({$emailOrig})";
            }
        }

        $cu = core_Users::getCurrent();
        if (!haveRole('user') && !Mode::is('text', 'plain') && ($cu != -1)) {
            $verbal = str_replace('@', ' [аt] ', $email); //CyrLat
        } else {
            $verbal = $email;
        }

        if (Mode::is('text', 'plain') || Mode::is('htmlEntity', 'none')) {
            $verbal = $email;
        } elseif ($this->params['link'] != 'no') {
            if($this->params['maskVerbal']){
                $verbal = str::maskEmail($email);
            }
            $verbal = $this->addHyperlink($email, $verbal);
        } else {
            if($this->params['maskVerbal']){
                $email = str::maskEmail($email);
            }
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
            $value .= "<script> document.getElementById('{$spanId}').innerHTML = \"<a href='mailto:{$user}\" + \"{$domain}'><span style='display:none;'>{$verbal}<\/span>\" + \"{$user}\" + \"{$domain}<\/a>\";</script>";
        } else {
            $value = $verbal;
        }
        
        return $value;
    }
    
    
    /**
     * Извлича домейна (частта след `@`) от имейл адрес
     *
     * @param string $value имейл адрес
     *
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
        preg_match_all('/[=\+\/\._a-zA-Z0-9-\']+@[\._a-zA-Z0-9-]+/i', $string, $matches);

        if (is_array($matches[0])) {
            foreach ($matches[0] as $id => $eml) {
                if (!self::isValidEmail($eml)) {
                    unset($matches[0][$id]);
                }
            }
        }
        
        return $matches[0];
    }

    /**
     * de-obfuscation на имейли в HTML текст
     */
    public static function deobfuscateEmails($html) 
    {
        // 1. Декодиране на HTML entities
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // 2. Премахване на HTML коментари
        $text = preg_replace('/<!--.*?-->/s', '', $text);
        
        // 3. Заменяне на най-често използваните obfuscation техники за @ символа
        $atReplacements = [
            // В квадратни скоби
            '/\s*\[at\]\s*/i' => '@',
            '/\s*\[a\]\s*/i' => '@',
            '/\s*\[@\]\s*/i' => '@',
            // В къдрави скоби
            '/\s*\{at\}\s*/i' => '@',
            '/\s*\{a\}\s*/i' => '@',
            '/\s*\{@\}\s*/i' => '@',
            // В кръгли скоби
            '/\s*\(at\)\s*/i' => '@',
            '/\s*\(a\)\s*/i' => '@',
            '/\s*\(@\)\s*/i' => '@',
             // В остри скоби
            '/\s*\<at\>\s*/i' => '@',
            '/\s*\<a\>\s*/i' => '@',
            '/\s*\<@\>\s*/i' => '@',
            // Със специални символи
            '/\s*\|at\|\s*/i' => '@',
            '/\s*\*at\*\s*/i' => '@',
            '/\s*#at#\s*/i' => '@',
            '/\s*&at&\s*/i' => '@',
        ];
        
        // Заменяне на обфускации за точката
        $dotReplacements = [
            // В квадратни скоби
            '/\s*\[dot\]\s*/i' => '.',
            '/\s*\[d\]\s*/i' => '.',
            '/\s*\[\.\]\s*/i' => '.',
            // В къдрави скоби  
            '/\s*\{dot\}\s*/i' => '.',
            '/\s*\{d\}\s*/i' => '.',
            '/\s*\{\.\}\s*/i' => '.',
            // В кръгли скоби
            '/\s*\(dot\)\s*/i' => '.',
            '/\s*\(d\)\s*/i' => '.',
            '/\s*\(\.\)\s*/i' => '.',
            // В остри скоби
            '/\s*\<dot\>\s*/i' => '.',
            '/\s*\<d\>\s*/i' => '.',
            '/\s*\<\.\>\s*/i' => '.',
            // Със специални символи
            '/\s*\|dot\|\s*/i' => '.',
            '/\s*\*dot\*\s*/i' => '.',
            '/\s*#dot#\s*/i' => '.',
            '/\s*&dot&\s*/i' => '.',
        ];
        
        // Прилагане на замените за @
        foreach ($atReplacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }
        
        // Прилагане на замените за .
        foreach ($dotReplacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }
        
        // 4. Премахване на HTML тагове около @ и . символи
        // Премахва <span>, <b>, <i>, <em>, <strong> и други inline елементи около @ и .
        $text = preg_replace('/\s*<[^>]*>\s*(@|at|\[at\]|\<at\>|\{at\})\s*<\/[^>]*>\s*/', '@', $text);
        $text = preg_replace('/\s*<[^>]*>\s*(\.|dot|\[dot\]|\{dot\}|\<dot\>)\s*<\/[^>]*>\s*/', '.', $text);
        
        // 5. Други техники за de-obfuscation
        
        // Премахване на нулева ширина символи (zero-width characters)
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);
        
        // Премахване на CSS hidden елементи които могат да съдържат spam символи
        $text = preg_replace('/<[^>]*style[^>]*display\s*:\s*none[^>]*>.*?<\/[^>]*>/si', '', $text);
        
        // Премахване на <noscript> тагове, които може да съдържат obfuscated emails
        $text = preg_replace('/<noscript[^>]*>.*?<\/noscript>/si', '', $text);
        
        return $text;
    }
}
