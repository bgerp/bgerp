<?php



/**
 * Ключа с който ще се криптира, ако не бъде зададен експлицитно
 */
defIfNot(EF_CRYPT_CODE, EF_SALT . 'EF_CRYPT_CODE');


/**
 * Клас 'core_Crypt' - Функции за двупосочно криптиране със споделен ключ
 *
 *
 * @category  all
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
    function enChange(&$pack, $md5)
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
    function deChange(&$pack, $md5)
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
    function enAdd(&$pack, $md5)
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
    function deAdd(&$pack, $md5)
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
    function encode16(&$pack, $md5, $len = 6)
    {
        $k = $md5;
        
        for ($i = 0; $i < $len; $i++) {
            if ($md5{$i} < '8') {
                $this->enChange($pack, $k);
                $this->enAdd($pack, $k);
            } else {
                $this->enAdd($pack, $k);
                $this->enChange($pack, $k);
            }
            $k = md5($k);
        }
    }
    
    
    /**
     * Декодиране на 16 знаков пакет
     */
    function decode16(&$pack, $md5, $len = 6)
    {
        $k = $md5;
        
        for ($i = 0; $i < $len; $i++) {
            $md5Arr[$i] = $k;
            $k = md5($k);
        }
        
        for ($i = $len - 1; $i >= 0; $i--) {
            if ($md5{$i} < '8') {
                $this->deAdd($pack, $md5Arr[$i]);
                $this->deChange($pack, $md5Arr[$i]);
            } else {
                $this->deChange($pack, $md5Arr[$i]);
                $this->deAdd($pack, $md5Arr[$i]);
            }
        }
    }
    
    
    /**
     * Обработка на събитие за бинарно кодиране
     */
    function encode(&$res, $str, $key, $minRand)
    {
        // Генерираме събитие, което дава възможност за бъдещо разширение
        if ($this->invoke('beforeEncode', array(
                    &$res,
                    &$str,
                    &$key,
                    &$minRand
                )) === FALSE)
        return;
        
        // Установяваме стринга-разделител
        $div = $this->getDivStr($key);
        
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
            $this->encode16($pack, md5($key . $res));
            $res .= $pack;
        }
        
        // Генерираме събитие след кодирането, с цел за бъдещо разширение
        $this->invoke('afterEncode', array(
                &$res,
                $str,
                $key
            ));
    }
    
    
    /**
     * Обработка на събитие за бинарно декодиране
     */
    function decode(&$res, $str, $key)
    {
        // Генерираме събитие, което дава възможност за бъдещо разширение
        if ($this->invoke('beforeDecode', array(
                    &$res,
                    $str,
                    $key
                )) === FALSE)
        return;
        
        // Ако дължината не е кратна на 16 връщаме грешка
        if (strlen($str) % 16) {
            $res = FALSE;
            
            return;
        }
        
        // Колко 16-знакови пакета имаме?
        $countPacks = strlen($str) / 16;
        
        for ($i = $countPacks - 1; $i >= 0; $i--) {
            $pack = substr($str, $i * 16, 16);
            $rest = substr($str, 0, $i * 16);
            $this->decode16($pack, md5($key . $rest));
            $res = $pack . $res;
        }
        
        // Установяваме стринга-разделител
        $div = $this->getDivStr($key);
        
        $divPos = strpos($res, $div);
        
        // Ако нямаме разделител - връщаме грешка
        if ($divPos === FALSE) {
            $res = FALSE;
            
            return;
        }
        
        // Резултата е равен на частта след разделителя
        $res = substr($res, $divPos + strlen($div));
        
        // Генерираме събитие след разкодирането, с цел бъдещо разширение
        $this->invoke('afterDecode', array(
                &$res,
                $str,
                $key
            ));
    }
    
    
    /**
     * Определя разделителя между хедър-а на кодираната част и данните
     */
    function getDivStr($key)
    {
        $crc32 = crc32($key);
        $div .= chr($crc32 % 256);
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
    function encodeStr($str, $key, $minRand = NULL)
    {
        $this->encode($res, $str, $key, $minRand);
        
        return $res;
    }
    
    
    /**
     * Декодира стринг
     */
    function decodeStr($str, $key)
    {
        $this->decode($res, $str, $key);
        
        return $res;
    }
    
    
    /**
     * Кодира променливи, масиви и обекти
     */
    function encodeVar($var, $code = EF_CRYPT_CODE)
    {
        $var = serialize($var);
        $var = gzcompress($var);
        $var = $this->encodeStr($var, $code . 'encodeVar');
        $var = base64_encode($var);
        
        return $var;
    }
    
    
    /**
     * Декодира променливи, масиви и обекти
     */
    function decodeVar($var, $code = EF_CRYPT_CODE)
    {
        $var = base64_decode($var);
        
        if (!$var)
        return FALSE;
        
        $var = $this->decodeStr($var, $code . 'encodeVar');
        
        if (!$var)
        return FALSE;
        
        $var = gzuncompress($var);
        
        if (!$var)
        return FALSE;
        
        $var = unserialize($var);
        
        return $var;
    }
}