<?php



/**
 * Клас 'drdata_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Core'
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class drdata_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {        
        $this->TAB('drdata_Countries', 'Страни');
        $this->TAB('drdata_Domains', 'Домейни', 'admin');
        $this->TAB('drdata_IpToCountry', 'IP-to-Country');
        $this->TAB('drdata_DialCodes', 'Тел. кодове');
        $this->TAB('drdata_Vats', 'ЗДДС №');
        $this->TAB('drdata_Mvr', 'МВР', 'admin, common');
        $this->TAB('drdata_DistrictCourts', 'Съдилища', 'admin, common');
        
        $this->title = 'Адресни данни';
    }
}