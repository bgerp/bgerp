<?php


defIfNot('IBAN_CODE_VERSION', '2.6.5');


/**
 * Клас 'iban_Setup'
 *
 * Исталиране/деинсталиране на Apachetika
 *
 *
 * @category  bgerp
 * @package   iban
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class iban_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Пакет за работа с IBAN полета';
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'IBAN_CODE_VERSION' => array('enum(1.1.2, 1.4.7, 2.5.6, 2.6.5)', 'caption=Версия на IBAN модула->Версия'),
    );
}
