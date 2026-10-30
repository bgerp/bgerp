<?php

namespace SPFLib\DNS {
    /** Изолира само DNS входа; използва реалния StandardResolver и SPF checker. */
    function dns_get_record($domain, $type)
    {
        return \spflib_tests_Fixture::resolve($domain, $type);
    }
}

namespace {
    /**
     * Синтетични SPF сценарии без външен DNS и без промени в библиотеката.
     *
     * @category bgerp
     * @package spflib
     * @author Yusein Yuseinov <y.yuseinov@gmail.com>
     * @copyright 2026 Experta EOOD
     * @license GPL 3
     * @since v 0.1
     */
    class spflib_tests_Fixture
    {
        public static $txt = array();
        public static $addresses = array();
        public static $mx = array();
        public static $lookups = array();


        /** Връща формата на dns_get_record; непредвиден домейн прекъсва теста. */
        public static function resolve($domain, $type)
        {
            self::$lookups[] = array($domain, $type);
            if ($domain === 'dns-error.example') return false;
            $records = array();
            if ($type === DNS_TXT && array_key_exists($domain, self::$txt)) {
                foreach (self::$txt[$domain] as $value) $records[] = array('type' => 'TXT', 'txt' => $value);
            } elseif ($type === (DNS_A | DNS_AAAA) && array_key_exists($domain, self::$addresses)) {
                foreach (self::$addresses[$domain] as $ip) {
                    $isV6 = strpos($ip, ':') !== false;
                    $records[] = array('type' => $isV6 ? 'AAAA' : 'A', $isV6 ? 'ipv6' : 'ip' => $ip);
                }
            } elseif ($type === DNS_MX && array_key_exists($domain, self::$mx)) {
                foreach (self::$mx[$domain] as $target) $records[] = array('type' => 'MX', 'target' => $target);
            } else {
                throw new \LogicException('Unexpected DNS lookup: ' . $domain . '/' . $type);
            }

            return $records;
        }


        /** Проверява реалния bgERP адаптер и мониторинга върху еднакви DNS данни. */
        public static function run($check)
        {
            self::$lookups = array();
            self::$txt = array(
                'ip.example' => array('unrelated TXT', 'v=spf1 ip4:192.0.2.0/24 ip6:2001:db8::/32 -all'),
                'soft.example' => array('v=spf1 ~all'),
                'neutral.example' => array('v=spf1 ?all'),
                'empty.example' => array(),
                'bad.example' => array('v=spf1 unknown:bad -all'),
                'multiple.example' => array('v=spf1 -all', 'v=spf1 +all'),
                'include.example' => array('v=spf1 include:ip.example -all'),
                'redirect.example' => array('v=spf1 redirect=ip.example'),
                'a.example' => array('v=spf1 a -all'),
                'mx.example' => array('v=spf1 mx -all'),
                'macro.example' => array('v=spf1 exists:%{i}.lookup.example -all'),
                'loop.example' => array('v=spf1 include:loop.example -all'),
                'void.example' => array('v=spf1 a:empty1.example a:empty2.example a:empty3.example -all'),
                'xn--e1afmkfd.example' => array('v=spf1 ip4:192.0.2.0/24 -all'),
            );
            self::$addresses = array(
                'a.example' => array('192.0.2.7', '2001:db8::7'),
                'mail.example' => array('192.0.2.7'),
                '192.0.2.7.lookup.example' => array('127.0.0.1'),
                'empty1.example' => array(), 'empty2.example' => array(), 'empty3.example' => array(),
            );
            self::$mx = array('mx.example' => array('mail.example'));
            $cases = array(
                array('192.0.2.7', 'ip.example', '', 'pass'),
                array('198.51.100.7', 'ip.example', '', 'fail'),
                array('2001:db8::7', 'ip.example', '', 'pass'),
                array('2001:db9::7', 'ip.example', '', 'fail'),
                array('192.0.2.7', 'soft.example', '', 'softfail'),
                array('192.0.2.7', 'neutral.example', '', 'neutral'),
                array('192.0.2.7', 'empty.example', '', 'none'),
                array('192.0.2.7', 'dns-error.example', '', 'temperror'),
                array('192.0.2.7', 'bad.example', '', 'permerror'),
                array('192.0.2.7', 'multiple.example', '', 'permerror'),
                array('192.0.2.7', 'include.example', '', 'pass'),
                array('198.51.100.7', 'include.example', '', 'fail'),
                array('192.0.2.7', 'redirect.example', '', 'pass'),
                array('192.0.2.7', 'a.example', '', 'pass'),
                array('2001:db8::7', 'a.example', '', 'pass'),
                array('198.51.100.7', 'a.example', '', 'fail'),
                array('192.0.2.7', 'mx.example', '', 'pass'),
                array('192.0.2.7', 'macro.example', '', 'pass'),
                array('192.0.2.7', 'loop.example', '', 'permerror'),
                array('192.0.2.7', 'void.example', '', 'permerror'),
                array('192.0.2.7', 'пример.example', '', 'pass'),
                array('192.0.2.7', 'ip.example', 'sender@soft.example', 'pass'),
                array('198.51.100.7', 'soft.example', 'sender@ip.example', 'fail'),
                array('', '', '', 'none'),
                array('192.0.2.7', '', '', 'none'),
            );
            foreach ($cases as $case) {
                $result = spflib_Checker::check($case[0], $case[1], $case[2]);
                $check($result instanceof \SPFLib\Check\Result && $result->getCode() === $case[3],
                    implode(' / ', $case) . ' got ' . $result->getCode());
            }
            foreach (array('ip.example' => 1, 'empty.example' => 0, 'soft.example' => -1,
                'bad.example' => 'permerror', 'dns-error.example' => 'temperror', 'neutral.example' => 'neutral') as $domain => $expected) {
                $check(sens2_ServMon::getSPF('192.0.2.7_' . $domain) === $expected, 'ServMon ' . $domain);
            }
            $check(sens2_ServMon::getSPF('198.51.100.7_ip.example') === -2, 'ServMon fail');
            $check(sens2_ServMon::getSPF('') === 0, 'ServMon empty setting');
            $invalid = false;
            try {
                spflib_Checker::check('invalid-IP', 'ip.example');
            } catch (\SPFLib\Exception\InvalidIPAddressException $e) {
                $invalid = true;
            }
            $check($invalid, 'Invalid IP retains the library exception');
            $check(in_array(array('xn--e1afmkfd.example', DNS_TXT), self::$lookups, true), 'IDNA conversion before DNS');
            $decoder = new \SPFLib\Decoder();
            $record = $decoder->getRecordFromTXT('v=spf1 ip4:192.0.2.0/24 -all');
            $check((string) $record === 'v=spf1 ip4:192.0.2.0/24 -all', 'Decode/encode SPF record');
            if (method_exists($decoder, 'getTXTRecordFromDomain')) {
                $check($decoder->getTXTRecordFromDomain('ip.example') === self::$txt['ip.example'][1], 'Raw SPF TXT skips unrelated records');
                $check($decoder->getTXTRecordFromDomain('empty.example') === '', 'Raw SPF TXT missing');
                $multiple = false;
                try {
                    $decoder->getTXTRecordFromDomain('multiple.example');
                } catch (\SPFLib\Exception\MultipleSPFRecordsException $e) {
                    $multiple = true;
                }
                $check($multiple, 'Raw SPF TXT rejects multiple SPF records');
            }
        }
    }
}
