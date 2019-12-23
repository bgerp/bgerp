<?php


/**
 * Версията на програмата
 */
defIfNot('APACHE_TIKA_VERSION', '1.7');


/**
 * Клас 'apachetika_Setup'
 *
 * Исталиране/деинсталиране на Apachetika
 *
 *
 * @category  bgerp
 * @package   apachetika
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class apachetika_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Разпознаване и извличане на метаданни и текст от различни типове';
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'APACHE_TIKA_VERSION' => array('enum(1.5, 1.7)', 'caption=Версия на програмата'),
    
    );
}
