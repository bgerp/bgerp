<?php


/**
 * Unit тестове за държавата по IP адрес
 *
 * @category  ef
 * @package   drdata
 *
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 */
class drdata_tests_IpToCountry extends unit_Class
{
    /**
     * Едно четене по индекса дава същото като пълното търсене на диапазон, и за адрес извън таблицата
     */
    public static function test_SameAsRangeSearch()
    {
        $stopCaching = core_Cache::$stopCaching;
        core_Cache::$stopCaching = true;
        try {
            foreach (array('8.8.8.8', '1.0.0.1', '1.0.1.1', '85.130.1.1', '224.0.0.5', '255.255.255.254') as $ip) {
                ut::expectEqual(drdata_IpToCountry::get($ip), self::searchRange($ip));
            }
        } finally {
            core_Cache::$stopCaching = $stopCaching;
        }
    }


    /**
     * Локалните и частните адреси ги няма в таблицата
     */
    public static function test_LocalAddresses()
    {
        foreach (array('127.0.0.1', '10.1.2.3', '192.168.1.1', '172.16.5.5', '169.254.1.1') as $ip) {
            ut::expectEqual(drdata_IpToCountry::get($ip), '??');
            ut::expectEqual(self::searchRange($ip), '??');
        }
    }


    /**
     * Предишната заявка: диапазонът с най-голямо начало, който съдържа адреса
     */
    private static function searchRange($ip)
    {
        $query = drdata_IpToCountry::getQuery();
        $ipInt = sprintf('%u', ip2long($ip));
        $query->where("#minIp <= {$ipInt} AND #maxIp >= {$ipInt}");
        $query->orderBy('minIp=DESC');
        $query->limit(1);
        $rec = $query->fetch();

        return ($rec && isset($rec->country2)) ? $rec->country2 : '??';
    }
}
