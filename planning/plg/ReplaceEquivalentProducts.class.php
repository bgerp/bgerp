<?php


/**
 * Плъгин подменящ заместващите артикули
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2019 Experta OOD
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
            
            // Подготвяме формата
            $data = new stdClass();
            $data->action = 'replaceproduct';
            $data->rec = $rec;
            $mvc->prepareEditForm($data);
            $form = &$data->form;
            $form->setAction(array($mvc, 'replaceproduct', $id));
            
            // Оставяме да се показват само определени полета
            $fields = $form->selectFields("#input != 'hidden' AND #input != 'none'");
            if (is_array($fields)) {
                foreach ($fields as $name => $fld) {
                    if (!in_array($name, array($mvc->replaceProductQuantityFieldName, $mvc->replaceProductFieldName, $mvc->replaceProductPackagingFieldName))) {
                        $form->setField($name, 'input=hidden');
                    }
                }
            }
            
            // Кои са допустимите заместващи артикули
            $equivalenProducts = planning_ObjectResources::getEquivalentProducts($rec->{$mvc->replaceProductFieldName});
            $equivalenProducts = array('x' => (object) array('title' => tr('Заместващи'), 'group' => true)) + $equivalenProducts;
            $form->setOptions($mvc->replaceProductFieldName, $equivalenProducts);
            
            // Инпутваме формата
            $form->input();
            $mvc->invoke('AfterInputEditForm', array($form));
            
            // Ако е събмитната
            if ($form->isSubmitted()) {
                
                // Обновяваме записа
                $nRec = $form->rec;
                $nFields = array();
                if ($mvc->isUnique($nRec, $nFields)) {
                    $nRec->autoAllocate = true;
                    $mvc->save($nRec);
                    
                    return followRetUrl();
                }
                
                $form->setError($nFields, 'Вече съществува запис със същите данни');
            }
            
            // Бутони и заглавие на формата
            $name = cat_Products::getHyperlink($rec->{$mvc->replaceProductFieldName}, true);
            $form->title = "Подмяна на |* <b>{$name}</b> |с друг в|* <b>" . $mvc->Master->getHyperlink($form->rec->{$mvc->masterKey}, true) . "</b>";
            $form->toolbar->addSbBtn('Подмяна', 'replaceproduct', 'ef_icon = img/16/star_2.png, title=Подмяна');
            $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
            
            // Рендиране на формата
            $res = $mvc->renderWrapping($form->renderHtml());
            core_Form::preventDoubleSubmission($res, $form);
            
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
                $equivalentProducts = planning_ObjectResources::getEquivalentProducts($rec->{$mvc->replaceProductFieldName});
                if (!countR($equivalentProducts)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
}
