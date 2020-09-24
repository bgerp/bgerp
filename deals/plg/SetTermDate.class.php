<?php


/**
 * Плъгин позволяващ да се зададе само време за изпъление/срок на документ имащ такова поле
 * Документа трябва да има дефиниран $termDateFld
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deals_plg_SetTermDate extends core_Plugin
{
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (!isset($mvc->termDateFld) || !isset($fields['-single']) || Mode::isReadOnly()) {
            
            return;
        }
        
        // Ако има права показване на линка за редакция
        if ($mvc->haveRightFor('settermdate', $rec)) {
            $row->{$mvc->termDateFld} .= (!empty($row->{$mvc->termDateFld})) ? '' : '<div class=border-field></div>';
            $row->{$mvc->termDateFld} = $row->{$mvc->termDateFld} . ht::createLink('', array($mvc, 'settermdate', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/edit.png,title=Задаване на нова дата');
        }
        
        if(!empty($rec->{$mvc->termDateFld}) && $rec->{$mvc->termDateFld} < dt::today()){
            $row->{$mvc->termDateFld} = ht::createHint($row->{$mvc->termDateFld}, 'Датата е в миналото', 'warning', false);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'settermdate') {
            $clone = null;
            if (isset($rec)) {
                if (!in_array($rec->state, array('draft', 'pending'))) {
                    $requiredRoles = 'no_one';
                } else {
                    $clone = clone $rec;
                    $clone->state = 'draft';
                }
            }
            
            if ($requiredRoles != 'no_one') {
                $requiredRoles = $mvc->getRequiredRoles('pending', $clone, $userId);
            }
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Mvc $mvc
     * @param mixed    $res
     * @param string   $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action != 'settermdate') {
            
            return;
        }
        
        // Проверка
        $mvc->requireRightFor('settermdate');
        expect($id = Request::get('id', 'int'));
        expect($rec = $mvc->fetch($id));
        $mvc->requireRightFor('settermdate', $rec);
        
        // Показване на формата за смяна на срока
        $form = cls::get('core_Form');
        $Field = $mvc->getField($mvc->termDateFld);
        $form->title = core_Detail::getEditTitle($mvc, $id, $Field->caption, null);
        $form->FLD('newTermDate', 'varchar', "caption={$Field->caption}");
        $form->setFieldType('newTermDate', $mvc->getFieldType($mvc->termDateFld));
        $form->setDefault('newTermDate', $rec->{$mvc->termDateFld});
        $form->setDefault('newTermDate', date('Y-m-d H:i'));
        $form->input();
        
        // Ако е събмитнат
        if ($form->isSubmitted()) {
            
            // Обновява се срока
            $rec->{$mvc->termDateFld} = $form->rec->newTermDate;
            $mvc->save_($rec, $mvc->termDateFld);
            $mvc->touchRec($rec);
            
            followRetUrl(null, 'Промяната е направена успешно');
        }
        
        $form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $mvc->getSingleUrlArray($id), 'ef_icon = img/16/close-red.png');
        
        // Рендиране на формата
        $res = $form->renderHtml();
        $res = $mvc->renderWrapping($res);
        core_Form::preventDoubleSubmission($res, $form);
        
        // ВАЖНО: спираме изпълнението на евентуални други плъгини
        return false;
    }
}
