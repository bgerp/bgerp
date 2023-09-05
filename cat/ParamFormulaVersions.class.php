<?php


/**
 * Мениджър за Версии на параметрите с формули
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Версии на параметрите с формули
 */
class cat_ParamFormulaVersions extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Версии на параметрите с формули';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, cat_Wrapper, plg_Modified';


    /**
     * Кой има право да пише?
     */
    public $canWrite = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'cat,ceo,sales,purchase';


    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'cat,ceo';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'paramId,oldFormula,newFormula,modifiedOn,modifiedBy';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('paramId', 'key(mvc=cat_Params,select=typeExt)', 'caption=Параметър,silent,removeAndRefreshForm=oldFormula|newFormula|oldFormulaHash,mandatory');
        $this->FLD('oldFormula', 'text(rows=2)', 'caption=Стара формула,input=none,mandatory');
        $this->FLD('newFormula', 'text(rows=2)', 'caption=Нова формула,input=none,mandatory');
        $this->FLD('oldFormulaHash', 'varchar', 'caption=Стара формула (хеш)', 'input=none');
        $this->FLD('newFormulaHash', 'varchar', 'caption=Нова формула (хеш)', 'input=none');

        $this->setDbUnique('paramId,oldFormulaHash');
        $this->setDbIndex('oldFormulaHash');
        $this->setDbIndex('newFormulaHash');
        $this->setDbIndex('paramId');
    }


    /**
     * След всеки запис
     *
     * @param core_Mvc $mvc
     * @param int      $id
     * @param stdClass $rec
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        $query = static::getQuery();
        $query->where("#newFormulaHash = '{$rec->oldFormulaHash}'");
        while($otherRec = $query->fetch()){
            $otherRec->newFormula = $rec->newFormula;
            $otherRec->newFormulaHash = md5(str::removeWhiteSpace($rec->newFormula));
            static::save($otherRec, 'newFormula,newFormulaHash');
        }
    }


    /**
     * Логване в модела
     *
     * @param int $paramId
     * @param string $oldFormula
     * @param string $newFormula
     * @return void
     */
    public static function log($paramId, $oldFormula, $newFormula)
    {
        // Записване на информацията в модела
        $self = cls::get(get_called_class());
        $rec = (object)array('paramId' => $paramId, 'oldFormula' => $oldFormula, 'newFormula' => $newFormula);
        $rec->oldFormulaHash = md5(str::removeWhiteSpace($oldFormula));
        $rec->newFormulaHash = md5(str::removeWhiteSpace($newFormula));

        $fields = array();
        $exRec = null;
        if (!$self->isUnique($rec, $fields, $exRec)) {
            $rec->id = $exRec->id;
        }

        static::save($rec);
    }


    /**
     * Връща заместващата формула на посочената
     *
     * @param int $paramId
     * @param mixed $domainClass
     * @param mixed $domainId
     * @param string $oldFormula
     * @return null
     */
    public static function getReplacementFormula($paramId, $domainClass, $domainId, $oldFormula)
    {
        $oldFormulaKey = md5(str::removeWhiteSpace($oldFormula));
        $replacementFormula = static::fetchField("#paramId = {$paramId} AND #oldFormulaHash='{$oldFormulaKey}'", 'newFormula');

        return !empty($replacementFormula) ? $replacementFormula : null;
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->paramId = cat_Params::getHyperlink($rec->paramId, true);

        if(mb_strlen($rec->oldFormula) > 80){
            $formula = "<i>" . tr('Покажи') . "</i>" . " <a href=\"javascript:toggleDisplay('{$rec->id}OldFormula')\"  style=\"background-image:url(" . sbf('img/16/toggle1.png', "'") . ');" class=" plus-icon more-btn"> </a>';
            $formula .= "<div style='margin-top:2px;margin-top:2px;margin-bottom:2px;display:none' id='{$rec->id}OldFormula'>{$rec->oldFormula}</div>";
            $row->oldFormula = $formula;
        }

        if(mb_strlen($rec->newFormula) > 80){
            $formula = "<i>" . tr('Покажи') . "</i>" . " <a href=\"javascript:toggleDisplay('{$rec->id}NewFormula')\"  style=\"background-image:url(" . sbf('img/16/toggle1.png', "'") . ');" class=" plus-icon more-btn"> </a>';
            $formula .= "<div style='margin-top:2px;margin-top:2px;margin-bottom:2px;display:none' id='{$rec->id}NewFormula'>{$rec->newFormula}</div>";
            $row->newFormula = $formula;
        }
    }


    /**
     * Подготовка на детайла
     *
     * @param stdClass $data
     */
    public function prepareDetail_($data)
    {
        if(!cat_Params::haveDriver($data->masterData->rec, 'cond_type_Formula')){
            $data->hide = true;
            return;
        }

        $data->recs = $data->rows = array();
        $query = $this->getQuery();
        $query->where("#paramId = {$data->masterId}");
        $query->orderBy('modifiedOn', 'DESC');
        while($rec = $query->fetch()){
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = static::recToVerbal($rec);
        }

        return $data;
    }


    /**
     * Рендиране на детайл
     */
    public function renderDetail_($data)
    {
        if($data->hide) return null;

        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $listTableMvc = clone $this;
        $listTableMvc->setField('oldFormula', 'tdClass=leftCol');
        $listTableMvc->setField('newFormula', 'tdClass=leftCol');
        $table = cls::get('core_TableView', array('mvc' => $listTableMvc));
        $data->listFields = arr::make('oldFormula=Стара формула,modifiedOn=Модифицирано->На,modifiedBy=Модифицирано->От', true);

        $listTableMvc->invoke('BeforeRenderListTable', array($tpl, &$data));

        $tableTpl = $table->get($data->rows, $data->listFields);
        $tpl->append(tr('Стари формули'), 'title');
        $tpl->replace($tableTpl, 'content');

        return $tpl;
    }
}