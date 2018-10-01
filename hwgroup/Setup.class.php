<?php


/**
 * class hwgroup_Setup
 *
 * Драйвери за IP сензор на HW Group
 *
 * @category  bgerp
 * @package   hwgroup
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see       http://www.hw-group.com/products_ip_monitoring_en.html
 */
class hwgroup_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    public $depends = '';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Драйвери за мониторинг на IP устройства от HW Group';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Добавяме наличните драйвери
        $drivers = array(
            'hwgroup_HWgSTE',
        );
        
        foreach ($drivers as $drvClass) {
            $html .= core_Classes::add($drvClass);
        }
        
        return $html;
    }
}
