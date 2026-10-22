<?php


/**
 * Приемане на двоични части без multipart parser-а на PHP
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_ChunkedUpload
{
    /** Максимален брой файлове в едно PWA споделяне */
    const MAX_FILES = 20;


    public static function getContentType()
    {
        return strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    }


    public static function readJson()
    {
        expect(self::getContentType() === 'application/json', 'Очакват се JSON метаданни');
        $raw = file_get_contents('php://input', false, null, 0, 65537);
        expect(is_string($raw) && strlen($raw) <= 65536, 'Твърде много метаданни');
        $data = json_decode($raw, true);
        expect(is_array($data), 'Невалидни метаданни');

        return $data;
    }


    /** При отказ от кофата причината остава в $errors за показване на потребителя. */
    public static function validateFiles($files, $bucketId, &$errors = array())
    {
        expect(is_array($files) && count($files) > 0 && count($files) <= self::MAX_FILES,
            'Невалиден брой файлове');
        $result = array();
        foreach ($files as $file) {
            expect(is_array($file), 'Невалидни метаданни');
            $name = $file['name'] ?? null;
            $size = $file['size'] ?? null;
            $sha256 = $file['sha256'] ?? null;
            expect(is_string($name) && trim($name) !== '' && strlen($name) <= 1024 && strpos($name, "\0") === false,
                'Невалидно име на файл');
            expect(is_int($size) && $size >= 0, 'Невалиден размер');
            expect(is_string($sha256) && preg_match('/^[a-f0-9]{64}$/D', $sha256), 'Невалиден SHA-256');
            $errors = array();
            expect(fileman_Buckets::isValid($errors, $bucketId, $name, null, $size),
                implode('; ', $errors));
            $result[] = array('name' => $name, 'size' => $size, 'sha256' => $sha256);
        }

        return $result;
    }


    public static function begin($files, $context, $scope)
    {
        $id = bin2hex(random_bytes(16));
        $path = rtrim(fileman::getTempDir(), '/') . '/raw-' . $id;
        expect(mkdir($path, 0700), 'Неуспешно създаване на временна директория');
        $chunkSize = (int) fileman_Setup::get('CHUNK_SIZE');
        expect($chunkSize > 0, 'Невалиден размер на част');
        $meta = array('files' => $files, 'context' => $context, 'scope' => $scope,
            'owner' => self::getOwner(), 'expires' => time() + 3600, 'chunkSize' => $chunkSize);
        self::saveMeta($path, $meta);
        expect(core_CallOnTime::setCall(__CLASS__, 'Cleanup', $id, dt::addSecs(3660)),
            'Неуспешно планиране на почистването');

        return array('ok' => true, 'uploadId' => $id, 'chunkSize' => $chunkSize,
            'exists' => false, 'nextChunkIndex' => 0);
    }


    /**
     * Повторение на последната заявка връща запазения резултат без ново добавяне.
     * Без $abort неуспешна част може да се повтори. С $abort грешка прекратява
     * незавършеното качване под lock-а, след което $abort освобождава ресурсите му.
     */
    public static function receive($scope, $finish, $abort = null)
    {
        expect(self::getContentType() === 'application/octet-stream', 'Очакват се двоични данни');
        $id = $_SERVER['HTTP_X_UPLOAD_ID'] ?? '';
        expect(preg_match('/^[a-f0-9]{32}$/D', $id), 'Невалидно uploadId');
        $fileIndex = $_SERVER['HTTP_X_FILE_INDEX'] ?? '0';
        $chunkIndex = $_SERVER['HTTP_X_CHUNK_INDEX'] ?? '';
        expect(preg_match('/^(0|[1-9][0-9]{0,8})$/D', $fileIndex) &&
            preg_match('/^(0|[1-9][0-9]{0,8})$/D', $chunkIndex), 'Невалиден номер на част');
        $fileIndex = (int) $fileIndex;
        $chunkIndex = (int) $chunkIndex;
        $path = rtrim(fileman::getTempDir(), '/') . '/raw-' . $id;
        $lockId = 'rawUpload|' . $id;
        expect(core_Locks::obtain($lockId, 120, 10, 10), 'Заето качване');
        $abortOnError = false;
        try {
            expect(is_file($path . '/meta.json'), 'Неизвестно качване');
            $meta = json_decode(file_get_contents($path . '/meta.json'), true);
            expect(is_array($meta) && ($meta['owner'] ?? '') === self::getOwner() &&
                ($meta['scope'] ?? '') === $scope && ($meta['expires'] ?? 0) >= time(), 'Изтекло или чуждо качване');
            $abortOnError = $abort && !isset($meta['result']);
            $file = $meta['files'][$fileIndex] ?? null;
            expect(is_array($file), 'Невалиден номер на файл');
            $chunkSize = $meta['chunkSize'];
            $totalChunks = max(1, (int) ceil($file['size'] / $chunkSize));
            expect($chunkIndex < $totalChunks, 'Невалиден номер на част');
            if (isset($meta['result'])) {
                return $meta['result'];
            }

            $expectedSize = min($chunkSize, $file['size'] - $chunkIndex * $chunkSize);
            $part = sprintf('%s/part.%d.%d', $path, $fileIndex, $chunkIndex);
            $input = fopen('php://input', 'rb');
            expect(is_resource($input), 'Неуспешно четене на част');
            $output = fopen($part . '.tmp', 'wb');
            try {
                expect(is_resource($output), 'Неуспешен запис на част');
                $written = stream_copy_to_stream($input, $output, $expectedSize + 1);
                expect($written === $expectedSize, 'Несъответствие в размера на частта');
            } finally {
                fclose($input);
                if (is_resource($output)) fclose($output);
                if (($written ?? null) !== $expectedSize) @unlink($part . '.tmp');
            }
            expect(rename($part . '.tmp', $part), 'Неуспешен запис на част');

            $received = 0;
            $complete = true;
            foreach ($meta['files'] as $index => $item) {
                $count = max(1, (int) ceil($item['size'] / $chunkSize));
                for ($i = 0; $i < $count; $i++) {
                    $partPath = sprintf('%s/part.%d.%d', $path, $index, $i);
                    if (!is_file($partPath)) {
                        $complete = false;
                    } else {
                        $received += filesize($partPath);
                    }
                }
            }
            if (!$complete) {
                return array('ok' => true, 'uploadId' => $id, 'received' => $received);
            }

            $files = array('name' => array(), 'type' => array(), 'tmp_name' => array(), 'error' => array(), 'size' => array());
            foreach ($meta['files'] as $index => $item) {
                $assembled = $path . '/assembled.' . $index;
                $output = fopen($assembled, 'wb');
                expect(is_resource($output), 'Неуспешен запис на файл');
                try {
                    $count = max(1, (int) ceil($item['size'] / $chunkSize));
                    for ($i = 0; $i < $count; $i++) {
                        $input = fopen(sprintf('%s/part.%d.%d', $path, $index, $i), 'rb');
                        expect(is_resource($input), 'Липсва част от файла');
                        try {
                            expect(stream_copy_to_stream($input, $output) !== false, 'Неуспешно сглобяване');
                        } finally {
                            fclose($input);
                        }
                    }
                } finally {
                    fclose($output);
                }
                clearstatcache(true, $assembled);
                expect(filesize($assembled) === $item['size'] && hash_file('sha256', $assembled) === $item['sha256'],
                    'Несъответствие в SHA-256 или размера на файла');
                $files['name'][] = $item['name'];
                $files['type'][] = '';
                $files['tmp_name'][] = $assembled;
                $files['error'][] = UPLOAD_ERR_OK;
                $files['size'][] = $item['size'];
            }
            $result = call_user_func($finish, $files, $meta['context']);

            // Завършеното качване остава за повторение, а ресурсите му - заети
            $abortOnError = false;
            $meta['result'] = $result;
            $meta['result']['uploadId'] = $id;
            self::saveMeta($path, $meta);
            foreach (glob($path . '/part.*') as $filePath) @unlink($filePath);
            foreach (glob($path . '/assembled.*') as $filePath) @unlink($filePath);

            return $meta['result'];
        } catch (core_exception_Redirect $e) {
            throw $e;
        } catch (Throwable $e) {
            if ($abortOnError) {
                // Без meta следващите части се отказват, затова ресурсите вече могат да се освободят
                @unlink($path . '/meta.json');
                core_Os::deleteDir($path);
                call_user_func($abort, $meta['context'] ?? null);
            }

            throw $e;
        } finally {
            core_Locks::release($lockId);
        }
    }


    /** Изоставените части не чакат общото почистване на временните файлове. */
    public function callback_Cleanup($id)
    {
        expect(is_string($id) && preg_match('/^[a-f0-9]{32}$/D', $id), 'Невалидно uploadId');
        $path = rtrim(fileman::getTempDir(), '/') . '/raw-' . $id;
        if (!is_dir($path)) return;
        $lockId = 'rawUpload|' . $id;
        if (!core_Locks::obtain($lockId, 120, 10, 10)) {
            core_CallOnTime::setCall(__CLASS__, 'Cleanup', $id, dt::addSecs(120));

            return;
        }
        try {
            $meta = is_file($path . '/meta.json') ? json_decode(file_get_contents($path . '/meta.json'), true) : array();
            if (($meta['expires'] ?? 0) >= time()) {
                core_CallOnTime::setCall(__CLASS__, 'Cleanup', $id, dt::addSecs($meta['expires'] - time() + 60));
            } else {
                core_Os::deleteDir($path);
            }
        } finally {
            core_Locks::release($lockId);
        }
    }


    private static function getOwner()
    {
        return hash('sha256', EF_APP_NAME . '|' . (int) core_Users::getCurrent() . '|' . log_Browsers::getBrid());
    }


    private static function saveMeta($path, $meta)
    {
        $json = json_encode($meta);
        expect($json !== false && file_put_contents($path . '/meta.tmp', $json) === strlen($json) &&
            rename($path . '/meta.tmp', $path . '/meta.json'), 'Неуспешно запазване на качването');
    }
}
