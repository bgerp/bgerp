<?php


/**
 * Пътя до офис пакет
 */
defIfNot('OFFICE_PACKET_PATH', 'soffice');


/**
 * Броя на конвертиранията, след които офис пакета ще се ресартира
 */
defIfNot('MAX_OFFICE_PACKET_CONVERT_COUNT', 50);


/**
 * Мениджира офис пакета. Стартира, изключва и рестартира офис пакета.
 * Има брояч за направените обработки с офис пакета.
 *
 * @category  vendors
 * @package   docoffice
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class docoffice_Office
{
    
    
    /**
     * Стартира офис пакета
     */
    public static function startOffice()
    {
        // Заключваме офис пакета
        static::lockOffice(20, 10);
        
        // Намираме и задаваме порта на офис пакета
        static::setOfficePort();
        
        // Вземаме порта на офис пакета
        $port = static::getOfficePort();
        
//        pclose(popen(OFFICE_PACKET_PATH . "2>&1 >/dev/null &", "r"));
        @pclose(popen('nohup `' . OFFICE_PACKET_PATH . " -headless -accept='socket,host=localhost,port={$port};urp;StarOffice.ServiceManager' -nofirststartwizard -nologo` &", 'r'));
        
        // Ако е стартиран успешно
        if (static::getStartedOfficePid()) {
            
            // Нулираме брояча за конвертиранията
            static::emptyConvertCount();
            
            log_System::add('docoffice_Office', OFFICE_PACKET_PATH . ' е стартиран на порт ' . $port, null, 'info');
            
            // Отключваме процеса
            static::unlockOffice();
            
            return true;
        }
            
        // Ако има грешка при стартирането
        log_System::add('docoffice_Office', 'Грешка при стартирането на ' . OFFICE_PACKET_PATH, null, 'info');
        
        
        return false;
    }
    
    
    /**
     * Проверява и ако има нужда рестартира офис пакета
     */
    public static function checkRestartOffice()
    {
        // Броя на направените обработки след последното нулиране на брояча
        $count = static::getConvertedCount();

        // Ако броя име е по голям или равен на максимално допустимия
        if ($count >= MAX_OFFICE_PACKET_CONVERT_COUNT) {
            
            // Рестартираме офис пакета
            static::restartOffice();
        }
    }
    
    
    /**
     * Рестартира офис пакета
     */
    public static function restartOffice()
    {
        // Убиваме офис пакета
        static::killOffice();
        
        // Стартираме офис пакета
        static::startOffice();
    }

    
    /**
     * Убива процеса на офис пакета
     */
    public static function killOffice()
    {
        // Вземаме process id' то на офис пакета
        $pid = static::getStartedOfficePid();

        if (!$pid) {
            return ;
        }
        
        $pid = escapeshellarg($pid);
        
        // Заключваме офис пакета
        static::lockOffice(100, 50);

        // Убиваме процеса
        $sh = "kill {$pid}";
        
        exec($sh, $dummy, $res);

        // Ако всичко е минало както трябва
        if ($res === 0) {
            
            // Премахваме от перманентните данни
            permanent_Data::remove('countOfficeProccess');
            
            // Премахваме от перманентните данни порта на офис пакета
            permanent_Data::remove('officePort');
            
            // Отключваме процеса
            static::unlockOffice();
            
            log_System::add('docoffice_Office', OFFICE_PACKET_PATH . ' е спрян', null, 'info');
            
            return true;
        }
            
        // Ако има грешка при спирането
        log_System::add('docoffice_Office', 'Грешка при спирането на ' . OFFICE_PACKET_PATH, null, 'warning');
        
        
        return false;
    }
    
    
    /**
     * Вземаме process id' то на стартирания процес
     */
    public static function getStartedOfficePid()
    {
        // Заключваме офис пакета
        static::lockOffice(20, 10);
        
        // Определяме името на офис пакета
        $baseName = basename(OFFICE_PACKET_PATH);
        $baseName = escapeshellarg($baseName);
        // Намираме process id' то на офис пакета
        $sh = "ps -aux | grep {$baseName} | grep -v grep | awk '{ print $2 }' | head -1";
        $pid = exec($sh);
        
        // Отключваме процеса
        static::unlockOffice();
        
        return $pid;
    }
    
    
    /**
     * Увеличаваме с единица броя на обработените документи
     */
    public static function increaseConvertCount()
    {
        // Вземаме броя на обаработени документи
        $data = static::getConvertedCount();
        
        // Увеличаваме с единица
        permanent_Data::write('countOfficeProccess', ++$data);
    }
    
    
    /**
     * Връща броя на обработените документи
     */
    public static function getConvertedCount()
    {
        $data = (int) permanent_Data::read('countOfficeProccess');
        
        return $data;
    }
    
    
    /**
     * Изпразваме брояча за обработените документи
     */
    public static function emptyConvertCount()
    {
        permanent_Data::write('countOfficeProccess', 0);
    }
    
    
    /**
     * Заключваме офис пакета
     *
     * @param int $maxDuration - Максималното време за което ще се опитаме да заключим
     * @param int $maxTray     - Максималният брой опити, за заключване
     */
    public static function lockOffice($maxDuration = 20, $maxTray = 10)
    {
        core_Locks::get('OfficePacket', $maxDuration, $maxTray, false);
    }
    
    
    /**
     * Отключваме офис пакета
     */
    public static function unlockOffice()
    {
        core_Locks::release('OfficePacket');
    }
    
    
    /**
     * Стартираме или рестартираме офис пакета в зависимост от състоянието му
     */
    public static function prepareOffice()
    {
        // Process id' то на office пакета
        $officePid = static::getStartedOfficePid();

        // Ако не е стартиране
        if (!$officePid) {
            
            // Стартираме офис пакета
            static::startOffice();
        } else {
            
            // Ако е стартиран проверяваме дали не трябва да се рестартира
            static::checkRestartOffice();
        }
    }
    
    
    /**
     * Сетва порта на който ще слуша офис пакета
     */
    public static function setOfficePort()
    {
        // Намираме свободен порт
        $port = static::findEmptyPort();
        
        // Записваме номера на порта
        permanent_Data::write('officePort', $port);
    }
    
    
    /**
     * Намира свободния порт
     */
    public static function findEmptyPort()
    {
        // Порта по подразбиране
        $port = 8100;
        
        $maxTrays = 30;
        
        // Докато не намери свободен порт
        while (@exec("netstat -tln | grep ':{$port}[^0-9]'")) {
            
            // Увеличаваме с единица
            $port++;
            
            if (!$maxTrays--) {
                break;
            }
        }
        
        return $port;
    }
    
    
    /**
     * Връща порта на който слуша офис пакета
     */
    public static function getOfficePort()
    {
        return permanent_Data::read('officePort');
    }
    
    
    /**
     * Конвертира подададени HTML стринг в doc файл
     *
     * @param string      $htmlStr
     * @param string      $htmlName
     * @param string|NULL $bucket
     *
     * @return string
     */
    public static function htmlToDoc($htmlStr, $htmlName = 'html.html', $bucket = null)
    {
        $htmlFile = fileman::addStrToFile($htmlStr, $htmlName);
        
        $nameAndExt = fileman::getNameAndExt($htmlFile);
        
        $docFile = $nameAndExt['name'] . '.doc';
        
        include_once getFullPath('/docoffice/HtmlToDoc.php');
        
        $c = new HTML_TO_DOC();
        $b = $c->createDoc($htmlFile, $docFile);
        
        expect(is_file($docFile));
        
        if (isset($bucket)) {
            $res = fileman::absorb($docFile, 'exportCsv');
        } else {
            $res = @file_get_contents($docFile);
        }
        
        return $res;
    }
}
