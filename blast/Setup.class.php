<?php

/**
 *  class blast_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  мениджъри свързани с 'blast'
 *
 */
class blast_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'blast_Lists';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    

    /**
     * Описание на модула
     */
    var $info = "Масово разпращане на емейл-и, sms-и, писма, ...";
   
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'blast_Lists',
            'blast_ListDetails',
        	'blast_Emails'
        );
        
        // Роля ръководител на организация 
        // Достъпни са му всички папки и документите в тях
        $role = 'blast';
        $html .= core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
 
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
         
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(1, 'Визитник', 'Разпращане', 'blast_Lists', 'default', "user");
         
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