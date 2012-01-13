<?php


/**
 * Столуващи хора
 *
 *
 * @category  bgerp
 * @package   catering
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class catering_EmployeesList extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Столуващи хора";
    
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Search,
                             catering_Wrapper,
                             CrmPersons=crm_Persons,
                             Nom=acc_Lists';
    
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'num, personId, tel, email, tools=Пулт';
    
    /**
     * полета от БД по които ще се търси
     */
    var $searchFields = 'personId'; // Полетата, които ще видим в таблицата
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = 'catering, admin';
    
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'catering, admin' ;
    
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FNC('num', 'int', 'caption=№, notSorting');
        $this->FLD('personId', 'key(mvc=crm_Persons, select=name)', 'caption=Име, notNull, mandatory');
        $this->FNC('tel', 'varchar(64)', 'caption=Телефон');
        $this->FNC('email', 'email(64)', 'caption=Email');
        
        $this->setDbUnique('personId');
    }
    
    
    
    /**
     * Преди извличане на записите от БД
     * Ако потребитела не е admin или catering показваме само неговия запис
     * Ако потребитела е admin или catering показваме списък от всички служители
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        // Check current user roles
        if (!haveRole('admin,catering')) {
            $personId = $this->getPersonIdForCurrentUser();
            
            $data->query->where("#id = '{$personId}'");
            
            unset($data->listFields['num']);
        }
    }
    
    
    
    /**
     * Сменяме заглавието
     *
     * @param stdClass $data
     */
    function on_BeforePrepareListTitle($data)
    {
        // Check current user roles
        if (!haveRole('admin,catering')) {
            $data->title = "Столуващ";
        }
    }
    
    
    
    /**
     * Слагаме пореден номер на реда
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Prpare 'Num'
        static $num;
        $num += 1;
        $row->num = $num;
        
        // Prepare tel, email
        $row->tel = $mvc->CrmPersons->fetchField($rec->personId, 'tel');
        $row->email = "<a href='mailto:" . $mvc->CrmPersons->fetchField($rec->personId, 'email') ."'>" . $mvc->CrmPersons->fetchField($rec->personId, 'email') . "</a>";
    }
    
    
    
    /**
     * Връща $personId от модела EmployeesList по потребител в системата
     *
     * @return int $personId
     */
    function getPersonIdForCurrentUser()
    {
        // get current user name
        $userName = Users::getCurrent('names');
        
        // get $personId
        $personId = $this->CrmPersons->fetchField("#name = '{$userName}'", 'id');
        
        // get $personId
        $personId = $this->fetchField("#personId = '{$personId}'", 'id');
        
        return $personId;
    }
    
    
    
    /**
     * Връща $personName по потребител в системата
     *
     * @return int $personName
     */
    function getPersonNameForCurrentUser()
    {
        // get current user name
        $userName = Users::getCurrent('names');
        
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
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        /*
        $nomPersonal = $mvc->Nom->fetchField("#name = 'Персонал'", 'id');
        $nomCatering = $mvc->Nom->fetchField("#name = 'Кетъринг'", 'id');
        
        $queryCrmPersons = $mvc->CrmPersons->getQuery();
        $where = "#type = 'person' AND #inLists LIKE '%|{$nomPersonal}|%' AND #inLists LIKE '%|{$nomCatering}|%'";
        */
        $queryCrmPersons = $mvc->CrmPersons->getQuery();
        $where = "1=1";
        
        while($recCrmPersons = $queryCrmPersons->fetch($where)) {
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
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'search';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        $data->listFilter->input('search', 'silent');
    }
}