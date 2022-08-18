<?php 

/**
 * Помощен клас за парсиране на
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see       https://github.com/bgerp/bgerp/issues/115
 */
class email_Mime extends core_BaseClass
{
    /**
     * Текстоватана имейл-а
     */
    public $textPart;
    
    
    /**
     * Текстовата част на имйела, без да се взема в предвид HTML частта
     */
    public $justTextPart;
    
    
    /**
     * Рейтинг на текстовата част
     */
    public $bestTextRate = 0;
    
    
    /**
     * Индекса на най-подходящата текстова част
     */
    public $bestTextIndex;
    
    
    /**
     * Събджект на писмото
     */
    public $subject;
    
    
    /**
     * Имейлът от хедъра 'To'
     */
    public $toEmail;
    
    
    /**
     * Името на изпращача от хедъра 'From'
     */
    public $fromName;
    
    
    /**
     * Имейла на изпращача от хедъра 'From'
     */
    public $fromEmail;
    
    
    /**
     * Времето на изпращане на имейла
     */
    public $sendingTime;
    
    
    /**
     * Езика на имейл-а
     */
    public $lg;
    
    
    /**
     * IP адреса на изпращача
     */
    public $senderIp;
    
    
    /**
     * Масив с id => [данни за файл] - прикачени файлове
     * ->name
     * ->data
     * ->type
     * ->param
     */
    public $files = array();
    
    
    /**
     * Масив със съобщения за грешки по време на парсирането
     */
    public $errors = array();
    
    
    /**
     * Връща събджекта на писмото
     *
     * @param boolean $cleanMB - премахва емотиконуте в събджекта
     *
     * @returbn string
     */
    public function getSubject($cleanMB = false)
    {
        if (!isset($this->subject)) {
            $this->subject = $this->getHeader('Subject');
            $this->subject = str_replace(array("\n\t", "\n"), array('', ''), $this->subject);
        }

        $subject = $this->subject;

        if ($cleanMB) {
            $subject = preg_replace('/[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0077}\x{E006C}\x{E0073}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0073}\x{E0063}\x{E0074}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0065}\x{E006E}\x{E0067}\x{E007F})|[\x{1F3F4}](?:\x{200D}\x{2620}\x{FE0F})|[\x{1F3F3}](?:\x{FE0F}\x{200D}\x{1F308})|[\x{0023}\x{002A}\x{0030}\x{0031}\x{0032}\x{0033}\x{0034}\x{0035}\x{0036}\x{0037}\x{0038}\x{0039}](?:\x{FE0F}\x{20E3})|[\x{1F415}](?:\x{200D}\x{1F9BA})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F468})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F468})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9BD})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9AF})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2640}\x{FE0F})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2642}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2695}\x{FE0F})|[\x{1F471}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F9CF}\x{1F647}\x{1F926}\x{1F937}\x{1F46E}\x{1F482}\x{1F477}\x{1F473}\x{1F9B8}\x{1F9B9}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F486}\x{1F487}\x{1F6B6}\x{1F9CD}\x{1F9CE}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}\x{1F9D8}](?:\x{200D}\x{2640}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B0})|[\x{1F471}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F9CF}\x{1F647}\x{1F926}\x{1F937}\x{1F46E}\x{1F482}\x{1F477}\x{1F473}\x{1F9B8}\x{1F9B9}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F486}\x{1F487}\x{1F6B6}\x{1F9CD}\x{1F9CE}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}\x{1F9D8}](?:\x{200D}\x{2642}\x{FE0F})|[\x{1F441}](?:\x{FE0F}\x{200D}\x{1F5E8}\x{FE0F})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FA}](?:\x{1F1FF})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1FA}](?:\x{1F1FE})|[\x{1F1E6}\x{1F1E8}\x{1F1F2}\x{1F1F8}](?:\x{1F1FD})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F9}\x{1F1FF}](?:\x{1F1FC})|[\x{1F1E7}\x{1F1E8}\x{1F1F1}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1FB})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1FB}](?:\x{1F1FA})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FE}](?:\x{1F1F9})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FA}\x{1F1FC}](?:\x{1F1F8})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F7})|[\x{1F1E6}\x{1F1E7}\x{1F1EC}\x{1F1EE}\x{1F1F2}](?:\x{1F1F6})|[\x{1F1E8}\x{1F1EC}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}](?:\x{1F1F5})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EE}\x{1F1EF}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1F8}\x{1F1F9}](?:\x{1F1F4})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1F3})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FF}](?:\x{1F1F2})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F1})|[\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FD}](?:\x{1F1F0})|[\x{1F1E7}\x{1F1E9}\x{1F1EB}\x{1F1F8}\x{1F1F9}](?:\x{1F1EF})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EB}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F3}\x{1F1F8}\x{1F1FB}](?:\x{1F1EE})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1ED})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1EC})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F9}\x{1F1FC}](?:\x{1F1EB})|[\x{1F1E6}\x{1F1E7}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FB}\x{1F1FE}](?:\x{1F1EA})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1E9})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FB}](?:\x{1F1E8})|[\x{1F1E7}\x{1F1EC}\x{1F1F1}\x{1F1F8}](?:\x{1F1E7})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F6}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}\x{1F1FF}](?:\x{1F1E6})|[\x{00A9}\x{00AE}\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}-\x{21AA}\x{231A}-\x{231B}\x{2328}\x{23CF}\x{23E9}-\x{23F3}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}-\x{25AB}\x{25B6}\x{25C0}\x{25FB}-\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}-\x{2615}\x{2618}\x{261D}\x{2620}\x{2622}-\x{2623}\x{2626}\x{262A}\x{262E}-\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{265F}-\x{2660}\x{2663}\x{2665}-\x{2666}\x{2668}\x{267B}\x{267E}-\x{267F}\x{2692}-\x{2697}\x{2699}\x{269B}-\x{269C}\x{26A0}-\x{26A1}\x{26AA}-\x{26AB}\x{26B0}-\x{26B1}\x{26BD}-\x{26BE}\x{26C4}-\x{26C5}\x{26C8}\x{26CE}-\x{26CF}\x{26D1}\x{26D3}-\x{26D4}\x{26E9}-\x{26EA}\x{26F0}-\x{26F5}\x{26F7}-\x{26FA}\x{26FD}\x{2702}\x{2705}\x{2708}-\x{270D}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2728}\x{2733}-\x{2734}\x{2744}\x{2747}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2763}-\x{2764}\x{2795}-\x{2797}\x{27A1}\x{27B0}\x{27BF}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{2B50}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}\x{1F191}-\x{1F19A}\x{1F201}-\x{1F202}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F23A}\x{1F250}-\x{1F251}\x{1F300}-\x{1F321}\x{1F324}-\x{1F393}\x{1F396}-\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}-\x{1F3F0}\x{1F3F3}-\x{1F3F5}\x{1F3F7}-\x{1F3FA}\x{1F400}-\x{1F4FD}\x{1F4FF}-\x{1F53D}\x{1F549}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F56F}-\x{1F570}\x{1F573}-\x{1F57A}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F590}\x{1F595}-\x{1F596}\x{1F5A4}-\x{1F5A5}\x{1F5A8}\x{1F5B1}-\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}-\x{1F64F}\x{1F680}-\x{1F6C5}\x{1F6CB}-\x{1F6D2}\x{1F6D5}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6EB}-\x{1F6EC}\x{1F6F0}\x{1F6F3}-\x{1F6FA}\x{1F7E0}-\x{1F7EB}\x{1F90D}-\x{1F93A}\x{1F93C}-\x{1F945}\x{1F947}-\x{1F971}\x{1F973}-\x{1F976}\x{1F97A}-\x{1F9A2}\x{1F9A5}-\x{1F9AA}\x{1F9AE}-\x{1F9CA}\x{1F9CD}-\x{1F9FF}\x{1FA70}-\x{1FA73}\x{1FA78}-\x{1FA7A}\x{1FA80}-\x{1FA82}\x{1FA90}-\x{1FA95}]/u', '_', $subject);
        }

        return $subject;
    }
    
    
    /**
     * Извлича адрес към когото е насочено писмото
     */
    public function getToEmail()
    {
        if (!isset($this->toEmail)) {
            $toHeader = $this->getHeader('To');
            $toParser = new email_Rfc822Addr();
            $parseTo = array();
            $toParser->ParseAddressList($toHeader, $parseTo);
            $toEmlArr = type_Email::extractEmails($parseTo[0]['address']);
            $this->toEmail = $toEmlArr[0];
        }
        
        return $this->toEmail;
    }
    
    
    /**
     * Връща името на изпращача
     */
    public function getFromName()
    {
        if (!isset($this->fromName)) {
            $this->parseFromEmail();
        }
        
        return $this->fromName;
    }
    
    
    /**
     * Връща името на изпращача
     */
    public function getFromEmail()
    {
        if (!isset($this->fromEmail)) {
            $this->parseFromEmail();
        }
        
        return $this->fromEmail;
    }
    
    
    /**
     * Извлича масив с два елемента: Името на изпращача и имейла му
     */
    private function parseFromEmail()
    {
        $fromHeader = $this->getHeader('From');
        $fromParser = new email_Rfc822Addr();
        $parseFrom = array();
        $fromParser->ParseAddressList($fromHeader, $parseFrom);
        $fromEmlStr = $parseFrom[0]['address'] ? $parseFrom[0]['address'] : $parseFrom[1]['address'];
        $this->fromName = $parseFrom[0]['name'] . ' ' . $parseFrom[1]['name'];

        $fromEmlArr = array();
        if (trim($fromEmlStr)) {
            $fromEmlArr = type_Email::extractEmails($fromEmlStr);
        }

        if (empty($fromEmlArr)) {
            $fromEmlArr = type_Email::extractEmails($this->getHeader('Return-Path'));
        }

        $this->fromEmail = $fromEmlArr[0];
    }
    
    
    /**
     * Определяне на датата на писмото, когато е изпратено
     */
    public function getSendingTime()
    {
        if (!isset($this->sendingTime)) {
            // Определяме датата на писмото
            $d = date_parse($this->getHeader('Date'));
            
            if (countR($d)) {
                $time = mktime($d['hour'], $d['minute'], $d['second'], $d['month'], $d['day'], $d['year']);

                if ($d['is_localtime']) {

                    // Фикс за времето
                    $zTime = $d['zone'];

                    // Ако е PHP под 7.2 - третираме като минути
                    if (PHP_VERSION_ID < 70200) {
                        $zTime *= 60;
                    } else {
                        $zTime *= -1;
                    }

                    $time = $time + $zTime + (date('O') / 100 * 60 * 60);
                }
                
                $this->sendingTime = dt::timestamp2Mysql($time);

                // Ако е в бъдеще - репортваме и записваме текущото време
                $now = dt::verbal2mysql();
                if ($now < $this->sendingTime) {
                    wp($d, $this->sendingTime, $time);
                    $this->sendingTime = $now;
                }
            }
        }
        
        return $this->sendingTime;
    }
    
    
    /**
     * Връща езика на който предполага, че е написан имейла
     */
    public function getLg()
    {
        if (!isset($this->lg)) {
            $defLg = '';
            $defLgArr = array();
            
            if (defined('EF_DEFAULT_LANGUAGE')) {
                $defLg = EF_DEFAULT_LANGUAGE;
                
                $defLgArr[$defLg] = 5;
            }
            
            if ($defLg != 'en' && !preg_match('/\p{Cyrillic}/ui', $this->textPart)) {
                $defLgArr['en'] = 3;
            }
            
            $this->lg = i18n_Language::detect($this->textPart, $defLgArr);
        }
        
        return $this->lg;
    }
    
    
    /**
     * Дали сървърът от който е изпратен имейла е публичен?
     */
    public function isFromPublicMailServer()
    {
        if (!isset($this->isFromPublicMailServer)) {
            $this->isFromPublicMailServer = drdata_Domains::isPublic($this->getFromEmail());
        }
        
        return $this->isFromPublicMailServer;
    }
    
    
    /**
     * Прави опит да намери IP адреса на изпращача
     */
    public function getSenderIp()
    {
        if (!isset($this->senderIp)) {
            $ip = trim($this->getHeader('X-Originating-IP', 1, -1), '[]');
            
            if (empty($ip) || (!type_Ip::isPublic($ip))) {
                $ip = trim($this->getHeader('X-Sender-IP', 1, -1), '[]');
            }
            
            if (empty($ip) || !type_Ip::isPublic($ip)) {
                $regExp = '/Received:.*\[((?:\d+\.){3}\d+)\]/';
                preg_match_all($regExp, $this->getHeadersStr(), $matches);
                
                if ($ipCnt = countR($matches[1])) {
                    for ($i = $ipCnt - 1; $i >= 0; $i--) {
                        if (type_Ip::isPublic($matches[1][$i])) {
                            if (strpos($matches[0][$i], '.google.com')) {
                                continue;
                            }
                            $ip = $matches[1][$i];
                            break;
                        }
                    }
                }
            }
            
            if (empty($ip) || !type_Ip::isPublic($ip)) {
                $regExp = '/Received:.*?((?:\d+\.){3}\d+)/';
                preg_match_all($regExp, $this->getHeadersStr(), $matches);
                
                if ($ipCnt = countR($matches[1])) {
                    for ($i = $ipCnt - 1; $i >= 0; $i--) {
                        if (strpos($matches[0][$i], '.google.com')) {
                            continue;
                        }
                        if (type_Ip::isPublic($matches[1][$i])) {
                            $ip = $matches[1][$i];
                            break;
                        }
                    }
                }
            }
            
            $this->senderIp = $ip;
        }
        
        return $this->senderIp;
    }
    
    
    /**
     * Изчислява коя е вероятната държава от където e изпратен имейл-а
     */
    public function getCountry()
    {
        $from = $this->getFromEmail();
        $lg = $this->getLg();
        $ip = $this->getSenderIp();
        
        // Вземаме топ-левъл-домейна на имейл-а на изпращача
        $tld = strtolower(substr($from, strrpos($from, '.')));
        
        $countries = array();
        
        // Двубуквен код на държава, според домейна, на изпращача на имейл-а
        if (strlen($tld) == 2) {
            if ($ccByEmail = strtolower(drdata_Countries::fetchField("#domain = '{$tld}'", 'letterCode2'))) {
                switch ($ccByEmail) {
                    case 'us':
                        $rate = 10;
                        break;
                    case 'gb':
                    case 'de':
                    case 'ru':
                        $rate = 20;
                        
                        // no break
                    default:
                    $rate = 40;
                }
                $countries[$ccByEmail] += $rate;
            }
        }
        
        // Двубуквен код на държава според $ip-то на изпращача
        if ($ip) {
            if ($ccByIp = strtolower(drdata_ipToCountry::get($ip))) {
                switch ($ccByIp) {
                    case 'us':
                        $rate = 30;
                        break;
                    case 'gb':
                    case 'de':
                    case 'ru':
                        $rate = 40;
                        
                        // no break
                    default:
                    $rate = 60;
                }
                
                // Намаме голямо доверие на IP-то получено от публична услуга
                if ($this->isFromPublicMailServer()) {
                    $rate = $rate / 1.2;
                }
                
                $countries[$ccByIp] += $rate;
            }
        }
        
        // Според държавата където е локиран маил-сървъра на изпращача
        
        // Списък с държави в които се говори намерения език
        if ($lg) {
            $countries[$lg] += 30;
        }
        
        // Намираме страната с най-много събрани точки
        if (countR($countries)) {
            $firstCountry = strtoupper(arr::getMaxValueKey($countries));
            $countryId = drdata_Countries::fetchField("#letterCode2 = '{$firstCountry}'", 'id');
            
            return $countryId;
        }
    }
    
    
    /**
     * Изходния код на писмото
     */
    public function getData()
    {
        return $this->data;
    }
    
    
    /**
     * Връща манипулатора на eml файл, отговарящ на писмото
     */
    public function getEmlFile()
    {
        // Записваме текста на писмото, като [hash].eml файл
        $emlFileName = md5($this->getHeadersStr()) . '.eml';
        
        $fmId = $this->addFileToFileman($this->data, $emlFileName);
        
        return $fmId;
    }
    
    
    /**
     * Връща прикачените файлове
     *
     * @return array - Масив с всички прикачени файлове
     */
    public function getFiles()
    {
        foreach ($this->files as  $fRec) {
            $list .= ($list ? '' : '|') . $fRec->fmId . '|';
        }
        
        return $list;
    }
    
    
    /**
     * Връща id на файла, в който е записана html часта
     */
    public function getHtmlFile()
    {
        return $this->htmlFile;
    }
    
    
    /**
     * Връща съдържанието на HTML часта, ако такава има
     */
    public function getHtml()
    {
        if ($this->firstHtmlIndex) {
            $p = $this->parts[$this->firstHtmlIndex];
            
            $html = i18n_Charset::convertToUtf8($p->data, $p->charset, true);
        }
        
        return $html;
    }
    
    
    /**
     * Записва във fileman всички файлове, които са извлечени при парсирането
     */
    public function saveFiles_()
    {
        foreach ($this->files as $id => &$fRec) {
            if (!$fRec->fmId) {
                $fRec->fmId = $this->addFileToFileman($fRec->data, $fRec->name);
            }
        }
        
        // Минаваме по всички текстови и HTML части да ги запишем като прикачени файлове
        // Пропускаме само тази PLAIN TEXT част, която е използване
        foreach ($this->parts as $index => $p) {
            if ($p->type == 'TEXT') {
                if (($index == $this->bestTextIndex) || (!$p->data)) {
                    continue;
                }
                
                // В HTML часта заместваме cid:... с линкве към файловете
                if ($p->subType == 'HTML') {
                    $p->data = $this->placeInlineFiles($p->data);
                }
                
                $fileName = $this->getFileName($index);
                
                $p->fileId = $this->addFile($p->data, $fileName, 'part', $p->subType);
                
                $FRecText = $this->files[$p->fileId];
                
                $FRecText->fmId = $this->addFileToFileman($FRecText->data, $FRecText->name);
                
                if ($index == $this->firstHtmlIndex) {
                    $this->htmlFile = $FRecText->fmId;
                }
            }
        }
    }
    
    
    /**
     * Добавя файл в списъка на прикачените файлове
     */
    public function addFile($data, $name, $type = null, $param = null)
    {
        $rec = (object) array(
            'name' => $name,
            'data' => $data,
            'type' => $type,
            'param' => $param);
        $id = countR($this->files) + 1;
        $this->files[$id] = $rec;
        
        return $id;
    }
    
    
    /**
     * Вкарва прикрепените файлове във Fileman
     *
     * @return int - манипулатора на файла
     */
    public function addFileToFileman($data, $name)
    {
        $fh = fileman::absorbStr($data, 'Email', $name);
        
        $id = fileman::fetchByFh($fh, 'id');
        
        return $id;
    }
    
    
    /**
     * Замества cid' овете в html частта с линкове от системата
     */
    public function placeInlineFiles($html)
    {
        if (countR($this->files)) {
            foreach ($this->files as $fRec) {
                if ($fRec->type != 'inline') {
                    continue;
                }
                
                $cid = $fRec->param;
                
                $patterns = array("cid:{$cid}" => '', "\"cid:{$cid}\"" => '"', "'cid:{$cid}'" => "'");
                
                $Download = cls::get('fileman_Download');
                
                foreach ($patterns as $ptr => $q) {
                    if (stripos($html, $ptr) !== false) {
                        $fh = fileman_Files::fetchField($fRec->fmId, 'fileHnd');
                        $fileUrl = toUrl(array('fileman_Download', 'Download', 'fh' => $fh));
                        $html = str_ireplace($ptr, "{$q}{$fileUrl}{$q}", $html);
                    }
                }
            }
        }
        
        return $html;
    }
    
    
    /***********************************************************************************
     *                                                                                 *
     *  ФУНКЦИИ  ЗА РАБОТА С ХЕДЪРИ                                                    *
     *                                                                                 *
     ***********************************************************************************/
    
