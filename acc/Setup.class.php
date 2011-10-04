<?php

/**
 *  class acc_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  мениджъри свързани със счетоводството
 *
 */
class acc_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'acc_Lists';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    

    /**
     * Описание на модула
     */
    var $info = "Двустранно счетоводство: Настроки, Журнали";
    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'acc_Lists',
            'acc_Items',
            'acc_Periods',
            'acc_Accounts',
            'acc_Limits',
        	'acc_Balances',
            'acc_BalanceDetails',
            'acc_Articles',
            'acc_ArticleDetails',
            'acc_Sales',
            'acc_SaleDetails',
            'acc_Journal',
            'acc_JournalDetails',
            'acc_Invoices',
            'acc_InvoiceDetails'
        );
        
        // Роля за power-user на този модул
        $role = 'acc';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }

        $Menu = cls::get('bgerp_Menu');
        
        $html .= $Menu->addItem(2, 'Счетоводство', 'Книги', 'acc_Balances', 'default', "{$role}, admin");
        $html .= $Menu->addItem(2, 'Счетоводство', 'Настройки', 'acc_Periods', 'default', "{$role}, admin");
        
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