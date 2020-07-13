<?php


/**
 * Път до външния файл
 */
defIfNot('CANVASGAUGE_VERSION', '2.1.3');


/**
 * Клас, който служи за създаване на Gauge с canvas
 *
 * @category  bgerp
 * @package   canvasgauge
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link      https://canvas-gauges.com/
 */
class canvasgauge_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Пакет за визуализация на gauge-индикатори с canvas';
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;

    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'CANVASGAUGE_VERSION' => array('enum(2.1.4, 2.1.7)', 'caption=Версия на canvasgauge->Версия')
    );
}
