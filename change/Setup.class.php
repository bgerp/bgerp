<?php


/**
 * Колко време след последната редакция да се записва запис в историята->Време
 */
defIfNot('CHANGE_LOG_VERSION_AFTER_LAST', '7200');


/**
 * Клас 'change_Setup' -
 *
 * @category  vendors
 * @package   chnage
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class change_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'change_Log';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Съхранение на стари версии на документи и обекти';


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'CHANGE_LOG_VERSION_AFTER_LAST' => array('time', 'caption=Колко време след последната редакция да се записва запис в историята->Време'),
    );


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'change_Log',
        'change_History'
    );
}
