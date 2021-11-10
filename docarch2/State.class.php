<?php

/**
 * Състояние
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
 * @title     Състояние
 */
class docarch2_State extends core_Master
{
    public $title = 'Състояние';

    public $loadList = 'plg_Created,plg_Search, plg_RowTools2, plg_State2, plg_Rejected,plg_Modified,docarch2_Wrapper';

    public $listFields = 'documentId,volumeId,userId,createdOn,createdBy';


    /**
     * Кой може да оттегля?
     */
    public $canReject = 'ceo,docarchMaster';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,docarch,docarchMaster';


    /**
     * Кой има право да чете?
     *
     * @var string|array
     */
    public $canRead = 'ceo,docarchMaster,docarch';


    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canEdit = 'ceo,docarchMaster';


    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = 'ceo,docarchMaster';


    /**
     * Кой може да го види?
     *
     * @var string|array
     */
    public $canView = 'ceo,docarchMaster,docarch';


    /**
     * Кой може да го изтрие?
     *
     * @var string|array
     */
    public $canDelete = 'no_one';


    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {
        //Документ containerId
        $this->FLD('documentId', 'key(mvc=doc_Containers)', 'caption=Документ/Том,input = hidden,silent');

        //къде се намира
        $this->FLD('volumeId', 'key(mvc=docarch2_Volumes)', 'caption=Том,input = hidden');

        //Отговорния потребител за последното действие
        $this->FLD('userId', 'int', 'caption=Потребител,input = hidden');

        //Получател на документ или том
        $this->FLD('recipientId', 'int', 'caption=Получил,input = hidden');

        //Получател на документ или том
        $this->FLD('movieType', 'varchar', 'caption=Движение,input = hidden');

    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;

    }


    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc $mvc Мениджър, в който възниква събитието
     * @param int $id Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass $rec Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields Имена на полетата, които трябва да бъдат записани
     * @param string $mode Режим на записа: replace, ignore
     */
    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {

    }


    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {

//        bp($rec);

    }


    /**
     * Преди показване на листовия тулбар
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->removeBtn('btnAdd');
    }


    /**
     * Добавя бутони  към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        ;
    }

    /**
     * Какви са полетата на таблицата
     */
    public static function on_AfterPrepareListFields($mvc, &$res, $data)
    {

    }


    /**
     * Преди подготовка на полетата за показване в списъчния изглед
     */
    public static function on_AfterPrepareListRows($mvc, $data)
    {

        if (!countR($data->recs)) {

            return;
        }

        $recs = &$data->recs;
        $rows = &$data->rows;
        $masterRec = $data->masterData->rec;

        foreach ($rows as $id => &$row) {
            $rec = $recs[$id];
            if ($rec->recipientId && $rec->volumeId == 0) {
                $row->volumeId = 'Получил: ' . core_Users::getNick($rec->recipientId);
            }

        }


    }

    /**
     * Филтър
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {

        $data->listFilter->view = 'horizontal';

        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');

        $data->listFilter->FNC('registerIdFilter', 'varchar', 'caption=Регистри,placeholder=Регистър,silent');

        $registersForChois = self::suggestionRegisters();

        $data->listFilter->FNC('volumeIdFilter', 'varchar', 'caption=Томове,placeholder=Томове,input = hidden');

        $data->listFilter->setOptions('registerIdFilter', array('' => ' ', '0' => 'Сборен') + $registersForChois);

        $data->listFilter->showFields = 'registerIdFilter';

        $data->listFilter->showFields .= ',volumeIdFilter';

        $data->listFilter->showFields .= ',search';

        $data->listFilter->input();

        if ($data->listFilter->isSubmitted()) {

            if (is_numeric($data->listFilter->rec->registerIdFilter)) {

                $volumessForChois = self::suggestionVolumes($data->listFilter->rec->registerIdFilter);

                $data->listFilter->setOptions('volumeIdFilter', array('' => ' ') + $volumessForChois);

                $data->query->EXT('registerId', 'docarch2_Volumes', 'externalName=registerId,externalKey=volumeId');

                $data->query->where(array("#registerId = '[#1#]'", $data->listFilter->rec->registerIdFilter));

                $data->listFilter->setField('volumeIdFilter', 'input,silent');

                //Филтър по том, задължително трябва да има филтър по регистър преди това
                if (($data->listFilter->rec->volumeIdFilter)) {

                    $data->query->where(array("#volumeId = '[#1#]'", $data->listFilter->rec->volumeIdFilter));
                }


            }
        }

    }

    /**
     * Връща предложения за избор на регистър в лист филтъра
     */
    public static function suggestionRegisters()
    {
        $regQuery = docarch2_Registers::getQuery();

        $registersArr = array();

        while ($register = $regQuery->fetch()) {

            $registersArr[$register->id] = type_Varchar::escape($register->name);
        }

        return $registersArr;
    }

    /**
     * Връща предложения за избор на том в лист филтъра
     */
    public static function suggestionVolumes($registerId)
    {
        $volQuery = docarch2_Volumes::getQuery();
        $volQuery->where("#registerId = $registerId");


        $volumesArr = array();

        while ($volume = $volQuery->fetch()) {

            $type = docarch2_ContainerTypes::fetch($volume->type)->name;

            $volumesArr[$volume->id] = type_Varchar::escape($type) . 'No' . $volume->number;
        }

        return $volumesArr;
    }

    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {

        $row->documentId = '';
        if ($rec->documentId) {
            if (in_array($rec->movieType, array('docIn', 'docOut', 'docMove'))) {
                $Document = doc_Containers::getDocument($rec->documentId);

                $className = $Document->className;

                $handle = $Document->singleTitle . '-' . $Document->getHandle();

                $url = toUrl(array("${className}", 'single', $Document->that));

                $row->documentId .= ht::createLink($handle, $url, false, array());
            } elseif (in_array($rec->movieType, array('volIn', 'volOut', 'volRelocation'))) {
                $row->documentId .= docarch2_Volumes::getHyperlink($rec->documentId);
            }
        }
        if ($rec->volumeId) {
            $row->volumeId = docarch2_Volumes::getHyperlink($rec->volumeId);
        }
        if ($rec->movieType == 'volOut'){
            $row->volumeId = 'Свободен' ;
        }

    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {

    }

    public static function getDocumentState($objectId)
    {
        $sQuery = docarch2_State::getQuery();
        $sQuery->where("#documentId = $objectId");
        $sQuery->orderBy('createdOn', 'desc');
        $sQuery->show('volumeId');
        $sQuery->limit(1);
        $state = $sQuery->fetch();
        return $state;
    }


}
