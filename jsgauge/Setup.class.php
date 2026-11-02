<?php


/**
 * Път до външния файл
 */
defIfNot('GAUGE_PATH', 'jsgauge/0.4.1');


/**
 * Клас 'jsgauge_Gauge'
 *
 * Клас, който служи за създаване на Gauge.
 * Съдържа необходимите функции за използването на
 * Gauge
 *
 *
 * @category  bgerp
 * @package   jsgauge
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link      http://code.google.com/p/jsgauge/
 */
class jsgauge_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Пакет за визуализация на gauge-индикатори';
    
    
    /**
     * Версията се избира локално, без промяна на стария път по подразбиране.
     */
    public $configDescription = array(
        'GAUGE_PATH' => array('enum(jsgauge/0.4.1=0.4.1,jsgauge/0.4.6=0.4.6)', 'mandatory,caption=Библиотека->Версия'),
    );
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
}
