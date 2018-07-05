<?php



/**
 * Ключа с който ще се криптира, ако не бъде зададен експлицитно
 */
defIfNot(EF_CRYPT_CODE, EF_SALT . 'EF_CRYPT_CODE');


/**
 * Клас 'core_Crypt' - Функции за двупосочно криптиране със споделен ключ
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Crypt extends core_BaseClass
{
    
    
    /**
     * Кодиране чрез размяна
     */
    public static function enChange(&$pack, $md5)
    {
        for ($i = 0; $i < 32; $i += 2) {
            $a = hexdec($md5{$i});
            $b = hexdec($md5{$i + 1});
            $c = $pack{$a};
            $pack{$a} = $pack{$b};
            $pack{$b} = $c;
        }
    }
    
    
    /**
     * Декодиране на 'размяна'
     */
    public static function deChange(&$pack, $md5)
    {
        for ($i = 30; $i >= 0; $i -= 2) {
            $a = hexdec($md5{$i});
            $b = hexdec($md5{$i + 1});
            $c = $pack{$a};
            $pack{$a} = $pack{$b};
            $pack{$b} = $c;
        }
    }
    
    
    /**
     * Кодиране чрез 'добавяне'
     */
    public static function enAdd(&$pack, $md5)
    {
        for ($i = 0; $i < 32; $i += 2) {
            $a = hexdec(substr($md5, $i, 2));
            $a += ($i < 30) ? ord($pack{1 + $i / 2}) : 0;
            $pack{$i / 2} = chr((ord($pack{$i / 2}) + $a) % 256);
        }
    }
    
    
    /**
     * Декодиране на 'добавяне'
     */
    public static function deAdd(&$pack, $md5)
    {
        for ($i = 30; $i >= 0; $i -= 2) {
            $a = hexdec(substr($md5, $i, 2));
            $a += ($i < 30) ? ord($pack{1 + $i / 2}) : 0;
            $pack{$i / 2} = chr((ord($pack{$i / 2}) - $a) % 256);
        }
    }
    
    
    /**
     * Кодиране на 16 знаков пакет
     */
    public static function encode16(&$pack, $md5, $len = 6)
    {
        $k = $md5;
        
        for ($i = 0; $i < $len; $i++) {
            if ($md5{$i} < '8') {
                self::enChange($pack, $k);
                self::enAdd($pack, $k);
            } else {
                self::enAdd($pack, $k);
                self::enChange($pack, $k);
            }
            $k = md5($k);
        }
    }
    
    
    /**
     * Декодиране на 16 знаков пакет
     */
    public static function decode16(&$pack, $md5, $len = 6)
    {
        $k = $md5;
        
        for ($i = 0; $i < $len; $i++) {
            $md5Arr[$i] = $k;
            $k = md5($k);
        }
        
        for ($i = $len - 1; $i >= 0; $i--) {
            if ($md5{$i} < '8') {
                self::deAdd($pack, $md5Arr[$i]);
                self::deChange($pack, $md5Arr[$i]);
            } else {
                self::deChange($pack, $md5Arr[$i]);
                self::deAdd($pack, $md5Arr[$i]);
            }
        }
    }
    
    
    /**
     * Обработка на събитие за бинарно кодиране
     */
    public static function encode(&$res, $str, $key, $minRand)
    {
        // Генерираме събитие, което дава възможност за бъдещо разширение

        // Установяваме стринга-разделител
        $div = self::getDivStr($key);
        
        // Поставяме символ-разделител преди стринга
        $str = $div . $str;
        
        // Запълваме стринг отляво със случайни символи различни
        // от разделителя, докато дължината му стане кратна
        // на 16. Поставяме минимум $minRand случайни символа
        do {
            while (($c = chr(rand(0, 255))) == $div{0}) {
            }
            $str = $c . $str;
            $minRand--;
        } while ($minRand > 0 || (strlen($str) % 16));
        
        // Колко 16-знакови пакета имаме?
        $countPacks = strlen($str) / 16;
        
        // Започваме с празен резултат
        $res = '';
        
        // Кодираме последователно всеки един от пакетите и ги съединяваме
        for ($i = 0; $i < $countPacks; $i++) {
            $pack = substr($str, $i * 16, 16);
            self::encode16($pack, md5($key . $res));
            $res .= $pack;
        }
    }
    
    
    /**
     * Обработка на събитие за бинарно декодиране
     */
    public static function decode(&$res, $str, $key)
    {
        
        // Ако дължината не е кратна на 16 връщаме грешка
        if (strlen($str) % 16) {
            $res = false;
            
            return;
        }
        
        // Колко 16-знакови пакета имаме?
        $countPacks = strlen($str) / 16;
        
        for ($i = $countPacks - 1; $i >= 0; $i--) {
            $pack = substr($str, $i * 16, 16);
            $rest = substr($str, 0, $i * 16);
            self::decode16($pack, md5($key . $rest));
            $res = $pack . $res;
        }
        
        // Установяваме стринга-разделител
        $div = self::getDivStr($key);
        
        $divPos = strpos($res, $div);
        
        // Ако нямаме разделител - връщаме грешка
        if ($divPos === false) {
            
            // Установяваме стринга-разделител
            // За стари криптирания в 32 битови ОС
            $div = self::getDivStr($key, false);
            $divPos = strpos($res, $div);
            
            if ($divPos === false) {
                $res = false;
            
                return;
            }
        }
        
        // Резултата е равен на частта след разделителя
        $res = substr($res, $divPos + strlen($div));
    }
    
    
    /**
     * Определя разделителя между хедър-а на кодираната част и данните
     */
    public static function getDivStr($key, $sprint = true)
    {
        $crc32 = crc32($key);
        
        if ($sprint) {
            // Фикс - за да са еднакви в 32 и 64 битови ОС-та
            $crc32 = sprintf('%u', $crc32);
        }
        
        $div = chr($crc32 % 256);
        $crc32 = $crc32 / 256;
        $div .= chr($crc32 % 256);
        $crc32 = $crc32 / 256;
        $div .= chr($crc32 % 256);
        $crc32 = $crc32 / 256;
        $div .= chr($crc32 % 256);
        
        return $div;
    }
    
    
    /**
     * Кодира стринг
     */
    public static function encodeStr($str, $key, $minRand = 8)
    {
        self::encode($res, $str, $key, $minRand);
        
        return $res;
    }
    
    
    /**
     * Декодира стринг
     */
    public static function decodeStr($str, $key)
    {
        self::decode($res, $str, $key);
        
        return $res;
    }
    
    
    /**
     * Кодира променливи, масиви и обекти
     */
    public static function encodeVar($var, $key = EF_CRYPT_CODE, $flat = 'serialize')
    {
        if ($flat == 'json') {
            $var = json_encode($var);
        } else {
            $var = serialize($var);
        }

        $var = gzcompress($var);
        $var = self::encodeStr($var, $key . 'encodeVar');
        $var = base64_encode($var);
        
        return $var;
    }
    
    
    /**
     * Декодира променливи, масиви и обекти
     */
    public static function decodeVar($var, $key = EF_CRYPT_CODE, $flat = 'serialize')
    {
        $var = base64_decode($var);
        
        if (!$var) {
            
            return false;
        }
        
        $var = self::decodeStr($var, $key . 'encodeVar');
        
        if (!$var) {
            
            return false;
        }
        
        $var = gzuncompress($var);
        
        if (!$var) {
            
            return false;
        }
        
        if ($flat == 'json') {
            $var = json_decode($var, true);
        } else {
            $var = unserialize($var);
        }

        
        return $var;
    }
}
