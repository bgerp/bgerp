<?php

/**
 * Синтетични проверки на избраната IBAN библиотека и bgERP адаптера.
 *
 * @category tests
 * @package iban
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
class iban_tests_Cases
{
    /**
     * Проверява публичния адаптер без запис или промяна на пакетни настройки.
     */
    public static function run($className = 'iban_Type')
    {
        $type = cls::get($className);
        $snapshot = array();
        $checks = 0;
        $knownIssues = array();
        $bg = 'BG80BNBG96611020345678';
        $valid = array($bg, 'bg80bnbg96611020345678', ' BG80 BNBG 9661 1020 3456 78 ',
            'IBAN BG80-BNBG-9661-1020-3456-78', 'DE89370400440532013000',
            'GB29NWBK60161331926819', 'FR1420041010050500013M02606');
        foreach ($valid as $value) {
            $result = $type->isValid($value);
            expect(empty($result['error']), 'Valid IBAN rejected: ' . $value);
            $canonical = $type->canonize($value);
            expect(verify_iban($canonical), 'Canonical IBAN rejected');
            $parts = $className::getParts($value);
            expect($className::getCountryPart($value) === substr($canonical, 0, 2), 'Country changed');
            $snapshot[$value] = array($result, $canonical, $parts, $type->toVerbal($value));
            $checks += 3;
        }
        expect($type->canonize($valid[1]) === $bg && $type->canonize($valid[2]) === $bg, 'Normalization changed');
        expect($className::getBankPart($bg) === 'BNBG', 'Bulgarian bank code changed');
        $parts = $className::getParts($bg);
        expect(($parts['branch'] ?? null) === '9661', 'Bulgarian branch changed');
        $checks += 3;
        foreach (array('BG81BNBG96611020345678', 'BG80BNBG9661102034567',
            $bg . '0', 'ZZ80BNBG96611020345678', 'БГ80БНБГ96611020345678', 'not-an-iban', '0') as $value) {
            $result = $type->isValid($value);
            expect(!empty($result['error']), 'Invalid IBAN accepted: ' . $value);
            $snapshot[$value] = $result;
            $checks++;
        }
        foreach (array(null, '', '   ') as $value) {
            expect($type->isValid($value) === null, 'Optional empty value rejected');
            $checks++;
        }
        $custom = '#Сметка 00-123';
        expect($type->isValid($custom) === array('value' => $custom), 'Non-IBAN account rejected');
        expect($type->canonize($custom) === '#Сметка00123', 'Non-IBAN normalization changed');
        expect($type->toVerbal($custom) === 'Сметка 00-123', 'Non-IBAN display changed');
        expect(strpos($type->toVerbal('#<script>'), '<script>') === false, 'HTML escaping lost');
        $snapshot['custom'] = array($type->isValid($custom), $type->canonize($custom), $type->toVerbal($custom));
        $checks += 4;

        // Сравняваме и известните невалидни примери от непроменената 1.4.7.
        $countries = iban_countries();
        foreach ($countries as $country) {
            $example = iban_country_get_iban_example($country);
            $result = $type->isValid($example);
            $validExample = verify_iban($example);
            $knownInvalid = iban_Setup::get('CODE_VERSION') === '1.4.7'
                && in_array($country, array('AA', 'BR', 'CR', 'BL', 'MF', 'KZ'), true);
            expect($validExample === !$knownInvalid && empty($result['error']) === !$knownInvalid, 'Registry example: ' . $country);
            if ($knownInvalid) $knownIssues[] = '1.4.7: invalid upstream ' . $country . ' example';
            $snapshot['registry'][$country] = array($example, $result, $validExample ? $className::getParts($example) : null);
            $checks++;
        }
        if (iban_Setup::get('CODE_VERSION') === '5.0.0') {
            foreach (array('OM', 'RU', 'SD', 'VA', 'YE') as $country) {
                expect(in_array($country, $countries, true), 'New country missing: ' . $country);
                $checks++;
            }
            foreach (array('BI' => 27, 'NI' => 28, 'DJ' => 27, 'HN' => 28) as $country => $length) {
                expect((int) iban_country_get_iban_length($country) === $length, 'Updated country length: ' . $country);
                $checks++;
            }
            foreach (array('BI33123412341234', 'NI92BAMC000000000000000003123123') as $obsolete) {
                expect(!empty($type->isValid($obsolete)['error']), 'Obsolete country format accepted');
                $checks++;
            }
        }
        $start = microtime(true);
        for ($i = 0; $i < 5000; $i++) {
            $value = $valid[$i % count($valid)];
            expect(empty($type->isValid($value)['error']), 'Batch validation failed');
            expect(verify_iban($type->canonize($value)), 'Batch normalization failed');
        }
        $checks += 10000;

        return array('checks' => $checks, 'countries' => count($countries), 'knownIssues' => $knownIssues,
            'batchSeconds' => round(microtime(true) - $start, 3), 'snapshot' => $snapshot);
    }
}
