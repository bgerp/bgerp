<?php


/**
 * Плъгин за Регистрите, който им добавя възможност обекти от регистрите да влизат като пера
 */
class plg_AccRegistry extends core_Plugin
{
    
    /**
     * @var acc_Lists
     */
    var $acc_Lists;
    
    
    /**
     *  Извиква се след описанието на модела
     */
    function on_AfterDescription($mvc)
    {
        // Това е плъгин само за регистри
        
        // Добавяме поле, което посочва в кои номенклатури е обекта
//        $mvc->FLD('inLists', "keylist(mvc=acc_Lists,select=name)", 'caption=Номенклатури');
        
       // $this->prepareFeatures($mvc);

        $mvc->interfaces['acc_RegiserIntf'] = 'acc_RegiserIntf';
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за добавяне/редактиране на обекта
     * Добавя поле за участие в номенклатури
     */
    function on_AfterPrepareEditForm1($mvc, $data)
    {
        $Lists = &cls::get('acc_Lists');
        
        $classId = core_Classes::fetchField(array("#name = '[#1#]'", $mvc->className), 'id');
        
        $query = $Lists->getQuery();
        $query->where("#regClassId = $classId");
        $query->where("#state = 'active'");
        $options = array();
        
        while ($listRec = $query->fetch()) {
            $options[$listRec->id] = $listRec->name;
        }
        
        if(count($options)) {
            $data->form->fields['inLists']->type->suggestions = $options;
        } else {
            $data->form->setField('inLists', 'input=none');
        }
    }
    
    
    /**
     * @param core_Manager $mvc
     * @param int $id
     * @param stdClass $rec
     */
    function on_AfterSave($mvc, &$id, &$rec)
    {
        $Items = &cls::get('acc_Items');
        
        // Заглавието и евентуално мярката и номера на перото идват от този метод на регистъра
        $itemRec = $mvc->getAccItemRec($rec);
        
        $itemRec->objectId = $rec->id;
        $itemRec->inList = $rec->inLists;
        $itemRec->regClassId = core_Classes::fetchField(array("#name = '[#1#]'", $mvc->className), 'id');
        
        $Items->addFromRegister($itemRec);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function prepareFeatures($mvc)
    {
        $features = array();
        
        if (method_exists($mvc, 'prepareFeatures')) {
            $features += $mvc->prepareFeatures();
        }
        
        if (isset($mvc->features)) {
            $mvc->features = arr::make($mvc->features, true);
            
            foreach ($mvc->features as $f=>$featureCls) {
                if (!isset($features[$f])) {
                    if ($f == $featureCls) {
                        $featureCls = 'acc_feature_Fld';
                    }
                    $features[$f] = new $featureCls($mvc, $f);
                }
            }
        }
        
        $mvc->features = $features;
    }
}