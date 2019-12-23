<?php


/**
 * Инсталатор за пакета eastron
 *
 *
 * @category  bgerp
 * @package   eastron
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eastron_Setup extends core_ProtoSetup
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
    public $info = 'Драйвери за устройства на фирма Eastron Group';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $defClasses = array(
        'eastron_SDM120',
        'eastron_SDM630',
    );
}
