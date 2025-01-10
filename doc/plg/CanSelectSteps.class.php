<?php


/**
 * Плъгин позволяващ на корица да може да се избират етапи
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class doc_plg_CanSelectSteps extends core_Plugin
{

    /**
     * Извиква се след описанието на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->FLD('steps', 'keylist(mvc=doc_UnsortedFolderSteps,select=name,makeLink)', 'caption=Настройки на задачите в проекта->Етапи');
        $mvc->FNC('addSubSteps', 'enum(no=Не,yes=Да)', 'caption=Настройки на задачите в проекта->Добави подетапи,input=hidden,after=stepId,maxRadio=2');

        $mvc->lastUsedKeys .= (!empty($mvc->lastUsedKeys) ? ',' : '') . 'steps';
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        // Ако има налични етапи - добавят се за избор и те
        $options = array();
        $sQuery = doc_UnsortedFolderSteps::getQuery();
        $sQuery->where("#state = 'active'");
        if(!empty($rec->steps)){
            $exStepStr = implode(',', keylist::toArray($rec->steps));
            $sQuery->orWhere("#id IN ({$exStepStr})");
        }
        while($sRec = $sQuery->fetch()){
            $options[$sRec->id] = cls::get('doc_UnsortedFolderSteps')->getSaoFullName($sRec);
        }

        if(countR($options)){
            $form->setSuggestions('steps', array('' => '') + $options);
            $form->setField('addSubSteps', 'input');
            $form->setDefault('addSubSteps', 'no');
        } else {
            $form->setField('steps', 'input=none');
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;

        if ($form->isSubmitted()) {

            // Проверка дали са премахнати етапи, които вече са избрани в задачи в проекта
            $cQuery = cal_Tasks::getQuery();
            $cQuery->where("#folderId = '{$rec->folderId}' AND #stepId IS NOT NULL");
            $stepsInTasks = arr::extractValuesFromArray($cQuery->fetchAll(), 'stepId');
            $selectTaskDescendants = array();
            if(!empty($rec->steps)) {
                $stepsArr = keylist::toArray($rec->steps);
                foreach ($stepsArr as $stepId) {
                    $selectTaskDescendants += array($stepId => $stepId) + doc_UnsortedFolderSteps::getParentsArr($stepId);
                }
            }

            $stepTaskError = array();
            foreach ($stepsInTasks as $stepInTask) {
                if(!array_key_exists($stepInTask, $selectTaskDescendants)) {
                    $stepTaskError[] = "<b>" . doc_UnsortedFolderSteps::getSaoFullName($stepInTask) . "</b>";
                }
            }

            if(countR($stepTaskError)) {
                $form->setError('steps', "Следните етапи вече са избрани в задачи в проекта|*: " . implode(',', $stepTaskError));
            }

            if(!$form->gotErrors()) {

                // Ако има етапи и е избрано добавяне на децата - да се добавят
                if(!empty($rec->steps) && $rec->addSubSteps == 'yes') {
                    $expandedSteps = array();
                    $steps = keylist::toArray($rec->steps);
                    foreach ($steps as $stepId) {
                        $expandedSteps += array($stepId => $stepId) + doc_UnsortedFolderSteps::getDescendantsArr($stepId);
                    }
                    $rec->steps = keylist::fromArray($expandedSteps);
                }
            }
        }
    }


    /**
     * Изпълнява се след закачане на детайлите
     */
    public static function on_AfterAttachDetails(core_Mvc $mvc, &$res, $details)
    {
        $details = arr::make($mvc->details);
        $details['Steps'] = 'doc_UnsortedFolderSteps';
        $mvc->details = $details;
    }


    /**
     * След подготовка на филтъра за филтриране в корицата
     *
     * @param core_mvc   $mvc
     * @param core_Form  $threadFilter
     * @param core_Query $threadQuery
     * @param array $listFilterAddedFields
     */
    protected static function on_AfterPrepareThreadFilter($mvc, core_Form &$threadFilter, core_Query &$threadQuery, &$listFilterAddedFields)
    {
        $Cover = doc_Folders::getCover($threadFilter->rec->folderId);
        $coverStepArr = keylist::toArray($Cover->fetchField('steps'));
        if(!countR($coverStepArr)) return;

        $filterOptions = doc_UnsortedFolderSteps::getOptionArr($coverStepArr);
        if(!countR($filterOptions)) return;

        // Добавяме поле за избор на етапи
        $listFilterAddedFields['stepId'] = 'stepId';
        $threadFilter->FLD('stepId', 'key(mvc=doc_UnsortedFolderSteps,select=name,allowEmpty)', 'caption=Етап,silent');
        $threadFilter->showFields .= ',stepId';
        $threadFilter->input('stepId', 'silent');
        $threadFilter->input('stepId');
        $threadFilter->setOptions('stepId', array('' => '') + $filterOptions);

        // Ако търсим по група
        if ($stepId = $threadFilter->rec->stepId) {
            $threadQuery->EXT('docClass', 'doc_Containers', 'externalName=docClass,externalKey=firstContainerId');

            // Ако има филтър по етап остават само тези нишки в които има задача за този етап
            $tQuery = cal_Tasks::getQuery();
            $tQuery->where("#folderId = '{$threadFilter->rec->folderId}' AND #stepId = {$stepId}");
            $tQuery->show('threadId');
            $threadIds = arr::extractValuesFromArray($tQuery->fetchAll(),  'threadId');

            if(countR($threadIds)) {
                $threadQuery->in('id', $threadIds);
            } else {
                $threadQuery->where("1=2");
            }
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        unset($row->steps);
    }
}