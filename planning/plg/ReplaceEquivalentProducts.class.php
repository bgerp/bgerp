<?php


/**
 * Плъгин подменящ заместващите артикули
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_plg_ReplaceEquivalentProducts extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->replaceProductFieldName, 'productId');
        setIfNot($mvc->replaceProductPackagingFieldName, 'packagingId');
        setIfNot($mvc->replaceProductQuantityFieldName, 'packQuantity');
        setIfNot($mvc->canReplaceproduct, $mvc->canEdit);
        
        expect($mvc instanceof core_Detail, "Трябва да е наследник на 'core_Detail'");
    }
    
    
    /**
     * Преди изпълнението на контролерен екшън
     *
     * @param core_Manager $mvc
     * @param core_ET      $res
     * @param string       $action
     */
    public static function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if (strtolower($action) == 'replaceproduct') {
            $mvc->requireRightFor('replaceproduct');
            expect($id = Request::get('id', 'int'));
            expect($rec = $mvc->fetch($id));
            $mvc->requireRightFor('replaceproduct', $rec);
            $exRec = clone $rec;
            
            // Подготвяме формата
            $data = new stdClass();
            $data->action = 'replaceproduct';
            $data->rec = $rec;
            $mvc->prepareEditForm($data);
            setIfNot($mvc->packagingFld, 'packagingId');
            
            $form = &$data->form;
            $form->setAction(array($mvc, 'replaceproduct', $id));
            $form->_replaceProduct = true;
            
            // Оставяме да се показват само определени полета
            $fields = $form->selectFields("#name != 'id' AND #name != {$mvc->replaceProductFieldName} AND #name != 'ret_url' AND #name != '{$mvc->masterKey}'");
            if (is_array($fields)) {
                $fields = array_keys($fields);
                foreach ($fields as $name) {
                    $form->setField($name, 'input=none');
                    unset($form->fields[$name]->mandatory);
                }
            }
           
            $form->setDefault($mvc->replaceProductFieldName, $rec->{$mvc->replaceProductFieldName});
            
            // Кои са допустимите заместващи артикули
            $equivalentArr = planning_GenericMapper::getEquivalentProducts($rec->{$mvc->replaceProductFieldName});
            $FieldType = $mvc->getFieldType($mvc->replaceProductFieldName);
            if($FieldType instanceof type_Key2){
                $equivalentIds = array_keys($equivalentArr);
                $form->setFieldTypeParams($mvc->replaceProductFieldName, array('onlyIn' => $equivalentIds));
            } else {
                $form->setOptions($mvc->replaceProductFieldName, $equivalentArr);
            }
            
            // Инпутваме формата
            $form->input();
            $form->setField($mvc->packagingFld, 'input=hidden');
            $mvc->invoke('AfterInputEditForm', array($form));
            
            // Ако е събмитната
            if ($form->isSubmitted()) {
                $nRec = $form->rec;
                
                $productMeasureId = cat_Products::fetchField($nRec->{$mvc->replaceProductFieldName}, 'measureId');
                $nRec->{$mvc->packagingFld} = $productMeasureId;
               
                if($mvc instanceof deals_ManifactureDetail){
                    $nRec->{$mvc->quantityInPackFld} = 1;
                } elseif($mvc instanceof cat_BomDetails) {
                    //bp($nRec);
                }
                
                
                
               // if (!$form->gotErrors()) {
                    
               // }
                
                if($nRec->{$mvc->replaceProductFieldName} == $exRec->{$mvc->replaceProductFieldName}) {
                    
                    return followRetUrl(null, 'Артикулът не е подменен');
                }
                
                // Обновяваме записа
                $nFields = array();
                if ($mvc->isUnique($nRec, $nFields)) {
                    $nRec->autoAllocate = true;
                    $mvc->save($nRec);
                    $mvc->Master->logWrite('Заместване на артикул в документа с друг подобен', $nRec->{$mvc->masterKey});
                    
                    return followRetUrl(null, 'Артикулът е заместен успешно');
                }
                
                $form->setError($nFields, 'Вече съществува запис със същите данни');
            }
            
            // Бутони и заглавие на формата
            $name = cat_Products::getHyperlink($rec->{$mvc->replaceProductFieldName}, true);
            $form->title = "Заместване на |* <b>{$name}</b> |с друг в|* <b>" . $mvc->Master->getHyperlink($form->rec->{$mvc->masterKey}, true) . "</b>";
            $form->toolbar->addSbBtn('Заместване', 'replaceproduct', 'ef_icon = img/16/star_2.png, title=Заместване на артикула в реда на документа');
            $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
            
            // Рендиране на формата
            $res = $mvc->renderWrapping($form->renderHtml());
            core_Form::preventDoubleSubmission($res, $form);
            $mvc->Master->logRead('Разглеждане на формата за подмяна на артикул', $form->rec->{$mvc->masterKey});
            
            // ВАЖНО: спираме изпълнението на евентуални други плъгини
            return false;
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterPrepareListRows($mvc, &$data)
    {
        $rows = &$data->rows;
        if (!countR($rows)) {
            
            return;
        }
        
        foreach ($rows as $id => $row) {
            $rec = $data->recs[$id];
            
            // Добавяме бутона за подмяна
            if ($mvc->haveRightFor('replaceproduct', $rec)) {
                $url = array($mvc, 'replaceproduct', $rec->id, 'ret_url' => true);
                if ($mvc->hasPlugin('plg_RowTools2')) {
                    core_RowToolbar::createIfNotExists($row->_rowTools);
                    $row->_rowTools->addLink('Заместване', $url, array('ef_icon' => 'img/16/arrow_refresh.png', 'title' => 'Избор на заместващ материал'));
                    $row->{$mvc->replaceProductFieldName} = ht::createHint($row->{$mvc->replaceProductFieldName}, 'Артикулът може да бъде заместен с подобен', 'notice', false);
                } elseif ($mvc->hasPlugin('plg_RowTools')) {
                    if (!is_object($row->{$mvc->rowToolsField})) {
                        $row->{$mvc->rowToolsField} = new core_ET('[#TOOLS#]');
                    }
                    
                    $btn = ht::createLink('', $url, false, 'ef_icon=img/16/arrow_refresh.png,title=Избор на заместващ материал');
                    $row->{$mvc->rowToolsField}->append($btn, 'TOOLS');
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'replaceproduct' && isset($rec)) {
            $requiredRoles = $mvc->getRequiredRoles('edit', $rec);
            
            // Могат да се подменят само артикулите, които имат други взаимозаменямеми
            if ($requiredRoles != 'no_one' && isset($rec->{$mvc->replaceProductFieldName})) {
                $equivalentProducts = planning_GenericMapper::getEquivalentProducts($rec->{$mvc->replaceProductFieldName});
                if (!countR($equivalentProducts)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
}
