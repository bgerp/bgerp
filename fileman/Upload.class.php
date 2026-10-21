<?php


/**
 * Клас 'fileman_Upload' - качване на файлове от диалогов прозорец
 *
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
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

        Request::setProtected('callback, bucketId, validUntil');

        $validUntil = Request::get('validUntil', 'datetime');

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
        expect404($bucketId && fileman_Buckets::fetch($bucketId));
        if (!fileman_Buckets::canAddFileToBucket($bucketId)) {
            $tpl = new ET('<div class="red">' . tr('Нямате право за качване тук|*. |Обновете предишната страница и опитайте пак') . '</div>');

            return $this->renderDialog($tpl);
        }

        // Шаблона с качените файлове и грешките
        $add = new ET('<div id="add-file-info"><div id="add-error-info">[#ERR#]</div><div id="add-success-info">[#ADD#]</div></div>');

        // Ако има id на кофата
        if ($bucketId) {

            // Вземаме максималния размер за файл в кофата
            $maxAllowedFileSize = fileman_Buckets::fetchField($bucketId, 'maxSize');
        }

        $tpl = $this->getProgressTpl($allowMultiUpload, $maxAllowedFileSize);

        $tpl->prepend($add);

        $this->checkLinkValidity($validUntil, $tpl);

        return $this->renderDialog($tpl);
    }


    /**
     * Проверява валидността на линка за качване
     *
     * @param $tpl
     * @param $validUntil
     *
     * @return null|string
     */
    public static function checkLinkValidity($validUntil, &$tpl = null)
    {
        if (!$validUntil || $validUntil < dt::now()) {
            $msg = tr('Тази връзка вече не е активна!|* Обновете предишната страница и опитайте пак!');
            $tpl = new ET('<div class="red">' . $msg . '</div>');

            return $msg;
        }

        return null;
    }


    /**
     * Качване на файлове чрез AJAX
     */
    public function act_Upload()
    {
        Request::setProtected('callback, bucketId, validUntil');
        $bucketId = Request::get('bucketId', 'int');
        $callback = Request::get('callback', 'identifier');
        try {
            expect(($_SERVER['REQUEST_METHOD'] ?? '') === 'POST', 'Очаква се POST');
            expect($bucketId && fileman_Buckets::canAddFileToBucket($bucketId), 'Нямате право за качване тук');
            expect(!self::checkLinkValidity(Request::get('validUntil', 'datetime')), 'Връзката за качване е изтекла');
            $scope = 'fileman|' . $bucketId;
            if (fileman_ChunkedUpload::getContentType() === 'application/json') {
                $data = fileman_ChunkedUpload::readJson();
                expect(!empty($data['init']), 'Невалидно начало на качване');
                $files = fileman_ChunkedUpload::validateFiles(array($data), $bucketId);
                $result = fileman_ChunkedUpload::begin($files, array('bucketId' => $bucketId, 'callback' => $callback), $scope);
                $result['fileHash'] = $files[0]['sha256'];
            } else {
                $result = fileman_ChunkedUpload::receive($scope, function ($files, $context) {
                    $resEt = new ET();
                    $success = false;
                    $handles = self::makeUpload(array('ulfile' => $files), $context['bucketId'], $resEt, $context['callback'], $success);
                    expect($success && count($handles), 'Неуспешно качване на файла');

                    return array('ok' => true, 'fileName' => $files['name'][0], 'fh' => reset($handles),
                        'callback' => $context['callback'], 'res' => $resEt->getContent());
                });
            }
        } catch (core_exception_Redirect $e) {
            throw $e;
        } catch (core_exception_Expect $e) {
            $result = array('ok' => false, 'error' => tr('Неуспешно качване. Проверете файла и валидността на връзката.'));
        } catch (Throwable $e) {
            reportException($e);
            $result = array('ok' => false, 'error' => tr('Грешка при качване на файл'));
        }
        core_App::outputJson($result);
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
    public static function makeUpload($files, $bucketId, &$resEt = null, $callback = null, &$success = null)
    {
        $resArr = array();
        $success = true;

        // Вземаме инфото на обекта, който ще получи файла
        $Buckets = cls::get('fileman_Buckets');

        // Обхождаме качените файлове
        foreach ((array) $files as $inputName => $inputArr) {

            foreach ((array) $inputArr['name'] as $id => $inpName) {
                $fh = null;
                $err = array();
                $fRec = null;

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

                                if ($callback && $fh && !$files[$inputName]['error'][$id]) {
                                    $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
                                    $jsCallback = json_encode($callback, $flags);
                                    $jsHandle = json_encode($fh, $flags);
                                    $jsName = json_encode($fRec->name ?? '', $flags);
                                    $resEt->append("<script>if (window.opener) { if (window.opener[{$jsCallback}]({$jsHandle}, {$jsName}) != true) self.close(); else self.focus(); }</script>");
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
                        case 1: $err[] = tr('Размерът на файла надвишава ограничението, зададено в "php.ini"'); break;
                        case 2: $err[] = tr('Размерът на файла надвишава стойността на "MAX_FILE_SIZE" от формуляра'); break;
                        case 3: $err[] = tr('Файлът беше качен само частично'); break;
                        case 4: $err[] = tr('Не е качен файл'); break;
                        case 6: $err[] = tr('Временната директория не може да бъде намерена'); break;
                        case 7: $err[] = tr('Грешка при записване на файла на диска'); break;
                    }
                }

                // Ако има грешки, показваме ги в прозореца за качване
                if (!empty($err)) {
                    $escapedName = ht::escapeAttr($files[$inputName]['name'][$id]);
                    $error = new ET("<div class='upload-error'><ul>{$escapedName}[#ERR#]</ul></div>");

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
                        fileman_Files::logWrite('Качен файл', $fRec->id ?? null);
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
            <form id="uploadform" method="post">
                <span class="uploaded-filenames"> </span>
                <input type="button" name="Upload" value="' . tr('Качване') . '" class="linkWithIcon button hidden" id="uploadBtn"/>
                    <div class="uploadBox"> 
                        <input id="ulfile" class="ulfile" name="ulfile[]" ' . $multiple . ' type="file" size="1" onchange="afterSelectFile(this, ' . $allowMultiUpload . ', ' . (int) $maxAllowedFileSize . ');" [#ACCEPT#]>
                        <span class="uploadMessage">' . $message. '</span>
                    </div>
            </form>');

        $currUrl = getCurrentUrl();
        $currUrl['ajax_mode'] = '1';
        $currUrl['Act'] = 'Upload';
        $uploadUrl = toUrl($currUrl);
        $uploadUrl = json_encode($uploadUrl);

        $crossImg = sbf('img/16/delete.png', '');
        $crossImg = json_encode($crossImg);

        $uploadErrStr = tr('Грешка при качване на файл') . ': ';
        $uploadErrStr = json_encode($uploadErrStr);

        $fileSizeErr = tr('Файлът е над допустимия размер');
        $fileSizeErr = json_encode($fileSizeErr);

        $tpl->appendOnce("var uploadUrl = {$uploadUrl}; var crossImgPng = {$crossImg}; var uploadErrStr = {$uploadErrStr}; var fileSizeErr = {$fileSizeErr}; var allowMultiupload = {$allowMultiUpload};", 'SCRIPTS');
        $tpl->appendOnce("const CHUNK_SIZE_DEFAULT = " . fileman_Setup::get('CHUNK_SIZE') . ';', 'SCRIPTS');

        $tpl->push('fileman/js/sha256.js', 'JS');
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