    /**
     * Връща масив с парсирани хедъри на миме-съобщение
     */
    public static function parseHeaders($headersStr)
    {
        $headers = str_replace("\n\r", "\n", $headersStr);
        $headers = str_replace("\r\n", "\n", $headers);
        $headers = str_replace("\r", "\n", $headers);
        $headers = trim($headers);     //
        $headers = explode("\n", $headers);
        
        // парсира масив с хедъри на имейл
        foreach ($headers as $h) {
            if (substr($h, 0, 1) != "\t" && substr($h, 0, 1) != ' ') {
                $pos = strpos($h, ':');
                $index = strtolower(substr($h, 0, $pos));
                $headersArr[$index][] = trim(substr($h, $pos + 1));
            } else {
                $current = countR($headersArr[$index]) - 1;
                $headersArr[$index][$current] .= "\n" . $h;
            }
        }
        
        return $headersArr;
    }
    
    
    /**
     * Връща хедърната част на писмото като текст
     */
    public function getHeadersStr($partIndex = 1)
    {
        return $this->parts[$partIndex]->headersStr;
    }
    
    
    /**
     * Връща указания хедър.
     * Ако се очаква повече от един хедър с това име, то:
     *
     * - ако $id е положително -се записа с индекс $id
     *
     * - ако $id e отрицателно - връща се хедър-а с номер $id, като броенето започва отзад на пред.
     * при $id == -1 се връща последния срещнат хедър с указаното име
     *
     * - ако $id == 0 се връща първият срещнат хедър с това име. Тази стойност за $id се приема по
     * подразбиране и може да не се цитира, ако се очаква с посоченото име да има само един хедър
     *
     * - ако $id == '*'конкатенация между всички записи за дадения хедър
     * разделени с интервал
     */
    public function getHeader($name, $part = 1, $headerIndex = 0, $decode = true)
    {
        if (is_object($part)) {
            $headersArr = $part->headersArr;
        } else {
            
            //Ако искаме всички части
            if ($part == '*') {
                foreach ($this->parts as $tPart) {
                    foreach ($tPart->headersArr as $key => $type) {
                        foreach ($type as $id => $val) {
                            
                            //Масив с всички хедъри
                            $headersArr[$key][$id] = $val;
                        }
                    }
                }
            } else {
                
                //Ако искаме точно определена част
                $headersArr = $this->parts[$part]->headersArr;
            }
        }
        
        return static::getHeadersFromArr($headersArr, $name, $headerIndex, $decode, $this->parts[1]->charset);
    }
    
    
    /**
     * Връща даден хедът от масив
     *
     * @param array  $headersArr  - Масив с хедърите
     * @param string $name        - Името на хедъра
     * @param mixed  $headerIndex - Число или * - Указва, кои да се извлекат
     * @param bool   $decode      - Дали да се декодира хедъра
     *
     * @retun string $res - Съдържанието на хедъра
     */
    public static function getHeadersFromArr($headersArr, $name, $headerIndex = 0, $decode = true, $charset = null)
    {
        $name = strtolower($name);
        
        if ($headerIndex == '*') {
            if (is_array($headersArr[$name])) {
                $res = implode(' ', $headersArr[$name]);
            }
        } else {
            if ($headerIndex < 0) {
                $headerIndex = countR($headersArr[$name]) + $headerIndex;
            }
            
            expect(is_int($headerIndex));
            
            $res = $headersArr[$name][$headerIndex];
        }

        if ($decode) {
            $res = static::decodeHeader($res, $charset);
        }
        
        return $res;
    }
    
    
    /**
     * Екстрактва информационните части на всеки хедър
     */
    public function extractHeader(&$part, $headerName, $autoAttributes = array())
    {
        $header = $this->getHeader($headerName, $part);
        
        $hParts = explode(';', $header);
        
        foreach ($hParts as $p) {
            if (!trim($p)) {
                continue;
            }
            $p2 = explode('=', $p, 2);
            
            if (countR($p2) == 1) {
                $res[] = $p;
            } else {
                $key = strtolower(trim($p2[0]));
                $value = trim($p2[1], "\"' ");
                $res[$key] = $value;
                
                if (in_array($key, $autoAttributes)) {
                    $part->{$key} = $value;
                }
            }
        }
        
        return $res;
    }


