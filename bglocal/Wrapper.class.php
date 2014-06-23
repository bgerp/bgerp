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
class bglocal_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {        
       
        $this->TAB('bglocal_Mvr', 'МВР', 'admin, common');
        $this->TAB('bglocal_DistrictCourts', 'Съдилища', 'admin, common');
        $this->TAB('bglocal_Banks', 'Банки');
        $this->TAB('bglocal_NKPD', 'НКПД');
        $this->TAB('bglocal_NKID', 'НКИД');
        
        $this->title = 'Данни за България';
        
    }
}