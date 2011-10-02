<?php
/**
 * Начално установяване на пакета `accda`
 *
 * @category   BGERP
 * @package    accda
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 *
 */
class accda_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'accda_Da';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Дълготрайни активи";

    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'accda_Da',
            'accda_Groups',
            'accda_Documents',
        );
        
        // Роля за power-user на този модул
        $role = 'accda';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }

        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(2, 'Счетоводство', 'ДА', 'accda_Da', 'default', "{$role}, admin");
        
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);

        return $res;
    }
}