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
    public function description()
    {
        $this->TAB('drdata_Countries', 'Страни', 'admin');
        $this->TAB('drdata_CountryGroups', 'Групи', 'admin');
        $this->TAB('drdata_Languages', 'Езици', 'admin');
        $this->TAB('drdata_Domains', 'Домейни', 'debug');
        $this->TAB('drdata_IpToCountry', 'IP-to-Country', 'debug');
        $this->TAB('drdata_DialCodes', 'Тел. кодове', 'admin');
        $this->TAB('drdata_Vats', 'ЗДДС №', 'admin');
        $this->TAB('drdata_PhoneCache', 'T. Кеш', 'debug');
        
        $this->title = 'Адресни данни';
        $this->title = 'Система « Адресни данни';
        Mode::set('menuPage', 'Адресни данни:Система');
    }
}
