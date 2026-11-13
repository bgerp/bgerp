<?php


/**
 * Базов клас за моделите със стойности на параметри към обекти
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
abstract class cat_products_ProtoParams extends doc_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Параметри';


    /**
     * Единично заглавие
     */
    public $singleTitle = 'Параметър';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_RowTools2, plg_LastUsedKeys, plg_SaveAndNew';


    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'paramId';


    /**
     * Кой може да листва
     */
    public $canList = 'debug';


    /**
     * Предлог в формата за добавяне/редактиране
     */
    public $formTitlePreposition = 'на';


    /**
     * Брой записи на страница
     *
     * @var int
     */
    public $listItemsPerPage = 20;


    /**
     * Връща ид на класа на обекта, към който е параметърът
     *
     * @param stdClass $rec
     *
     * @return int
     */
    abstract public function getDomainClassId($rec);


    /**
     * Връща параметрите, които могат да се добавят към обекта
     *
     * @param stdClass $rec         - запис от модела
     * @param string   $warningMsg  - съобщение, ако няма параметри за добавяне
     *
     * @return array
     */
    abstract public function getParamOptions($rec, &$warningMsg);


    /**
     * Дали стойността на параметъра е задължителна във формата
     *
     * @param stdClass $data
     *
     * @return bool
     */
    public function isParamValueMandatory($data)
    {
        return true;
    }


    /**
     * Заявка към параметрите без оттеглените, подредени по група и ред на параметъра
     *
     * @return core_Query $query
     */
    public static function getParamQuery()
    {
        $query = static::getQuery();
        $query->EXT('group', 'cat_Params', 'externalName=group,externalKey=paramId');
        $query->EXT('order', 'cat_Params', 'externalName=order,externalKey=paramId');
        $query->EXT('state', 'cat_Params', 'externalName=state,externalKey=paramId');
        $query->XPR('orderEx', 'varchar', 'COALESCE(#order, 999999)');
        $query->where("#state != 'rejected'");
        $query->orderBy('group,orderEx,id', 'ASC');

        return $query;
    }


    /**
     * Подготвя реда за показване в блока с параметрите
     *
     * @param stdClass $rec
     *
     * @return stdClass $row
     */
    public function prepareParamRow($rec)
    {
        $row = $this->recToVerbal($rec);
        $row->_paramId = $rec->paramId;
        $row->_paramValue = $rec->paramValue;
        $row->_domainClassId = $this->getDomainClassId($rec);
        $row->_domainId = $rec->{$this->masterKey};

        return $row;
    }


    /**
     * Добавя полетата за параметъра и стойността му
     */
    protected function addParamFields()
    {
        $this->FLD('paramId', 'key(mvc=cat_Params,select=typeExt,forceOpen)', 'input,caption=Параметър,mandatory,silent');
        $this->FLD('paramValue', 'text', 'input=none,caption=Стойност');
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $paramRec = cat_Params::fetch($rec->paramId);
        $paramRec->name = tr($paramRec->name);
        $row->paramId = cat_Params::getVerbal($paramRec, 'name');
        if (!empty($paramRec->group)) {
            $paramRec->group = tr($paramRec->group);
            $row->group = cat_Params::getVerbal($paramRec, 'group');
        }

        try{
            if(trim($rec->paramValue ?? '') !== ''){
                $row->paramValue = cond_Parameters::toVerbal($paramRec, $mvc->getDomainClassId($rec), $rec->{$mvc->masterKey}, $rec->paramValue);

                if (!empty($paramRec->suffix)) {
                    $suffix = cat_Params::getVerbal($paramRec, 'suffix');
                    $row->paramValue .= ' ' . tr($suffix);
                }
            } else {
                $row->paramValue = "<span class='blueText'>n/a</span>";
            }
            if(!empty($rec->type)){
                $row->paramValue = ht::createHint($row->paramValue, $row->type ?? '', 'notice', false);
            }
        } catch(core_exception_Expect $e){
            $row->paramValue = "<span class='color'>" . tr("Проблем при показване") . "</span>";
        }
    }


    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        $domainClassId = $mvc->getDomainClassId($rec);
        $domainId = $rec->{$mvc->masterKey} ?? null;

        if ($mvc->isParamValueMandatory($data)) {
            $form->setField('paramValue', 'mandatory');
        }

        if (empty($rec->id)) {
            $form->setField('paramId', array('removeAndRefreshForm' => 'paramValue|paramValue[lP]|paramValue[rP]'));
            $warningMsg = 'Няма параметри за добавяне';
            $options = $mvc->getParamOptions($rec, $warningMsg);

            if (!countR($options)) {
                followRetUrl(null, $warningMsg, 'warning');
            }

            $form->setOptions('paramId', array('' => '') + $options);
            $form->paramOptions = $options;

            if (countR($options) == 1) {
                $form->setDefault('paramId', key($options));
                $form->setReadOnly('paramId');
            }
        } else {
            $form->setReadOnly('paramId');
        }

        if (!empty($rec->paramId)) {
            $pRec = cat_Params::fetch($rec->paramId);
            if ($Type = cat_Params::getTypeInstance($rec->paramId, $domainClassId, $domainId, $rec->paramValue ?? null)) {
                $form->setField('paramValue', 'input');
                $form->setFieldType('paramValue', $Type);
                if($Type instanceof type_Key2 || $Type instanceof type_Key){
                    $form->setField('paramValue', 'class=w100');
                }

                $defaultValue = cat_Params::getDefaultValue($rec->paramId, $domainClassId, $domainId, $rec->paramValue ?? null);
                $form->setDefault('paramValue', $defaultValue);
                if($pRec->valueType == 'readonly' && isset($rec->id)){
                    if(isset($defaultValue)){
                        $form->info = tr("|*<div class='richtext-message richtext-warning'>|Параметърът е дефиниран като „Само за четене“|*!<br>|Промяната наложителна ли е|*?<br>Съвет|*: |Опитайте първо да презапишете с автоматично заредената дефолтна стойност|*!</div>");
                    }
                    else {
                        $form->info = tr("|*<div class='richtext-message richtext-warning'>|Параметърът е дефиниран като „Само за четене“|*!<br>|Промяната наложителна ли е|*?</div>");
                    }
                }

                if (!empty($pRec->suffix)) {
                    $suffix = cat_Params::getVerbal($pRec, 'suffix');
                    $form->setField('paramValue', "unit={$suffix}");
                }
            } else {
                $form->setError('paramId', 'Има проблем при зареждането на типа');
            }
        }
    }


    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        if (isset($data->form->paramOptions) && countR($data->form->paramOptions) <= 1) {
            $data->form->toolbar->removeBtn('saveAndNew');
        }
    }


    /**
     * Рендиране на параметрите
     */
    public static function renderDetail($data)
    {
        // При експорт (sync_plg_ProductExport) описанието се рендира без подготвени параметри
        $params = $data->params ?? null;
        if (is_array($params)) {
            foreach ($params as &$row) {

                // Ревербализиране на файловете да се покажат с подходящите права
                if(!empty($row->_paramId)){
                    $ParamDriver = cat_Params::getDriver($row->_paramId);
                    if($ParamDriver instanceof cond_type_File || $ParamDriver instanceof cond_type_Image){
                        $row->paramValue = cat_Params::toVerbal($row->_paramId, $row->_domainClassId ?? null, $row->_domainId ?? null, $row->_paramValue);
                    }
                }

                // Наследената стойност е в синьо, с HTML хинт откъде идва
                if(!empty($row->_inheritedHint)){
                    $row->paramValue = ht::createHint("<span class='blueText'>{$row->paramValue}</span>", $row->_inheritedHint, 'notice', false, array('isHtml' => true));
                }

                core_RowToolbar::createIfNotExists($row->_rowTools);
                if (empty($data->noChange) && !Mode::isReadOnly()) {
                    $minRowToolbar = $data->minRowToolbar ?? null;
                    $row->tools = $row->_rowTools->renderHtml($minRowToolbar);
                } else {
                    unset($row->tools);
                }
            }
        }

        $paramCaption = $data->paramCaption ?? null;

        $tpl = cat_Params::renderParamBlock($params, $paramCaption);
        $tpl->replace(get_called_class(), 'DetailName');

        if (($data->noChange ?? null) !== true) {
            $tpl->append($data->changeBtn ?? null, 'addParamBtn');
        }

        $tpl->removeBlocks();

        return $tpl;
    }


    /**
     * Рендира параметрите на обекта
     */
    public static function renderParams($data)
    {
        if (isset($data->addUrl) && !Mode::isReadOnly()) {
            $data->changeBtn = ht::createLink('<img src=' . sbf('img/16/add.png') . " style='vertical-align: middle; margin-left:5px;'>", $data->addUrl, false, 'title=Добавяне на нов параметър');
        }

        return static::renderDetail($data);
    }


    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Ако има указани роли за параметъра, потребителя трябва да ги има за редакция/изтриване
        // и за добавяне на конкретен параметър (напр. при предефиниране на наследен)
        $isAddOfParam = ($action == 'add') && !empty($rec->paramId);
        if (($action == 'edit' || $action == 'delete' || $isAddOfParam) && $requiredRoles != 'no_one' && isset($rec)) {
            $pRec = cat_Params::fetch($rec->paramId, 'roles,valueType');
            if (!empty($pRec->roles) && !haveRole($pRec->roles, $userId)) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Извиква се преди запис в модела
     */
    protected static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if(isset($rec->id)){

            // Кои са старите стойности
            $exRec = $mvc->fetch($rec->id, '*', 'paramId,paramValue');
            $rec->_exParamId = $exRec->paramId;
            $rec->_exParamValue = $exRec->paramValue;
        } else {
            $rec->_isCreated = true;
        }
    }


    /**
     * След запис
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        $Class = cls::get($mvc->getDomainClassId($rec));
        $objectId = $rec->{$mvc->masterKey};

        // Има ли промяна на стойноста на параметъра
        $exValue = $rec->_exParamValue ?? null;
        $paramValue = $rec->paramValue ?? null;
        if($exValue != $paramValue){
            $res = cat_Params::onParamChanged($rec->paramId, $Class, $objectId, $paramValue, $exValue);
            if(!empty($res['msg'])){
                core_Statuses::newStatus($res['msg']);
            }
            if(!empty($res['error'])){
                core_Statuses::newStatus($res['error'], 'error');
            }
        }

        $paramName = cat_Params::getVerbal($rec->paramId, 'typeExt');
        $logMsg = (!empty($rec->_isCreated)) ? 'Добавяне на параметър' : 'Редактиране на параметър';

        $Class->logWrite($logMsg, $objectId);
        $Class->logDebug("{$logMsg}: {$paramName}", $objectId);
        if(cls::haveInterface('doc_DocumentIntf', $Class)){
            $Class->touchRec($objectId);
        } elseif($Class->getField('modifiedOn', false)) {
            $cRec = $Class->fetch($objectId);
            $cRec->modifiedOn = dt::now();
            $cRec->modifiedBy = core_Users::getCurrent();
            $Class->save_($cRec, 'modifiedOn,modifiedBy');
        }
    }


    /**
     * След изтриване
     */
    public static function on_AfterDelete($mvc, &$res, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            $Class = cls::get($mvc->getDomainClassId($rec));
            $objectId = $rec->{$mvc->masterKey};
            $paramName = cat_Params::getVerbal($rec->paramId, 'typeExt');
            $Class->logWrite('Изтриване на параметър', $objectId);
            $Class->logDebug("Изтриване на параметър: {$paramName}", $objectId);
        }
    }


    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'paramId';
        $data->listFilter->setFieldTypeParams('paramId', array('allowEmpty' => 'allowEmpty', 'forceOpen' => null));
        $data->listFilter->setField('paramId', 'placeholderType=all');
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        if($filter = $data->listFilter->rec){
            if(!empty($filter->paramId)){
                $data->query->where("#paramId = {$filter->paramId}");
            }
        }

        $data->query->orderBy('id', 'DESC');
    }
}
