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
        'migrate::updatefindealdocuments',
        'migrate::migrateClosedWith'
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('pettyCashReport'),
        array('findeals', 'pettyCashReport,seePrice'),
        array('findealsMaster', 'findeals'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(2.3, 'Финанси', 'Сделки', 'findeals_Deals', 'default', 'findeals, ceo, acc'),
    );
    
    
    /**
     * Обновява документите за прехвърляне на взимане/задължение
     */
    function updatefindealdocuments()
    {
        $toSave1 = $toSave2 = array();
        $query = findeals_CreditDocuments::getQuery();
        $query->where("#dealId IS NOT NULL");
        $query->show("dealId");
        while($rec = $query->fetch()){
            if($rec->dealId = findeals_Deals::fetchField($rec->dealId, 'containerId')){
                $toSave1[] = $rec;
            }
        }
        
        if(count($toSave1)){
            cls::get('findeals_CreditDocuments')->saveArray($toSave1, 'id,dealId');
        }
        
        $query1 = findeals_DebitDocuments::getQuery();
        $query1->where("#dealId IS NOT NULL");
        $query1->show("dealId");
        while($rec = $query1->fetch()){
            if($rec->dealId = findeals_Deals::fetchField($rec->dealId, 'containerId')){
                $toSave2[] = $rec;
            }
        }
        
        if(count($toSave2)){
            cls::get('findeals_DebitDocuments')->saveArray($toSave2, 'id,dealId');
        }
    }
    
    
    /**
     * Обновява кеш полето за коя сделка с коя е приключена
     */
    function migrateClosedWith()
    {
        cls::get('deals_Setup')->updateClosedWith('findeals_Deals', 'findeals_ClosedDeals');
    }
}
