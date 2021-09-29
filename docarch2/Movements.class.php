<?php

/**
 * Мениджър описващ движенията на документи и контейнери в архива
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
 * @title     Движения в архива
 */
class docarch2_Movements extends core_Master
{
    public $title = 'Движения в архива';

    public $loadList = 'plg_Created,plg_Search,plg_Printing,docarch2_Wrapper';

    public $listFields = 'type,objectId,volumeId,createdBy=Създал,createdOn=Дата';


    /**
     * Кой има право да чете?
     *
     * @var string|array
     */
    public $canRead;


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,docarch,docarchMaster';


    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canEdit = 'no_one';


    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = 'ceo,docarch,docarchMaster';


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
        //Избор на типа движение в архива
        $this->FLD('type', 'varchar(set options)', 'caption=Действие');

        //Документ - ако движението е на документ
        $this->FLD('objectId', 'int', 'caption=Документ/Том,input=hidden,silent,smartCenter');

        //Контейнер получател
        $this->FLD('volumeId', 'key(mvc=docarch2_Volumes)', 'caption= Том получател,hint = Контейнер получател');

        //Потребител получил документа или контейнера
        $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Потребител');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $types = array('docIn' => 'Архивирране на документ', 'docOut' => 'Изваждане на документ', 'volIn' => 'Включване на том',
            'volOut' => 'Изключване на том', 'docRelocation' => 'Преместване на документ', 'volRelocation' => 'Преместване на том');
        $form->setOptions('type', $types);

        //Архивиране на документ
        if (($rec->objectId && !$rec->id)) {

            $currentUser = core_Users::getCurrent();

            $posibleRegistersArr = array();
            $archArr = array();

            $Document = doc_Containers::getDocument($rec->objectId);

            $documentClassName = $Document->className;

            $documentClassId = core_Classes::getId($documentClassName);

            //Подготовка на масива с предложеня на том за архивиране на документа
            $volumeSuggestionsArr = array();

            // има ли регистри дефинирани за документи от този клас , или за всякакви документи
            $documentContainerId = ($rec->objectId);

            $regQuery = docarch2_Registers::getQuery();

            $regQuery->show('documents,users,name');

            //Има ли права текущия потребител за този регистър
            $regQuery->likeKeylist('users', $currentUser);

            //Може ли в този регистър да има документи от вида на документа
            $regQuery->likeKeylist('documents', $documentClassId);

            //Ако регистъра е от общ тип
            $regQuery->orWhere('#documents IS NULL');

            if ($regQuery->count() > 0) {
                while ($registers = $regQuery->fetch()) {
                    $posibleRegistersArr[$registers->id] = $registers->id;
                }
            }

            //Типове контейнери, които могат да съдържат документи $documentTypesArr
            $typeQuery = docarch2_ContainerTypes::getQuery();
            $typeQuery->where("#canInclude IS NULL");
            $documentTypesArr = arr::extractValuesFromArray($typeQuery->fetchall(), 'id');

            //Предложени томове за архивиране на този документ
            $volQuery = docarch2_Volumes::getQuery();
            $volQuery->in('type', $documentTypesArr);
            $volQuery->in('registerId', $posibleRegistersArr);

            expect(!empty($volQuery->fetchAll()), 'Няма регистър за този тип документи');

            while ($vRec = $volQuery->fetch()) {

                $register = ($vRec->registerId == '0') ? 'Сборен' : docarch2_Registers::fetch($vRec->registerId)->name;

                $volName = docarch2_ContainerTypes::fetch($vRec->type)->name;

                $volumeSuggestionsArr[$vRec->id] = $volName . '-No' . $vRec->number . ' / регистър: ' . $register;
            }

            expect($Document->haveRightFor('single'), 'Недостатъчни права за този документ.');

            $form->setOptions('volumeId', $volumeSuggestionsArr);

            $types = array('docIn' => 'Архивирране на документ');
            $form->setOptions('type', $types);

            $form->setField('userId', 'input=hidden');
            $form->setDefault('userId', "{$currentUser}");
        }

    }


    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_Form $form
     * @param stdClass $data
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {

        }
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
        //Брояч на документи в томовете
        //Масив с дейности, които увеличават броя на документите в тома
        $incrementMoves = array('archiving');

        //Масив с дейности, които намаляват броя на документите в тома
        $decrementMoves = array('taking');


        if (in_array($rec->type, $incrementMoves)) {
            if (!is_null($rec->toVolumeId)) {
                $volRec = docarch_Volumes::fetch($rec->toVolumeId);
            }

            $volRec->_isCreated = true;

            if (is_null($volRec->docCnt) || $volRec->docCnt == 0) {
                $volRec->firstDocDate = $rec->createdOn;
            }

            if ($rec->type == 'archiving') {
                $volRec->lastDocDate = $rec->createdOn;
            }

            $volRec->docCnt++;

            docarch_Volumes::save($volRec);
        }

        if (in_array($rec->type, $decrementMoves)) {
            if (!is_null($rec->toVolumeId)) {
                $volRec = docarch_Volumes::fetch($rec->toVolumeId);
            }

            $volRec->_isCreated = true;

            $volRec->docCnt--;


            docarch_Volumes::save($volRec, 'docCnt');
        }
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
        $data->toolbar->removeBtn('btnPrint');

        if ($data->filterCheck) {
            $documentContainerId = ($data->listFilter->rec->objectId);

            $Document = doc_Containers::getDocument($documentContainerId);
            $documentName = $Document->singleTitle . '-' . $Document->getHandle();
            $data->title = "Движение в архива на: {$documentName}";

            //Архивиран ли e документа в момента
            if ($documentContainerId) {
                //Проверявам дали е архивиран в момента
                $isArchive = docarch2_State::getDocumentState($documentContainerId)->volumeId;
            }

            //Извежда бутона "Вземане" ако докумета е в поне един том
            if ($isArchive) {
                $data->toolbar->addBtn('Изваждане', array($mvc, 'DocOut',
                    'objectId' => $documentContainerId,
                    'ret_url' => true));
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

        $data->listFilter->FNC('filterVolumeId', 'key(mvc=docarch_Volumes,allowEmpty, select=title)', 'caption=Входящ том,placeholder=Входящ том');

        $data->listFilter->showFields = 'filterVolumeId';

        $data->listFilter->FNC('filterObjectId', 'key(mvc=doc_Containers)', 'caption=Документ,input=hidden,silent');

        $data->listFilter->showFields .= ',search,filterObjectId';

        $data->listFilter->input(null, true);

        if ($data->listFilter->isSubmitted() || $data->listFilter->rec->objectId) {
            if ($data->listFilter->rec->volumeId) {
                $data->query->where(array("#volumeId = '[#1#]'", $data->listFilter->rec->volumeId));
            }

            if ($data->listFilter->rec->objectId) {
                $data->query->where(array("#objectId = '[#1#]'", $data->listFilter->rec->objectId));

                $data->filterCheck = true;
            }
        }

        // Сортиране на записите по дата на създаване
        $data->query->orderBy('#createdOn', 'DESC');
    }


    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $Enum = cls::get('type_Enum', array('options' => array('docIn' => 'Архивирране на документ', 'docOut' => 'Изваждане на документ', 'volIn' => 'Включване на том',
            'volOut' => 'Изключване на том', 'docMove' => 'Преместване на документ', 'volRelocation' => 'Преместване на том')));


        $url = toUrl(array($mvc, 'single', $rec->id));
        $mov = $Enum->toVerbal($rec->type);
        $row->type = ht::createLink($mov, $url, false, array());

        $row->objectId = '';
        if (($rec->objectId && in_array($rec->type,array('docIn','docOut','docMove')))) {
            $Document = doc_Containers::getDocument($rec->objectId);

            $className = $Document->className;

            $handle = $Document->singleTitle . '-' . $Document->getHandle();

            $url = toUrl(array("${className}", 'single', $Document->that));

            $row->objectId .= ht::createLink($handle, $url, false, array());

        }

        if ($rec->objectId && in_array($rec->type,array('volIn','volOut','volRelocation'))) {

            $row->objectId .= docarch2_Volumes::getHyperlink($rec->objectId);
            $row->volumeId = docarch2_Volumes::getHyperlink($rec->volumeId);

        }

        //Ако движението е "Архивиране на документ"
        if ($rec->type == 'docIn') {
            if ($rec->volumeId) {
                $row->volumeId = docarch2_Volumes::getHyperlink($rec->volumeId) . '</br>';

            }
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

    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        // Прави запис в модела на състоянията
        if ($rec->documentId) {
            $movies = self::getBalanceOfDocumentMovies($rec->objectId);
        }

        if(in_array($rec->type,array('docIn','docOut','docMove'))){

        $Document = doc_Containers::getDocument($rec->objectId);
        $documentName = $Document->singleTitle . '-' . $Document->getHandle();
        $docRec = $Document->className::fetch($Document->that);
    }
        $volumeId = ($rec->volumeId) ? $rec->volumeId : 'Получена от: ' . $rec->recipientId;

        $mRec = (object)array('documentId' => $rec->objectId,
            'volumeId' => $volumeId,
            'userId' => $rec->userId,
            'recipientId' => $rec->recipientId,
            'state' => 'active',
            'createdOn' => dt::now(),
            'searchKeywords' => $docRec->searchKeywords,
            'movieType' => $rec->type,
        );

        $object = ($rec->objectId) ? $rec->objectId : $rec->id;
        $stQuery = docarch2_State::getQuery();
        $stQuery->where("#documentId = $object");

        //Премахваме всички записи за този документ/том от модела за състояниета
        //за да запишем само последното
        if ($stQuery->count() > 0) {
            while ($stRec = $stQuery->fetch()) {

                $stRec->state = 'rejected';
                cls::get('docarch2_State')->save($stRec, 'state');

            }

        }

        cls::get('docarch2_State')->save_($mRec);
    }


    /**
     * Връща броя архиви към документа
     *
     * @param int $containerId - ид на контейнера
     *
     * @return string $html - броя документи
     */
    public static function getSummary($containerId)
    {
        $html = '';

        //Колко пъти е бил архивиран.
        $archCnt = 0;

        $balanceDocMove = self::getBalanceOfDocumentMovies($containerId);

        if ($balanceDocMove[$containerId]->countMovie > 0) {
            $count = cls::get('type_Int')->toVerbal($balanceDocMove[$containerId]->countMovie);
            $actionVerbal = tr('архиви2');
            $Document = doc_Containers::getDocument($containerId);

            if ($Document->haveRightFor('single')) {
                $linkArr = array('docarch2_State', 'documentId' => $containerId,'ret_url' => true);
            }

            $link = ht::createLink("<b>{$count}</b><span>{$actionVerbal}</span>", $linkArr, false, array());

            $html .= "<li class=\"action archiveSummary\">{$link}</li>";
        }

        return $html;
    }


    /**
     * Вземане документ от том
     */
    public function act_DocOut()
    {
        /**
         * Установява необходима роля за да се стартира екшъна
         */

        requireRole('docarch');

        $form = cls::get('core_Form');
        $form->title = 'Вземане на документ';
        $form->FLD('objectId', 'int', 'input=hidden,silent');
        $form->input(null, true);

        $form->FLD('type', 'enum(docOut=Вземане)', 'caption=Действие, hint=Вземане на документ, ');

        $form->input(null, 'silent');

        $Document = doc_Containers::getDocument($form->rec->objectId);
        $documentName = $Document->singleTitle . '-' . $Document->getHandle();

        $form->title .= ' ' . $documentName;

        $documentLastState = docarch2_State::getDocumentState($form->rec->objectId);

        //$form->FLD('volumeId', 'key(mvc=docarch_Volumes, select=title)', 'caption=том, input=hidden');

        //  $form->setOptions('volumeId', $volumeSuggestionsArr);

        //Потребител получил документа или контейнера
        $form->FLD('userId', 'key(mvc=core_Users)', 'caption= Оператор,readOnly');
        $form->FLD('recipientId', 'key(mvc=core_Users)', 'caption=Приел, hint = На кого е предаден документа');

        $currentUser = core_Users::getCurrent();
        $form->setDefault('userId', "{$currentUser}");
        $form->setDefault('operUserId', "{$currentUser}");

        $form->setReadOnly('userId');
        $form->rec->volumeId = null;

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');

        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        $form->input();

        if ($form->isSubmitted()) {

            $this->save($form->rec);

            return new Redirect(getRetUrl());
        }

        return $this->renderWrapping($form->renderHtml());
    }


    /**
     * Включва един том в по-голям
     */
    public function act_VolIn()
    {
        /**
         * Установява необходима роля за да се стартира екшъна
         */
        requireRole('docarch');

        $volIn = new stdClass();
        $mRec = new stdClass();

        $form = cls::get('core_Form');

        $thisVolId = Request::get('id');


        $thisVolRec = docarch2_Volumes::fetch($thisVolId);

        $volIn->id = $thisVolId;

        $thisVolName = docarch2_Volumes::getRecTitle($thisVolRec);

        $form->title = "Включване на том|* ' " . ' ' . $thisVolName . "' ||*";

        //В кой по голям том се включва
        $form->FLD('volumeId', 'key(mvc=docarch2_Volumes)', 'caption=Включи в,input');

        $form->FLD('objectId', 'key(mvc=docarch2_Volumes)', 'caption=Том,input = hidden');

        $options = docarch2_Volumes::getVolumePossibleForInclude($thisVolRec);

        $form->setOptions('volumeId', $options);

        $form->input(null, true);

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');

        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        $mRec = $form->input();

        $volIn->id = $thisVolId;

        if ($form->isSubmitted()) {

            //по-малкия том
            $volIn = (object)array(
                'id' => $thisVolId,
                'parentId' => $mRec->volumeId,
            );

            $mRec->objectId = $thisVolId;
            $mRec->type = 'volIn';

            docarch2_Volumes::save($volIn,'parentId');

            $this->save($mRec);

            return new Redirect(getRetUrl());
        }

        return $this->renderWrapping($form->renderHtml());
    }


    /**
     * Изключва един том от по-голям
     */
    public function act_VolOut()
    {
        /**
         * Установява необходима роля за да се стартира екшъна
         */
        requireRole('docarch');

        $volOut = new stdClass();
        $mRec = new stdClass();

        $thisVolId = Request::get('id');

        $thisVolRec = docarch2_Volumes::fetch($thisVolId);


        //Тома в който е включен (по-големия том)
        $upVolId = docarch2_Volumes::fetch($thisVolRec->parentId)->id;
        $upVolTitle = docarch2_Volumes::getTitleById($thisVolRec->parentId);

        $volOut->parentId = null;

        $volOut->id = $thisVolId;

        $mRec = (object)array(
            'objectId' => $thisVolId,
            'type' => 'volOut',
        );

        $this::save($mRec);

        docarch2_Volumes::save($volOut);

        return new Redirect(getRetUrl());
    }

    /**
     * Преместване един том
     */
    public function act_VolRelocation()
    {
        /**
         * Установява необходима роля за да се стартира екшъна
         */
        requireRole('docarch');

        $volRelocation = new stdClass();
        $mRec = new stdClass();

        $form = cls::get('core_Form');

        $thisVolId = Request::get('id');


        $thisVolRec = docarch2_Volumes::fetch($thisVolId);

        $volRelocation->id = $thisVolId;

        $thisVolName = docarch2_Volumes::getRecTitle($thisVolRec);

        $form->title = "Преместване на том|* ' " . ' ' . $thisVolName . "' ||*";

        //В кой по голям том се премести
        $form->FLD('volumeId', 'key(mvc=docarch2_Volumes)', 'caption=Премести в,input');

        $form->FLD('objectId', 'key(mvc=docarch2_Volumes)', 'caption=Том,input = hidden');

        $options = docarch2_Volumes::getVolumePossibleForInclude($thisVolRec);

        $form->setOptions('volumeId', $options);

        $form->input(null, true);

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');

        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        $mRec = $form->input();

        $volRelocation->id = $thisVolId;

        if ($form->isSubmitted()) {

            //по-малкия том
            $volRelocation = (object)array(
                'id' => $thisVolId,
                'parentId' => $mRec->volumeId,
            );

            $mRec->objectId = $thisVolId;
            $mRec->type = 'volRelocation';

            docarch2_Volumes::save($volRelocation,'parentId');

            $this->save($mRec);

            return new Redirect(getRetUrl());
        }

        return $this->renderWrapping($form->renderHtml());
    }

    /**
     * Преместване Документ
     */
    public function act_DocRelocation()
    {
        /**
         * Установява необходима роля за да се стартира екшъна
         */
        requireRole('docarch');

        $docRelocation = new stdClass();
        $mRec = new stdClass();

        $form = cls::get('core_Form');

        $thisDocumentContainerId = Request::get('objectId');

        $thisDocument =doc_Containers::getDocument($thisDocumentContainerId);

        $thisDocRec = $thisDocument->className::fetch($thisDocument->that);

        $docRelocation->id = $thisDocument->that;

        $handle = $thisDocument->className::getHandle($thisDocument->that);

        $form->title = "Преместване на документ|* ' " . ' ' . $handle . "' ||*";

        //В кой том да се премести
        $form->FLD('volumeId', 'key(mvc=docarch2_Volumes)', 'caption=Премести в,input');

       // $form->FLD('objectId', 'key(mvc=docarch2_Volumes)', 'caption=Том,input = hidden');

        $options = docarch2_Volumes::getVolumePossibleForArhiveDocument($thisDocRec);
bp($options);
        $form->setOptions('volumeId', $options);

        $form->input(null, true);

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');

        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        $mRec = $form->input();

        $volRelocation->id = $thisDocument;

        if ($form->isSubmitted()) {

            //по-малкия том
            $volRelocation = (object)array(
                'id' => $thisDocument,
                'parentId' => $mRec->volumeId,
            );

            $mRec->objectId = $thisDocument;
            $mRec->type = 'volRelocation';

            docarch2_Volumes::save($volRelocation,'parentId');

            $this->save($mRec);

            return new Redirect(getRetUrl());
        }

        return $this->renderWrapping($form->renderHtml());
    }


    /**
     * Взема името на действието
     *
     * @param string $type -ключа на името на типа
     *
     * @return string
     */
    public static function getMoveName($type)
    {
        switch ($type) {

            case 'creating':
                $typeName = 'Създаване';
                break;

            case 'archiving':
                $typeName = 'Архивиране';
                break;

            case 'taking':
                $typeName = 'Изваждане';
                break;

            case 'destruction':
                $typeName = 'Унищожаване';
                break;

            case 'include':
                $typeName = 'Включване';
                break;

            case 'exclude':
                $typeName = 'Изключване';
                break;

            case 'deleting':
                $typeName = 'Изтриване';
                break;

            case 'edit':
                $typeName = 'Редактиране';
                break;

        }

        return $typeName;
    }


    /**
     * Връща баланса на движенията на документ в том / томове
     *
     * @param string $containerId -контернер Id на документа
     * @param int $volume -Id на тома. Ако е null връща баланса на движенията във всички томове.
     *
     * @return array
     */
    public static function getBalanceOfDocumentMovies($containerId, $arch = null)
    {
        $balanceOfDocumentMovies = array();

        $mQuery = self::getQuery();

        $mQuery->where('#objectId IS NOT NULL');

        $mQuery->where("#objectId = ${containerId}");

        $mQuery->orderBy('createdOn', 'ASC');

        //Ако документа никога не е архивиран връща null
        if ($mQuery->count() == 0) {

            return $balanceOfDocumentMovies = null;
        }

        while ($movie = $mQuery->fetch()) {

            if (!is_null($movie->objectId) && $movie->objectId == $containerId) {
                expect(in_array($movie->type, array('docIn', 'docOut')));

                $counter = $movie->type == 'docIn' ? 1 : -1;

                if (!array_key_exists($movie->objectId, $balanceOfDocumentMovies)) {
                    $balanceOfDocumentMovies[$movie->objectId] = (object)array(
                        'objectId' => $movie->objectId,
                        'volumeId' => $movie->volumeId,
                        'isArchive' => $counter,
                        'countMovie' => 1
                    );
                } else {
                    $obj = &$balanceOfDocumentMovies[$movie->objectId];
                    $obj->isInArchive += $counter;
                    $obj->countMovie++;
                }
            }
        }

        return $balanceOfDocumentMovies;
    }
}
