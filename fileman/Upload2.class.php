<?php


/**
 * Клас 'fileman_Upload' - качване на файлове от диалогов прозорец
 *
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_Upload2 extends core_Manager
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
    {bp(fileman_Setup::get('CHUNK_SIZE'));
        // Дали ще качаваме много файлове едновременно
        $allowMultiUpload = false;

        Request::setProtected('callback, bucketId, validUntil');

        $validUntil = Request::get('validUntil', 'datetime');
        expect($validUntil && ($validUntil > dt::now()), 'Линкът за качване е изтекъл');

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
     * Качване на файлове чрез AJAX
     */
    public function act_Upload()
    {
        // Дали ще качаваме много файлове едновременно
        $allowMultiUpload = false;
        Request::setProtected('callback, bucketId, validUntil');
        $callback = Request::get('callback', 'identifier');

        $metaFileName = 'meta.json';
        $chunkSize = fileman_Setup::get('CHUNK_SIZE');

        // Вземаме id' то на кофата
        $bucketId = Request::get('bucketId', 'int');

        if (!$bucketId) {
            core_App::outputJson(array('ok' => false, 'error' => tr('Непълни данни.')));
        }

        if (!fileman_Buckets::canAddFileToBucket($bucketId)) {
            core_App::outputJson(array('ok' => false, 'error' => tr('Нямате право за качване тук')));
        }

        // Вземаме параметрите от json или от Request
        $json = file_get_contents('php://input');
        $json = json_decode($json, false);
        $name = $json ? $json->name : Request::get('fileName');
        $size = $json ? $json->size : Request::get('fileSize', 'int');
        $mime = $json ? $json->mime : Request::get('mime', 'text');
        $ext = $json ? $json->ext : Request::get('ext', 'text');
        $totalChunks = $json ? $json->totalChunks : Request::get('totalChunks', 'int');
        $sha256 = $json ? $json->sha256 : Request::get('sha256', 'text');

        $sha256 = strtolower(preg_replace('/[^a-f0-9]/', '', $sha256));

        $oExt = fileman::getExt($name);
        if (!$oExt) {
            self::logNotice("Неуспешно извличане на разширение за файл {$name}");
            $oExt = $ext;
        }
        $oMime = fileman_Mimes::getMimeByExt($ext);
        if (!$oMime) {
            self::logNotice("Неуспешно извличане на mime тип за файл {$name}");
            $oMime = $mime;
        }
        if ($oExt !== $ext) {
            self::logNotice("Несъответствие в разширението: {$oExt} != {$ext} за файл {$name}");
            $ext = $oExt;
        }
        if ($oMime && $oMime !== $mime) {
            self::logNotice("Несъответствие в mime типа: {$oMime} != {$mime} за файл {$name}");
            $mime = $oMime;
        }

        $isValid = fileman_Buckets::isValid($errArr, $bucketId, $name, null, $size);
        if (!$isValid) {
            core_App::outputJson(array('ok' => false, 'error' => tr($errArr[0])));
        }

        // Начало на качването
        if ($json && $json->init) {
            $isExist = false;
            // @todo - проверка дали файл със същия sha256 вече съществува в кофата - по sha256, размер и bucketId?
            // Ако съществува, връщаме exists = true и uploadId = null
            // $exists = true;
            $uploadId = null;

            if (!$isExist) {
                $tPath = fileman::getTempPath();
                if (!is_dir($tPath) || !is_readable($tPath)) {
                    self::logErr('Грешка при създаване на временна директория');
                    core_App::outputJson(array('ok' => false, 'error' => 'Грешка при качване'));
                }
                $uploadId = basename($tPath);
            }
            expect($uploadId);
            $uploadId = core_Crypt::base64ToBase36(base64_encode($uploadId));

            // meta.json
            $meta = array('bucketId' => $bucketId,
                'name' => $name,
                'size' => $size,
                'mime' => $mime,
                'ext' => $ext,
                'sha256' => $sha256,
                'chunkSize' => $chunkSize,
                'totalChunks' => $totalChunks,
                'received' => 0,
                'createdOn' => time(),
                'createdBy' => core_Users::getCurrent());

            expect($tPath);

            $addMeta = @file_put_contents(rtrim($tPath, '/') . "/" . $metaFileName, json_encode($meta));

            if (!$addMeta) {
                self::logErr('Грешка при запис на ' . $metaFileName . ' в ' . $tPath);
                core_App::outputJson(array('ok' => false, 'error' => 'Грешка при качване'));
            }

            core_App::outputJson(array('ok' => true,
                    'uploadId' => $uploadId,
                    'fileHash' => $sha256,
                    'chunkSize' => $chunkSize,
                    'nextChunkIndex' => 0,
                    'exists' => $isExist)
            );
        }

        // Самото качване на части от файла и финализиране

        // Проверка на uploadId
        $uploadId = Request::get('uploadId');
        $uploadIdOrig = $uploadId;
        $uploadId = base64_decode(core_Crypt::base36ToBase64($uploadId));

        if (!$uploadId || !trim($uploadId)) {
            self::logErr('Липсва uploadId');
            core_App::outputJson(array('ok' => false, 'error' => tr('Липсва uploadId')));
        }
        $uploadId = preg_replace('/[^a-z0-9]/', '', $uploadId);

        // Проверка на временната директория
        $tDir = fileman::getTempDir();
        $tPath = rtrim($tDir, '/') . '/' . $uploadId;
        if (!is_dir($tPath) || !is_readable($tPath)) {
            self::logWarning('Неизвестно uploadId');
            core_App::outputJson(array('ok' => false, 'error' => tr('Липсва временна директория'), 'uploadId' => $uploadIdOrig));
        }

        // Проверка на meta.json
        $metaFile = rtrim($tPath, '/') . '/' . $metaFileName;
        if (!is_file($metaFile) || !is_readable($metaFile)) {
            self::logWarning('Грешка при достъп до ' . $metaFileName);
            core_App::outputJson(array('ok' => false, 'error' => tr('Грешка при качване'), 'uploadId' => $uploadIdOrig));
        }
        $meta = @json_decode(file_get_contents($metaFile), true);
        if (!$meta) {
            self::logWarning('Грешка при четене на ' . $metaFileName);
            core_App::outputJson(array('ok' => false, 'error' => tr('Грешка при четене'), 'uploadId' => $uploadIdOrig));
        }

        // Проверки на метаданните
        if (((int) $meta['size'] !== (int) $size) || ($meta['name'] !== $name) || ($meta['sha256'] !== $sha256) || ((int)$meta['bucketId'] !== (int)$bucketId)) {
            self::logWarning('Несъответствие в метаданните: ' . core_Type::mixedToString(array('meta' => $meta, 'size' => $size, 'name' => $name, 'sha256' => $sha256, 'bucketId' => $bucketId)));
            core_App::outputJson(array('ok' => false, 'error' => tr('Несъответствие в метаданните'), 'uploadId' => $uploadIdOrig));
        }

        // Качване на частите на файла

        if (!$json && !Request::get('init')) {
            $chunkIndex = Request::get('chunkIndex', 'int');

            if (!isset($chunkIndex) || ($chunkIndex < 0)) {
                core_App::outputJson(array('ok' => false, 'error' => tr('Липсва chunkIndex'), 'uploadId' => $uploadIdOrig));
            }

            if (empty($_FILES['chunk']['tmp_name'])) {
                core_App::outputJson(array('ok' => false, 'error' => tr('Липсва chunk'), 'uploadId' => $uploadIdOrig));
            }

            // Запис на самия chunk под фиксирано име
            $chunkPath = sprintf('%s/part.%06d', $tPath, $chunkIndex);

            if (!move_uploaded_file($_FILES['chunk']['tmp_name'], $chunkPath)) {
                core_App::outputJson(array('ok' => false, 'error' => tr('Неуспешен запис на chunk'), 'uploadId' => $uploadIdOrig));
            }

            // Обновяване на прогреса
            $received = 0;
            foreach (glob("{$tPath}/part.*") as $p) {
                $received += filesize($p);
            }
            $meta['received'] = $received;
            file_put_contents($metaFile, json_encode($meta));

            if (--$totalChunks > $chunkIndex) {
                core_App::outputJson(array('ok' => true, 'received' => $received, 'uploadId' => $uploadIdOrig));
            }
        }

        // Финализиране на качването
        // Сглобяване (concatenate) във временен път
        $assembled = tempnam($tPath, 'assembled_');
        $out = fopen($assembled, 'wb');
        $idx = 0;
        $written = 0;
        while (true) {
            $chunkPath = sprintf('%s/part.%06d', $tPath, $idx);
            if (!is_file($chunkPath)) break;
            $in = fopen($chunkPath, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
            $written += filesize($chunkPath);
            $idx++;
        }
        fclose($out);

        if ($written !== (int)$meta['size']) {
            @unlink($assembled);
            core_App::outputJson(array('ok' => false, 'error' => tr('Размерът на сглобения файл е различен'), 'uploadId' => $uploadIdOrig));
        }

        // Валидация SHA-256 на сървър
        $ctx = hash_init('sha256');
        $f = fopen($assembled, 'rb');
        while (!feof($f)) {
            $buf = fread($f, $chunkSize);
            if ($buf !== false) hash_update($ctx, $buf);
        }
        fclose($f);
        $srvHash = hash_final($ctx);
        if ($srvHash !== $sha256) {
            core_App::outputJson(array('ok' => false, 'error' => tr('SHA-256 не съвпада'), 'uploadId' => $uploadIdOrig));
        }

        // Подаваме към makeUpload(), за да минат всички твои валидатори и логове
        // Симулираме $_FILES структура за един файл
        $fakeTmp = tempnam($tPath, 'upl_');
        if (!@rename($assembled, $fakeTmp)) {
            // ако rename не може през FS, пробваме копиране
            if (!@copy($assembled, $fakeTmp)) {
                @unlink($assembled);
                core_App::outputJson(array('ok' => false, 'error' => tr('Грешка при качването')));
            }
            @unlink($assembled);
        }

        $files = [
            'ulfile' => [
                'name' => array($name),
                'type' => array(''),
                'tmp_name' => array($fakeTmp),
                'error' => array(0),
                'size' => array($size),
            ],
        ];

        $resEt = new ET();
        $success = false;
        $res = self::makeUpload($files, $bucketId, $resEt, $callback, $success);
        $resContent = $resEt instanceof core_ET ? $resEt->getContent() : '';

        if (!$success) {
            core_App::outputJson(array('ok' => false, 'error' => tr('Неуспешно качване на файла: ') . $resContent));
        }

        try {
            core_Os::deleteDir($tPath);
        } catch (core_exception_Expect $e) {
            wp('Грешка при изтриване', $tPath, $e);
        }
        // $res е масив [fh=>fh]; вземаме първия
        $fh = reset($res);

        core_App::outputJson(array('ok' => true, 'fileName' => $name, 'fh' => $fh, 'callback' => $callback, 'uploadId' => $uploadIdOrig, 'res' => $resContent));
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
                        case 1: $err[] = tr('Размерът на файла надвишава ограничението, зададено в "php.ini"'); break;
                        case 2: $err[] = tr('Размерът на файла надвишава стойността на "MAX_FILE_SIZE" от формуляра'); break;
                        case 3: $err[] = tr('Файлът беше качен само частично'); break;
                        case 4: $err[] = tr('Не е качен файл'); break;
                        case 6: $err[] = tr('Временната директория не може да бъде намерена'); break;
                        case 7: $err[] = tr('Грешка при записване на файла на диска'); break;
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

        $tpl->push('fileman/js/upload2.js', 'JS');

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
