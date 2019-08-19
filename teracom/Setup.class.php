<?php


/**
 * class teracom_Setup
 *
 * Инсталиране/Деинсталиране на драйвери за устройствата на Тераком ООД - Русе
 *
 * @category  bgerp
 * @package   teracom
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class teracom_Setup extends core_ProtoSetup
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
    public $info = 'Драйвери за контролери на Тераком ООД';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Добавяме наличните драйвери
        $drivers = array(
            'teracom_TCW181BCM',
            'teracom_TCW122BCM',
            'teracom_TCW122B',
            'teracom_TCW121',
        );
        
        foreach ($drivers as $drvClass) {
            $html .= core_Classes::add($drvClass);
        }
        
        return $html;
    }
}
