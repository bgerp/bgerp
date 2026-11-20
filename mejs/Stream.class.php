<?php


/**
 * Подава избрана писта на библиотечния плеър със същия контекст на достъп.
 *
 * @category  bgerp
 * @package   mejs
 * @author    Mustafa Mustafov <mmustafov084@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mejs_Stream extends core_Mvc
{
    public function act_Play()
    {
        // Не допускаме HTML обвивката, компресирането или PHP буфера да променят диапазона.
        while (ob_get_level()) ob_end_clean();
        if (function_exists('apache_setenv')) apache_setenv('no-gzip', '1');

        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($method !== 'GET' && $method !== 'HEAD') {
            header('Allow: GET, HEAD');
            self::finish(405);
        }

        $id = Request::get('objectId', 'int');
        $slot = Request::get('source', 'int');
        $source = self::getSource($id, $slot);
        if (!$source) self::finish(403);

        $data = $source['dataId'] ? fileman_Data::fetch($source['dataId']) : null;
        $path = $data ? fileman_Data::getGoodFilePath($data, false) : '';
        $input = $path !== '' && is_file($path) ? @fopen($path, 'rb') : false;
        if (!$input) self::finish(404);

        $stat = fstat($input);
        $size = $stat['size'] ?? null;
        if ($size === null) {
            fclose($input);
            self::finish(404);
        }
        // Без валидатор за If-Range клиентът трябва да получи цялата текуща версия.
        $range = empty($_SERVER['HTTP_IF_RANGE']) ? ($_SERVER['HTTP_RANGE'] ?? '') : '';
        $bounds = self::getByteRange($range, $size);
        header('Accept-Ranges: bytes');
        if ($bounds === false) {
            header('Content-Range: bytes */' . $size);
            fclose($input);
            self::finish(416);
        }

        list($start, $end) = $bounds;
        $length = $end - $start + 1;
        if ($range !== '') {
            http_response_code(206);
            header("Content-Range: bytes {$start}-{$end}/{$size}");
        }
        core_Session::pause();
        header('Content-Type: ' . $source['mime']);
        self::setHeaders($length);
        if ($method === 'GET' && $length > 0) {
            $output = fopen('php://output', 'wb');
            stream_copy_to_stream($input, $output, $length, $start);
            fclose($output);
        }
        fclose($input);
        flush();
        core_App::shutdown(false);
    }


    /** Пътят и MIME идват само от записания файл на избрания драйвер. */
    private static function getSource($id, $slot)
    {
        $id = (int) $id;
        $slot = (int) $slot;
        if ($id < 1 || !in_array($slot, array(1, 2), true)) return null;
        $rec = cms_Library::fetch($id, '*', false);
        if (!$rec || !cms_Library::hasAccessContext($rec) || !cms_Library::canViewAccessContext($rec)) return null;
        $driver = !empty($rec->driverClass) ? core_Classes::getName($rec->driverClass) : null;
        if ($driver !== 'mejs_VideoDriver') return null;

        return mejs_VideoDriver::getProtectedSource($rec->{'source' . $slot} ?? null);
    }


    /** Един byte-range, включително отворен край или суфикс. */
    private static function getByteRange($range, $size)
    {
        if ($range === '') return array(0, $size - 1);
        if (!$size || !preg_match('/^bytes=(\d*)-(\d*)$/iD', trim($range), $m)) return false;
        if ($m[1] === '') {
            $suffix = (int) $m[2];

            return $suffix > 0 ? array(max(0, $size - $suffix), $size - 1) : false;
        }

        $start = (int) $m[1];
        $end = $m[2] === '' ? $size - 1 : min((int) $m[2], $size - 1);

        return $start <= $end ? array($start, $end) : false;
    }


    private static function setHeaders($length)
    {
        header('Cache-Control: private, no-store, no-transform, max-age=0');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . $length);
    }


    private static function finish($status)
    {
        http_response_code($status);
        self::setHeaders(0);
        flush();
        core_App::shutdown(false);
    }
}
