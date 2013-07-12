<?php 


/**
 * Мениджър за записване на изпратените факсове
 *
 * @category  bgerp
 * @package   callcenter
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class callcenter_Fax extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Изпратени факсове';
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'user';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'callcenter_Wrapper, plg_RowTools, plg_Printing, plg_Search, plg_Sorting, plg_RefreshRows, plg_Created';
    
    
    /**
     * 
     */
    var $refreshRowsTime = 15000;
    
    
    /**
     * Поле за търсене
     */
    var $searchFields = 'faxNum, createdOn';
    
    
    /**
     * 
     */
    var $listFields = 'id, faxNum, contragent, cid, createdOn=Изпратено->От, createdBy=Изпратено->На';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('faxNum', 'drdata_PhoneType', 'caption=Контрагент->Номер');
        $this->FNC('contragent', 'varchar', 'caption=Контрагент->Име');
        $this->FLD('cid', 'key(mvc=doc_Containers)', 'caption=Документ');
        $this->FLD('classId', 'key(mvc=core_Classes, select=name)', 'caption=Визитка->Клас');
        $this->FLD('contragentId', 'int', 'caption=Визитка->Номер');
    }
    
    
    /**
     * Записва изпращането на факса
     * 
     * @param integer $faxNum - Факс номера, до който се праща
     * @param integer $cid - id на документа
     */
    static function saveSend($faxNum, $cid)
    {
        // Вземаме записа за номера
        $extRec = callcenter_Numbers::getRecForNum($faxNum);
        
        // Създаваме записа
        $rec = new stdClass();
        $rec->classId = $extRec->classId;
        $rec->contragentId = $extRec->contragentId;
        $rec->faxNum = callcenter_Numbers::getNumberStr($faxNum);
        $rec->cid = $cid;
        
        static::save($rec);
    }
    

	/**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
         // Ако има клас   
         if ($rec->classId) {
             
            // Инстанция на класа
            $class = cls::get($rec->classId);
            
            // Ако класа е профил
            if (strtolower($class->className) == 'crm_profiles') {
                
                // Вземаме линк към профила
                $card = crm_Profiles::createLink($rec->contragentId);
            } else {
                
                // Вземаме записите за съответния клас
                $cardRec = $class->fetch($rec->contragentId);
                
                // Ако имаме права за сингъл
                if ($class->haveRightFor('single', $cardRec)) {
                    
                    // Вземаме линка към сингъла
                    $card = ht::createLink($cardRec->name, array($class, 'single', $rec->contragentId)) ;
                } else {
                    
                    // Ако нямаме права
                    
                    // Вземаме линк към профила на отговорника
                    $inChargeLink = crm_Profiles::createLink($cardRec->inCharge);
                    
                    // Добавяме линка
                    $card = $class->getVerbal($cardRec, 'name') . " - {$inChargeLink}";
                }
            }
            
            // Добавяме линка към контрагента
            $row->contragent = $card;
        }
        
        // Ако има потребител
        if ($rec->createdBy) {
            
            // Създаваме линк към профила му
            $row->createdBy = crm_Profiles::createLink($rec->createdBy);
        }
        
        // Ако има id на документ
        if ($rec->cid) {
            
            // Използваме него
            $row->cid = doc_Containers::getLinkForSingle($rec->cid);
        }
    }
    
    
    /**
     * 
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('usersSearch', 'users(rolesForAll=ceo, rolesForTeams=ceo|manager)', 'caption=Потребител,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        
        // В хоризонтален вид
        $data->listFilter->view = 'horizontal';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search,usersSearch';
        
        $data->listFilter->input('search,usersSearch', 'silent');
    }

    
    /**
     * 
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        // Ако не е избран потребител по подразбиране
        if(!$data->listFilter->rec->usersSearch) {
            
            // Да е текущия
            $data->listFilter->rec->usersSearch = '|' . core_Users::getCurrent() . '|';
        }
        
        // Сортиране на записите по num
        $data->query->orderBy('createdOn', 'DESC');
        
        // Ако има филтър
        if($filter = $data->listFilter->rec) {
            
            // Ако филтъра е по потребители
            if($filter->usersSearch) {
                
                $userSearchArr = type_Keylist::toArray($filter->usersSearch);
                // Показваме само на потребителя
    			$data->query->orWhereArr('createdBy', $userSearchArr);
    			
  			    // Ако се търси по всички и има права admin или ceo
    			if ((strpos($filter->usersSearch, '|-1|') !== FALSE) && (haveRole('ceo, admin'))) {
    			    
    			    // Показваме и празните резултати 
                    $data->query->orWhere("#createdBy IS NULL");
                }
    		}
        }
    }
}