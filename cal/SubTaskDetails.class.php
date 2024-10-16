<?php


/**
 * Помощен детайл подготвящ показването на подзадачите на дадена задача
 *
 * @category  bgerp
 * @package   cal
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cal_SubTaskDetails extends core_Manager
{
    /**
     * Подготовка на Детайлите
     */
    public function prepareDetail_($data)
    {
        $masterRec = $data->masterData->rec;
        $taskRecs = $data->recs = $data->rows = array();
        cal_Tasks::expandChildrenArr($taskRecs, $masterRec->id);

        foreach ($taskRecs as $taskRec){
            $row = cal_Tasks::recToVerbal($taskRec);
            $row->modified = "{$row->modifiedOn} " . tr('от') . "{$row->modifiedBy}";

            $depth = countR($taskRec->_path) - 1;
            $indent = ($depth > 0) ? $depth * 15 : 0;

            $row->title = ht::createElement("div", array('style' => "margin-left:{$indent}px;", ''), $row->title);
            $row->state = "<span class='state-{$taskRec->state} document-handler'>{$row->state}</span>";
            $data->recs[$taskRec->id] = $taskRec;
            unset($row->ROW_ATTR);
            $data->rows[$taskRec->id] = $row;
        }

        $countSubTasks = countR($data->recs);
        if($countSubTasks){

            $cQuery = doc_Comments::getQuery();
            $cQuery->where(array("#originId = '[#1#]'", $masterRec->containerId));
            $cQuery->where(array("#driverClass = '[#1#]'", cal_Progresses::getClassId()));
            $cQuery->where("#state != 'draft'");
            if($cQuery->count()){
                $data->TabCaption = "Подзадачи|* ({$countSubTasks})";
                $data->Tab = 'top';
            }
        } else {
            $data->hide = true;
        }
    }


    /**
     * Рендиране на детайл
     */
    public function renderDetail_($data)
    {
        if($data->hide) return null;

        $tpl = new ET('<div class="clearfix21 portal" style="margin-top:20px;background-color:transparent;">
                            <div class="legend" style="background-color:#ffc;font-size:0.9em;padding:2px;color:black">' . tr('Подзадачи') . '</div>
                            <div class="listRows" style="margin-top:10px">
                            [#TABLE#]
                            </div>
	                   </div>');

        $listTableMvc = clone $data->masterMvc;
        $listTableMvc->setField('title', 'tdClass=leftCol');
        $listTableMvc->setField('state', 'smartCenter');
        $table = cls::get('core_TableView', array('mvc' => $listTableMvc));
        $data->listFields = arr::make('title=Задача,progress=Прогрес,state,expectationTimeEnd=Оч. край,assign=Възложено на,modified=Промяна', true);

        $listTableMvc->invoke('BeforeRenderListTable', array($tpl, &$data));
        $data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, 'expectationTimeEnd,assign');
        $tableTpl = $table->get($data->rows, $data->listFields);
        $tpl->replace($tableTpl, 'TABLE');

        return $tpl;
    }
}