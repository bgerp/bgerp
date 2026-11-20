<?php


/**
 * Интерактивна графика в мултимедийната библиотека
 *
 * @category  bgerp
 * @package   chartjs
 *
 * @author    Mustafov1 <mmustafov3@icloud.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class chartjs_ChartDriver extends core_BaseClass
{
    public $interfaces = 'cms_LibraryIntf';

    public $title = 'Графика';


    public static function addFields(&$form)
    {
        $form->FLD('chartType', 'enum(line=Линейна,bar=Стълбовидна,pie=Кръгова)', 'caption=Вид графика,mandatory');
        $form->FLD('chartTitle', 'varchar(160)', 'caption=Заглавие,mandatory,single=none');
        $form->FLD('chartData', 'text(rows=6)', 'caption=Данни (JSON),mandatory,single=none');
    }


    /**
     * Един и същ договор за формата на библиотеката и за програмно създаване.
     */
    public static function prepareFields($chartType, $title, $data)
    {
        if (!in_array($chartType, array('line', 'bar', 'pie'), true)) {
            throw new InvalidArgumentException('Видът на графиката трябва да е line, bar или pie.');
        }
        if (!self::validText($title)) {
            throw new InvalidArgumentException('Заглавието трябва да е непразен текст до 160 знака.');
        }

        $data = self::objectFields($data);
        $keys = $chartType === 'pie' ? array('info') : array('labels', 'values');
        if (count($data) !== count($keys) || array_diff($keys, array_keys($data))) {
            throw new InvalidArgumentException('Очаквани полета в data: ' . implode(', ', $keys) . '.');
        }

        if ($chartType === 'pie') {
            $slices = self::objectFields($data['info']);
            if (!$slices || count($slices) > 200) {
                throw new InvalidArgumentException('info трябва да съдържа между 1 и 200 именувани дяла.');
            }
            foreach ($slices as $label => $value) {
                if (!self::validText((string) $label)) {
                    throw new InvalidArgumentException('Имената на дяловете трябва да са непразен текст до 160 знака.');
                }
                if (!self::validNumber($value) || $value < 0) {
                    throw new InvalidArgumentException('Дяловете трябва да са крайни неотрицателни числа.');
                }
            }
            $sum = array_sum($slices);
            if ($sum <= 0 || !is_finite($sum)) {
                throw new InvalidArgumentException('Сборът на дяловете трябва да е положително крайно число.');
            }
            $data = array('info' => (object) $slices);
        } else {
            $labels = $data['labels'];
            if (!is_array($labels) || !$labels || count($labels) > 200 || array_values($labels) !== $labels) {
                throw new InvalidArgumentException('labels трябва да е списък с между 1 и 200 етикета.');
            }
            foreach ($labels as $label) {
                if (!self::validText($label)) {
                    throw new InvalidArgumentException('Етикетите трябва да са непразен текст до 160 знака.');
                }
            }
            $series = self::objectFields($data['values']);
            if (!$series || count($series) > 16) {
                throw new InvalidArgumentException('values трябва да съдържа между 1 и 16 именувани серии.');
            }
            foreach ($series as $name => $points) {
                if (!self::validText((string) $name)) {
                    throw new InvalidArgumentException('Имената на сериите трябва да са непразен текст до 160 знака.');
                }
                if (!is_array($points) || count($points) !== count($labels) || array_values($points) !== $points) {
                    throw new InvalidArgumentException('Всяка серия трябва да е списък с по една стойност за всеки етикет.');
                }
                foreach ($points as $value) {
                    if (!self::validNumber($value)) {
                        throw new InvalidArgumentException('Стойностите в сериите трябва да са крайни числа.');
                    }
                }
            }
            $data = array('labels' => $labels, 'values' => (object) $series);
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false || strlen($json) > 65536) {
            throw new InvalidArgumentException('JSON данните за графиката трябва да са до 64 KiB.');
        }

        return array('chartType' => $chartType, 'chartTitle' => trim($title), 'chartData' => $json);
    }


    private static function objectFields($value)
    {
        if ($value instanceof stdClass || (is_array($value) && array_values($value) !== $value)) {
            return (array) $value;
        }

        return array();
    }


    private static function validText($value)
    {
        return is_string($value) && mb_strlen($value, 'UTF-8') <= 160
            && preg_match('/\S/u', $value) && !preg_match('/[\x00-\x1f\x7f]/', $value);
    }


    private static function validNumber($value)
    {
        return (is_float($value) || is_int($value)) && is_finite($value);
    }


    protected static function on_AfterInputEditForm($Driver, $Embedder, &$form)
    {
        if (!$form->isSubmitted()) {
            return;
        }
        $rec = $form->rec;
        $json = $rec->chartData ?? null;
        $data = is_string($json) ? json_decode($json, false, 8) : null;
        if (!$data instanceof stdClass) {
            $form->setError('chartData', 'Въведете валиден JSON обект.');

            return;
        }
        try {
            $fields = self::prepareFields($rec->chartType ?? null, $rec->chartTitle ?? null, $data);
            foreach ($fields as $field => $value) {
                $rec->{$field} = $value;
            }
        } catch (InvalidArgumentException $e) {
            $form->setError('chartType,chartTitle,chartData', $e->getMessage());
        }
    }


    public static function render($rec, $maxWidth = 1200, $absolute = false)
    {
        $type = $rec->chartType ?? null;
        $title = htmlspecialchars($rec->chartTitle ?? '', ENT_QUOTES, 'UTF-8');
        if (Mode::is('text', 'plain') || Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf')) {
            return $title;
        }
        $data = json_decode($rec->chartData ?? '', false, 8);
        if (!$data instanceof stdClass || !in_array($type, array('line', 'bar', 'pie'), true)) {
            return '';
        }

        return new ET('<div><strong>[#1#]</strong>[#2#]</div>', $title, chartjs_Adapter::prepare($data, $type));
    }
}
