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
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'workpreff_WorkPreff';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "";


/**
* Списък с мениджърите, които съдържа пакета
*/
    var $managers = array(

        'workpreff_WorkPreff',
        'workpreff_FormCv',
    );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'ceo.hr';
    
        
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
              

        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}