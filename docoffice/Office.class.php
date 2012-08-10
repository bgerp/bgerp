<?php


/**
 * Пътя до офис пакет
 */
defIfNot('OFFICE_PACKET_PATH', '/usr/lib/openoffice/program/soffice.bin');


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
    static function startOffice()
    {
        // Заключваме офис пакета
        static::lockOffice(20, 10);
        
//        pclose(popen(OFFICE_PACKET_PATH . "2>&1 >/dev/null &", "r"));
        $srated = pclose(popen(OFFICE_PACKET_PATH . ' &', "r"));

        // Ако е стартиран успешно
        if ($srated == 0) {
            
            // Нулираме брояча за конвертиранията
            static::emptyConvertCount();
            
            core_Logs::Log(OFFICE_PACKET_PATH . tr('| е стартиран.|*'));
            
            // Отключваме процеса
            static::unlockOffice();
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Проверява и ако има нужда рестартира офис пакета
     */    
    static function checkRestartOffice()
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
    static function restartOffice()
    {
        // Убиваме офис пакета
        static::killOffice();
        
        // Стартираме офис пакета
        static::startOffice();
    }

    
    /**
     * Убива процеса на офис пакета
     */
    static function killOffice()
    {
        // Вземаме process id' то на офис пакета
        $pid = static::getStartedOfficePid();
        
        if (!$pid) return ;
        
        // Заключваме офис пакета
        static::lockOffice(100, 50);

        // Убиваме процеса
        $sh = "kill {$pid}";
        exec($sh, $dummy, $res);
        
        // Ако всичко е минало както трябва
        if ($res == 0) {
            
            // Премахваме от перманентните данни
            permanent_Data::remove('countOfficeProccess');
            
            // Отключваме процеса
            static::unlockOffice();
            
            core_Logs::Log(OFFICE_PACKET_PATH . tr('| е спрян.|*'));
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
	/**
     * Вземаме process id' то на стартирания процес
     */
    static function getStartedOfficePid()
    {
        // Заключваме офис пакета
        static::lockOffice(20, 10);
        
        // Определяме името на офис пакета
        $baseName = basename(OFFICE_PACKET_PATH);
        
        // Намираме process id' то на офис пакета
        $sh = "ps ux | grep {$baseName} | grep -v grep | awk '{ print $2 }' | head -1";
        $pid = exec($sh);
        
        // Отключваме процеса
        static::unlockOffice();
        
        return $pid;
    }
    
    
    /**
     * Увеличаваме с единица броя на обработените документи
     */
    static function increaseConvertCount()
    {
        // Вземаме броя на обаработени документи
        $data = static::getConvertedCount();
        
        // Увеличаваме с единица
        permanent_Data::write('countOfficeProccess', ++$data);
    }
    
    
    /**
     * Връща броя на обработените документи
     */
    static function getConvertedCount()
    {
        $data = (int)permanent_Data::read('countOfficeProccess');
        
        return $data;
    }
    
    
    /**
     * Изпразваме брояча за обработените документи
     */
    static function emptyConvertCount()
    {
        permanent_Data::write('countOfficeProccess', 0);
    }
    
    
    /**
     * Заключваме офис пакета
     * 
     * @param int $maxDuration - Максималното време за което ще се опитаме да заключим
     * @param int $maxTray - Максималният брой опити, за заключване
     */
    static function lockOffice($maxDuration=20, $maxTray=10)
    {
        core_Locks::get('OfficePacket', $maxDuration, $maxTray, FALSE);
    }
    
    
    /**
     * Отключваме офис пакета
     */
    static function unlockOffice()
    {
        core_Locks::release('OfficePacket');
    }
}