<?php


/**
 * Класа, който ще се използва за конвертиране
 */
defIfNot('LEGALACT_DOCS_ROOT', 'c:/test/docs/');


 
/**
 *
 * @category  vendors
 * @package   legalact
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2012 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class legalact_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Актове на българските съдилища";
    

    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'legalact_Acts';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    

    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    
        // Кой клас да се използва за конвертиране на офис документи
        'LEGALACT_DOCS_ROOT' => array ('varchar', 'mandatory, caption=Кой клас да се използва за конвертиране на офис документи->Клас'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var  $managers = array(
            'legalact_Acts',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'legal';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.81, 'Система', 'Съдилища', 'legalact_Acts', 'default', 'ceo,admin,legal'),
        );

    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}