<?php


/**
 * Версията на пакета за генериране на баркодове
 */
defIfNot('PHP_BARCODE_VERSION', '2.0.1');


/**
 * Версията на пакета за генериране на баркодове
 */
defIfNot('PHP_QRCODE_VERSION', '1.1.4');


/**
 * Клас 'barcode_Generator' - Генериране на баркодове
 *
 * @category  vendors
 * @package   barcode
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class barcode_Generator extends core_Manager
{
    
    
    /**
     * Генерира и връща баркод GD image
     * 
     * @param string $type    - Типа на баркода, който ще се генерира - Пример: QR, EAN13, EAN8 и т.н.
     * @param string $content - Съдържанието на баркода
     * @param mixed  $size    - Масив с широчината width и височината height на изображенията. Съща така може да приема и стринг 
     * (small, medium, large), които са размери зададени по подразбиране спрямо типа на баркода.
     * @param array  $params  - Други параметри. 
     * 
     * За QR:
     * $params['pixelPerPoint'] - Брой пиксели на точка. По подразбиране е 3.
     * $params['outFileName'] - Името на файла
     * $params['quality'] - Качеството на QR изображението. По подразбиране е 'l'
     * $params['outerFrame'] - Рамката на изображението. По подразбирена е 0.
     * 
     * За останалите:
     * $params['angle'] - Ъгул на завъртане. По подразбиране е 0. Представалява ъгъла на завъртане спрямо центъра.
     * Ако е 90 или 270, баркода ще е вертикален. Ако е 180 баркода ще е обърнат надолу.
     *
     * @param array $output - Масив, в който се записват данните след генерирането на баркода 
     * 
     * @return gd img $im
     */
    static function getImg($type, $conten, $size = NULL, $params = array(), &$output=array())
    {
        $type = strtolower($type);
        
        // Разрешените типове за баркодове
        $permittedType = array( 
            'codabar',
            'code11',
            'code39',
            'code93',
            'code128',
            'ean8',
            'ean13',
            'std25',
            'int25',
            'msi',
            'datamatrix',
            'qr');
        
        // Очакваме да е подаден един от разрешените типове баркод
        expect(in_array($type, $permittedType), "Не се поддържа '{$type}' баркод.");
        
        // Ако баркода е QR
        if (strtolower($type) == 'qr') {
            
            // Вкарваме файловете необходими за работа с програмата.
            require_once 'phpqrcode/' . PHP_QRCODE_VERSION . '/qrlib.php';
            
            // Ако не се зададени параметрите, използваме по подразбиране
            $pixelPerPoint = $params['pixelPerPoint'] ? $params['pixelPerPoint'] : 3;
            $outFileName = $params['outFileName'] ? $params['outFileName'] : NULL;
            $quality = $params['quality'] ? $params['quality'] : 'l';
            $outerFrame = $params['outerFrame'] ? $params['outerFrame'] : 0;

            // Генерира QR изображение
            $im = QRcode::png($conten, $outFileName, $quality, $pixelPerPoint, $outerFrame);
            
            return $im;
        }
        
        // Вкарваме файловете необходими за работа с баркодове
        require_once 'phpbarcode/' . PHP_BARCODE_VERSION . '/php-barcode.php';
        
        // Проверяваме дали подадени данни са коректни
        self::checkContent($type, $conten);
        
        // Минималната дължина и широчина на баркода
        $minWidthAndHeightArr = self::getMinWidthAndHeight($type, $conten);
        
        // Ако размера не е масив
        if (!is_array($size)) {
            
            // Преобразуваме размера от текст
            $size = self::getSizeFromText($type, $size);
        }
        
        // Проверява размерите дали са въведени коректно
        self::checkSizes($type, $size, $minWidthAndHeightArr);
        
        // Съотношението за определяна на размерите на баркода
        $ratioArr = self::getBarcodeRatio($type, $size, $minWidthAndHeightArr);

        // Размера на изображението в коет ще е баркода
        $width = $size['width'];
        $height = $size['height'];
        
        // Създаваме GD изображението
        $im = imagecreatetruecolor($width, $height);  
        
        // Цвета на баркода - черен
        $black = ImageColorAllocate($im,0x00,0x00,0x00);
        
        // Фона на баркода - бял цвят
        $white = ImageColorAllocate($im,0xff,0xff,0xff);  
        
        // Боядисваме изображението в цвета на фона
        imagefilledrectangle($im, 0, 0, $width, $height, $white); 
        
        // Генерираме баркода
        $output = Barcode::gd($im, $black, $width/2, $height/2, 0, $type, $conten, $ratioArr['width'], $ratioArr['height']);  

        // Съобщение за грешка, ако не може да се генерира баркода
        expect($output, 'Не може да се генерира баркода.');
        
        // Ъгъл на завъртане на баркода
        if ($params['angle']) {
            $im = imagerotate($im, $params['angle'], $white);    
        }
        
        return $im;
    }
    
    
	/**
     * Показва баркод изображението
     * 
     * @param string $type    - Типа на баркода, който ще се генерира - Пример: QR, EAN13, EAN8 и т.н.
     * @param string $content - Съдържанието на баркода
     * @param mixed  $size    - Масив с широчината width и височината height на изображенията. Съща така може да приема и стринг 
     * (small, medium, large), които са размери зададени по подразбиране спрямо типа на баркода.
     * @param array  $params  - Други параметри. 
     * 
     * За QR:
     * $params['pixelPerPoint'] - Брой пиксели на точка. По подразбиране е 3.
     * $params['outFileName'] - Името на файла
     * $params['quality'] - Качеството на QR изображението. По подразбиране е 'l'
     * $params['outerFrame'] - Рамката на изображението. По подразбирена е 0.
     * 
     * За останалите:
     * $params['angle'] - Ъгул на завъртане. По подразбиране е 0. Представалява ъгъла на завъртане спрямо центъра.
     * Ако е 90 или 270, баркода ще е вертикален. Ако е 180 баркода ще е обърнат надолу.
     * 
     * @return - Показва изображението и спира изпълнението на скрипта
     */
    static function printImg($type, $conten, $size, $params=array())
    {
        $gdImg = self::getImg($type, $conten, $size, $params);

        header('Content-type: image/png');
        imagepng($gdImg);
        imagedestroy($gdImg);
        shutdown();
    }
    
    
    /**
     * Връща минималната дължина и широчина на баркода,
     * в зависимост от типа и съдържанието на текста,
     * за да може да се покаже коректно.
     * 
     * @param string $type - Типа на баркода
     * @param string $text - Текста, който ще се проверява
     * 
     * @return array $minWidthAndHeight - Масив с минималната дължина и широчина на типа
     * 
     * @access private
     */
    static function getMinWidthAndHeight($type, $text)
    {
        // Типа да е с малки букви
        $type = strtolower($type);
        
        // Височината е различна само при квадратните баркодове - QR и codabar
        $height = 1;
        
        // Опредяляме минималната дъжина и широчина
        switch ($type) {
            
            case 'codabar':
                $width = strlen(BarcodeCodabar::getDigit($text));
            break;
            
            case 'code11':
                $width = strlen(Barcode11::getDigit($text));
            break;
            
            case 'code39':
                $width = strlen(Barcode39::getDigit($text));
            break;
            
            case 'code93':
                $width = strlen(Barcode93::getDigit($text, TRUE));
            break;
            
            case 'code128':
                $width = strlen(Barcode128::getDigit($text));
            break;
            
            case 'ean8':
                $width = strlen(BarcodeEAN::getDigit($text, 'ean8'));
            break;
            
            case 'ean13':
                $width = strlen(BarcodeEAN::getDigit($text, 'ean13'));
            break;
            
            case 'std25':
                $width = strlen(BarcodeI25::getDigit($text, TRUE, 'std25'));
            break;
            
            case 'int25':
                $width = strlen(BarcodeI25::getDigit($text, TRUE, 'int25'));
            break;
            
            case 'msi':
                $width = strlen(BarcodeMSI::getDigit($text, TRUE));
            break;
            
            case 'datamatrix':
                $width = count(BarcodeDatamatrix::getDigit($text, FALSE));
                $height = &$width;
            break;
            
            case 'qr':
                
            break;
            
            default:
                expect(FALSE, "Типа '{$type}' не е дефиниран.");
            break;
            
        }

        // Очакваме да може да се определят размерите
        if (!$width || !$height) expect(FALSE, 'Не може да се определят размерите на баркода.');
        
        // Ако широчината не е четно число, тогава му добавяме единица
        if ($width % 2 != 0) {
            $width++;
        }
        
        //Записмава данните в масива
        $minWidthAndHeight['width'] = $width;
        $minWidthAndHeight['height'] = $height;

        return $minWidthAndHeight;
    }
    
    
    /**
     * Определяме 
     */
    static function getBarcodeRatio($type, $size, $minWidthAndHeight)
    {
        //intval->round
        // Определяме съотношениет на баркода, за да няма разтягане на баркода
        $newSize['width'] = intval($size['width']/$minWidthAndHeight['width']);
        $newSize['height'] = intval($size['height']/$minWidthAndHeight['height']);
        
        return $newSize;
    }
    
    
    /**
     * Проверява въведени данни дали са коректни. Ако има грешка, throw' а грешка.
     * 
     * @param string $type - Типа, за който ще се проверява
     * @param string $content - Съдържаниете, което ще се проверява
     */
    static function checkContent($type, $content)
    {
        // Типа да е с малки букви
        $type = strtolower($type);
        
        switch ($type) {
            
            case 'ean8':
                expect(preg_match("/^[0-9]{7,8}$/", $content), "Типа '{$type}' позволява само числа с дължина 7 или 8 цифри. Въвели сте " . mb_strlen($content) . " символа.");    
            break;
            
            case 'ean13':
                expect(preg_match("/^[0-9]{12,13}$/", $content), "Типа '{$type}' позволява само числа с дължина 12 или 13 цифри. Въвели сте " . mb_strlen($content) . " символа.");    
            break;
                
            case 'codabar':    
            case 'msi':
                expect(preg_match("/^[0-9]+$/", $content), "Типа '{$type}' позволява само цифри.");
            break;
            
            case 'code11':
                expect(preg_match("/^[0-9\-]+$/", $content), "Типа '{$type}' позволява само цифри и '-'.");
            break;
            
            default:
                
            break;
        }
    }
    
    
    /**
     * В зависимост от типа преобразува подадения текст в масив от широчина и височина на баркода
     * 
     * @param string $type - Типа на баркода
     * @param string $size - Размере в текстов вид - small, medium или large
     * 
     * @return array $sizeArr - Масив с височина и широчина за съответния тип
     */
    static function getSizeFromText($type, $size)
    {
        $sizeArr = array();
        
        $size = strtolower($size);
        $type = strtolower($type);
        
        // В зависимост от размера и типа връщаме размерите
        switch ($size) {
            case 'small':
                
                switch ($type) {
                    case 'datamatrix':
                    case 'qr':
                        $sizeArr['width'] = 50;
                        $sizeArr['height'] = 50;    
                    break;  
    
                    default:
                        $sizeArr['width'] = 100;
                        $sizeArr['height'] = 20;    
                    break;
                }
                
            break;
            
            case 'medium':
                
                switch ($type) {
                    case 'datamatrix':
                    case 'qr':
                        $sizeArr['width'] = 100;
                        $sizeArr['height'] = 100;    
                    break;  
    
                    default:
                        $sizeArr['width'] = 200;
                        $sizeArr['height'] = 50;    
                    break;
                }
                
            break;
            
            case 'large':
                
                switch ($type) {
                    case 'datamatrix':
                    case 'qr':
                        $sizeArr['width'] = 150;
                        $sizeArr['height'] = 150;    
                    break;  
    
                    default:
                        $sizeArr['width'] = 300;
                        $sizeArr['height'] = 100;    
                    break;
                }
                
            break;
            
            default:
                // Очакваме да има текст, който да отговаря на размерите
                expect(FALSE, "Размера трябва да е масив с 'width' и 'height' или стринг от изброените: small, medium, large. Въвели сте '{$size}'.");
            break;
        }
        
        return $sizeArr;
    }
    
    
    /**
     * Проверява размерите дали са въведени коректно
     * 
     * @param string $type - Типа на баркода
     * @param array $size - Масив с височината и широчината на баркода
     */
    static function checkSizes($type, $size, $minWidthAndHeightArr)
    {
        $type = strtolower($type);
        
         // В зависимост от размера и типа връщаме размерите
        switch ($type) {
            case 'datamatrix':
            case 'qr':
                expect($size['width'] == $size['height'], "За типа '{$type}' височината и широчината трябва да са равни.");
            break;  

            default:
                expect($size['width'], 'Не сте въвели широчина.');
                expect($size['height'], 'Не сте въвели височина.');
            break;    
        }
        
        // Минималната широчина на баркода трябва да е по малка от широчината зададена от потребителя
        expect($size['width'] >= $minWidthAndHeightArr['width'], "Минималната широчина за баркода е {$minWidthAndHeightArr['width']}");
        expect($size['height'] >= $minWidthAndHeightArr['height'], "Минималната височина за баркода е {$minWidthAndHeightArr['height']}");
    }
    
    
	/**
     * Връща баркодовете във файла
     * 
     * @param fileHnd - Манупулатор на файла, в който ще се търсят баркодове
     * 
     * @return array $barcodesArr - Масив с типовете и баркодовете във файла
     */
    static function getBarcodesFromFile($fh)
    {
        // Генерираме URL за сваляне на файл
        $downloadUrl = fileman_Download::getDownloadUrl($fh);

        // Изпълняваме командата за намиране на баркодове
        exec("zbarimg {$downloadUrl}", $allBarcodesArr);
        
        // Масива с намерените баркодове
        $barcodesArr = array();
        
        // Ако има окрит баркод
        if (count($allBarcodesArr)) {
            
            // Обикаляме намерените баркодове
            foreach ($allBarcodesArr as $key => $barcode) {
                
                // Разделяме типа на баркода от съдържанието му
                $explodeBarcodeArr = explode(':', $barcode);
                
                // Записваме намерените резултатис
                $barcodesArr[$key]->type = $explodeBarcodeArr[0];
                $barcodesArr[$key]->code = $explodeBarcodeArr[1];
            }
        }
        
        return $barcodesArr;
    }
}