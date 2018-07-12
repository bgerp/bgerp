<?php


/**
 * Клас 'zbar_Reader' - Прочитана на баркодове от файл
 *
 * @category  vendors
 * @package   zbar
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class zbar_Reader
{
    /**
     * Масив със стойности, които ще се приемат за тип на баркод
     */
    protected static $barcodesArr = array('qr', 'ean', 'code', 'datamatrix', 'std', 'int', 'msi', 'codabar');
    
    
    /**
     * Връща баркодовете във файла
     *
     * @param fileHnd - Манупулатор на файла, в който ще се търсят баркодове
     *
     * @return array $barcodesArr - Масив с типовете и баркодовете във файла
     */
    public static function getBarcodesFromFile($fh)
    {
        // Масива с намерените баркодове
        $barcodesArr = array();
        
        // Екстрактваме файла и вземаме пътя до екстрактнатия файл
        $filePath = fileman::extract($fh);
        
        if (!$filePath) {
            
            return $barcodesArr;
        }
        
        // Изпълняваме командата за намиране на баркодове
        exec('zbarimg -q ' . escapeshellarg($filePath), $allBarcodesArr, $errorCode);
        
        // Изтриване на временния файл
        fileman::deleteTempPath($filePath);
        
        if (($errorCode !== 0) && ($errorCode !== 4)) {
            log_System::add('zbar_Reader', "Грешка (№{$errorCode}) при извличане на баркод от URL - '{$filePath}'", null, 'debug');
            
            throw new fileman_Exception('Възникна грешка при обработка.');
        }
        
        // Ако има окрит баркод
        if ((is_array($allBarcodesArr)) && count($allBarcodesArr)) {
            $fBarcodeStr = '';
            
            // Обикаляме намерените баркодове
            foreach ($allBarcodesArr as $key => $barcode) {
                
                // Разделяме типа на баркода от съдържанието му
                list($barcodeType, $barcodeStr) = explode(':', $barcode, 2);
                $isBarcodeType = false;
                foreach (self::$barcodesArr as $bStr) {
                    if (stripos($barcodeType, $bStr) === 0) {
                        $isBarcodeType = true;
                        break;
                    }
                }
                
                if (!$isBarcodeType) {
                    if ($barcodeType || $barcodeStr) {
                        $fBarcodeStr .= "\n" . $barcodeType . ':' . $barcodeStr;
                    }
                    
                    continue;
                }
                if (!is_object($barcodesArr[$key])) {
                    $barcodesArr[$key] = new stdClass();
                }
                
                if (!empty($fBarcodeStr)) {
                    $barcodeStr .= $fBarcodeStr;
                    $fBarcodeStr = '';
                }
                
                // Записваме намерените резултатис
                $barcodesArr[$key]->type = $barcodeType;
                $barcodesArr[$key]->code = $barcodeStr;
            }
            
            if (!empty($fBarcodeStr)) {
                if (!empty($barcodesArr)) {
                    end($barcodesArr);
                    $key = key($barcodesArr);
                    $barcodesArr[$key]->code .= $fBarcodeStr;
                }
            }
        }
        
        return $barcodesArr;
    }
}
