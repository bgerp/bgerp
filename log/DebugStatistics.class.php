<?php


/**
 * Филтриране и статистика само по имената на дебъг файловете.
 *
 * @category  bgerp
 * @package   log
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class log_DebugStatistics extends core_BaseClass
{
    /**
     * Разпознава завършен запис или първоначалния файл без потребител и размер.
     */
    public static function parseName($name)
    {
        if (preg_match('/\A(-?[0-9]+)(?:\|[0-9]+)?_0*(-?[0-9]+)_([0-9]{2})_([0-9]{2})_([0-9]{2})_[0-9]+_([0-9]+)\.debug\z/', $name, $m)) {
            $rec = array('status' => $m[1], 'user' => (int) $m[2], 'hour' => (int) $m[3], 'minute' => (int) $m[4], 'second' => (int) $m[5], 'size' => (int) $m[6]);
        } elseif (preg_match('/\A000_([0-9]{2})_([0-9]{2})_([0-9]{2})_[0-9]+\.debug\z/', $name, $m)) {
            $rec = array('status' => '000', 'user' => null, 'hour' => (int) $m[1], 'minute' => (int) $m[2], 'second' => (int) $m[3], 'size' => null);
        } else {
            return false;
        }

        if ($rec['hour'] > 23 || $rec['minute'] > 59 || $rec['second'] > 59) {
            return false;
        }
        $rec['time'] = $rec['hour'] * 3600 + $rec['minute'] * 60 + $rec['second'];

        return $rec;
    }


    /**
     * Час от филтъра; липсващата граница се задава от извикващия код.
     */
    public static function parseTime($value, $default = null)
    {
        $value = trim($value ?? '');
        if ($value === '') {
            return $default;
        }
        if (!preg_match('/\A([0-9]{1,2})(?::([0-9]{2}))?(?::([0-9]{2}))?\z/', $value, $m)) {
            return false;
        }
        $hour = (int) $m[1];
        $minute = (int) ($m[2] ?? 0);
        $second = (int) ($m[3] ?? 0);

        return ($hour < 24 && $minute < 60 && $second < 60) ? $hour * 3600 + $minute * 60 + $second : false;
    }


    /**
     * Подготвя условията еднократно преди обхождането.
     */
    public static function prepareFilter($search)
    {
        $user = isset($search['user']) && $search['user'] !== '' ? (string) $search['user'] : null;
        if ($user === (string) PHP_INT_MAX) {
            $user = null;
        } elseif ($user === (string) (PHP_INT_MAX - 1)) {
            $user = 'withoutSystem';
        }
        $from = self::parseTime($search['execTimeFrom'] ?? '', 0);
        $to = self::parseTime($search['execTimeTo'] ?? '', 86399);
        if ($from === false || $to === false) {
            throw new InvalidArgumentException('Invalid debug time filter');
        }
        if ($from > $to) {
            list($from, $to) = array($to, $from);
        }
        $interval = $search['statInterval'] ?? '60';
        if (!in_array((string) $interval, array('1', '5', '30', '60'), true)) {
            throw new InvalidArgumentException('Invalid debug statistics interval');
        }
        $times = array('fast' => '00', 'slow' => '05', 'verySlow' => '20', 'overTime' => '50');
        $sizes = array('small' => array(0, 100000), 'big' => array(100000, 300000), 'veryBig' => array(300000, null), 'veryBig5' => array(500000, null));
        $status = trim($search['status'] ?? '');
        $patterns = array();
        if ($status !== '') {
            foreach (explode('|', $status) as $part) {
                $patterns[] = str_replace('x', '[0-9]', preg_quote($part, '/'));
            }
        }

        return array(
            'search' => trim($search['search'] ?? ''), 'user' => $user,
            'duration' => $times[$search['execTime'] ?? ''] ?? null,
            'size' => $sizes[$search['execSize'] ?? ''] ?? null,
            'from' => $from, 'to' => $to,
            'interval' => (int) $interval,
            'status' => $patterns ? '/\A(?:' . implode('|', $patterns) . ')\z/' : null,
        );
    }


    /**
     * Общ предикат за списъка и статистиката.
     */
    public static function matches($name, $rec, $filter)
    {
        if ($filter['search'] !== '' && strpos($name, $filter['search']) === false) {
            return false;
        }
        $user = $rec['user'] ?? null;
        if ($filter['user'] !== null) {
            switch ($filter['user']) {
                case 'registered': $matchesUser = $user !== null && $user > 0; break;
                case 'service': $matchesUser = $user !== null && $user < 0; break;
                case 'withoutSystem': $matchesUser = $user !== -1; break;
                case 'withoutSystemAndAnonymous': $matchesUser = $user !== null && $user !== -1 && $user !== 0; break;
                default: $matchesUser = $user !== null && (string) $user === $filter['user'];
            }
            if (!$matchesUser) {
                return false;
            }
        }
        $status = $rec['status'] ?? '';
        if ($filter['duration'] !== null && $status !== '2' . $filter['duration'] && $status !== '8' . $filter['duration']) {
            return false;
        }
        if ($filter['size'] !== null) {
            $size = $rec['size'] ?? null;
            if ($size === null || $size < $filter['size'][0] || ($filter['size'][1] !== null && $size > $filter['size'][1])) {
                return false;
            }
        }
        if ($rec && ($rec['time'] < $filter['from'] || $rec['time'] > $filter['to'])) {
            return false;
        }
        if (!$rec && ($filter['from'] > 0 || $filter['to'] < 86399)) {
            return false;
        }

        return $filter['status'] === null || (bool) preg_match($filter['status'], $status);
    }


    /**
     * Обхожда само имената, без stat, сортиране и отваряне на файлове.
     */
    public static function scan($path, $filter, $excludeName = '')
    {
        $dir = @opendir($path);
        if ($dir === false) {
            return false;
        }
        try {
            $names = function () use ($dir) {
                while (($name = readdir($dir)) !== false) {
                    yield $name;
                }
            };

            return self::summarize($names(), $filter, $excludeName);
        } finally {
            closedir($dir);
        }
    }


    /**
     * Паметта зависи само от броя интервали и категории, а не от броя файлове.
     */
    public static function summarize($names, $filter, $excludeName = '')
    {
        $empty = array('total' => 0, 'success' => 0, 'errors' => 0, 'pending' => 0, 'observations' => 0, 'other' => 0);
        $durations = array('00' => 0, '05' => 0, '20' => 0, '50' => 0, 'unknown' => 0);
        $interval = $filter['interval'] ?? 60;
        $step = $interval * 60;
        $result = array('totals' => $empty, 'intervals' => array(), 'interval' => $interval, 'ajax' => 0, 'durations' => $durations, 'ignored' => 0, 'generatedAt' => time());
        for ($bucket = (int) floor($filter['from'] / $step); $bucket <= (int) floor($filter['to'] / $step); $bucket++) {
            $result['intervals'][$bucket] = $empty + array('durations' => $durations, 'size' => array('count' => 0, 'sum' => 0, 'max' => 0));
        }
        foreach ($names as $name) {
            if ($name === $excludeName || substr($name, -6) !== '.debug') {
                continue;
            }
            $rec = self::parseName($name);
            if (!$rec) {
                $result['ignored']++;
                continue;
            }
            if (!self::matches($name, $rec, $filter)) {
                continue;
            }
            $status = $rec['status'];
            $duration = 'unknown';
            if (strlen($status) === 3 && ($status[0] === '2' || $status[0] === '8')) {
                $group = 'success';
                if ($status[0] === '8') {
                    $result['ajax']++;
                }
                if (isset($durations[substr($status, 1)])) {
                    $duration = substr($status, 1);
                }
            } elseif ($status === '000') {
                $group = 'pending';
            } elseif ($status === '150') {
                $group = 'observations';
            } elseif ($status === '0' || (strlen($status) === 3 && ($status[0] === '4' || $status[0] === '5'))) {
                $group = 'errors';
            } else {
                $group = 'other';
            }
            $result['totals']['total']++;
            $result['totals'][$group]++;
            $bucket = (int) floor($rec['time'] / $step);
            $result['intervals'][$bucket]['total']++;
            $result['intervals'][$bucket][$group]++;
            $result['intervals'][$bucket]['durations'][$duration]++;
            $result['durations'][$duration]++;
            if ($rec['size'] !== null) {
                $result['intervals'][$bucket]['size']['count']++;
                $result['intervals'][$bucket]['size']['sum'] += $rec['size'];
                $result['intervals'][$bucket]['size']['max'] = max($result['intervals'][$bucket]['size']['max'], $rec['size']);
            }
        }
        $result['average'] = $result['totals']['total'] / count($result['intervals']);

        return $result;
    }


    /**
     * Общи производни показатели за графиките и текстовия експорт.
     */
    public static function getIntervalMetrics($values)
    {
        $known = $values['total'] - $values['durations']['unknown'];
        $size = $values['size'];

        return array(
            'fastPercent' => $known ? 100 * $values['durations']['00'] / $known : null,
            'errorPercent' => $values['total'] ? 100 * $values['errors'] / $values['total'] : null,
            'sizeAverage' => $size['count'] ? $size['sum'] / $size['count'] : null,
        );
    }


    /**
     * Самостоятелен текст с контекст и всички интервали, подходящ за LLM.
     */
    public static function exportText($stats, $filter, $search)
    {
        $search['user'] = $filter['user'] ?? 'all';
        $search['execTimeFrom'] = gmdate('H:i:s', $filter['from']);
        $search['execTimeTo'] = gmdate('H:i:s', $filter['to']);
        $search['statInterval'] = $stats['interval'];
        $number = function ($value) {
            if ($value === null) return 'NA';

            return is_float($value) ? rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.') : (string) $value;
        };
        $text = "# Статистика на дебъг записите в bgERP\n\n";
        $text .= 'Изчислено на: ' . date('Y-m-d H:i:s T', $stats['generatedAt']) . "\n";
        $text .= 'Интервал: ' . $stats['interval'] . " минути\n";
        $text .= 'Часов диапазон (включително): ' . gmdate('H:i:s', $filter['from']) . ' - ' . gmdate('H:i:s', $filter['to']) . "\n";
        $text .= 'Брой интервали, включително празните: ' . count($stats['intervals']) . "\n";
        $text .= "\n## Приложен филтър\n";
        $text .= "search = част от името; user = ID или група потребители (all = всички); execTime = категория за бързина; execSize = категория за размер; status = код/група кодове. Празна стойност означава без ограничение.\n";
        $text .= json_encode($search, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) . "\n";
        $text .= "\n## Как да се тълкуват данните\n";
        $text .= "Източник са само имената на наличните .debug файлове. Съдържание и файлови метаданни не се четат.\n";
        $text .= "Имената нямат дата: едни и същи часове от различни дни са сумирани. Това не е хронология по дни. Крайните интервали може да са частични според часовия филтър.\n";
        $text .= "Една заявка може да има няколко записа; част от AJAX заявките не се записват. Успешните записи и грешките се пазят различно дълго. Брой записи не е точен брой HTTP заявки или измерване на CPU натоварването.\n";
        $text .= "Бързината е категория след закръгляване до цяла секунда: 200/800 = 0 - 1 сек.; 205/805 = 2 - 5 сек.; 220/820 = 6 - 20 сек.; 250/850 = над 20 сек. Кодовете не са точна продължителност; точна средна продължителност и точни персентили не могат да се изчислят.\n";
        $text .= "fast_percent е делът на кодовете 200/800 само сред записите с разпозната бързина. Бавни са останалите три категории с време. error_percent е делът на грешките от всички записи в интервала. Общите проценти се изчисляват от общите броеве, а не чрез осредняване на процентите по интервали.\n";
        $text .= "Размерът е броят байтове на дебъг записа според името, не размер на HTTP отговор или използвана памет. Графиката показва KiB = bytes / 1024. Записите без размер не участват в средния размер.\n";
        $text .= "NA означава липса на данни, не нула. Таблицата включва всички интервали, включително празните.\n";
        $text .= "\n## Обобщение\n";
        $captions = array('total' => 'Общо записи', 'success' => 'Успешни', 'errors' => 'Грешки', 'pending' => 'Неприключени', 'observations' => 'Наблюдения', 'other' => 'Други');
        foreach ($captions as $key => $caption) {
            $text .= $caption . ': ' . $stats['totals'][$key] . "\n";
        }
        $known = $stats['totals']['total'] - $stats['durations']['unknown'];
        $text .= 'Успешни по AJAX: ' . $stats['ajax'] . "\n";
        $text .= 'Среден брой записи на интервал: ' . $number($stats['average']) . "\n";
        $text .= 'Общ дял бързи (%): ' . $number($known ? 100 * $stats['durations']['00'] / $known : null) . "\n";
        $text .= 'Общ дял грешки (%): ' . $number($stats['totals']['total'] ? 100 * $stats['totals']['errors'] / $stats['totals']['total'] : null) . "\n";
        $text .= 'Пропуснати неразпознати имена в директорията: ' . $stats['ignored'] . "\n";
        $text .= "\n## Стойности зад петте графики\n";
        $text .= "Колони: начало на интервала; общо; успешни; грешки; неприключени; наблюдения; други; четирите категории за време; без данни за време; дял бързи (%); дял грешки (%); записи с размер; общ размер; среден размер; максимален размер (в байтове).\n\n";
        $text .= "```tsv\ninterval_start\ttotal\tsuccess\terrors\tpending\tobservations\tother\tfast_0_1s\tslow_2_5s\tslow_6_20s\tslow_over20s\tno_duration\tfast_percent\terror_percent\tsize_count\tsize_sum_bytes\tsize_avg_bytes\tsize_max_bytes\n";
        foreach ($stats['intervals'] as $bucket => $values) {
            $minute = $bucket * $stats['interval'];
            $row = array(sprintf('%02d:%02d', (int) floor($minute / 60), $minute % 60));
            foreach ($captions as $key => $caption) $row[] = $values[$key];
            foreach (array('00', '05', '20', '50', 'unknown') as $key) $row[] = $values['durations'][$key];
            $metrics = self::getIntervalMetrics($values);
            $row[] = $metrics['fastPercent'];
            $row[] = $metrics['errorPercent'];
            $row[] = $values['size']['count'];
            $row[] = $values['size']['sum'];
            $row[] = $metrics['sizeAverage'];
            $row[] = $values['size']['count'] ? $values['size']['max'] : null;
            $text .= implode("\t", array_map($number, $row)) . "\n";
        }

        return $text . "```\n";
    }
}
