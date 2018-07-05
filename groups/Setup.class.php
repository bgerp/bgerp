<?php


/**
 * Клас 'groups_Setup'
 *
 * Исталиране/деинсталиране на Apachetika
 *
 *
 * @category  bgerp
 * @package   groups
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class groups_Setup extends core_ProtoSetup
{

    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Разширяване на единичния изглед на мастъри с данни от мениджъри';
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
}
