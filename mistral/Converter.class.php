<?php


/**
 * OCR обработка на файлове с помощта на mistral.ai
 *
 * @category  vendors
 * @package   mistral
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class mistral_Converter extends core_Manager
{
    /**
     * Интерфейсни методи
     */
    public $interfaces = 'fileman_OCRIntf, fileman_FileActionsIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Mistral.AI';
    
    
    /**
     * Кои потребители имат права за OCR на докуемент
     */
    public static $canOCR = 'powerUser';
    public $canOcr = 'powerUser';
    
    
    /**
     * Позволените разширения
     */
    public static $allowedExt = array('pdf', 'bmp', 'pcx', 'dcx', 'jpeg', 'jpg', 'tiff', 'tif', 'gif', 'png');

    
    /**
     * Интерфейсен метод на fileman_FileActionsIntf
     *
     * Връща масив с действия, които могат да се извършат с дадения файл
     *
     * @param stdClass $fRec - Обект са данни от модела
     *
     * @return array|NULL $arr - Масив с данните
     *                    $arr['url'] - array URL на действието
     *                    $arr['title'] - Заглавието на бутона
     *                    $arr['icon'] - Иконата
     */
    public static function getActionsForFile_($fRec)
    {
        $arr = null;
        
        if (self::haveRightFor('ocr') && self::canExtract($fRec)) {
            $btnParams = array();
            
            $btnParams['order'] = 80;
            $btnParams['title'] = 'Разпознаване на текст с mistral.ai';
            
            // Ако вече е извлечена текстовата част
            $procTextOcr = fileman_Indexes::isProcessStarted(array('type' => 'textOcr', 'dataId' => $fRec->dataId));
            if ($procTextOcr) {
                $btnParams['warning'] = 'Файлът е преминал през разпознаване на текст';
            } elseif (!self::haveTextForOcr($fRec)) {
                $btnParams['warning'] = 'Няма текст за разпознаване';
            }

            $arr = array();
            $arr['mistral']['url'] = array(get_called_class(), 'getTextByOcr', $fRec->fileHnd, 'ret_url' => true);
            $arr['mistral']['title'] = 'OCR';
            $arr['mistral']['icon'] = 'img/16/mistral.png';
            $arr['mistral']['btnParams'] = $btnParams;
        }
        
        return $arr;
    }


    /**
     * Екшъна за извличане на текст чрез OCR
     *
     * @see fileman_OCRIntf
     */
    public function act_getTextByOcr()
    {
        // Манипулатора на файла
        $fh = Request::get('id');
        
        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fh);
        
        expect($fRec);
        
        // Очакваме да може да се извлича
        expect(static::canExtract($fRec));
        
        fileman_Files::requireRightFor('single', $fRec);
        
        $this->getTextByOcr($fRec);
        
        // URL' то където ще редиректваме
        $retUrl = getRetUrl();
        
        // Ако не може да се определи
        if (empty($retUrl)) {
            
            // URL' то където ще редиректваме
            $retUrl = array('fileman_Files', 'single', $fRec->fileHnd);
        }
        
        if ($fRec->dataId && ($dRec = fileman_Data::fetch((int) $fRec->dataId))) {
            fileman_Data::resetProcess($dRec);
        }
        
        return new Redirect($retUrl);
    }
    
    
    /**
     *
     *
     * @param stdClass|string $fRec
     *
     * @return string|NULL
     *
     * @see fileman_OCRIntf
     */
    public function getTextByOcr($fRec)
    {
        // Инстанция на класа
        $me = get_called_class();
        
        // Параметри необходими за конвертирането
        $params = array(
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'textOcr',
        );
        
        if (is_object($fRec)) {
            $params['dataId'] = $fRec->dataId;
            $file = $fRec->fileHnd;
        } else {
            $params['isPath'] = true;
            $file = $fRec;
        }
        
        $lId = fileman_webdrv_Generic::prepareLockId($fRec);
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = fileman_webdrv_Generic::getLockId($params['type'], $lId);
        
        // Проверявама дали няма извлечена информация или не е заключен
        if (core_Locks::isLocked($params['lockId'])) {
            if ($params['asynch']) {
                // Добавяме съобщение
                status_Messages::newStatus('|В момента се прави тази обработка');
            }
        } else {
            // Заключваме процеса за определено време
            if (core_Locks::obtain($params['lockId'], 300, 0, 0, false)) {
                fileman_Data::logWrite('OCR обработка на файл с tesseract', $fRec->dataId);
                fileman_Files::logWrite('OCR обработка на файл с tesseract', $fRec->id);
                
                // Стартираме извличането
                return static::getText($file, $params);
            }
        }
    }
    
    
    /**
     * Вземаме текстова част от подадения файл
     *
     * @param string $fileHnd - Манипулатора на файла и път до файла
     * @param array  $params  - Допълнителни параметри
     *
     * @return string
     */
    public static function getText($fileHnd, $params)
    {
        core_App::setTimeLimit(300);
        
        $convArr = array();
        
        if (!$params['isPath']) {
            // Вземам записа за файла
            $fRec = fileman_Files::fetchByFh($fileHnd);
            
            // Очакваме да има такъв запис
            expect($fRec);
            
            // Очакваме да може да се извлече информация от файла
            expect(static::canExtract($fRec));
            
            $ext = fileman_Files::getExt($fRec->name);

            $fName = $fRec->name;
        } else {
            expect(static::canExtract($fileHnd));
            
            $ext = fileman_Files::getExt($fileHnd);

            $fName = basename($fileHnd);
        }

        $ocrModel = mistral_Setup::get('OCR_MODEL');
        if (!$ocrModel) {
            $ocrModel = null;
        }

        $documentType = 'document_url';
        if ($ext != 'pdf') {
            $documentType = 'image_url';
        }

        if (mistral_Setup::get('OCR_USE_BASE_64_ON_ATTACHED_IMAGES') == 'yes') {
            $fType = fileman::getType($fName);
            $fUrl = base64_encode(fileman::extractStr($fileHnd));
            $documentUrl = "data:{$fType};base64,{$fUrl}";
        } else {
            $documentUrl = fileman_Download::getDownloadUrl($fileHnd);
        }

        setIfNot($params['getImageBase64'], false);
        setIfNot($params['getMarkdown'], true);

        $data = array(
            'model' => $ocrModel,
            'document' => array(
                'type' => $documentType,
                $documentType => $documentUrl
            ),
            'include_image_base64' => $params['getImageBase64'],
        );

        $url = mistral_Setup::get('API_URL');
        $authBase = mistral_Setup::get('API_KEY');

        $curl = curl_init($url);

        // Да не се проверява сертификата
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, @json_encode($data));

        // Хедъри
        $headersArr = array('Content-Type: application/json', 'Accept: application/json');
        if (isset($authBase)) {
            $headersArr[] = "Authorization: Bearer {$authBase}";
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headersArr);


        // Изпълняваме заявката
        $response = curl_exec($curl);

        $res = '';

        // Затваряме cURL сесията
        curl_close($curl);

        $response = @json_decode($response);
        if ($response && $response->pages) {
            $md = '';
            foreach ($response->pages as $page) {
                $md .= $page->markdown;
            }
            if ($md) {
                if (!$params['getMarkdown']) {
                    $res = markdown_Render::Convert($md);
                } else {
                    $res = $md;
                }
            }
        }

        $params['content'] = $res;
        core_Locks::release($params['lockId']);
        fileman_Indexes::saveContent($params);

        return $res;
    }

    
    /**
     * Проверява дали файл с даденото име може да се екстрактва
     *
     * @param stdClass|string $fRec
     *
     * @return bool - Дали може да се екстрактва от файла
     *
     * @see fileman_OCRIntf
     */
    public static function canExtract($fRec)
    {
        $name = $fRec;
        if (is_object($fRec)) {
            $name = $fRec->name;
        }
        $ext = strtolower(fileman_Files::getExt($name));
        
        // Ако разширението е в позволените
        if ($ext && in_array($ext, self::$allowedExt)) {
            // Ако всичко е OK връщаме TRUE
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Бърза проврка дали има смисъл от OCR-ване на текста
     *
     * @param stdClass|string $fRec
     *
     * @see fileman_OCRIntf
     */
    public static function haveTextForOcr($fRec)
    {

        return true;
    }
}
