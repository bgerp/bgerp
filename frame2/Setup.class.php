<?php


/**
 * Как да е форматирана датата
 */
defIfNot('FRAME2_CLOSE_LAST_SEEN_BEFORE_MONTHS', '4');


/**
 * Как да е форматирана датата
 */
defIfNot('FRAME2_MAX_VERSION_HISTORT_COUNT', '10');


/**
 * class frame2_Setup
 *
 * Инсталиране/Деинсталиране на пакета frame2
 *
 *
 * @category  bgerp
 * @package   frame2
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class frame2_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    public $depends = '';
    
    
    /**
     * Начален контролер на пакета за връзката в core_Packs
     */
    public $startCtr = 'frame2_Reports';
    
    
    /**
     * Начален екшън на пакета за връзката в core_Packs
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Динамични справки и отчети';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'frame2_Reports',
        'frame2_ReportVersions',
        'frame2_AllReports',
        'migrate::migrateStates',
        'migrate::keyToKeylist1',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'report';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'FRAME2_CLOSE_LAST_SEEN_BEFORE_MONTHS' => array('int', 'caption=Затваряне на последно видяни справки преди->Месеца'),
        'FRAME2_MAX_VERSION_HISTORT_COUNT' => array('int', 'caption=Колко версии да се пазят на справките->Брой'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'frame2_CsvExport';
    
    
    /**
     * Миграция за състоянията
     */
    public function migrateStates()
    {
        $Frames = cls::get('frame2_Reports');
        $query = frame2_Reports::getQuery();
        $query->where("#brState = 'closed' AND #state = 'closed'");
        while($rec = $query->fetch()){
            $rec->brState = 'active';
            $Frames->save_($rec, 'brState');
        }
    }
    
    /**
     * Миграция: в спрвките "Артикули наличности и лимити"
     * промяна на полето storeId от key на keylist
     */
    public function keyToKeylist1()
    {
        $reportClassId =store_reports_ProductAvailableQuantity::getClassId();
        if (!$reportClassId)return;
        
        $Frames = cls::get('frame2_Reports');
        
        $reportQuery=(frame2_Reports::getQuery());
        
        $reportQuery->where("#driverClass = $reportClassId");
        
        while ($fRec = $reportQuery->fetch()){
            
            if (is_null($fRec->driverRec['storeId']) || keylist::isKeylist($fRec->driverRec['storeId']))continue;
            
            $fRec->driverRec['storeId'] ='|'.$fRec->driverRec['storeId'].'|';
            $fRec->storeId =$fRec->driverRec['storeId'];
            
            
            $Frames->save($fRec);
            
        }
       
    }
}
