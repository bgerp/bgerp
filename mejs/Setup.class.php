<?php


/**
 * Версията на продукта
 */
defIfNot('MEJS_VERSION', '2.11.0.0');


/**
 * Клас 'mejs_Setup'
 *
 * Исталиране/деинсталиране на Apachetika
 *
 *
 * @category  bgerp
 * @package   mejs
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class mejs_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Пакет за създаване на плейър за изпълнение на видео и аудио';
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'MEJS_VERSION' => array('enum(2.11.0.0, 2.20.0)', 'caption=Версия на MEJS'),
    );
}
