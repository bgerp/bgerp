<?php

/**
 * Клас ' workpreff_Setup'
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със подбора на персонал
 *
 * @category  bgerp
 * @package   workpreff
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class workpreff_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'workpreff_WorkPreff';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = '';


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(

        'workpreff_WorkPreff',
        'workpreff_FormCv',
        'workpreff_WorkPreffDetails',
    );
    

    /**
     * Роли за достъп до модула
     */
//    var $roles = 'ceo,hr';

    public $depends = 'hr=0.1';

//
//    /**
//     * Инсталиране на пакета
//     */
//    function install()
//    {
//        $html = parent::install();
//
//
//
//        return $html;
//    }
//
//
//    /**
//     * Де-инсталиране на пакета
//     */
//    function deinstall()
//    {
//        // Изтриване на пакета от менюто
//        $res = bgerp_Menu::remove($this);
//
//        return $res;
//    }
}
