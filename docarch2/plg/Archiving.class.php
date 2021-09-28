<?php


/**
 * Клас 'docarch2_plg_Archiving' -Плъгин за архивиране
 *
 *
 * @category  bgerp
 * @package   docarch2
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class docarch2_plg_Archiving extends core_Plugin
{
    /**
     * Добавя бутон за архивиране към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        $currentUser = core_Users::getCurrent();
        $intfCond = in_array('doc_DocumentIntf',cls::get($mvc)->interfaces);
        
        if ((!core_Packs::isInstalled('docarch2'))  || !$intfCond) {
            
            return;
        }
        $rec = &$data->rec;
        $posibleRegistersArr = array();
        $archArr = array();
        
        
        // има ли регистри дефинирани за документи от този клас , или за всякакви документи
        $docClassId = $mvc->getClassId();
        
        $documentContainerId = ($rec->containerId);
        
        $registersQuery = docarch2_Registers::getQuery();

        $registersQuery->show('documents');

        $registersQuery->likeKeylist('documents', $docClassId);        //дали може да се архивира документ от този тип

        $registersQuery->orWhere('#documents IS NULL');                // ако регистъра е от общ тип
        
        if ($registersQuery->count() > 0) {

            $posibleRegistersArr = arr::extractValuesFromArray($registersQuery->fetchAll(),'id');

            //Проверявам дали е архивиран в момента
            $isArchive = docarch2_State::getDocumentState($documentContainerId)->volumeId;
;

            // Има ли във възможните регистри томове дефинирани да архивират документи, с отговорник текущия потребител
            $volQuery = docarch2_Volumes::getQuery();

            $volQuery->EXT('regUsers', 'docarch2_Registers', 'externalName=users,externalKey=registerId');

            $volQuery->in('registerId', $posibleRegistersArr);

            $volQuery->likeKeylist('regUsers', $currentUser);
            //Добавяне на бутон за АРХИВИРАНЕ във второто меню
            //Ако има том който да отговатя на условията за него, показва бутон за архивиране
            if (!$isArchive && in_array($rec->state, array('active','closed')) && $volQuery->count() > 0) {
                $data->toolbar->addBtn(
                    'Архивиране2',
                    array(
                        'docarch2_Movements',
                        'Add',
                        'objectId' => $documentContainerId,
                        'ret_url' => true
                    ),
                                                             'ef_icon=img/16/archive.png,row=2'
                );
            }

            //Добавяне на бутон за ПРЕМЕСТВАНЕ във второто меню
            //Ако има том който да отговатя на условията за него, показва бутон за архивиране

            $arrForReloc = $volQuery->fetchAll();
          //  bp($arrForReloc,docarch2_State::getDocumentState($rec->containerId),$rec);
            $docArchiveState = docarch2_State::getDocumentState($rec->containerId);
            if($docArchiveState && !empty($arrForReloc)){
                unset($arrForReloc[$docArchiveState->volumeId]);
            }

            if ($isArchive && in_array($rec->state, array('active','closed')) && !empty($arrForReloc)) {
                $data->toolbar->addBtn(
                    'Преместване2',
                    array(
                        'docarch2_Movements',
                        'docRelocation',
                        'objectId' => $documentContainerId,
                        'ret_url' => true
                    ),
                    'ef_icon=img/16/archive.png,row=2'
                );
            }

            //Ако документа е архивиран в моментата и тома не е вложен в по-голям активираме бутона за изваждане
            if ($isArchive && !docarch2_Volumes::fetch($isArchive)->volumeId) {

                $data->toolbar->addBtn(
                    'Изваждане2',
                    array(
                        'docarch2_Movements',
                        'DocOut',
                        'objectId' => $documentContainerId,
                        'ret_url' => true
                    ),
                    'ef_icon=img/16/archive.png,row=2'
                );
            }


        }
    }
    
    
    /**
     * Показва допълнителни действие в doclog история
     *
     * @param core_Master $mvc
     * @param string|null $html
     * @param int         $containerId
     * @param int         $threadId
     */
    public static function on_RenderOtherSummary($mvc, &$html, $containerId, $threadId)
    {
        if (!(core_Packs::isInstalled('docarch2'))) {
            
            return;
        }

        $html .= docarch2_Movements::getSummary($containerId);
    }

    /**
     * @return string
     */
    public function act_Action()
    {
        /**
         * Установява необходима роля за да се стартира екшъна
         */
        requireRole('admin');

        $text = 'Това е съобщение за изтекъл срок';
        $msg = new core_ET($text);

        $url = array(
            'docarch2_Volumes',
            'single',
            109
        );

        $msg = $msg->getContent();


        bgerp_Notifications::add($msg, $url, 1219);
    }
}
