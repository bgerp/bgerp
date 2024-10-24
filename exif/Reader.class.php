<?php


/**
 * Връщане на exif информация за файл
 *
 * @category  vendors
 * @package   exif
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class exif_Reader
{
    /**
     * Връща exif информация за файла
     *
     * @param string $fileHnd - Манипулатор на файл
     */
    public static function get($fileHnd)
    {
        // Името на файла
        $name = fileman_Files::fetchByFh($fileHnd, 'name');
        
        // Масив с името и разширението на файла
        $namesAndExt = fileman_Files::getNameAndExt($name);
        
        // Разширението на файла
        $ext = strtolower($namesAndExt['ext']);

        // Разширението трябва да е един от посочните
        if (($ext != 'jpg') && ($ext != 'jpeg') && ($ext != 'tiff') && ($ext != 'tif') && ($ext != 'webp')) {
            
            return ;
        }

        $exif = core_Cache::get('EXIF_READ_DATA', $fileHnd);
        if ($exif !== false) {

            return $exif;
        }

        Mode::push('FILEMAN_STOP_LOG_INFO', true);

        try {
            // Пътя до файла
            $path = fileman::extract($fileHnd);
        } catch (core_exception_Expect $e) {
            log_System::add(get_called_class(), "Грешка при екстрактване на '{$fileHnd}'", null, 'warning');
        }

        Mode::pop('FILEMAN_STOP_LOG_INFO');
        
        // Трябва да има валиден път
        if (!$path) {
            
            return;
        }

        core_Debug::startTimer('EXIF_READER_TIMER');

        // EXIF информация
        $exif = @exif_read_data($path);

        core_Debug::stopTimer('EXIF_READER_TIMER');
        
        // Изтриваме временния файл
        fileman::deleteTempPath($path);

        core_Cache::set('EXIF_READ_DATA', $fileHnd, $exif, 10000);

        // Връщаме exif информация
        return $exif;
    }
    
    
    /**
     * Връща GPS координатите на файла от exif
     *
     * @param string $fileHnd - Манипулатор на файл
     *
     * @return array $gps - Масив с GPS позиция
     *               double $gps['lon'] - Дължнина
     *               double $gps['lat'] - Ширина
     */
    public static function getGps($fileHnd)
    {
        // Ако няма exif информация
        try{
            if (!($exif = static::get($fileHnd))) {
                
                return;
            }
        } catch(core_exception_Expect $e){
            reportException($e);
            
            return;
        }
        
        // Ако няма такава информация
        if (!$exif['GPSLongitude'] || !$exif['GPSLatitude']) {
            
            return;
        }
        
        // Вземаме координатите
        $gps = array();
        $gps['lon'] = static::getGpsCoord($exif['GPSLongitude'], $exif['GPSLongitudeRef']);
        $gps['lat'] = static::getGpsCoord($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
        
        return $gps;
    }
    
    
    /**
     * Пресмята GPS координатите
     *
     * @param string $exifCoord
     * @param string $hemi
     *
     * @return float
     */
    protected static function getGpsCoord($exifCoord, $hemi)
    {
        // Броя на координатите в масива
        $countExif = countR($exifCoord);
        
        // Градуси
        $degrees = $countExif > 0 ? static::gps2Num($exifCoord[0]) : 0;
        
        // Минути
        $minutes = $countExif > 1 ? static::gps2Num($exifCoord[1]) : 0;
        
        // Секуди
        $seconds = $countExif > 2 ? static::gps2Num($exifCoord[2]) : 0;
        
        // В кое полукълбо се намира
        $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;
        
        // Изчисляваме стойностите
        return $flip * ($degrees + ($minutes / 60) + ($seconds / 3600));
    }
    
    
    /**
     * Преобразува GPS в число
     *
     * @param string $coordPart
     *
     * @return float
     */
    protected static function gps2Num($coordPart)
    {
        // Разделяме числата
        $parts = explode('/', $coordPart);
        
        // Броя на частите
        $counts = countR($parts);
        
        // Ако няма части
        if (!$counts) {
            
            return 0;
        }
        
        // Ако имаме само една част, връщаме нея
        if (countR($parts) == 1) {
            
            return $parts[0];
        }
        
        // Изчислява и връщаме стойността
        return floatval($parts[0]) / floatval($parts[1]);
    }
}
