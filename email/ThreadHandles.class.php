<?php


/**
 * Модел за работа с манипулатори на нишки, използвани в изходящите писма
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class email_ThreadHandles extends core_Manager
{
    /**
     * Зареждаме плъгините
     */
    public $loadList = 'email_Wrapper,plg_RowTools';
    
    
    /**
     * Наименование на мениджъра
     */
    public $title = 'Манипулатори на нишки';
    
    
    /**
     * Никой не може да добавя или изтрива или променя манипулаторите
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Разглеждането на манипулаторите е оставено за debug-режим
     */
    public $canList = 'debug';
    
    
    /**
     * Описание полетата на модела
     */
    public function description()
    {
        // id на нишката
        $this->FLD('threadId', 'int', 'caption=Нишка');
        
        // Манипулатор на нишката (thread handle)
        $this->FLD('handle', 'varchar(32)', 'caption=Манипулатор');
        
        // Индекси за бързодействие
        $this->setDbUnique('threadId');
        $this->setDbUnique('handle');
    }
    
    
    /**
     * Добавя манипулатор на нишка в събджект на имейл
     *
     * Манипулатора не се добавя, ако вече присъства в събджекта.
     * EMAIL_THREAD_HANDLE_POS със стойности BEFORE_SUBJECT и AFTER_SUBJECT
     * определя позицията, където ще бъде добавен маниполатора
     *
     * @param string $subject
     * @param int    $threadId
     *
     * @return string
     */
    public static function decorateSubject($subject, $threadId)
    {
        expect($handle = self::getHandleByThreadId($threadId));
        
        // Добавяме манипулатора само ако го няма
        if (strpos(" {$handle} ", " {$subject} ") === false) {
            // Манипулатора може да бъде поставен в началото или в края на събджекта
            $conf = core_Packs::getConfig('email');
            if ($conf->EMAIL_THREAD_HANDLE_POS == 'BEFORE_SUBJECT') {
                $subject = "{$handle} {$subject}";
            } else {
                $subject = "{$subject} {$handle}";
            }
        }
        
        return $subject;
    }
    
    
    /**
     * Премахва срещанията на манипулатора на посочената нишка от зададения събджект
     *
     * @param string subject
     * @param int    threadId
     *
     * @return string
     */
    public static function stripSubject($subject, $threadId)
    {
        if ($threadId) {
            $handle = self::getHandleByThreadId($threadId);
            
            if ($handle) {
                $handle = preg_quote($handle, '/');
                $subject = trim(preg_replace("/\s*{$handle}\s*/", ' ', $subject));
            }
        }
        
        return $subject;
    }
    
    
    /**
     * Връща манипулатора на нишка според id-то и
     */
    public static function getHandleByThreadId($threadId)
    {
        expect(is_numeric($threadId));
        
        $handle = self::fetchField("#threadId = {$threadId}", 'handle');
        
        if (!$handle) {
            $conf = core_Packs::getConfig('email');
            $createFunc = array('email_ThreadHandles','createThreadHandle_' . $conf->EMAIL_THREAD_HANDLE_TYPE);
            $handle = call_user_func($createFunc, $threadId);
            self::save((object) array('threadId' => $threadId, 'handle' => $handle));
        }
        
        return $handle;
    }
    
    
    /**
     * Връща id на нишка, според манипулатора й
     */
    public static function getThreadIdByHandle($handle)
    {
        $threadId = self::fetchField(array("#handle = '[#1#]'", $handle), 'threadId');
        
        return $threadId;
    }
    
    
    /**
     * Извлича всички кандидат-манипулатори на нишка
     *
     * @param string $subject обикновено това е събждект на писмо
     *
     * @return array масив от стрингове, които е възможно (от синтактична гледна точка) да са
     *               манипулатори на нишка.
     */
    public static function extractThreadFromSubject($subject)
    {
        $handles = array();
        
        $conf = core_Packs::getConfig('email');
        
        $handleTypes = arr::make($conf->EMAIL_THREAD_HANDLE_TYPE .',' . $conf->EMAIL_THREAD_HANDLE_LEGACY_TYPES, true);
        
        foreach ($handleTypes as $type) {
            $extFunct = array('email_ThreadHandles', 'extractThreadHandles_' . $type);
            
            if (is_callable($extFunct)) {
                $handles = call_user_func($extFunct, $subject);
                
                if (is_array($handles) && count($handles)) {
                    
                    // Проверяваме хендлърите последователно
                    foreach ($handles as $handle) {
                        if ($threadId = self::getThreadIdByHandle($handle)) {
                            
                            return $threadId;
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Създава манипулатор за нишка от тип 0
     */
    public static function createThreadHandle_Type0($threadId)
    {
        $handle = "<{$threadId}>";
        
        return $handle;
    }
    
    
    /**
     * Извлича всички кандидат-манипулатори на нишка от тип 0
     *
     * @param string $str обикновено това е събждект на писмо
     *
     * @return array масив от стрингове, които е възможно (от синтактична гледна точка) да са
     *               манипулатори на нишка.
     */
    public static function extractThreadHandles_Type0($str)
    {
        $handles = array();
        
        if (preg_match_all('/(\<[\d]{1,10}\>)/i', $str, $matches)) {
            $handles = arr::make($matches[1], true);
        }
        
        return $handles;
    }
    
    
    /**
     * Генерира манипулатор за указаната нишка от тип 1
     */
    public static function createThreadHandle_Type1($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        
        $handle = '#' . $firstDoc->getHandle() . str::getRand('AAA');
        
        return $handle;
    }
    
    
    /**
     * Извлича всички кандидат-манипулатори на нишка от тип 1
     *
     * @param string $str обикновено това е събждект на писмо
     *
     * @return array масив от стрингове, които е възможно (от синтактична гледна точка) да са
     *               манипулатори на нишка.
     */
    public static function extractThreadHandles_Type1($str)
    {
        $handles = array();
        
        if (preg_match_all('/(#[a-z]{1,3}[\d]{1,10}[a-z]{3})/i', $str, $matches)) {
            $handles = arr::make($matches[1], true);
        }
        
        return $handles;
    }
    
    
    /**
     * Генерира манипулатор за указаната нишка от тип 2
     */
    public static function createThreadHandle_Type2($threadId)
    {
        $handle = '#' . $threadId . self::getHashForType2($threadId);
        
        return $handle;
    }
    
    
    /**
     * Извлича всички кандидат-манипулатори на нишка от тип 2
     *
     * @param string $str обикновено това е събждект на писмо
     *
     * @return array масив от стрингове, които е възможно (от синтактична гледна точка) да са
     *               манипулатори на нишка.
     */
    public static function extractThreadHandles_Type2($str)
    {
        $handles = array();
        
        if (preg_match_all('/(#[\d]{3,12})/i', $str, $matches)) {
            $handles = arr::make($matches[1], true);
            foreach ($handles as $key => $h) {
                $h = substr($h, 1);
                if (self::getHashForType2(substr($h, 0, strlen($h) - 2)) != substr($h, strlen($h) - 2)) {
                    unset($handles[$key]);
                }
            }
        }
        
        return $handles;
    }
    
    
    /**
     * Помощна функция, служеща за подписване на манипулатор на тред
     */
    private static function getHashForType2($threadId)
    {
        $num = abs(crc32(EF_SALT . $threadId . 'Type2Hash'));
        
        $hash = str_pad(abs($num % 100), 2, '0', STR_PAD_LEFT);
        
        return $hash;
    }
    
    
    /**
     * Генерира манипулатор за указаната нишка от тип 3
     */
    public static function createThreadHandle_Type3($threadId)
    {
        do {
            $rand = str::getRand('aaaaa');
            $handle = '(' . $rand . self::getHashForType3($rand) . ')';
        } while (email_ThreadHandles::fetch(array("#handle = '[#1#]'", $handle)));
        
        return $handle;
    }
    
    
    /**
     * Извлича всички кандидат-манипулатори на нишка от тип 2
     *
     * @param string $str обикновено това е събждект на писмо
     *
     * @return array масив от стрингове, които е възможно (от синтактична гледна точка) да са
     *               манипулатори на нишка.
     */
    public static function extractThreadHandles_Type3($str)
    {
        $handles = array();
        
        if (preg_match_all('/(\([a-z]{7}\))/i', $str, $matches)) {
            $handles = arr::make($matches[1], true);
            
            foreach ($handles as $key => $h) {
                $h = trim($h, '()');
                if (self::getHashForType3(substr($h, 0, strlen($h) - 2)) != substr($h, strlen($h) - 2)) {
                    unset($handles[$key]);
                }
            }
        }
        
        return $handles;
    }
    
    
    /**
     * Помощна функция, служеща за подписване на манипулатор на тред
     */
    private static function getHashForType3($rand)
    {
        $num = abs(crc32(EF_SALT . $rand . 'Type3Hash'));
        
        $hash = chr(($num % 26) + ord('a')) . chr((floor($num / 26) % 26) + ord('a'));
        
        return $hash;
    }
}
