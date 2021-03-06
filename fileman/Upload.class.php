<?php


/**
 * Клас 'fileman_Upload' - качване на файлове от диалогов прозорец
 *
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_Upload extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'fileman_DialogWrapper';
    
    
    /**
     * Заглавие
     */
    public $title = 'Качвания на файлове';
    
    
    public $canAdd = 'every_one';
    
    
    /**
     * @todo Чака за документация...
     */
    public function act_Dialog()
    {
        // Дали ще качаваме много файлове едновременно
        $allowMultiUpload = false;
        
        Request::setProtected('callback, bucketId');
        
        // Вземаме callBack'а
        if ($callback = Request::get('callback', 'identifier')) {
            
            // Ако файловете ще се добавят в richText
            if (stripos($callback, 'placeFile_') !== false) {
                
                // Позволяваме множествено добавяне
                $allowMultiUpload = true;
            }
        }
        
        // Вземаме id' то на кофата
        $bucketId = Request::get('bucketId', 'int');
        expect(fileman_Buckets::canAddFileToBucket($bucketId), fileman_Buckets::fetch((int) $bucketId));
        
        // Шаблона с качените файлове и грешките
        $add = new ET('<div id="add-file-info"><div id="add-error-info">[#ERR#]</div><div id="add-success-info">[#ADD#]</div></div>');
        
        $add->push('fileman/simpleUpload/1.0/simpleUpload.min.js', 'JS');
        
        // Ако е стартрино качването
        if (Request::get('Upload')) {
            $resEt = new ET();
            
            $this->makeUpload($_FILES, $bucketId, $resEt, $callback);
            
            if (Request::get('ajax_mode')) {
                core_App::outputJson(array('success' => $success, 'res' => $resEt->getContent()));
            } else {
                $add->prepend($resEt);
            }
        }
        
        // Ако има id на кофата
        if ($bucketId) {
            
            // Вземаме максималния размер за файл в кофата
            $maxAllowedFileSize = fileman_Buckets::fetchField($bucketId, 'maxSize');
        }
        
        $tpl = $this->getProgressTpl($allowMultiUpload, $maxAllowedFileSize);
        
        $tpl->prepend($add);
        
        return $this->renderDialog($tpl);
    }
    
    
    /**
     * Помощна функция за качвана на файловете
     * 
     * @param array $files
     * @param integer $bucketId
     * @param null|core_ET $resEt
     * @param null|string $callback
     * 
     * @return array
     */
    public static function makeUpload($files, $bucketId, &$resEt = null, $callback = null)
    {
        $resArr = array();
        
        // Вземаме инфото на обекта, който ще получи файла
        $Buckets = cls::get('fileman_Buckets');
        
        // Обхождаме качените файлове
        foreach ((array) $files as $inputName => $inputArr) {
            
            $fh = null;
            
            // Масив с грешките
            $err = array();
            
            $fRec = new stdClass();
            
            foreach ((array) $inputArr['name'] as $id => $inpName) {
                
                // Ако файла е качен успешно
                if ($files[$inputName]['name'][$id] && $files[$inputName]['tmp_name'][$id]) {
                    
                    // Ако има кофа
                    if ($bucketId) {
                        
                        // Ако файла е валиден по размер и разширение - добавяме го към собственика му
                        if ($Buckets->isValid($err, $bucketId, $files[$inputName]['name'][$id], $files[$inputName]['tmp_name'][$id])) {
                            try {
                                $bucketName = fileman_Buckets::fetchField($bucketId, 'name');
                                
                                $fh = fileman::absorb($files[$inputName]['tmp_name'][$id], $bucketName, $files[$inputName]['name'][$id]);
                            } catch (ErrorException $e) {
                                reportException($e);
                                self::logWarning('Грешка при качване на файл: ' . $e->getMessage());
                            }
                            
                            if (isset($fh)) {
                                $fRec = fileman_Files::fetchByFh($fh);
                            }
                            
                            if (isset($resEt)) {
                                $resEt->append($Buckets->getInfoAfterAddingFile($fh));
                                
                                if ($callback && !$files[$inputName]['error'][$id]) {
                                    $resEt->append("<script>  if (window.opener) { if(window.opener.{$callback}('{$fh}','{$fRec->name}') != true) self.close(); else self.focus();}</script>");
                                }
                            }
                        }
                    } else {
                        $err[] = 'Не е избрана кофа';
                    }
                }
                
                // Ако има грешка в $files за съответния файл
                if ($files[$inputName]['error'][$id]) {
                    // Ако са възникнали грешки при качването - записваме ги в променливата $err
                    switch ($files[$inputName]['error'][$id]) {
                        case 1: $err[] = tr('Достигнато е ограничението за размер на файла в "php.ini"'); break;
                        case 2: $err[] = tr('Размерът на файла е над "MAX_FILE_SIZE"'); break;
                        case 3: $err[] = tr('Не е качен целия файл'); break;
                        case 4: $err[] = tr('Не е качен файл'); break;
                        case 6: $err[] = tr('Не може да се намери временната директория'); break;
                        case 7: $err[] = tr('Грешка при записване на файла'); break;
                    }
                }
                
                $success = true;
                
                // Ако има грешки, показваме ги в прозореца за качване
                if (!empty($err)) {
                    $error = new ET("<div class='upload-error'><ul>{$files[$inputName]['name'][$id]}[#ERR#]</ul></div>");
                    
                    foreach ($err as $e) {
                        $error->append('<li>' . tr($e) . '</li>', 'ERR');
                        fileman_Files::logWarning('Грешка при добавяне на файл: ' . $e);
                        $success = false;
                    }
                    if (isset($resEt)) {
                        $resEt->append($error);
                    }
                } else {
                    if (isset($fh)) {
                        fileman_Files::logWrite('Качен файл', $fRec->id);
                        $resArr[$fh] = $fh;
                    }
                }
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща линк към подадения обект
     *
     * @param int $objId
     *
     * @return core_ET
     */
    public static function getLinkForObject($objId)
    {
        return ht::createLink(get_called_class(), array());
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function renderDialog_($tpl)
    {
        return $tpl;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function getProgressTpl($allowMultiUpload = false, $maxAllowedFileSize = 0)
    {
        $uploadStr = tr('Качване') . ':';
        
        $multiple = '';
        if ($allowMultiUpload) {
            $multiple = 'multiple';
        }
        $message = tr("Изберете, поставете (Ctrl+V) или провлачете файл");
        $allowMultiUpload = (int) $allowMultiUpload;
        
        $tpl = new ET('
            <div id="uploads" class="uploads-holder"><div id="uploadsTitle" style="display: none;"><b><i>' . $uploadStr . '</i></b></div></div>
            <form id="uploadform" enctype="multipart/form-data" method="post">
                <span class="uploaded-filenames"> </span>
                <input type="button" name="Upload" value="' . tr('Качване') . '" class="linkWithIcon button hidden" id="uploadBtn"/>
                    <div class="uploadBox"> 
                        <input id="ulfile" class="ulfile" name="ulfile[]" ' . $multiple . ' type="file" size="1" onchange="afterSelectFile(this, ' . $allowMultiUpload . ', ' . (int) $maxAllowedFileSize . ');" [#ACCEPT#]>
                        <span class="uploadMessage">' . $message. '</span>
                    </div>
            </form>');
        
        $currUrl = getCurrentUrl();
        $currUrl['Upload'] = '1';
        $currUrl['ajax_mode'] = '1';
        $uploadUrl = toUrl($currUrl);
        $uploadUrl = json_encode($uploadUrl);
        
        $crossImg = sbf('img/16/delete.png', '');
        $crossImg = json_encode($crossImg);
        
        $uploadErrStr = tr('Грешка при качване на файл') . ': ';
        $uploadErrStr = json_encode($uploadErrStr);
        
        $fileSizeErr = tr('Файлът е над допустимия размер');
        $fileSizeErr = json_encode($fileSizeErr);
        
        $tpl->appendOnce("var uploadUrl = {$uploadUrl}; var crossImgPng = {$crossImg}; var uploadErrStr = {$uploadErrStr}; var fileSizeErr = {$fileSizeErr}; var allowMultiupload = {$allowMultiUpload};", 'SCRIPTS');
        
        $tpl->push('fileman/js/upload.js', 'JS');

        $tpl->appendOnce("window.addEventListener('paste', e => {
                                                                        if (e.clipboardData.files && e.clipboardData.files.length) {
                                                                            e.preventDefault();
                                                                            var fileInput = document.getElementById('ulfile');
                                                                            fileInput.files = e.clipboardData.files;
                                                                            
                                                                            afterSelectFile(fileInput, {$allowMultiUpload}, " . (int) $maxAllowedFileSize . ");
                                                                            
                                                                            document.getElementById('uploadBtn').click();
                                                                            
                                                                            return false;
                                                                         }
                                                                        });", 'SCRIPTS');

        $tpl->appendOnce("window.addEventListener('dragover', function(e){e.preventDefault(); return false;}, false);
                                  window.addEventListener('drop', function(e) {
                                        e.preventDefault();
                                        
                                        var fileInput = document.getElementById('ulfile');
                                                                                                    
                                        fileInput.files = e.dataTransfer.files;
                                        
                                        afterSelectFile(fileInput, {$allowMultiUpload}, " . (int) $maxAllowedFileSize . ");
                                        
                                        document.getElementById('uploadBtn').click();
                                        
                                        return false;
                                    }, false);", 'SCRIPTS');
        
        return $tpl;
    }
}