    /**
     * Рекурсивна функция, която съединява съседни стрингове с еднакви енкодинзи
     *
     * @param $imapDecodeArr
     *
     * @return array
     */
    protected static function fixDocedeArr($imapDecodeArr)
    {
        if (!$imapDecodeArr) {

            return $imapDecodeArr;
        }

        $aCnt = countR($imapDecodeArr);

        if ($aCnt <= 1) {

            return $imapDecodeArr;
        }

        $imapDecodeArr = array_values($imapDecodeArr);

        foreach ($imapDecodeArr as $id => $header) {
            if(isset($imapDecodeArr[$id-1]) && $imapDecodeArr[$id-1]->charset == $header->charset) {
                $imapDecodeArr[$id-1]->text .= $header->text;
                unset($imapDecodeArr[$id]);
            }
        }

        if (countR($imapDecodeArr) == $aCnt) {

            return $imapDecodeArr;
        }

        return self::fixDocedeArr($imapDecodeArr);
    }

    
    /**
     * Декодира хедърната част част
     */
    public static function decodeHeader($val, $charset = null)
    {
        // Ако стойността на хедъра е 7-битова, той може да е кодиран
        if (i18n_Charset::is7Bit($val) || (strpos($val, '=?') !== false)) {
            $imapDecodeArr = @imap_mime_header_decode($val);
            $decoded = '';

            if (is_array($imapDecodeArr) && countR($imapDecodeArr) > 0) {
                $imapDecodeArr = self::fixDocedeArr($imapDecodeArr);
                foreach ($imapDecodeArr as $header) {
                    if(isset($header->charset) && $header->charset != '' && $header->charset != 'default') {
                        $decoded .= @iconv($header->charset, 'utf-8', $header->text);
                    } else {
                        $decoded .= i18n_Charset::convertToUtf8($header->text, $charset);
                    }
                }
            } else {
                $decoded = i18n_Charset::convertToUtf8($val, $charset);
            }
        } else {
            $decoded = i18n_Charset::convertToUtf8($val, $charset);
        }

        return $decoded;
    }
    
    
    /**
     * Парсира цяло MIME съобщение
     */
    public function parseAll($data, $index = 1)
    {
        // Ако не е записано, зачистваме цялото съдържание на писмото
        if (empty($this->data)) {
            $this->data = $data;
        }
        
        $bestPos = strlen($data);
        
        foreach (array("\r\n", "\r", "\n") as $c) {
            $headerDelim = $c . $c;
            $pos = strpos($data, $headerDelim);
            if ($pos > 0 && $pos < $bestPos) {
                $bestPos = $pos;
                $nl = $c;
            }
        }

        $headerStr = '';
        $headerDelim = "\n\r";
        if ($nl != $headerDelim) {
            $headerStrArr = explode($headerDelim, $data, 2);
            if (countR($headerStrArr) > 1) {
                $headerStr = trim($headerStrArr[0]);
                $data = trim($headerStrArr[1]);
            }
        }

        if (!$headerStr) {

            // Отделяме хедърите
            $headerStr = mb_strcut($data, 0, $bestPos);

            // Отделяме данните
            $data = mb_strcut($data,$bestPos);
        }

        $p = &$this->parts[$index];
        
        if (!is_object($p)) {
            $p = new stdClass();
        }
        
        // Записваме хедъри-те на тази част като стринг
        $p->headersStr = $headerStr;
        
        // Записваме хедъри-те на тази част като масив (за по-лесно търсене)
        // Масивът е двумерен, защото един хедър може (макар и рядко)
        // да се среща няколко пъти
        $p->headersArr = $this->parseHeaders($headerStr);
        
        // Парсираме хедър-а 'Content-Type'
        $ctParts = $this->extractHeader($p, 'Content-Type', array('boundary', 'charset', 'name'));
        
        list($p->type, $p->subType) = explode('/', strtoupper($ctParts[0]), 2);
        
        $p->type = trim($p->type);
        $p->subType = trim($p->subType);
        
        $knownTypes = array('MULTIPART', 'TEXT', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'MODEL', 'X-UNKNOWN');
        
        // Ако типа не е от познатите типове, търсим ги като стринг в хедър-а 'Content-Type'
        // Ако някой познат тип се среща в хедър-а, то приемаме, че той е търсения тип
        if (!in_array($p->type, $knownTypes)) {
            $ct = $this->getHeader('Content-Type', $p);
            
            foreach ($knownTypes as $t) {
                if (stripos($ct, $t)) {
                    $p->type = $t;
                    break;
                }
            }
        }
        
        // Ако по никакъв начин не сме успели да определим типа, приемаме че е 'TEXT'
        if (empty($p->type)) {
            if (!$p->name) {
                $p->type = 'TEXT';
            } else {
                $p->type = 'X-UNKNOWN';
            }
        }
        
        $knownSubTypes = array('PLAIN', 'HTML');
        
        // Ако под-типа не е от познатите под-типове, търсим ги като стринг в хедър-а 'Content-Type'
        // Ако някой познат под-тип се среща в хедър-а, то приемаме, че той е търсения под-тип
        if (!in_array($p->subType, $knownSubTypes)) {
            $ct = $this->getHeader('Content-Type', $p);
            
            foreach ($knownSubTypes as $t) {
                if (stripos($ct, $t)) {
                    $p->subType = $t;
                    break;
                }
            }
        }
        
        $p->charset = i18n_Charset::getCanonical($p->charset);
        
        // Парсираме хедър-а 'Content-Transfer-Encoding'
        $cte = $this->extractHeader($p, 'Content-Transfer-Encoding');
        
        if ($cte[0]) {
            $p->encoding = i18n_Encoding::getCanonical($cte[0]);
        }
        
        // Парсираме хедър-а 'Content-Disposition'
        $cd = $this->extractHeader($p, 'Content-Disposition', array('filename'));
        
        // Парсираме хедър-а 'Content-ID'
        $cid = $this->getHeader('Content-ID', $p);
        
        if ($cd[0]) {
            $p->attachment = $cd[0];
        } else {
            
            // Ако е изпуснат Content-Disposition, но има Content-ID, отбелязваме файла, като inline
            if ($cid) {
                $p->attachment = 'inline';
            }
        }
        
        // Ако частта е съставна, рекурсивно изваждаме частите и
        if (($p->type == 'MULTIPART') && $p->boundary) {
            $data = explode('--' . $p->boundary, $data);
            
            $cntParts = countR($data);
            
            if ($cntParts == 2) {
                $this->errors[] = "Само едно  boundary в MULTIPART частта (${cntParts})";
                
                if (strlen($data[0]) > strlen($data[1])) {
                    unset($data[1]);
                } else {
                    unset($data[0]);
                }
            }
            
            if ($cntParts == 1) {
                $this->errors[] = "Няма нито едно boundary в MULTIPART частта (${cntParts})";
            }
            
            if ($cntParts >= 3) {
                if (strlen($data[0]) > 255) {
                    $this->errors[] = 'Твърде много текст преди първата MULTIPART част';
                } else {
                    unset($data[0]);
                }
                
                if (strlen($data[$cntParts - 1]) > 255) {
                    $this->errors[] = 'Твърде много текст след последната MULTIPART част';
                } else {
                    unset($data[$cntParts - 1]);
                }
            }
            
            for ($i = 0; $i < $cntParts; $i++) {
                if ($data[$i]) {
                    $this->parseAll(ltrim($data[$i], $nl), $index . '.' . $i);
                }
            }
            
            // Ако частта не е съставна, декодираме, конвертираме към UTF-8 и
            // евентуално записваме прикачения файл
        } else {
            $data2 = false;
            
            // Декодиране
            switch ($p->encoding) {
                case 'BASE64':
                    $data2 = imap_base64($data);
                    break;
                case 'QUOTED-PRINTABLE':
                    $data2 = imap_qprint($data);
                    break;
                case '8BIT':
                case '7BIT':
                default:
            }
            if ($data2 !== false) {
                $data = $data2;
            }
            
            // Ако часта e текстова и не е атачмънт, то по подразбиране, този текст е PLAIN
            if ($p->attachment != 'attachment' && $p->type == 'TEXT' && !trim($p->subType)) {
                $p->subType = 'PLAIN';
            }
            
            // Конвертиране към UTF-8
            if ($p->type == 'TEXT' && ($p->subType == 'PLAIN' || $p->subType == 'HTML') && ($p->attachment != 'attachment')) {
                $text = i18n_Charset::convertToUtf8($data, $p->charset, $p->subType == 'HTML');
                
                // Текстовата част, без да се гледа HTML частта
                if ($p->subType == 'PLAIN') {
                    $this->justTextPart = $text;
                }
                
                // Ако часта е HTML - конвертираме я до текст
                if ($p->subType == 'HTML') {
                    $text = html2text_Converter::toRichText($text);
                }
                
                $textRate = $this->getTextRate($text);
                
                // Отдаваме предпочитания на плейн-частта, ако идва от bgERP
                if ($p->subType == 'PLAIN') {
                    if ($this->getHeader('X-Bgerp-Hash')) {
                        $textRate = $textRate * 4;
                    } else {
                        $textRate = $textRate * 0.8;
                    }
                    
                    // Ако обаче, текст частта съдържа значително количество HTML елементи,
                    // ние не я предпочитаме
                    $k = (mb_strlen(strip_tags($text)) + 1) / (mb_strlen($text) + 1);
                    $textRate = $textRate * $k * $k;
                }
                
                // Ако нямаме никакъв текст или картинки в тази текстова част, не записваме данните
                if (($textRate < 1) && (stripos($data, '<img ') === false)) {
                    
                    return;
                }
                
                if ($p->subType == 'HTML') {
                    $p->data = $data;
                } else {
                    $p->data = $text;
                }
                
                // Ако е прикачен файл, намаляме рейтинга
                if ($p->attachment) {
                    $textRate = $textRate * 0.5;
                }
                
                if ($textRate > (1.05 * $this->bestTextRate)) {
                    // Записваме данните
                    $this->textPart = $text;
                    
                    // Премахваме излишните празни линии
                    $this->textPart = type_Richtext::removeEmptyLines($this->textPart, 2);
                    
                    if ($p->subType != 'HTML') {
                        $this->bestTextIndex = $index;
                    }
                    
                    $this->bestTextRate = $textRate;
                    $this->charset = i18n_Charset::getCanonical($p->charset);
                    $this->detectedCharset = i18n_Charset::detect($data, $p->charset, $p->subType == 'HTML');
                }
                
                if ($p->subType == 'HTML' && (!$this->firstHtmlIndex) && ($textRate > 1 || (stripos($data, '<img ') === false))) {
                    $this->firstHtmlIndex = $index;
                }
            } else {
                
                // Ако частта представлява атачнат файл, определяме името му и разширението му
                $fileName = $this->getFileName($index);
                
                $cid = trim($cid, '<>');
                
                $p->filemanId = $this->addFile($data, $fileName, 'inline', $cid);
            }
        }
    }
    
    
    /**
     * Връща рейтинга на текст
     * Колкото е по-голям рейтинга, толкова текста е по-съдържателен
     */
    public static function getTextRate($text)
    {
        $textRate = 0;
        $text = str_replace('&nbsp;', ' ', $text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        if (trim($text, " \n\r\t" . chr(194) . chr(160))) {
            ++$textRate;
            $notWords = preg_replace('/(\pL{2,})/iu', '', $text);
            $textRate += mb_strlen($text) - mb_strlen($notWords);
        }
        
        return $textRate;
    }
    
    
    /**
     * Връща най-доброто име за прикачен файл съответстващ на прикачената част
     */
    private function getFileName($partIndex)
    {
        $p = $this->parts[$partIndex];
        
        setIfNot($fileName, $p->filename, $p->name);
        
        // Ако липсва файл, името му е производно на хеша на съдържанието му
        if (!$fileName) {
            $partIndexName = str_replace('.', '-', $partIndex);
            $fileName = $partIndexName . '_' . substr(md5($p->data), 0, 6);
        }
        
        // Ако липсва файлово разширение се опитваме да го определим от 'Content-Type'
        if (!fileman_Files::getExt($fileName)) {
            $ctParts = $this->extractHeader($partIndex, 'Content-Type');
            $mimeT = strtolower($ctParts[0]);
            $fileName = fileman_mimes::addCorrectFileExt($fileName, $mimeT);
        }
        
        return $fileName;
    }
    
    
    //---------------------------------------------------------------------------------------------------------------------------------------
    
    
    /**
     * Взема хедърите от манипулатора на eml файл
     *
     * @param fileman_Files $emlFileHnd   - Манипулатора на eml файла
     * @param bool          $parseHeaders - Дали да се парсират в масив откритите хедъри
     *
     * @return array $headersArr - Масив с хедърите
     *               string $headersArr['string'] - Стринг с хедърите
     *               array $headersArr['array'] - Масив с парсираните хедърите /Ако е зададено/
     */
    public function getHeadersFromEmlFile($emlFileHnd, $parseHeaders = false)
    {
        // Ако хедърите не са били извлечени
        if (!($headersStr = $this->getHeadersStr())) {
            
            // Вземаме съдържанието на eml файла
            $emlFileContent = fileman_Files::getContent($emlFileHnd);
            
            // Парсираме съдържанието
            $this->parseAll($emlFileContent);
            
            // Стринг с хедърите
            $headersStr = $this->getHeadersStr();
        }
        
        // Добавяме в масива
        $headersArr['string'] = $headersStr;
        
        // Ако е зададено да се парсират хедърите
        if ($parseHeaders) {
            
            // Добавяме в масива парсираните хедъри
            $headersArr['array'] = $this->parseHeaders($headersStr);
        }
        
        return $headersArr;
    }
    
    
    /**
     * Връща текстовата част на EML файла /Без да взема в предвид HTML частта/
     *
     * @return string - Текстова част на имейла
     */
    public function getJustTextPart()
    {
        return $this->justTextPart;
    }
    
    
    /**
     * Екстрактва имейлите и връща само имейл частта на масива
     *
     * @param string $str  - Стринг с имейлите
     * @param bool   $uniq - Дали да е уникален имейла
     *
     * @return string $res - Резултата
     */
    public static function getAllEmailsFromStr($str, $uniq = false)
    {
        // Инстанция на класа
        $toParser = new email_Rfc822Addr();
        
        // Масив в който ще парсираме
        $parseToArr = array();
        
        // Парсираме
        $toParser->ParseAddressList($str, $parseToArr);
        
        // Обхождаме масива
        foreach ((array) $parseToArr as $key => $dummy) {
            
            // Извличаме само имейлите
            $emlArr = type_Email::extractEmails($parseToArr[$key]['address']);
            
            // Преобразуваме в стринг
            $implode = implode(', ', $emlArr);
            
            // Добавяме към полето
            $res .= ($res) ? ', '. $implode : $implode;
        }
        
        // Ако имейла трябва да е уникален
        if ($uniq) {
            
            // Разделяме стринга в масив
            $resExplode = explode(', ', $res);
            
            // Махаме повтарящите се записи
            $uniqArr = array_unique($resExplode);
            
            // Обръщаме в стринг
            $res = implode(', ', $uniqArr);
        }
        
        return $res;
    }
    
    
    /**
     * Преобразува списък от имейли, както се срещат в хедърите, във врбална стойност
     */
    public static function emailListToVerbal($list)
    {
        if (countR($list)) {
            foreach ($list as $item) {
                $address = $item['address'];
                
                if ($address) {
                    if ($item['isExternal']) {
                        $inst = cls::get('type_Email');
                        $address = $inst->toVerbal($address);
                    } else {
                        $address = type_Email::escape($address);
                        
                        if ($item['isWrong']) {
                            $address = "<span style='border-bottom: 1px solid red;'>" . $address . '</span>';
                        }
                    }
                    
                    $res .= '<span>' . $address;
                    if ($item['name']) {
                        $res .= ' (' . $item['name'] . ')';
                    }
                    $res .= '</span>, ';
                }
            }
            $res = rtrim($res, ', ');
        }
        
        return $res;
    }
    
    
    /**
     * Връща вербално представяне на хедърите на съобщението
     *
     * @param bool $decode
     * @param bool $escape
     *
     * @return string
     */
    public function getHeadersVerbal($decode = true, $escape = true)
    {
        $headers = $this->getHeadersStr();
        $headers = $this->parseHeaders($headers);
        $res = '';
        if (is_array($headers)) {
            $me = cls::get(get_called_class());
            foreach ($headers as $h => $c) {
                if ($h == 'subject') {
                    $s = true;
                }
                
                $a = implode('; ', $c);
                $h = str_replace(' ', '-', ucwords(str_replace('-', ' ', $h)));
                
                if ($decode) {
                    $a = $me->decodeHeader($a);
                }
                
                if ($escape) {
                    $a = type_Varchar::escape($a);
                }
                
                $res .= "<div><b>{$h}</b>: {$a}</div>";
            }
        }
        
        return $res;
    }
}
