<?php



/**
 * Версията на пакета за генериране на баркодове
 */
defIfNot('PHP_BARCODE_VERSION', '2.0.3');


/**
 * Версията на пакета за генериране на баркодове
 */
defIfNot('PHP_QRCODE_VERSION', '1.1.4');

/**
 * Вкарваме файловете необходими за работа с баркодове
 */
require_once 'phpbarcode/' . PHP_BARCODE_VERSION . '/php-barcode.php';

/**
 * Вкарваме файловете необходими за работа с програмата.
 */
require_once 'phpqrcode/' . PHP_QRCODE_VERSION . '/qrlib.php';


/**
 * Клас 'barcode_Generator' - Генериране на баркодове
 *
 * @category  bgerp
 * @package   barcode
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class barcode_Generator extends core_Manager
{
    
    
    /**
     * Сол за генериране на ключ за криптиране
     */
    const KEY_SALT = 'BARCODE IMAGE';
    
    /**
     * Масив с поддържаните баркодове и вербалните им стойности
     */
    static $barcodeTypesArr = array(
        'qr' => 'QR',
        'ean8' => 'EAN-8',
        'ean13' => 'EAN-13',
        'code11' => 'code11',
        'code39' => 'code39',
        'code93' => 'code93',
        'code128' => 'code128',
        'datamatrix' => 'DataMatrix',
        'std25' => 'STD25',
        'int25' => 'INT25',
        'msi' => 'MSI',
        'codabar' => 'Codabar',
    );
    
    /**
     * Размер на шрифта
     */
    static $fontSize = 11;
    
    /**
     * Път до шрифта
     */
    static $font = 'barcode/fonts/FreeSans.ttf';
    
    
    /**
     * Връща всички баркодове, за които можем да генерираме изображение
     *
     * @return array -
     */
    static function getAllowedBarcodeTypesArr()
    {
        
        return self::$barcodeTypesArr;
    }
    
    
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
     * $params['saveAndPrint'] - При генериране на изображение, да се запише във файла и да се изведе на екрана.
     *
     * За останалите:
     * $params['angle'] - Ъгъл на завъртане. По подразбиране е 0. Представалява ъгъла на завъртане спрямо центъра.
     * Ако е 90 или 270, баркода ще е вертикален. Ако е 180 баркода ще е обърнат надолу.
     * $params['addText'] - Дали да се дабави текста под баркодата
     * $params['addText']['font'] - Определен шрифт от системата
     * $params['addText']['fontSiz'] - Размер на шрифта
     * $params['addText']['bgOnlyText'] - Фона (background) на текста, да е колко дължината му
     *
     * @param array $output - Масив, в който се записват данните след генерирането на баркода
     *
     * @return resource gdRes
     */
    static function getImg($type, $content, $size = NULL, $params = array(), &$output = array())
    {
        $type = strtolower($type);
        
        // Разрешените типове за баркодове
        $permittedType = self::getAllowedBarcodeTypesArr();
        
        // Очакваме да е подаден един от разрешените типове баркод
        expect($permittedType[$type], "Не се поддържа '{$type}' баркод.");
        
        // Ако баркода е QR
        if (strtolower($type) == 'qr') {
            
            // Ако не се зададени параметрите, използваме по подразбиране
            $pixelPerPoint = $params['pixelPerPoint'] ? $params['pixelPerPoint'] : 3;
            $outFileName = $params['outFileName'] ? $params['outFileName'] : FALSE;
            $quality = $params['quality'] ? $params['quality'] : 'l';
            $outerFrame = $params['outerFrame'] ? $params['outerFrame'] : 0;
            $params['saveAndPrint'] = $outFileName ? $params['saveAndPrint'] : FALSE;
            
            // Генерира QR изображение
            $im = QRcode::png($content, $outFileName, $quality, $pixelPerPoint, $outerFrame, $params['saveAndPrint']);
            
            return $im;
        }
        
        // Проверяваме дали подадени данни са коректни
        self::checkContent($type, $content);
        
        // Минималната дължина и широчина на баркода
        $minWidthAndHeightArr = self::getMinWidthAndHeight($type, $content);

        // Ако размера не е масив
        if (!is_array($size)) {
            
            // Преобразуваме размера от текст
            $size = self::getSizeFromText($type, $size);
        }
        
        // Вземаем размерите в зависимост от съотношението
        $size['width'] = self::getNewSize($size['width'], $params['ratio']);
        $size['height'] = self::getNewSize($size['height'], $params['ratio']);
        
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
        $black = ImageColorAllocate($im, 0x00, 0x00, 0x00);
        
        // Фона на баркода - бял цвят
        $white = ImageColorAllocate($im, 0xff, 0xff, 0xff);
        
        // Боядисваме изображението в цвета на фона
        imagefilledrectangle($im, 0, 0, $width, $height, $white);
        
        // Генерираме баркода
        $output = Barcode::gd($im, $black, $width / 2, $height / 2, 0, $type, $content, $ratioArr['width'], $ratioArr['height']);
        
        // Съобщение за грешка, ако не може да се генерира баркода
        expect($output, 'Не може да се генерира баркода.');
        
        // Ако ще се добавя и текст
        // На QR и datamatrix не се добавя
        if (isset($params['addText']) && $type != 'qr' && $type != 'datamatrix') {
            
            // Ако не е зададен размер на шрифта
            if (!($fontSize = $params['addText']['fontSize'])) {
                
                // Задава стойността
                $fontSize = self::$fontSize;
            }
            
            // Вземаме размера на шрифта в зависимост от съоношението
            $fontSize = self::getNewSize($fontSize, $params['ratio']);
            
            // Ако не е зададен фонт
            if (!($font = $params['addText']['font'])) {
                
                // Задаваме стойността
                $font = self::$font;
            }
            
            // Фонт на шрифта
            $font = core_App::getFullPath($font);
            
            // Вземаме размерите на шрифта
            $box = imagettfbbox($fontSize, 0, $font, $output['hri']);
            
            // Отместване
            $marginBottom = 2;
            $marginTop = 4;
            
            // Височината на фонта
            $f = abs($box[5]);
            
            // Дължина на квадрата - от край до край
            $x1 = 0;
            $x2 = $width;
            
            // Височина на квадрата
            $y1 = $height-$f - $marginTop;
            $y2 = $height + $f;
            
            // Ако е зададено да се отрязва само колкото е дълъг текста
            if ($params['addText']['bgOnlyText']) {
                
                // Тези отрязват квадрат, колкото е големината на текста
                $x1 = $width / 2 - abs($box[2]) / 2;
                $x2 = $width / 2 + abs($box[2]) / 2;
            }
            
            // Начертаваме квадрат в долната част
            imagefilledrectangle($im, $x1, $y1, $x2, $y2, $white);
            
            // X и Y координатите на текста
            $textX = $width / 2 - $box[2] / 2;
            $textY = $height - $marginBottom;
            
            // Добавяме надписа в празното квадратче
            imagettftext($im, $fontSize, 0, $textX, $textY, $black, $font, $output['hri']);
        }
        
        // Ъгъл на завъртане на баркода
        if ($params['angle']) {
            
            // Завъртаме избображението
            $im = imagerotate($im, $params['angle'], $white);
        }
        
        return $im;
    }
    
    
    /**
     * Връща новия размер в зависимост от съотношението
     *
     * @param number $size - Размер
     * @param integer $ratio - Съотношение
     */
    static function getNewSize($size, $ratio)
    {
        // Ако има размер и не е 1
        if ($ratio && $ratio != 1) {
            
            // Умножаваме размера по съотношението
            $size *= $ratio;
        }
        
        return $size;
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
     * $params['angle'] - Ъгъл на завъртане. По подразбиране е 0. Представалява ъгъла на завъртане спрямо центъра.
     * Ако е 90 или 270, баркода ще е вертикален. Ако е 180 баркода ще е обърнат надолу.
     *
     * Показва изображението и спира изпълнението на скрипта
     */
    static function printImg($type, $content, $size, $params = array())
    {
        // Вземаме изображението
        $im = self::getImg($type, $content, $size, $params);
        
        // Ако е ресурс
        if (is_resource($im)) {
            
            // Връщаме png
            imagepng($im);
        }
        
        header('Content-Type: image/png');
        
        shutdown();
    }
    
    
    /**
     * Връща HTML 'img' линк за показване на баркод
     *
     * @param string $type    - Типа на баркода, който ще се генерира - Пример: QR, EAN13, EAN8 и т.н.
     * @param string $content - Съдържанието на баркода
     * @param array  $size    - Масив с широчината width и височината height на изображенията. Съща така може да приема и стринг
     *
     * @param array - img таг с линка
     */
    static function getLink($type, $content, $size = NULL, $params = array())
    {
        $attr = array();
        
        // Вземаме линка
        $attr['src'] = self::getUrl($type, $content, $size, $params);
        
        // Задаваме аттрибутите на тага
        $attr['alt'] = $content;
        $attr['class'] = $params['class'];
        
        // Ако е зададен определен ъгъл
        switch ($params['angle']) {
            
            // Ако е 0 или 180
            case 0 :
            case 180 :
                $attr['width'] = $size['width'];
                $attr['height'] = $size['height'];
                break;
                
                // Ако е 90 или 270
                // Да се разменят местата на размерите
            case 90 :
            case 270 :
                $attr['width'] = $size['height'];
                $attr['height'] = $size['width'];
                break;
            
            default :
            ;
            break;
        }
        
        // Създаваме линка
        $link = ht::createElement('img', $attr);
        
        return $link;
    }
    
    
    /**
     * Връща URL за показване на баркод
     *
     * @param string $type    - Типа на баркода, който ще се генерира - Пример: QR, EAN13, EAN8 и т.н.
     * @param string $content - Съдържанието на баркода
     * @param array  $size    - Масив с широчината width и височината height на изображенията. Съща така може да приема и стринг
     *
     * @param core_Et - Линк
     */
    static function getUrl($type, $content, $size = NULL, $params = array())
    {
        // Ако е зададен да е абсолют
        if ($params['absolute']) {
            
            // Линка да е абсолютен
            $linkType = 'absolute';
        } else {
            
            // Линка да е релативен
            $linkType = 'relative';
        }
        
        // Масив с данните за криптиране
        $cryptArr = array();
        $cryptArr['type'] = $type;
        $cryptArr['content'] = $content;
        $cryptArr['size'] = $size;
        $cryptArr['params'] = $params;
        
        // Криптираме данните
        $id = core_Crypt::encodeVar($cryptArr, self::getCryptKey());
        
        // Връщаме линка
        return toUrl(array('barcode_Generator', 'S', 't' => $id), $linkType);
    }
    
    
    /**
     * Връща ключа за криптиране на отложение връзки
     *
     * @return string - Ключ за кодиране
     */
    static function getCryptKey()
    {
        $key = sha1(EF_SALT . self::KEY_SALT);
        
        return $key;
    }
    
    
    /**
     * Екшън за показване на баркод
     */
    function act_S()
    {
        // Параметрите
        $t = Request::get('t');
        
        // Масив с параметрите
        $arr = core_Crypt::decodeVar($t, self::getCryptKey());
        
        // Показваме изображението
        self::printImg($arr['type'], $arr['content'], $arr['size'], $arr['params']);
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

        $text = (string)$text;
        // Опредяляме минималната дъжина и широчина
        switch ($type) {
            
            case 'codabar' :
                $width = strlen(BarcodeCodabar::getDigit($text));
                break;
            
            case 'code11' :
                $width = strlen(Barcode11::getDigit($text));
                break;
            
            case 'code39' :
                $width = strlen(Barcode39::getDigit($text));
                break;
            
            case 'code93' :
                $width = strlen(Barcode93::getDigit($text, TRUE));
                break;
            
            case 'code128' :
                $width = strlen(Barcode128::getDigit($text));
                break;
            
            case 'ean8' :
                $width = strlen(BarcodeEAN::getDigit($text, 'ean8'));
                break;
            
            case 'ean13' :
                $width = strlen(BarcodeEAN::getDigit($text, 'ean13'));
                break;
            
            case 'std25' :
                $width = strlen(BarcodeI25::getDigit($text, TRUE, 'std25'));
                break;
            
            case 'int25' :
                $width = strlen(BarcodeI25::getDigit($text, TRUE, 'int25'));
                break;
            
            case 'msi' :
                $width = strlen(BarcodeMSI::getDigit($text, TRUE));
                break;
            
            case 'datamatrix' :
                $width = count(BarcodeDatamatrix::getDigit($text, FALSE));
                $height = &$width;
                break;
            
            case 'qr' :
                $width = $height;
                break;
            
            default :
            expect(FALSE, "Типа '{$type}' не е дефиниран.");
            break;
        }
        
        // Очакваме да може да се определят размерите
        if (!$width || !$height) {
            self::logWarning("Проблем при генериране на баркод с текст '{$text}' в тип '{$type}'");
            expect(FALSE, 'Проблем при генериране на баркод', $text, $type);
        }
        
        // Ако широчината не е четно число, тогава му добавяме единица
        if ($width % 2 != 0) {
            $width++;
        }
        
        //Записмава данните в масива
        $minWidthAndHeight = array();
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
        $newSize = array();
        $newSize['width'] = intval($size['width'] / $minWidthAndHeight['width']);
        $newSize['height'] = intval($size['height'] / $minWidthAndHeight['height']);
        
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
            
            case 'ean8' :
                expect(preg_match("/^[0-9]{7,8}$/", $content), "Типа '{$type}' позволява само числа с дължина 7 или 8 цифри. Въвели сте " . mb_strlen($content) . " символа.");
                break;
            
            case 'ean13' :
                expect(preg_match("/^[0-9]{12,13}$/", $content), "Типа '{$type}' позволява само числа с дължина 12 или 13 цифри. Въвели сте " . mb_strlen($content) . " символа.");
                break;
            
            case 'codabar' :
            case 'msi' :
                expect(preg_match("/^[0-9]+$/", $content), "Типа '{$type}' позволява само цифри.");
                break;
            
            case 'code11' :
                expect(preg_match("/^[0-9\-]+$/", $content), "Типа '{$type}' позволява само цифри и '-'.");
                break;
            
            default :
            
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
            case 'small' :
                
                switch ($type) {
                    case 'datamatrix' :
                    case 'qr' :
                        $sizeArr['width'] = 50;
                        $sizeArr['height'] = 50;
                        break;
                    
                    default :
                    $sizeArr['width'] = 100;
                    $sizeArr['height'] = 20;
                    break;
                }
                
                break;
            
            case 'medium' :
                
                switch ($type) {
                    case 'datamatrix' :
                    case 'qr' :
                        $sizeArr['width'] = 100;
                        $sizeArr['height'] = 100;
                        break;
                    
                    default :
                    $sizeArr['width'] = 200;
                    $sizeArr['height'] = 50;
                    break;
                }
                
                break;
            
            case 'large' :
                
                switch ($type) {
                    case 'datamatrix' :
                    case 'qr' :
                        $sizeArr['width'] = 150;
                        $sizeArr['height'] = 150;
                        break;
                    
                    default :
                    $sizeArr['width'] = 300;
                    $sizeArr['height'] = 100;
                    break;
                }
                
                break;
            
            default :
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
            case 'datamatrix' :
            case 'qr' :
                expect($size['width'] == $size['height'], "За типа '{$type}' височината и широчината трябва да са равни");
                break;
            
            default :
            expect($size['width'], 'Не сте въвели широчина на баркода');
            expect($size['height'], 'Не сте въвели височина на баркода');
            break;
        }
        
        // Минималната широчина на баркода трябва да е по-малка от широчината зададена от потребителя
        expect($size['width'] >= $minWidthAndHeightArr['width'], "Минималната широчина за баркода е {$minWidthAndHeightArr['width']}");
        expect($size['height'] >= $minWidthAndHeightArr['height'], "Минималната височина за баркода е {$minWidthAndHeightArr['height']}");
    }
    
    
    /**
     * Връща линк към подадения обект
     * 
     * @param integer $objId
     * 
     * @return core_ET
     */
    public static function getLinkForObject($objId)
    {
        
        return ht::createLink(get_called_class(), array());
    }
}