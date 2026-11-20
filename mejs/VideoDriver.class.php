<?php


/**
 * Клас 'mejs_Driver'
 *
 * Вграждане на видео и аудио обекти в bgERP
 *
 * @category  bgerp
 * @package   mejs
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 */
class mejs_VideoDriver extends core_BaseClass
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cms_LibraryIntf';

    private const FORMATS = array(
        'mp4' => array('mov', array('h264'), array('aac')),
        'webm' => array('matroska', array('vp8', 'vp9'), array('opus', 'vorbis')),
        'ogv' => array('ogg', array('theora'), array('vorbis')),
    );
    
    
    /**
     * Заглавие на класа
     */
    public $title = 'Видео';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    public static function addFields(&$form)
    {
        $form->FLD('mediaTitle', 'varchar(160)', 'caption=Заглавие,input=none,single=none');
        $form->FLD('source1', 'fileman_FileType(bucket=cms_Video,align=vertical)', 'caption=Файл|* 1');
        $form->FLD('source2', 'fileman_FileType(bucket=cms_Video,align=vertical)', 'caption=Файл|* 2');
        $form->FLD('width', 'int(min=10,max=1200)', 'caption=Широчина,placeholder=100%');
        $form->FLD('height', 'int(min=10,max=1200)', 'caption=Височина,placeholder=100%');

    }
    
    
    /**
     * Връща HTML представянето на обекта
     *
     * @param stdClass $rec Записа за елемента от модела-библиотека
     * @param $maxWidth int Максимална широчина на елемента
     * @param $isAbsolute bool Дали URL-тата да са абсолютни
     *
     * @return core_ET|string Представяне на обекта в HTML шабло
     */
    public static function render($rec, $maxwidth = 1200, $absolute = false)
    {
        if (Mode::is('text', 'plain')) return '';

        $scoped = cms_Library::hasAccessContext($rec);
        if ($scoped && (empty($rec->id) || !cms_Library::canViewAccessContext($rec))) return '';

        $sources = array();
        foreach (array(1 => $rec->source1 ?? null, 2 => $rec->source2 ?? null) as $slot => $fh) {
            if (!$fh) continue;
            if ($scoped) {
                $source = self::getProtectedSource($fh);
                if (!$source) return '';
                $sources[$fh] = array(
                    'url' => toUrl(array('mejs_Stream', 'play', 'objectId' => $rec->id, 'source' => $slot), $absolute ? 'absolute' : 'relative'),
                    'type' => $source['mime'],
                );
            } else {
                $sources[$fh] = $fh;
            }
        }
        if ($scoped && !$sources) return '';

        $params = array(
            'width' => !empty($rec->width) ? $rec->width . 'px' : '100%',
            'height' => !empty($rec->height) ? $rec->height . 'px' : '100%',
        );
        if ($scoped) $params['title'] = htmlspecialchars($rec->mediaTitle ?? '', ENT_QUOTES, 'UTF-8');

        return mejs_Adapter::createVideo($sources, $params, $scoped ? 'stream' : 'handler');
    }


    /** Файл и MIME за плеъра и за всяка отделна заявка към потока. */
    public static function getProtectedSource($fh)
    {
        if (!$fh || cms_Library::getEmbeddedFileError($fh) !== null) return null;
        $file = fileman_Files::fetchByFh($fh);
        $name = $file->name ?? '';
        if (empty($file->dataId) || !isset(self::FORMATS[fileman_Files::getExt($name)])) return null;

        return array('dataId' => $file->dataId, 'mime' => fileman_Files::getType($name));
    }


    /** Библиотеката е проверила файловия идентификатор преди отварянето на съдържанието. */
    private static function getCodecError($fh)
    {
        $file = fileman_Files::fetchByFh($fh);
        $extension = fileman_Files::getExt($file->name ?? '');
        if (!isset(self::FORMATS[$extension])) {
            return 'Допустимите формати за този плеър са ' . implode(', ', array_keys(self::FORMATS)) . '.';
        }

        // Фиксираният демултиплексор и локалният протокол изключват плейлисти и външни адреси.
        list($format, $videoCodecs, $audioCodecs) = self::FORMATS[$extension];
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
        $hasVideo = false;
        foreach ($info['streams'] ?? array() as $stream) {
            $codec = $stream['codec_name'] ?? '';
            $type = $stream['codec_type'] ?? '';
            if ($type === 'video' && empty($stream['disposition']['attached_pic'])) {
                if (!in_array($codec, $videoCodecs, true) || !in_array($stream['pix_fmt'] ?? '', array('yuv420p', 'yuvj420p'), true)) {
                    return 'Неподдържан видеокодек или пикселен формат. Използвайте ' . implode('/', $videoCodecs) . ' с 8-битов YUV 4:2:0.';
                }
                $hasVideo = true;
            } elseif ($type === 'audio') {
                if (!in_array($codec, $audioCodecs, true)) {
                    return 'Неподдържан аудиокодек за този формат. Използвайте ' . implode('/', $audioCodecs) . '.';
                }
            }
        }

        if (!$hasVideo) return 'Файлът няма възпроизводима видеописта.';

        return null;
    }


    /** Допълнителните изисквания важат само за обектите от разговор. */
    public static function on_AfterPrepareEditForm($driver, $embedder, $data)
    {
        $form = $data->form ?? null;
        if ($form && cms_Library::hasAccessContext($form->rec ?? null)) {
            $form->setField('source1,mediaTitle', 'input,mandatory');
        }
    }


    /** Формата и инструментът приемат едни и същи медийни файлове за обектите от разговори. */
    public static function on_AfterInputEditForm($driver, $embedder, $form)
    {
        $rec = $form->rec ?? null;
        if (!$rec || !cms_Library::hasAccessContext($rec) || !$form->isSubmitted()) return;

        foreach (array('source1', 'source2') as $field) {
            if (empty($rec->{$field})) continue;
            if ($error = self::getCodecError($rec->{$field})) {
                $form->setError($field, $error);
            }
        }
    }
}
