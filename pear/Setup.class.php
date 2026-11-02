<?php

defIfNot('PEAR_FILE_IMC_VERSION', 'legacy');

/**
 * Клас 'pear_Setup'
 *
 * Настройки за File_IMC
 *
 *
 * @category  bgerp
 * @package   pear
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class pear_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Пакет за работа със vCard и vCalendar файлове';
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;


    /** Досегашното местно копие остава default. */
    public $configDescription = array(
        'PEAR_FILE_IMC_VERSION' => array('enum(legacy=Досегашна,0.6.1)', 'mandatory,caption=File_IMC->Версия'),
    );
}
