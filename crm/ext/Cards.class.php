<?php


/**
 * Модел за клиентски карти
 *
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class crm_ext_Cards extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Клиентски карти';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'pos_Cards';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'crm_Wrapper, plg_Search, plg_Sorting, plg_State2, plg_RowTools2, plg_Created';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Клиентска карта';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo, crm';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, crm';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'contragentId=Контрагент,number=Карта,createdOn,createdBy,state=Състояние';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('number', 'varchar(32)', 'caption=Номер,mandatory,smartCenter');
        $this->FLD('contragentId', 'int', 'input=hidden,silent,tdClass=leftCol');
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,silent');
        
        $this->setDbUnique('number');
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $rec = $data->form->rec;
        if (isset($rec->contragentClassId) && isset($rec->contragentId)) {
            $data->form->title = core_Detail::getEditTitle($rec->contragentClassId, $rec->contragentId, $mvc->singleTitle, $rec->id, $mvc->formTitlePreposition);
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-list'])) {
            $Contragent = cls::get($rec->contragentClassId);
            $row->contragentId = $Contragent->getHyperLink($rec->contragentId, true);
        }
        
        $row->created = tr("|на|* {$row->createdOn} |от|* {$row->createdBy}");
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->showFields = 'search';
    }
    
    
    /**
     * Подготовка на клиентските карти на избрания клиент
     */
    public function prepareCards($data)
    {
        $Contragent = $data->masterMvc;
        $masterRec = $data->masterData->rec;
        $data->listFields = arr::make('number=Карта,created=Създаване,state=Състояние', true);
        
        // Подготовка на клиентските карти
        $query = $this->getQuery();
        $query->where("#contragentClassId = '{$Contragent->getClassId()}' AND #contragentId = {$masterRec->id}");
        $query->orderBy("#state");
        while ($rec = $query->fetch()) {
            $row = $this->recToVerbal($rec);
            $data->rows[$rec->id] = $row;
        }
        
        // Добавяне на бутон при нужда
        if ($Contragent->haveRightFor('edit', $data->masterId) && $this->haveRightFor('add')) {
            $addUrl = array($this, 'add', 'contragentClassId' => $Contragent->getClassId(), 'contragentId' => $data->masterId, 'ret_url' => true);
            $data->addBtn = ht::createLink('', $addUrl, null, array('ef_icon' => 'img/16/add.png', 'class' => 'addSalecond', 'title' => 'Добавяне на нова клиентска карта'));
        }
    }
    
    
    /**
     * Рендиране на клиентските карти на избрания клиент
     */
    public function renderCards($data)
    {
        $tpl = new core_ET('');
        $tpl->append(tr('Клиентски карти'), 'cardTitle');
        
        $table = cls::get('core_TableView');
        $table->class = 'simpleTable';
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $details = $table->get($data->rows, $data->listFields);
        $tpl->append($details);
        
        if (isset($data->addBtn)) {
            $tpl->append($data->addBtn, 'addCardBtn');
        }
        
        return $tpl;
    }
    
    
    /**
     * Връща контрагента отговарящ на номера на картата
     *
     * @param string $number     - номер на карта
     * @param int    $ctrClassId - ид на класа от който трябва да е контрагента
     *
     * @return FALSE|core_ObjectReference - референция към контрагента
     */
    public static function getContragent($number, $ctrClassId = null)
    {
        $query = static::getQuery();
        $query->where("#number = '{$number}'");
        if (isset($ctrClassId)) {
            $query->where("#contragentClassId = ${ctrClassId}");
        }
        
        if ($rec = $query->fetch()) {
            
            return new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
        }
        
        return false;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)) {
            if (!cls::get($rec->contragentClassId)->haveRightFor('edit', $rec->contragentId)) {
                $requiredRoles = 'no_one';
            }
        }
    }
}
