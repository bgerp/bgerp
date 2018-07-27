<?php


/**
 * Исторически данни за одит и обратна връзка
 *
 *
 * @category  bgerp
 * @package   log
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doclog_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'doclog_Documents';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Хронология на действията с документите';
    
    
    public $managers = array(
        'doclog_Documents',
        'doclog_Files',
        'doclog_Used',
    );
}
