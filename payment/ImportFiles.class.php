<?php
/**
 * Обща обработка на XML и ZIP файлове с банкови извлечения.
 *
 * @category bgerp
 * @package payment
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
class payment_ImportFiles
{
    const MAX_XML_BYTES = 20971520;
    const MAX_ZIP_ENTRIES = 100;
    const PASSWORD_ERROR = 1;

    /**
     * Разпознава и парсира извлеченията по съдържание за двата входа за импорт.
     * batches запазва отделните извлечения за съществуващата проверка за дубликати.
     */
    public static function getRecs($fh, $password = null, $servicePrefix = 'Import', $includeUnicredit = true)
    {
        $res = (object) array('recs' => array(), 'batches' => array(), 'warnings' => array(),
            'errors' => array(), 'recognized' => 0, 'passwordError' => false, 'status' => 'unsupported');
        try {
            $contents = self::readContents($fh, $password);
        } catch (fileman_Exception $e) {
            $res->errors[] = $e->getMessage();
            $res->passwordError = $e->getCode() == self::PASSWORD_ERROR;
            $res->status = 'error';

            return $res;
        }

        core_App::setTimeLimit(30 + round(array_sum(array_map('strlen', $contents)) / 100000));
        foreach ($contents as $index => $xml) {
            $xml = ltrim($xml, "\xEF\xBB\xBF \t\r\n");
            if (strpos($xml, 'urn:iso:std:iso:20022:tech:xsd:camt.') !== false) {
                $parser = 'payment_ParserIso20022';
                $service = 'ISO20022';
            } elseif ($includeUnicredit && strpos($xml, 'APAccounts') !== false) {
                $parser = 'payment_ParserUC';
                $service = 'Unicredit';
            } else {
                continue;
            }
            $res->recognized++;
            $prefix = count($contents) > 1 ? 'Извлечение №' . ($index + 1) . ': ' : '';
            $parsed = $parser::getRecs($xml, $servicePrefix . ' ' . $service);
            if (!$parsed) {
                $res->errors[] = $prefix . 'Неподдържан формат на банковото извлечение';
                continue;
            }
            foreach (array('warnings', 'errors') as $type) {
                foreach ($parsed->{$type} ?? array() as $message) {
                    $res->{$type}[] = $prefix . $message;
                }
            }
            if (!empty($parsed->recs)) {
                $res->batches[] = $parsed->recs;
                $res->recs = array_merge($res->recs, $parsed->recs);
            }
        }

        if ($res->errors || $res->warnings) {
            $res->status = $res->recs ? 'partial' : 'error';
        } elseif ($res->recognized) {
            $res->status = 'success';
        }

        return $res;
    }


    /**
     * Чете съдържанието без извличане на пътища от архива върху диска.
     */
    public static function readContents($fh, $password = null)
    {
        $path = fileman_Files::fetchByFh($fh, 'path');
        $size = $path && is_readable($path) ? filesize($path) : false;
        if ($size === false) {
            throw new fileman_Exception('Файлът с извлечението липсва или не може да се прочете');
        }

        $header = file_get_contents($path, false, null, 0, 512);
        if ($header === false) {
            throw new fileman_Exception('Не може да се прочете извлечението');
        }
        $signature = substr($header, 0, 4);
        $name = fileman_Files::fetchByFh($fh, 'name');
        $extension = strtolower(fileman::getExt($name ?? ''));
        $isZip = $extension == 'zip' || in_array($signature, array("PK\x03\x04", "PK\x05\x06"), true);
        $xmlStart = ltrim($header, "\xEF\xBB\xBF \t\r\n");
        if (!$isZip && !in_array($extension, array('xml', 'camt'), true)
            && $xmlStart !== '' && substr($xmlStart, 0, 1) !== '<') {
            return array();
        }
        if ($size > self::MAX_XML_BYTES) {
            throw new fileman_Exception('Извлечението надвишава 20 MB');
        }
        if (!$isZip) {
            $content = file_get_contents($path, false, null, 0, self::MAX_XML_BYTES + 1);
            if ($content === false || strlen($content) > self::MAX_XML_BYTES) {
                throw new fileman_Exception('Не може да се прочете извлечението');
            }

            return array($content);
        }

        if (!class_exists('ZipArchive')) {
            throw new fileman_Exception('За ZIP извлечения е необходимо PHP разширението zip');
        }
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
            throw new fileman_Exception('Невалиден или повреден ZIP архив');
        }

        try {
            $filePassword = fileman_webdrv_Generic::getPassword($fh);
            if ($filePassword !== null) {
                $password = $filePassword;
            }
            if (isset($password) && $password !== '') {
                if (strpos($password, "\0") !== false || !$zip->setPassword($password)) {
                    throw new fileman_Exception('Невалидна парола за ZIP извлечението', self::PASSWORD_ERROR);
                }
            }
            if ($zip->numFiles > self::MAX_ZIP_ENTRIES) {
                throw new fileman_Exception('ZIP извлечението съдържа повече от 100 записа');
            }

            $contents = array();
            $totalSize = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat === false || !isset($stat['size'])) {
                    throw new fileman_Exception('Не може да се прочете съдържанието на ZIP извлечението');
                }
                $entrySize = $stat['size'];
                $totalSize += $entrySize;
                if ($entrySize < 0 || $totalSize > self::MAX_XML_BYTES) {
                    throw new fileman_Exception('Разархивираното извлечение надвишава 20 MB');
                }
                if (!$entrySize) {
                    continue;
                }
                $content = @$zip->getFromIndex($i, self::MAX_XML_BYTES + 1);
                if ($content === false) {
                    if (in_array($zip->status, array(ZipArchive::ER_NOPASSWD, ZipArchive::ER_WRONGPASSWD), true)) {
                        throw new fileman_Exception('Липсва или е грешна паролата за ZIP архива. Проверете паролата на файла или тази за импорта', self::PASSWORD_ERROR);
                    }
                    throw new fileman_Exception('Не може да се прочете файл от ZIP архива - повредени данни или неподдържан метод на защита');
                }
                if (strlen($content) != $entrySize) {
                    throw new fileman_Exception('Размерът на прочетения файл не съвпада с този в ZIP архива');
                }
                $contents[] = $content;
            }

            return $contents;
        } finally {
            $zip->close();
        }
    }
}
