<?php


/**
 * Обща логика за защитените файлове на аудио и видео драйверите в библиотеката.
 *
 * @category  bgerp
 * @package   mejs
 * @author    Mustafa Mustafov <mmustafov084@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class mejs_MediaDriver extends core_BaseClass
{
    /** Разширение => демултиплексор, видеокодеци, аудиокодеци. Без видеокодеци файлът е само звук. */
    protected const FORMATS = array();

    /** Полета, задължителни за обектите от разговор. */
    protected const SCOPED_FIELDS = 'source1';


    /** Договор с mejs_Stream: файл и MIME за избраната писта на записа. */
    public static function getStreamSource($rec, $slot)
    {
        if (!in_array($slot, array(1, 2), true)) return null;

        return static::getProtectedSource($rec->{'source' . $slot} ?? null);
    }


    /** Файл и MIME за плеъра и за всяка отделна заявка към потока. */
    public static function getProtectedSource($fh)
    {
        if (!$fh || cms_Library::getEmbeddedFileError($fh) !== null) return null;
        $file = fileman_Files::fetchByFh($fh);
        $name = $file->name ?? '';
        if (empty($file->dataId) || !isset(static::FORMATS[fileman_Files::getExt($name)])) return null;

        return array('dataId' => $file->dataId, 'mime' => fileman_Files::getType($name));
    }


    /** Адреси към потока за плеъра или null при недостъпен обект или файл. */
    protected static function getStreamSources($rec, $absolute)
    {
        if (empty($rec->id) || !cms_Library::canViewAccessContext($rec)) return null;

        $sources = array();
        foreach (array(1, 2) as $slot) {
            $fh = $rec->{'source' . $slot} ?? null;
            if (!$fh) continue;
            $source = static::getProtectedSource($fh);
            if (!$source) return null;
            $sources[$fh] = array(
                'url' => toUrl(array('mejs_Stream', 'play', 'objectId' => $rec->id, 'source' => $slot), $absolute ? 'absolute' : 'relative'),
                'type' => $source['mime'],
            );
        }

        return $sources ?: null;
    }


    /** Библиотеката е проверила файловия идентификатор преди отварянето на съдържанието. */
    protected static function getCodecError($fh)
    {
        $file = fileman_Files::fetchByFh($fh);
        $extension = fileman_Files::getExt($file->name ?? '');
        if (!isset(static::FORMATS[$extension])) {
            return 'Допустимите формати за този плеър са ' . implode(', ', array_keys(static::FORMATS)) . '.';
        }

        // Фиксираният демултиплексор и локалният протокол изключват плейлисти и външни адреси.
        list($format, $videoCodecs, $audioCodecs) = static::FORMATS[$extension];
        $path = fileman_Files::fetchByFh($fh, 'path');
        $cmd = 'timeout -k 1 10 ffprobe -v error -protocol_whitelist file -max_streams 32 -f ' . $format
            . ' -show_entries stream=codec_type,codec_name,pix_fmt:stream_disposition=attached_pic -of json -i '
            . escapeshellarg($path ?? '') . ' 2>/dev/null';
        if (!is_callable('exec')) {
            return 'Сървърът не позволява проверка на кодеците чрез ffprobe.';
        }
        exec($cmd, $output, $status);
        if ($status == 126 || $status == 127) {
            return 'За проверка на кодеците сървърът изисква ffprobe и timeout.';
        }
        if ($status == 124 || $status == 137) {
            return 'Проверката на кодеците надхвърли 10 секунди.';
        }
        if ($status != 0) {
            return 'Файлът е нечетим или съдържанието му не отговаря на избрания формат.';
        }

        $info = json_decode(implode("\n", $output), true);
        $hasVideo = $hasAudio = false;
        foreach ($info['streams'] ?? array() as $stream) {
            $codec = $stream['codec_name'] ?? '';
            $type = $stream['codec_type'] ?? '';
            if ($type === 'video' && empty($stream['disposition']['attached_pic'])) {
                if (!$videoCodecs) return 'Аудиофайлът не може да съдържа видеописта.';
                if (!in_array($codec, $videoCodecs, true) || !in_array($stream['pix_fmt'] ?? '', array('yuv420p', 'yuvj420p'), true)) {
                    return 'Неподдържан видеокодек или пикселен формат. Използвайте ' . implode('/', $videoCodecs) . ' с 8-битов YUV 4:2:0.';
                }
                $hasVideo = true;
            } elseif ($type === 'audio') {
                if (!in_array($codec, $audioCodecs, true)) {
                    return 'Неподдържан аудиокодек за този формат. Използвайте ' . implode('/', $audioCodecs) . '.';
                }
                $hasAudio = true;
            }
        }

        if ($videoCodecs) return $hasVideo ? null : 'Файлът няма възпроизводима видеописта.';

        return $hasAudio ? null : 'Файлът няма възпроизводима аудиописта.';
    }


    /**
     * Допълнителните изисквания важат само за обектите от разговор.
     * Наследеният обработвач се вика през базовия клас, затова наследникът идва от $driver.
     */
    public static function on_AfterPrepareEditForm($driver, $embedder, $data)
    {
        $form = $data->form ?? null;
        if ($form && cms_Library::hasAccessContext($form->rec ?? null)) {
            $form->setField($driver::SCOPED_FIELDS, 'input,mandatory');
        }
    }


    /** Формата и инструментът приемат едни и същи медийни файлове за обектите от разговори. */
    public static function on_AfterInputEditForm($driver, $embedder, $form)
    {
        $rec = $form->rec ?? null;
        if (!$rec || !cms_Library::hasAccessContext($rec) || !$form->isSubmitted()) return;

        foreach (array('source1', 'source2') as $field) {
            if (empty($rec->{$field})) continue;
            if ($error = $driver::getCodecError($rec->{$field})) {
                $form->setError($field, $error);
            }
        }
    }
}
