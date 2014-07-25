<?php


/**
 * Инсталиране на модул 'price'
 *
 * Ценови политики на фирмата
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class price_Setup extends core_ProtoSetup
{
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'price_Lists';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Ценови политики, ценоразписи, разходни норми";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'price_Groups',
            'price_GroupOfProducts',
            'price_Lists',
            'price_ListToCustomers',
            'price_ListRules',
            'migrate::priceHistoryTruncate',
            'price_History',
        	'price_ConsumptionNorms',
        	'price_ConsumptionNormDetails',
        	'price_ConsumptionNormGroups',
        	'price_ListDocs',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'price';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.44, 'Артикули', 'Ценообразуване', 'price_Lists', 'default', "price, ceo"),
        );
    
    
    /**
     * Път до css файла
     */
    var $commonCSS = 'price/tpl/NormStyles.css';
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
    

    /**
     * Миграция, която изтрива таблицата price_History
     * за да може да се постави уникален индекс
     */
    function priceHistoryTruncate()
    {
        $history = cls::get('price_History');
        $history->truncate();
    }
}
