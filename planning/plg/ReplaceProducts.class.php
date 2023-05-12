<?php


/**
 * Плъгин подменящ заместващите артикули
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_plg_ReplaceProducts extends core_Plugin
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
        setIfNot($mvc->packQuantityFld, 'packQuantity');
        setIfNot($mvc->quantityInPackFld, 'quantityInPack');
        setIfNot($mvc->quantityFld, 'quantity');
        setIfNot($mvc->productFld, 'productId');
        setIfNot($mvc->packagingFld, 'packagingId');

        expect($mvc instanceof core_Detail, "Трябва да е наследник на 'core_Detail'");
    }

    /**
     * Преди изпълнението на контролерен екшън
     *
     * @param core_Manager $mvc
     * @param core_ET $res
     * @param string $action
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
            $fields = $form->selectFields("#name != 'id' AND #name != 'ret_url' AND #name != '{$mvc->masterKey}'");
            if (is_array($fields)) {
                $fields = array_keys($fields);
                foreach ($fields as $name) {
                    $form->setField($name, 'input=none');
                    unset($form->fields[$name]->mandatory);
                }
            }

            $replaceOptions = static::getReplaceOptions($mvc, $rec->id, $rec->{$mvc->replaceProductFieldName});
            $form->FLD('replaceProduct', 'varchar(minimumResultsForSearch=5,forceOpen)', "caption=Заместване,mandatory,silent,removeAndRefreshForm={$mvc->replaceProductFieldName}");

            $selectedGenericId = planning_GenericProductPerDocuments::getRec($mvc, $rec->id);
            $selectedKey = "{$rec->{$mvc->replaceProductFieldName}}|{$selectedGenericId}";

            if (array_key_exists($selectedKey, $replaceOptions)) {
                $form->setDefault('replaceProduct', $selectedKey);
            } else {
                $startWith = "{$rec->{$mvc->replaceProductFieldName}}|";
                $usedArr = array_filter($replaceOptions, function ($k) use ($startWith) {
                    return strpos($k, $startWith) === 0;
                }, ARRAY_FILTER_USE_KEY);
                if (countR($usedArr) == 1) {
                    $form->setDefault('replaceProduct', key($usedArr));
                } else {
                    $replaceOptions = array($rec->{$mvc->replaceProductFieldName} => cat_Products::getTitleById($rec->{$mvc->replaceProductFieldName})) + $replaceOptions;
                }
            }

            $form->setOptions('replaceProduct', $replaceOptions);
            $form->input('replaceProduct', 'silent');
            if (isset($form->rec->replaceProduct)) {
                list($form->rec->{$mvc->replaceProductFieldName}, $form->rec->_genericProductId) = explode('|', $form->rec->replaceProduct);
            }

            $form->input();
            $form->setField($mvc->packagingFld, 'input=hidden');
            $mvc->invoke('AfterInputEditForm', array($form));

            // Ако е събмитната
            if ($form->isSubmitted()) {
                $nRec = $form->rec;
                $newProductRec = cat_Products::fetch($nRec->{$mvc->replaceProductFieldName}, 'id,measureId');
                $oldProductRec = cat_Products::fetch($exRec->{$mvc->replaceProductFieldName}, 'id,measureId');

                if ($mvc instanceof deals_ManifactureDetail) {
                    $quantityFld = $mvc->quantityFld;
                } elseif ($mvc instanceof cat_BomDetails) {
                    $formula = trim($nRec->propQuantity);
                    if (is_numeric($formula)) {
                        $quantityFld = 'propQuantity';
                    }
                }

                if(!empty($quantityFld)){
                    if($exRec->{$mvc->packagingFld} != $newProductRec->measureId){
                        $convertRate = cat_Products::convertToUom($oldProductRec->id, $newProductRec->measureId);
                        if(empty($convertRate)){

                            $secondMeasureId = cat_products_Packagings::getSecondMeasureId($newProductRec->id);
                            if($secondMeasureId == $exRec->{$mvc->packagingFld}){
                                $packRec = cat_products_Packagings::getPack($newProductRec->id, $secondMeasureId);
                                $nRec->{$mvc->packagingFld} = $packRec->packagingId;
                                $nRec->{$mvc->quantityInPackFld} = $packRec->quantity;
                                $nRec->{$quantityFld} = ($exRec->{$quantityFld} / $exRec->{$mvc->quantityInPackFld}) * $packRec->quantity;
                            }
                        } else {
                            $nRec->{$mvc->packagingFld} = $newProductRec->measureId;
                            $nRec->{$quantityFld} = $convertRate * $nRec->{$quantityFld};
                        }
                    }
                }


                $exGenericProductId = isset($exRec->id) ? planning_GenericProductPerDocuments::getRec($mvc, $exRec->id) : null;

                if ($nRec->{$mvc->replaceProductFieldName} == $exRec->{$mvc->replaceProductFieldName} && $nRec->_genericProductId == $exGenericProductId) {

                    return followRetUrl(null, 'Артикулът не е подменен');
                }

                // Обновяваме записа
                $nFields = array();
                if ($mvc->isUnique($nRec, $nFields)) {
                    $nRec->autoAllocate = true;

                    $mvc->save($nRec);
                    $mvc->Master->logWrite('Заместване на артикул в документа с друг', $nRec->{$mvc->masterKey});

                    // Ако е подменен артикул се рекалкулират документите, които са генерирали записи спрямо този запис
                    store_StockPlanning::recalcByReff($mvc->Master, $nRec->{$mvc->masterKey});

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
        if (!countR($rows)) return;

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
            // Могат да се подменят само артикулите, които имат други взаимозаменямеми
            if ($requiredRoles != 'no_one' && isset($rec->{$mvc->replaceProductFieldName})) {

                $options = static::getReplaceOptions($mvc, $rec->id, $rec->{$mvc->replaceProductFieldName});
                if (!countR($options)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }

    private static function getReplaceOptions($mvc, $id, $productId)
    {
        $temp = $options = array();

        $genericProductId = planning_GenericProductPerDocuments::getRec($mvc, $id);
        if($query = planning_GenericMapper::getHelperQuery($productId, $genericProductId)){
            while ($dRec = $query->fetch()) {
                $temp[$dRec->genericProductId][$dRec->genericProductId] = $dRec->genericProductId;
                $temp[$dRec->genericProductId][$dRec->productId] = $dRec->productId;
            }

            foreach ($temp as $gProductId => $products) {
                $options["g{$gProductId}"] = (object)array('group' => true, 'title' => cat_Products::getTitleById($gProductId));
                foreach ($products as $pId) {
                    $options["{$pId}|{$gProductId}"] = cat_Products::getTitleById($pId);
                }
            }
        }

        return $options;
    }


    /**
     * Изпълнява се преди клониране
     */
    protected static function on_BeforeSaveClonedDetail($mvc, &$rec, $oldRec)
    {
        if($genericProductId = planning_GenericProductPerDocuments::getRec($mvc, $oldRec->id)){
            $rec->_genericProductId = $genericProductId;
        }
    }


    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave($mvc, &$id, $rec)
    {
        if(isset($rec->{$mvc->replaceProductFieldName})){
            $updateGenericProductId = null;
            if(isset($rec->_genericProductId)){
                $updateGenericProductId = $rec->_genericProductId;
            } else {
                $generic = cat_Products::fetchField("{$rec->{$mvc->replaceProductFieldName}}", 'generic');
                if($generic == 'yes'){
                    $updateGenericProductId = $rec->{$mvc->replaceProductFieldName};
                }
            }

            if(isset($updateGenericProductId)){
                $containerId = $mvc->Master->fetchField("#id = {$rec->{$mvc->masterKey}}", 'containerId');
                planning_GenericProductPerDocuments::sync($mvc, $id, $rec->{$mvc->replaceProductFieldName}, $containerId, $updateGenericProductId);
            }
        }
    }


    /**
     * След изтриване на запис
     */
    protected static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            planning_GenericProductPerDocuments::sync($mvc, $rec->id, null, null);
        }
    }
}