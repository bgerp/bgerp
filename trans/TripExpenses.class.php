<?php


/**
 * Клас 'trans_TripExpenses'
 *
 * Разходи по пътен лист, записани в една валута
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class trans_TripExpenses extends core_BaseClass
{
    /**
     * Перата на разходите
     *
     * @return array
     */
    private static function getCategories()
    {
        return array('electricity' => 'Електроенергия', 'fuel' => 'Гориво', 'adblue' => 'AdBlue',
            'toll' => 'Пътни такси', 'parking' => 'Паркинг', 'repair' => 'Ремонт',
            'consumables' => 'Консумативи', 'other' => 'Други');
    }


    /**
     * Мерките на перата
     *
     * @return array
     */
    public static function getCategoryUnits()
    {
        return array('electricity' => 'kWh', 'fuel' => 'l', 'adblue' => 'l',
            'toll' => 'pcs', 'parking' => 'pcs', 'repair' => 'pcs', 'consumables' => 'pcs', 'other' => 'pcs');
    }


    /**
     * Типът на таблицата с разходите
     *
     * @return type_Table
     */
    public static function getType()
    {
        $type = core_Type::getByName('table(columns=date|category|quantity|unit|amount|document|note,captions=Дата|Перо|Количество|Мярка|Сума с ДДС|Документ|Бележка,mandatory=mandatory,render=trans_TripExpenses::render)');
        $type->params['category_opt'] = array('' => '') + self::getCategories();
        $type->params['unit_opt'] = array('' => '', 'kWh' => 'kWh', 'l' => 'l', 'pcs' => 'бр.');

        return $type;
    }


    /**
     * Непразните редове на таблицата или null при некоректни данни
     *
     * @param mixed $value
     *
     * @return array|null
     */
    private static function getRows($value)
    {
        if ($value === null || $value === '') {
            return array();
        }

        $columns = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($columns)) {
            return null;
        }

        $allowed = self::getType()->getColumns();
        foreach ($columns as $field => $values) {
            if (!isset($allowed[$field]) || !is_array($values)) {
                return null;
            }
            if ($values && array_keys($values) !== range(0, count($values) - 1)) {
                return null;
            }
            foreach ($values as $cell) {
                if ($cell !== null && !is_scalar($cell)) {
                    return null;
                }
            }
        }

        return array_filter(type_Table::toArray(self::getType()->fromVerbal($columns)), function ($row) {
            foreach (array('category', 'quantity', 'amount', 'document', 'note') as $field) {
                if (trim((string) ($row->{$field} ?? '')) !== '') {
                    return true;
                }
            }

            return false;
        });
    }


    /**
     * Число от въведения текст: null при празно, false при грешка
     *
     * @param mixed $value
     *
     * @return float|null|false
     */
    private static function parseNumber($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $number = core_Type::getByName('double')->fromVerbal($value);

        return $number !== false && $number !== null && is_finite((float) $number) ? $number : false;
    }


    /**
     * Дата от въведения текст: null при празно, false при грешка
     *
     * @param mixed $value
     *
     * @return string|null|false
     */
    private static function parseDate($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $parts)) {
            list(, $year, $month, $day) = $parts;
        } elseif (preg_match('~^(\d{1,2})[./-](\d{1,2})[./-](\d{4})$~', $value, $parts)) {
            list(, $day, $month, $year) = $parts;
        } else {
            return false;
        }

        // Типът date коригира несъществуващи дни, а разходът трябва да е с датата от бележката
        if (!checkdate((int) $month, (int) $day, (int) $year)) {
            return false;
        }

        return core_Type::getByName('date')->fromVerbal($value);
    }


    /**
     * Сума в центове: null при празно, false при грешка или дробни центове
     *
     * @param mixed $value
     *
     * @return int|null|false
     */
    private static function parseAmountCents($value)
    {
        $number = self::parseNumber($value);
        if ($number === null || $number === false) {
            return $number;
        }

        $scaled = $number * 100;
        if (!is_finite($scaled) || abs($scaled) >= PHP_INT_MAX) {
            return false;
        }

        $cents = round($scaled);

        // Допуска се само грешка от закръгляне, не дробен цент
        if (abs($scaled - $cents) > abs($scaled) * PHP_FLOAT_EPSILON * 4) {
            return false;
        }

        return (int) $cents;
    }


    /**
     * Таблицата за формата - датите като дд.мм.гггг, както се въвеждат
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function toFormValue($value)
    {
        $columns = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($columns) || empty($columns['date']) || !is_array($columns['date'])) {

            return $value;
        }

        foreach ($columns['date'] as $index => $date) {
            $parsed = is_scalar($date) ? self::parseDate($date) : null;
            if (!empty($parsed)) {
                $columns['date'][$index] = dt::mysql2verbal($parsed, 'd.m.Y');
            }
        }

        return is_string($value) ? json_encode($columns, JSON_UNESCAPED_UNICODE) : $columns;
    }


    /**
     * Нормализира таблицата: мярка по перото, датите и сумите в машинен вид
     *
     * @param mixed       $value
     * @param string|null $defaultDate
     *
     * @return string
     */
    public static function normalize($value, $defaultDate = null)
    {
        $rows = self::getRows($value);
        if ($rows === null) {
            return is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $columns = array();
        $categoryUnits = self::getCategoryUnits();
        foreach ($rows as $row) {
            foreach (self::getType()->getColumns() as $field => $definition) {
                $cell = trim((string) ($row->{$field} ?? ''));
                $parsed = null;
                if ($field === 'unit') {
                    $cell = $categoryUnits[$row->category ?? ''] ?? '';
                }
                if ($field === 'date') {
                    if ($cell === '') {
                        $cell = trim((string) $defaultDate);
                    }
                    $parsed = self::parseDate($cell);
                }
                if ($field === 'quantity') {
                    $parsed = self::parseNumber($cell);
                }
                if ($field === 'amount') {
                    $cents = self::parseAmountCents($cell);
                    $parsed = $cents !== null && $cents !== false ? $cents / 100 : $cents;
                }
                $columns[$field][] = $parsed !== null && $parsed !== false ? $parsed : $cell;
            }
        }

        return json_encode($columns, JSON_UNESCAPED_UNICODE);
    }


    /**
     * Грешките в таблицата с разходите
     *
     * @param mixed $value
     *
     * @return array
     */
    public static function getErrors($value)
    {
        $rows = self::getRows($value);
        if ($rows === null) {
            return array('expenses' => 'Некоректни таблични данни за разходите');
        }

        $categories = self::getCategories();
        $messages = array();
        $totalCents = 0;
        foreach ($rows as $index => $row) {
            $errors = array();
            $category = $row->category ?? '';
            $amount = self::parseAmountCents($row->amount ?? '');
            $quantity = self::parseNumber($row->quantity ?? '');
            if (!isset($categories[$category])) {
                $errors[] = 'изберете перо';
            }
            if ($amount === null) {
                $errors[] = 'въведете сума';
            } elseif ($amount === false || $amount < 0) {
                $errors[] = 'сумата трябва да е число от 0 нагоре, с до два знака след десетичната запетая';
            } elseif ($amount > PHP_INT_MAX - $totalCents) {
                $errors[] = 'общата сума е прекалено голяма';
            } else {
                $totalCents += $amount;
            }
            if (self::parseDate($row->date ?? '') === false) {
                $errors[] = 'въведете валидна дата (дд.мм.гггг)';
            }
            if ($quantity !== null && ($quantity === false || $quantity <= 0)) {
                $errors[] = 'количеството трябва да е положително число, ако е попълнено';
            }
            if ($category === 'other' && trim($row->note ?? '') === '') {
                $errors[] = 'пояснете другия разход в бележката';
            }
            if ($errors) {
                $messages[] = 'Ред ' . ($index + 1) . ': ' . implode('; ', $errors);
            }
        }

        return $messages ? array('expenses' => implode("\n", $messages)) : array();
    }


    /**
     * Екранира стойност за HTML
     *
     * @param mixed $value
     *
     * @return string
     */
    private static function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }


    /**
     * Началото на таблицата със заглавния ред
     *
     * @param string $currencyCode
     *
     * @return string
     */
    private static function getTableStart($currencyCode = '')
    {
        $html = '<table class="innerTable border-table paddingCells trip-expenses" style="width:100%"><thead><tr>';
        foreach (self::getType()->getColumns() as $field => $definition) {
            $caption = tr($definition->caption ?? '');
            if ($field === 'amount' && $currencyCode !== '') {
                $caption .= ' (' . $currencyCode . ')';
            }
            $html .= '<th>' . self::escape($caption) . '</th>';
        }

        return $html . '</tr></thead><tbody>';
    }


    /**
     * Показва разходите със сборове по пера
     *
     * @param mixed $value
     * @param mixed $currencyCode
     *
     * @return string
     */
    public static function render($value, $currencyCode = null)
    {
        $rows = self::getRows($value);
        if (!$rows) {
            return '';
        }

        // type_Table подава като втори аргумент себе си, а не валута
        $currencyCode = is_string($currencyCode) ? $currencyCode : '';
        $categories = self::getCategories();
        $units = self::getType()->params['unit_opt'];
        $categoryUnits = self::getCategoryUnits();
        $number = core_Type::getByName('double(smartRound)');
        $money = core_Type::getByName('double(smartRound,minDecimals=2)');
        $totals = array();
        $html = self::getTableStart($currencyCode);
        foreach ($rows as $row) {
            $category = $row->category ?? '';
            $date = self::parseDate($row->date ?? '');
            $quantity = self::parseNumber($row->quantity ?? '');
            $amount = self::parseAmountCents($row->amount ?? '');
            if (isset($categories[$category]) && $amount !== null && $amount !== false && $amount >= 0) {
                $totals[$category] = ($totals[$category] ?? 0) + $amount;
            }
            $html .= '<tr style="page-break-inside:avoid"><td>' . self::escape($date ? dt::mysql2verbal($date, 'd.m.Y') : ($row->date ?? '')) . '</td>'
                . '<td>' . self::escape(tr($categories[$category] ?? $category)) . '</td>'
                . '<td class="rightCol">' . ($quantity !== null && $quantity !== false ? $number->toVerbal($quantity) : self::escape($row->quantity ?? '')) . '</td>'
                . '<td>' . self::escape($units[$categoryUnits[$category] ?? ''] ?? '') . '</td>'
                . '<td class="rightCol">' . ($amount !== null && $amount !== false ? $money->toVerbal($amount / 100) : self::escape($row->amount ?? '')) . '</td>'
                . '<td>' . self::escape($row->document ?? '') . '</td><td>' . self::escape($row->note ?? '') . '</td></tr>';
        }

        $html .= '</tbody><tfoot>';
        foreach ($categories as $category => $caption) {
            if (!isset($totals[$category])) {
                continue;
            }
            $html .= '<tr><td colspan="4">' . self::escape(tr('Общо') . ' — ' . tr($caption)) . '</td><td class="rightCol">'
                . $money->toVerbal($totals[$category] / 100) . '</td><td colspan="2"></td></tr>';
        }
        $html .= '<tr><th colspan="4">' . self::escape(tr('Общо разходи') . ($currencyCode !== '' ? ' (' . $currencyCode . ')' : ''))
            . '</th><th class="rightCol">' . $money->toVerbal(array_sum($totals) / 100) . '</th><th colspan="2"></th></tr>';

        return $html . '</tfoot></table>';
    }
}
