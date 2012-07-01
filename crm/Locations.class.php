<?php



/**
 * Локации
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class crm_Locations extends core_Master {
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    // var $interfaces = 'acc_RegisterIntf';
    
    
    
    /**
     * Заглавие
     */
    var $title = "Локации на контрагенти";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, crm_Wrapper, plg_Rejected, plg_RowNumbering, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, title, contragent=Контрагент, type, countryId, place, pCode, address, comment, gln";


    /**
     * Кой може да чете и записва локации?
     */
    var $canRead  = 'ceo';
    var $canWrite = 'user';
    var $canSingle = 'user';


    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Локация";
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/location_pin.png';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsSingleField = 'title';

    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('contragentCls', 'class(interface=crm_ContragentAccRegIntf)', 'caption=Собственик->Клас,input=hidden,silent');
        $this->FLD('contragentId', 'int', 'caption=Собственик->Id,input=hidden,silent');
        $this->FLD('title', 'varchar(255)', 'caption=Наименование,mandatory,width=100%');
        $this->FLD('type', 'enum(correspondence=За кореспонденция,
            headquoter=Главна квартира,
            shipping=За получаване на пратки,
            office=Офис,shop=Магазин,
            storage=Склад,
            factory=Фабрика,
            other=Друг)', 'caption=Тип,mandatory');
        $this->FLD('countryId', 'key(mvc=drdata_Countries, select=commonName, allowEmpty)', 'caption=Юрисдикция,mandatory');
        $this->FLD('place', 'varchar(64)', 'caption=Град,mandatory,oldFieldName=city');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,mandatory');
        $this->FLD('gln', 'gs1_TypeEan13', 'caption=GLN код');
        $this->FLD('gpsCoords', 'location_Type', 'caption=Координати');
        $this->FLD('comment', 'richtext', 'caption=@Информация');
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $rec = $data->form->rec;
        
        $Contragents = cls::get($rec->contragentCls);
        expect($Contragents instanceof core_Master);
        $details = arr::make($Contragents->details);
        expect($details['ContragentLocations'] == 'crm_Locations');
        
        $contragentRec = $Contragents->fetch($rec->contragentId);
        
        $data->form->setDefault('countryId', $contragentRec->country);
        $data->form->setDefault('place', $contragentRec->place);
        $data->form->setDefault('pCode', $contragentRec->pCode);
        
        $contragentTitle = $Contragents->getTitleById($contragentRec->id);
        
        if($rec->id) {
            $data->form->title = 'Редактиране на локация на |*' . $contragentTitle;
        } else {
            $data->form->title = 'Нова локация на |*' . $contragentTitle;
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $cMvc = cls::get($rec->contragentCls);
        $field = $cMvc->rowToolsSingleField;
        $cRec = $cMvc->fetch($rec->contragentId);
        $cRow = $cMvc->recToVerbal($cRec, "-list,{$field}");
        $row->contragent = $cRow->{$field};
     }
    
    
    /**
     * @todo Чака за документация...
     */
    function prepareContragentLocations($data)
    {
        expect($data->contragentCls = core_Classes::fetchIdByName($data->masterMvc));
        expect($data->masterId);
        $query = $this->getQuery();
        $query->where("#contragentCls = {$data->contragentCls} AND #contragentId = {$data->masterId}");
        
        while($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $row = $data->rows[$rec->id] = $this->recToVerbal($rec);
        }
    }


    /**
     * Премахване на бутона за добавяне на нова локация от лист изгледа
     */
    function on_BeforeRenderListToolbar($mvc, &$tpl, &$data)
    {
        $data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * Рендира данните
     */
    function renderContragentLocations($data)
    {
        $tpl = new ET(getFileContent('crm/tpl/ContragentDetail.shtml'));
        
        $tpl->append(tr('Локации'), 'title');
        
        if(count($data->rows)) {
            
            foreach($data->rows as $id => $row) {
                $tpl->append("<div style='margin:3px;'>", 'content');
                
                $tpl->append("{$row->title}, {$row->type}", 'content');
                
                if(!Mode::is('printing')) {
                    if($this->haveRightFor('edit', $id)) {
                        // Добавяне на линк за редактиране
                        $tpl->append("<span style='margin-left:5px;'>", 'content');
                        $url = array($this, 'edit', $id, 'ret_url' => TRUE);
                        $img = "<img src=" . sbf('img/16/edit-icon.png') . " width='16' height='16'>";
                        $tpl->append(ht::createLink($img, $url, FALSE, 'title=' . tr('Редактиране на локация')), 'content');
                        $tpl->append('</span>', 'content');
                    }
                    
                    if($this->haveRightFor('delete', $id)) {
                        // Добавяне на линк за изтриване
                        $tpl->append("<span style='margin-left:5px;'>", 'content');
                        $url = array($this, 'delete', $id, 'ret_url' => TRUE);
                        $img = "<img src=" . sbf('img/16/delete-icon.png') . " width='16' height='16'>";
                        $tpl->append(ht::createLink($img, $url, 'Наистина ли желаете да изтриете локацията?', 'title=' . tr('Изтриване на локация')), 'content');
                        $tpl->append('</span>', 'content');
                    }
                }
                
                $tpl->append("</div>", 'content');
            }
        } else {
            $tpl->append(tr("Все още няма локации"), 'content');
        }
        
        if(!Mode::is('printing')) {
            $url = array($this, 'add', 'contragentCls' => $data->contragentCls, 'contragentId' => $data->masterId, 'ret_url' => TRUE);
            $img = "<img src=" . sbf('img/16/add.png') . " width='16' height='16'>";
            $tpl->append(ht::createLink($img, $url, FALSE, 'title=' . tr('Добавяне на нова локация')), 'title');
        }
        
        return $tpl;
    }



    /**
     *
     */
    static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($rec->contragentCls) {
            $contragent = cls::get($rec->contragentCls);
            $requiredRoles = $contragent->getRequiredRoles($action, $rec->contragentId, $userId);
        }
    }
}