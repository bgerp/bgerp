<?php


/**
 * Столуващи хора
 *
 *
 * @category  bgerp
 * @package   catering
 *
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class catering_EmployeesList extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Столуващи хора';
    
    
    /**
     * Заглавие в еднично число
     */
    public $singleTitle = 'Столуващ персонал';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_Search,
                             catering_Wrapper,
                             CrmPersons=crm_Persons,
                             Nom=acc_Lists';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, personId, tel, email';
    
    
    /**
     * полета от БД по които ще се търси
     */
    public $searchFields = 'personId';     // Полетата, които ще видим в таблицата
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Кой  може да пише?
     */
    public $canWrite = 'catering, ceo';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'catering, ceo' ;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('personId', 'key(mvc=crm_Persons, select=name)', 'caption=Име, notNull, mandatory');
        $this->FNC('tel', 'varchar(64)', 'caption=Телефон');
        $this->FNC('email', 'email(64)', 'caption=Email');
        
        $this->setDbUnique('personId');
    }
    
    
    /**
     * Сменяме заглавието
     *
     * @param stdClass $data
     */
    public static function on_BeforePrepareListTitle($data)
    {
        // Check current user roles
        if (!haveRole('admin,catering')) {
            $data->title = 'Столуващ';
        }
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
        
        /*// Prepare tel, email
        $row->tel = $mvc->CrmPersons->fetchField($rec->personId, 'tel');
        $row->email = "<a href='mailto:" . $mvc->CrmPersons->fetchField($rec->personId, 'email') . "'>" . $mvc->CrmPersons->fetchField($rec->personId, 'email') . "</a>";*/
    }
    
    
    /**
     * Връща $personId от модела EmployeesList по потребител в системата
     *
     * @return int $personId
     */
    public static function getPersonIdForCurrentUser()
    {
        // get current user name
        $userName = Users::getCurrent('names');
        
        // get $personId
        $personId = crm_Persons::fetchField("#name = '{$userName}'", 'id');
        
        // get $personId
        $personId = self::fetchField("#personId = '{$personId}'", 'id');
        
        return $personId;
    }
    
    
    /**
     * Връща $personName по потребител в системата
     *
     * @return int $personName
     */
    public static function getPersonNameForCurrentUser()
    {
        // get current user name
        $userName = core_Users::getCurrent('names');
        
        $personName = $userName;
        
        return $personName;
    }
    
    
    /**
     * Листваме само тези записи от контактите, които са 'person' с ном. 'Персонал' и 'Кетъринг'
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        /*
        $nomPersonal = $mvc->Nom->fetchField("#name = 'Персонал'", 'id');
        $nomCatering = $mvc->Nom->fetchField("#name = 'Кетъринг'", 'id');

        $queryCrmPersons = $mvc->CrmPersons->getQuery();
        $where = "#type = 'person' AND #inLists LIKE '%|{$nomPersonal}|%' AND #inLists LIKE '%|{$nomCatering}|%'";
        */
        $queryCrmPersons = $mvc->CrmPersons->getQuery();
        $where = '1=1';
        
        while ($recCrmPersons = $queryCrmPersons->fetch($where)) {
            $selectOptCrmPersons[$recCrmPersons->id] = $recCrmPersons->name;
        }
        
        $data->form->setOptions('personId', $selectOptCrmPersons);
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
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'search';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input('search', 'silent');
        
        // Check current user roles
        if (!haveRole('ceo,catering')) {
            $personId = self::getPersonIdForCurrentUser();
            
            $data->query->where("#id = '{$personId}'");
            
            unset($data->listFields['num']);
        }
    }
}
