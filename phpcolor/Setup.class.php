<?php

/** Версия на PHPColors със запазен досегашен default. */
defIfNot('PHPCOLOR_VERSION', '0.3');

/**
 * Клас 'phpcolor_Setup'
 *
 * Исталиране/деинсталиране на phpcolor
 *
 * @category  bgerp
 * @package   phpcolor
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class phpcolor_Setup extends core_ProtoSetup
{
    /**
     * Описание на модула
     */
    public $info = 'Библиотека за работа с цветове';


    /** Версията се избира за конкретната инсталация. */
    public $configDescription = array(
        'PHPCOLOR_VERSION' => array('enum(0.3,1.0.4)', 'mandatory,caption=PHPColors->Версия'),
    );
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
}
