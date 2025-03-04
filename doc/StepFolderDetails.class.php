<?php


/**
 * Детайли на етапите в папките
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_StepFolderDetails extends core_Manager
{



    /**
     * Поготовка на проектите като детайл на етапите
     *
     * @param stdClass $data
     */
    public function prepareStepFolders(&$data)
    {
        $data->TabCaption = tr('Папки');

        $data->recs = $data->rows = array();
        $children = $data->masterMvc->getDescendantsArr($data->masterData->rec);
        $children[$data->masterId] = $data->masterId;
        foreach (array('doc_UnsortedFolders', 'support_Systems') as $class) {
            $Class = cls::get($class);
            $fields = $Class->selectFields();
            $fields['-list'] = true;

            $uQuery = $Class->getQuery();
            $uQuery->where("#state != 'rejected'");
            $uQuery->likeKeylist('steps', $children);
            while($rec = $uQuery->fetch()) {
                $rec->_fields = $fields;
                $rec->_class = $Class;
                $data->recs["{$class}|{$rec->id}"] = $rec;
            }
        }


        $data->Pager = cls::get('core_Pager', array('itemsPerPage' => 20));
        $data->count = countR($data->recs);
        $data->Pager->itemsCount = $data->count;
        foreach($data->recs as $key => $rec) {
            if (!$data->Pager->isOnPage()) continue;
            $data->rows[$key] = $rec->_class->recToVerbal($rec, $rec->_fields);
        }
    }


    /**
     * Рендиране на проектите като детайл на етапите
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderStepFolders(&$data)
    {
        $tpl = new core_ET('');

        // Рендиране на таблицата с оборудването
        $data->listFields = arr::make('name=Корица,folder=Папка,createdOn=Създадено->На,createdBy=Създадено->От');
        $listTableMvc = clone $this;
        $listTableMvc->FLD('name','varchar',  'tdClass=leftCol wrap');
        $table = cls::get('core_TableView', array('mvc' => $listTableMvc));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $tpl->append($table->get($data->rows, $data->listFields));
        if ($data->Pager) {
            $tpl->append($data->Pager->getHtml());
        }

        $resTpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $resTpl->append($tpl, 'content');
        $resTpl->append(tr("Папки|* ({$data->count})"), 'title');

        return $resTpl;
    }
}