<?php


/**
 * class findeals_Setup
 *
 * Инсталиране/Деинсталиране на
 * финансови сделки
 *
 *
 * @category  bgerp
 * @package   findeals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class findeals_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'findeals_Deals';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'acc=0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Финансови операции';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'findeals_Deals',
        'findeals_AdvanceDeals',
        'findeals_DebitDocuments',
        'findeals_CreditDocuments',
        'findeals_ClosedDeals',
        'findeals_AdvanceReports',
        'findeals_AdvanceReportDetails',
        'migrate::oldDeals2614',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('pettyCashReport', 'invoicerFindeal'),
        array('findeals', 'pettyCashReport,seePrice'),
        array('findealsMaster', 'findeals'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(2.1, 'Финанси', 'Сделки', 'findeals_Deals', 'default', 'findeals, ceo, acc'),
    );


    /**
     * Миграция на финансовите сделки без вальор
     */
    public function oldDeals2614()
    {
        $valiorColName = str::phpToMysqlName('valior');
        $activatedOnColName = str::phpToMysqlName('activatedOn');
        $createdOnColName = str::phpToMysqlName('createdOn');
        $stateColName = str::phpToMysqlName('state');

        foreach (array('findeals_Deals', 'findeals_AdvanceDeals') as $Cls){
            $Cls = cls::get($Cls);
            $query = "UPDATE {$Cls->dbTableName} SET {$valiorColName} = COALESCE({$activatedOnColName}, {$createdOnColName}) WHERE {$valiorColName} IS NULL AND {$stateColName} IN ('active', 'closed')";
            $Cls->db->query($query);
        }
    }
}
