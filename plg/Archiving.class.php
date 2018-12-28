<?php

/**
 * Клас 'plg_Archiving' -Плъгин за архивиране на документи
 *
 *
 * @category  bgerp
 * @package   plg
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class plg_Archiving extends core_Plugin
{

    /**
     * Добавя бутон за архивиране към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        $rec = &$data->rec;
        $arcivesArr = array();

        // има ли архиви дефинирани за документи от този клас , или за всякакви документи
        $docClassId = $mvc->getClassId();

        $mQuery = docarch_Archives::getQuery();
        $mQuery->show('documents');
        $mQuery->likeKeylist('documents', $docClassId);
        $mQuery->orWhere("#documents IS NULL");

        if (! empty($mQuery->fetchAll())) {
            while ($arcives = $mQuery->fetch()) {
                $arcivesArr[] = $arcives->id;
            }

            // Има ли в тези архиви томове дефинирани да архивират документи, с отговорник текущия потребител
            $volQuery = docarch_Volumes::getQuery();
            $volQuery->in('archive', $arcivesArr);
            $currentUser = core_Users::getCurrent();
            $volQuery->where("#isForDocuments = 'yes' AND #inCharge = $currentUser");
            if ($volQuery->count() > 0) {
                $data->toolbar->addBtn('Архивиране', array('docarch_Movements', 'Add', 'documentId' => $docClassId, 'ret_url' => true),"ef_icon=img/16/clone.png,row=2");
            }
        }
    }
}