<?php


/**
 * class tangra_Setup
 *
 * Инсталиране/Деинсталиране на драйвери за устройствата на Тангра - София
 *
 * @category  bgerp
 * @package   tangra
 *
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tangra_Setup extends core_ProtoSetup
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
    public $info = 'Драйвери за контролери на Тангра';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Добавяме наличните драйвери
        $drivers = array(
            'tangra_AHU01',
        );
        
        foreach ($drivers as $drvClass) {
            $html .= core_Classes::add($drvClass);
        }
        
        return $html;
    }
}
