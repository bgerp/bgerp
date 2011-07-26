<?php

/**
 *  class dma_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  мениджъри свързани с DMA
 *
 */
class hr_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'hr_EmployeeContracts';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    
    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'hr_WorkingCycles',
            'hr_Shifts',
            'hr_Departments',
            'hr_Positions',
            'hr_ContractTypes',
            'hr_EmployeeContracts',
        );
        
        // Роля за power-user на този модул
        $role = 'hr';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(2, 'Персонал', 'HR', 'hr_EmployeeContracts', 'default', "{$role}, admin");
        
        return $html;
    }
    
    private function setupRoles()
    {
        $html = '';
        
        $Roles = &cls::get('core_Roles');
        $catRoleId = $Roles->save(
        (object)array(
            'role' => 'cat'
        ),
        NULL, 'ignore'
        );
        
        if ($catRoleId === 0) {
            $html .= '<li>OK, вече съществува роля `cat`</li>';
        } elseif ($catRoleId) {
            $html .= '<li style="color: green;">Добавена роля `cat`</li>';
        } else {
            $html .= '<li style="color: red;">Грешка при добавяне на роля `cat`</li>';
        }
        
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        return "";
    }
}