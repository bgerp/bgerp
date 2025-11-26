<?php


/**
 * Мениджър на глоби и удръжки
 *
 *
 * @category  bgerp
 * @package   hr
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Глоби
 */
class hr_Deductions extends core_Master
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'hr_IndicatorsSourceIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Удръжки';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Удръжка';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_State, plg_SaveAndNew, doc_plg_TransferDoc, bgerp_plg_Blank,plg_Sorting, 
    				 doc_DocumentPlg, doc_ActivatePlg,hr_Wrapper,acc_plg_DocumentSummary, deals_plg_SaveValiorOnActivation';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,hrMaster';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,hrMaster';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,hrMaster';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,hrMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,hrMaster';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,hrMaster';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,hrMaster';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, date, personId, type, sum';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'personId,date,type';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '5.5|Човешки ресурси';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'type';
    
    
    /**
     * Единична икона
     */
    public $singleIcon = 'img/16/banknote.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'hr/tpl/SingleLayoutDeductions.shtml';
    
    
    /**
     * За плъгина acc_plg_DocumentSummary
     */
    public $filterFieldDateFrom = 'date';


    /**
     * За плъгина acc_plg_DocumentSummary
     */
    public $filterFieldDateTo = 'date';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, date,modifiedOn';


    /**
     * Поле за филтриране по дата
     */
    public $valiorFld = 'date';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('date', 'date', 'caption=Дата');
        $this->FLD('personId', 'key2(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Служител,mandatory');
        $this->FLD('type', 'richtext(bucket=Notes)', 'caption=Основание,mandatory');
        $this->FLD('sum', 'double(Min=0,decimals=2)', 'caption=Сума,mandatory');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,maxRadio=1)', 'caption=Валута,silent,removeAndRefreshForm');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // Ако имаме права да видим визитката
        if (crm_Persons::haveRightFor('single', $rec->personId)) {
            $name = crm_Persons::fetchField("#id = '{$rec->personId}'", 'name');
            $row->personId = ht::createLink($name, array('crm_Persons', 'single', 'id' => $rec->personId), null, 'ef_icon = img/16/vcard.png');
        }

        $row->sum = currency_Currencies::decorate($row->sum, $rec->currencyId, true);
        $row->title = $mvc->getLink($rec->id, 0);
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->setFieldTypeParams('personId', array('allowEmpty' => 'allowEmpty', 'groups' => keylist::addKey('', crm_Groups::getIdFromSysId('employees'))));
        $data->listFilter->showFields = 'personId,date';
        $data->listFilter->view = 'vertical';
        $data->listFilter->input('personId, date', 'silent');
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        if ($data->listFilter->rec->personId) {
            $data->query->where("#personId = '{$data->listFilter->rec->personId}'");
        }
        
        if ($data->listFilter->rec->date) {
            $data->query->where("#date = '{$data->listFilter->rec->date}'");
        }
    }
    
    
    /**
     * Метод за вземане на резултатност на хората. За определена дата се изчислява
     * успеваемостта на човека спрямо ресурса, които е изпозлвал
     *
     * @param datetime $timeline - Времето, след което да се вземат всички модифицирани/създадени записи
     *
     * @return array $result  - масив с обекти
     *
     * 			o date        - дата на стайноста
     * 		    o personId    - ид на лицето
     *          o docId       - ид на документа
     *          o docClass    - клас ид на документа
     *          o indicatorId - ид на индикатора
     *          o value       - стойноста на индикатора
     *          o isRejected  - оттеглена или не. Ако е оттеглена се изтрива от индикаторите
     */
    public static function getIndicatorValues($timeline)
    {
        $result = array();
        $query = self::getQuery();
        $query->where("#modifiedOn  >= '{$timeline}' AND #state != 'draft' AND #state != 'template' AND #state != 'pending'");
        
        $iRec = hr_IndicatorNames::force('Удръжка', __CLASS__, 1);
        if($iRec->state == 'closed') return $result;

        while ($rec = $query->fetch()) {
            $periodRec = acc_Periods::fetchByDate($rec->date);
            $periodCurrencyCode = currency_Currencies::getCodeById($periodRec->baseCurrencyId);

            $result[] = (object) array(
                'date' => $rec->date,
                'personId' => $rec->personId,
                'docId' => $rec->id,
                'docClass' => core_Classes::getId('hr_Deductions'),
                'indicatorId' => $iRec->id,
                'value' => currency_CurrencyRates::convertAmount($rec->sum, $rec->date, $rec->currencyId, $periodCurrencyCode),
                'isRejected' => $rec->state == 'rejected',
            );
        }
        
        return $result;
    }
    
    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     *
     * @param datetime $date
     *
     * @return array $result
     */
    public static function getIndicatorNames()
    {
        $result = array();
        $rec = hr_IndicatorNames::force('Удръжка', __CLASS__, 1);
        if($rec->state != 'closed'){
            $result[$rec->id] = $rec->name;
        }
        
        return $result;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $Cover = doc_Folders::getCover($folderId);
        
        // Трябва да е в папка на лице или на проект
        if ($Cover->className != 'crm_Persons' && $Cover->className != 'doc_UnsortedFolders') {
            
            return false;
        }
        
        // Ако е в папка на лице, лицето трябва да е в група служители
        if ($Cover->className == 'crm_Persons') {
            $emplGroupId = crm_Groups::getIdFromSysId('employees');
            $personGroups = $Cover->fetchField('groupList');
            if (!keylist::isIn($emplGroupId, $personGroups)) {
                
                return false;
            }
        }
        
        if ($Cover->className == 'doc_UnsortedFolders') {
            $cu = core_Users::getCurrent();
            if (!haveRole('ceo,hr', $cu)) {
                
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @param int $id
     *
     * @return stdClass $row
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = "Удръжка  №{$rec->id}";
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        $row->recTitle = $this->getRecTitle($rec, false);
        $row->subTitle = crm_Persons::getTitleById($rec->personId);

        return $row;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $me = cls::get(get_called_class());
        
        $title = tr('Удръжка  №|*'. $rec->id . ' за|* ') . $me->getVerbal($rec, 'personId');
        
        return $title;
    }

    /**
     * Модифициране на edit формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = &$data->form;
        $form->setFieldTypeParams('personId', array('groups' => keylist::addKey('', crm_Groups::getIdFromSysId('employees'))));
    }
}
