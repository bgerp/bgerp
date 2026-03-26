<?php


/**
 * Видове релации между артикулите
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_RelationTypes extends core_Manager
{
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_RowTools2, cat_Wrapper, plg_Created, plg_SaveAndNew, plg_StructureAndOrder';


    /**
     * Заглавие на мениджъра
     */
    public $title = 'Видове релации';


    /**
     * Единично заглавие
     */
    public $singleTitle = 'Вид релация';


    /**
     * Права за писане
     */
    public $canWrite = 'cat,admin';


    /**
     * Права за запис
     */
    public $canDelete = 'cat,admin';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'cat,admin';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title, group1Name=Първо, group2Name=Второ, isSymmetric=Симетр., saoOrder=Подредба, createdOn, createdBy';


    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'id';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FNC('title', 'varchar(120)', 'caption=Релация');

        $this->FLD('group1Name', 'varchar(32)', 'caption=Първа група->Име,mandatory');
        $this->FLD('group1GroupId', 'key(mvc=cat_Groups, select=name,allowEmpty)', 'caption=Първа група->Група,mandatory');
        $this->FLD('group1Info', 'varchar(128)', 'caption=Първа група->Описание');

        $this->FLD('group2Name', 'varchar(32)', 'caption=Втора група->Име');
        $this->FLD('group2GroupId', 'key(mvc=cat_Groups, select=name,allowEmpty)', 'caption=Втора група->Група');
        $this->FLD('group2Info', 'varchar(128)', 'caption=Втора група->Описание');
        $this->FLD('isSymmetric', 'enum(yes=Да,no=Не)', 'caption=Допълнително->Симетричност,maxRadio=0,notNull,value=no,silent,removeAndRefreshForm');
        $this->FLD('show1InExternal', 'enum(yes=Да,no=Не)', 'caption=Показване във външната част->Първа група,notNull,value=yes');
        $this->FLD('show2InExternal', 'enum(yes=Да,no=Не)', 'caption=Показване във външната част->Втора група,notNull,value=yes');

        $this->setDbIndex('group1GroupId');
        $this->setDbIndex('group2GroupId');
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;

            if(empty($rec->group2Name) || $rec->isSymmetric == 'yes'){
                $rec->group2Name = $rec->group1Name;
            }

            if(empty($rec->group2GroupId) || $rec->isSymmetric == 'yes'){
                $rec->group2GroupId = $rec->group1GroupId;
            }
        }
    }


    /**
     * Изчислява полето 'title'
     */
    protected static function on_CalcTitle($mvc, $rec)
    {
        $rec->title = $rec->group1Name . ' ⬌ ' . $rec->group2Name;
    }


    /**
     *  Обработка на формата за редакция и добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $form->setDefault('isSymmetric', 'no');

        if($rec->isSymmetric == 'yes'){
            $form->setField('group2Name', 'input=hidden');
            $form->setField('group2GroupId', 'input=hidden');
            $form->setField('group2Info', 'input=hidden');
        }
    }


    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // По-хубаво показване на информацията за групите
        if(cat_Products::haveRightFor('list')){
            $group1GroupIdVerbal = $mvc->getFieldType('group1GroupId')->toVerbal($rec->group1GroupId);
            $group1GroupIdVerbal = ht::createLink($group1GroupIdVerbal, array('cat_Products', 'list', 'groupId' => $rec->group1GroupId));
            $row->group1Name .= tr("|<div class='small'><span class='quiet'>|Група|*</span>: {$group1GroupIdVerbal}</div>");

            $group2GroupIdVerbal = $mvc->getFieldType('group2GroupId')->toVerbal($rec->group2GroupId);
            $group2GroupIdVerbal = ht::createLink($group2GroupIdVerbal, array('cat_Products', 'list', 'groupId' => $rec->group2GroupId));
            $row->group2Name .= tr("|<div class='small'><span class='quiet'>|Група|*</span>: {$group2GroupIdVerbal}</div>");

            if(cat_products_Relations::haveRightFor('list')){
                $row->title = ht::createLink($rec->title, array('cat_products_Relations', 'list', 'relTypeId' => $rec->id));
            }
        }

        $row->show1InExternal = $mvc->getFieldType('show1InExternal')->toVerbal($rec->show1InExternal);
        $row->group1Name .= "<div class='small'><span class='quiet'>" . tr('Показване навън') . "</span>: <i>{$row->show1InExternal}</i></div>";

        $row->show2InExternal = $mvc->getFieldType('show2InExternal')->toVerbal($rec->show2InExternal);
        $row->group2Name .= "<div class='small'><span class='quiet'>" . tr('Показване навън') . "</span>: <i>{$row->show2InExternal}</i></div>";

        if(!empty($rec->group1Info)){
            $row->group1Info = $mvc->getFieldType('group1Info')->toVerbal($rec->group1Info);
            $row->group1Name .= "<hr style='margin-bottom:2px;'><div class='richtext small'>{$row->group1Info}</div>";
        }

        if(!empty($rec->group2Info)){
            $row->group2Info = $mvc->getFieldType('group2Info')->toVerbal($rec->group2Info);
            $row->group2Name .= "<hr style='margin-bottom:2px;'><div class='richtext small'>{$row->group2Info}</div>";
        }
        $row->ROW_ATTR['class'] = "state-active";
    }


    /**
     * След изтриване на запис
     */
    protected static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        // При изтриване на релация ще се изтрият вече направените връзки от този тип
        foreach ($query->getDeletedRecs() as $rec) {
            cat_products_Relations::delete("#relTypeId = {$rec->id}");
        }
    }


    /**
     * Записи за подреждане
     * @see plg_StructureAndOrder
     */
    public static function getSaoItems($rec)
    {
        $items = array();
        $query = self::getQuery();
        while ($rec = $query->fetch()) {
            $items[$rec->id] = $rec;
        }

        return $items;
    }


    /**
     * Може ли да има поднива
     * @see plg_StructureAndOrder
     */
    public function saoCanHaveSublevel($rec, $newRec = null)
    {
        return false;
    }
}