<?php


/**
 *
 *
 * @category  bgerp
 * @package   googlecharts
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class googlecharts_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Изчертаване на графики във вид на торти, линии и барове. Поддържат се и серии за сравнения';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'googlecharts_Adapter'
    );
}
