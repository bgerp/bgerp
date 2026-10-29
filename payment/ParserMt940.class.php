<?php


/**
 * Четене на MT940 извлечения в общия формат на банковия импорт.
 * UTF-8, сметка по IBAN, суми с точност до стотинки и години 1970–2069.
 * Банковите референции са в отделно MT940 пространство; не се приравняват
 * автоматично с референции от CAMT. Банково-специфичните описания се пазят цели.
 *
 * @category bgerp
 * @package payment
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
class payment_ParserMt940
{
    public static function isCandidate($text)
    {
        return preg_match('/(?:\A|[\r\n]|\{4:):20:/', ltrim($text ?? '', "\xEF\xBB\xBF \t\r\n"))
            && preg_match('/[\r\n]:25:/', $text ?? '');
    }


    public static function getRecs($text, $serviceId = 'MT940 Import')
    {
        $res = (object) array('recs' => array(), 'warnings' => array(), 'errors' => array(),
            'recordCurrencies' => array(), 'recordSources' => array(), 'skippedEntries' => 0);
        if (!self::isCandidate($text)) {
            return null;
        }
        $text = ltrim($text, "\xEF\xBB\xBF \t\r\n");
        if (!preg_match('//u', $text) || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $text)) {
            $res->errors[] = 'MT940 трябва да е текст в UTF-8 без управляващи символи';

            return $res;
        }
        $text = str_replace(array("\r\n", "\r"), "\n", $text);
        if (substr_count($text, "\n") > 100000) {
            $res->errors[] = 'MT940 надвишава 100000 текстови реда';

            return $res;
        }
        // Приемаме само обвивка за съобщение 940; други SWIFT видове не се преобразуват.
        $text = preg_replace('/\{1:[^{}]*\}\{2:[IO]940[^{}]*\}(?:\{3:(?:[^{}]|\{[^{}]*\})*\})?\{4:(.*?)\-\}(?:\{5:(?:[^{}]|\{[^{}]*\})*\})?/s', "$1\n", $text);
        if ($text === null) {
            $res->errors[] = 'SWIFT обвивката е прекалено голяма или не може да се прочете';

            return $res;
        }
        $statements = $fields = array();
        $fieldCount = 0;
        foreach (explode("\n", trim($text)) as $line) {
            if (preg_match('/^:([0-9]{2}[A-Z]?):(.*)$/D', $line, $match)) {
                if (++$fieldCount > 50000) {
                    $res->errors[] = 'MT940 надвишава 50000 полета';

                    return $res;
                }
                if ($match[1] === '20' && $fields) {
                    $statements[] = $fields;
                    $fields = array();
                }
                $fields[] = array($match[1], $match[2]);
            } elseif (trim($line) !== '') {
                if (!$fields || substr($line, 0, 1) === ':' || strpos($line, '{1:') !== false || strpos($line, '-}') !== false) {
                    $res->errors[] = 'Невалидна структура или неподдържана SWIFT обвивка на MT940';

                    return $res;
                }
                $fields[count($fields) - 1][1] .= "\n" . $line;
            }
        }
        if ($fields) {
            $statements[] = $fields;
        }
        $entryNumber = 0;
        foreach ($statements as $index => $statement) {
            $error = null;
            $parsed = self::readStatement($statement, $entryNumber, $error);
            $entryCount = count(array_filter($statement, function ($field) { return $field[0] === '61'; }));
            $res->skippedEntries += $entryCount;
            $prefix = 'MT940 извлечение №' . ($index + 1) . ': ';
            if ($error !== null) {
                $res->errors[] = $prefix . $error . '; извлечението не се импортира';
                continue;
            }
            $account = bank_Accounts::fetch(array("#iban = '[#1#]'", $parsed->iban));
            $own = $account ? bank_OwnAccounts::fetch(array("#bankAccountId = '[#1#]'", $account->id ?? null)) : null;
            if (!$own) {
                $res->errors[] = $prefix . 'IBAN ' . $parsed->iban . ' не е намерен сред собствените банкови сметки';
                continue;
            }
            foreach ($parsed->recs as $rec) {
                if (bank_OwnAccounts::getDefaultCurrency($own->id, $rec->valior, true) !== $parsed->currency) {
                    $error = 'валутата ' . $parsed->currency . ' не съответства на собствената сметка към вальора';
                    break;
                }
            }
            if ($error !== null) {
                $res->errors[] = $prefix . $error . '; извлечението не се импортира';
                continue;
            }
            foreach ($parsed->recs as $i => $rec) {
                $rec->serviceId = $serviceId;
                $rec->ownAccountId = $own->id ?? null;
                // Старите TBB/TFC записи без референция са били идентифицирани с празен IBAN.
                if (!empty($parsed->checkLegacy[$i]) && bank_Register::fetch(array(
                    "#ownAccountId = '[#1#]' AND #valior = '[#2#]' AND #type = '[#3#]' AND #amount = '[#4#]' AND (#contragentIban IS NULL OR #contragentIban = '') AND #bankReferenceId IS NULL AND #serviceId LIKE '%MT940%'",
                    $rec->ownAccountId, $rec->valior ?? '', $rec->type ?? '', $rec->amount ?? 0
                ))) {
                    $res->warnings[] = $prefix . 'операция №' . ($parsed->entries[$i] ?? 0) . ': има възможен запис от стария MT940 импорт без IBAN и референция; проверете банковия регистър';
                    continue;
                }
                $res->recs[] = $rec;
                $res->recordCurrencies[] = $parsed->currency;
                $res->recordSources[] = array('iban' => $parsed->iban, 'entry' => $parsed->entries[$i]);
                $res->skippedEntries--;
            }
        }

        return $res;
    }


    /**
     * Един повреден ред блокира цялото извлечение, включително проверката на салдата.
     */
    private static function readStatement($fields, &$entryNumber, &$error)
    {
        $single = $transactions = $entries = array();
        $lastTransaction = null;
        $opened = $closed = false;
        foreach ($fields as $field) {
            list($tag, $value) = $field;
            $value = trim($value);
            if ($tag === '61') {
                $entries[] = ++$entryNumber;
                if (!$opened || $closed) {
                    $error = 'операция извън началното и крайното салдо';
                }
                $lastTransaction = count($transactions);
                $transactions[] = array('line' => $value, 'details' => null);
            } elseif ($tag === '86') {
                // 86 след крайното салдо е информация за цялото извлечение.
                if (!$closed && $lastTransaction !== null) {
                    if ($transactions[$lastTransaction]['details'] !== null) {
                        $error = 'повторено поле 86 за една операция';
                    }
                    $transactions[$lastTransaction]['details'] = $value;
                }
            } else {
                $key = in_array($tag, array('60F', '60M'), true) ? '60' : (in_array($tag, array('62F', '62M'), true) ? '62' : $tag);
                if (!in_array($key, array('20', '21', '25', '28', '28C', '60', '62', '64', '65', '90C', '90D'), true)) {
                    $error = 'неподдържано поле ' . $tag;
                } elseif (isset($single[$key]) && $key !== '65') {
                    $error = 'повторено поле ' . $tag;
                }
                if ($key === '60') {
                    if ($opened || $closed) $error = 'неправилен ред на салдата';
                    $opened = true;
                } elseif ($key === '62') {
                    if (!$opened || $closed) $error = 'неправилен ред на салдата';
                    $closed = true;
                } elseif (in_array($key, array('20', '21', '25', '28', '28C'), true) && $opened) {
                    $error = 'заглавно поле след началното салдо';
                } elseif (in_array($key, array('64', '65', '90C', '90D'), true) && !$closed) {
                    $error = 'контролно поле преди крайното салдо';
                }
                $single[$key] = $value;
            }
        }
        if ($error !== null) return null;
        if (!preg_match('/^[^\r\n]{1,16}$/D', $single['20'] ?? '') || !isset($single['25'], $single['60'], $single['62'])
            || (isset($single['28'], $single['28C'])) || !preg_match('/^[0-9]{1,5}(?:\/[0-9]{1,5})?$/D', $single['28C'] ?? $single['28'] ?? '')) {
            $error = 'липсват или са невалидни задължителни полета 20, 25, 28C/28, 60F/60M, 62F/62M';

            return null;
        }
        $iban = self::iban($single['25']);
        $opening = self::balance($single['60']);
        $closing = self::balance($single['62']);
        if ($iban === '' || !$opening || !$closing || $opening['currency'] !== $closing['currency'] || $opening['date'] > $closing['date']) {
            $error = 'невалиден IBAN или несъпоставими начално и крайно салдо';

            return null;
        }
        $parsed = (object) array('iban' => $iban, 'currency' => $opening['currency'], 'recs' => array(), 'entries' => $entries, 'checkLegacy' => array());
        $sums = $counts = array('C' => 0, 'D' => 0);
        $references = array();
        foreach ($transactions as $i => $transaction) {
            $parts = explode("\n", $transaction['line']);
            if (count($parts) > 2 || !preg_match('/^(\d{6})(\d{4})?(RC|RD|C|D)([A-Z])?([0-9]+,[0-9]*)([A-Z][A-Z0-9]{3})(.+)$/D', $parts[0], $match)) {
                $error = 'невалидно поле 61 при операция №' . $entries[$i];
                return null;
            }
            $date = self::date($match[1]);
            $amount = self::amount($match[5]);
            $refs = explode('//', $match[7]);
            $entryDate = $match[2];
            if ($date === null || $amount === null || $amount > 200000000000
                || ($entryDate !== '' && !checkdate((int) substr($entryDate, 0, 2), (int) substr($entryDate, 2, 2), 2000))
                || count($refs) > 2 || strlen($refs[0]) > 16 || strlen($refs[1] ?? '') > 16) {
                $error = 'невалидна дата, сума или референция при операция №' . $entries[$i];
                return null;
            }
            $reversal = strlen($match[3]) === 2;
            $direction = in_array($match[3], array('C', 'RD'), true) ? 'C' : 'D';
            $counts[$direction]++;
            $sums[$direction] += $amount;
            $rec = (object) array('valior' => $date, 'amount' => $amount / 100,
                'type' => $direction === 'C' ? 'incoming' : 'outgoing', 'contragentIban' => '', 'contragentName' => '');
            $details = $transaction['details'] ?? '';
            // Извличаме само известните подполета; пълният текст остава в основанието.
            if (preg_match('/^(?:[0-9]{3}|TBB|TFC)([?+])[0-9]{2}/', $details, $separator)) {
                $subfields = preg_split('/' . preg_quote($separator[1], '/') . '([0-9]{2})/', str_replace("\n", '', $details), -1, PREG_SPLIT_DELIM_CAPTURE);
                $values = array();
                for ($j = 1; $j + 1 < count($subfields); $j += 2) $values[$subfields[$j]][] = $subfields[$j + 1];
                if (count($values['31'] ?? array()) === 1) $rec->contragentIban = self::iban($values['31'][0]);
                foreach (array('32', '33') as $key) {
                    if (count($values[$key] ?? array()) === 1) $rec->contragentName .= trim($values[$key][0]) . ' ';
                }
                $rec->contragentName = trim($rec->contragentName);
                $parsed->checkLegacy[$i] = $rec->contragentIban !== '' && !ctype_digit(substr($details, 0, 3));
            }
            $rec->reason = trim($details . "\n" . ($parts[1] ?? '') . "\n" . (self::hasReference($refs[0]) ? $refs[0] : ''));
            if ($reversal) $rec->reason = 'Сторно: ' . $rec->reason;
            $reference = trim($refs[1] ?? '');
            if (self::hasReference($reference)) {
                $rec->bankReference = 'MT940:' . $parsed->currency . ':' . ($reversal ? 'R:' : 'N:') . $reference;
                $key = $rec->type . ':' . $rec->bankReference;
                if (isset($references[$key])) {
                    $error = 'една банкова референция е използвана за няколко операции';
                    return null;
                }
                $references[$key] = true;
            }
            $parsed->recs[] = $rec;
        }
        if ($opening['amount'] + $sums['C'] - $sums['D'] !== $closing['amount']) {
            $error = 'начално салдо плюс постъпления минус плащания не е равно на крайното салдо';
            return null;
        }
        foreach (array('C', 'D') as $direction) {
            if (isset($single['90' . $direction])) {
                if (!preg_match('/^([0-9]{1,5})([A-Z]{3})([0-9]+,[0-9]*)$/D', $single['90' . $direction], $total)
                    || (int) $total[1] !== $counts[$direction] || $total[2] !== $parsed->currency || self::amount($total[3]) !== $sums[$direction]) {
                    $error = 'контролната сума или броят операции в 90' . $direction . ' не съвпада';
                    return null;
                }
            }
        }

        return $parsed;
    }


    private static function date($text)
    {
        if (!preg_match('/^[0-9]{6}$/D', $text)) return null;
        $year = (int) substr($text, 0, 2);
        $date = ($year >= 70 ? '19' : '20') . substr($text, 0, 2) . '-' . substr($text, 2, 2) . '-' . substr($text, 4, 2);

        return payment_XmlParser::isValidDate($date) ? $date : null;
    }


    /**
     * Стотинки без float закръгляне; регистърът работи с два десетични знака.
     */
    private static function amount($text)
    {
        if (strlen($text) > 15 || !preg_match('/^([0-9]+),([0-9]*)$/D', $text, $parts)) return null;
        $whole = ltrim($parts[1], '0');
        $fraction = rtrim($parts[2], '0');
        if (strlen($whole) > 13 || strlen($fraction) > 2) return null;

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }


    private static function balance($text)
    {
        if (!preg_match('/^([CD])([0-9]{6})([A-Z]{3})([0-9]+,[0-9]*)$/D', $text, $parts)) return null;
        $date = self::date($parts[2]);
        $amount = self::amount($parts[4]);
        if ($date === null || $amount === null) return null;

        return array('date' => $date, 'currency' => $parts[3], 'amount' => $amount * ($parts[1] === 'D' ? -1 : 1));
    }


    private static function iban($text)
    {
        $text = strtoupper(preg_replace('/\s+/', '', $text));
        // 25 може да съдържа BIC/IBAN; не отгатваме сметка по BBAN.
        $parts = explode('/', $text);
        $text = end($parts);

        return preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/D', $text) ? $text : '';
    }


    private static function hasReference($text)
    {
        $value = strtoupper(preg_replace('/[^a-z0-9]/i', '', $text));

        return $value !== '' && !preg_match('/^0+$/D', $value)
            && !in_array($value, array('NONREF', 'NOTPROVIDED', 'NOTAVAILABLE', 'UNKNOWN', 'NA', 'NONE'), true);
    }
}
