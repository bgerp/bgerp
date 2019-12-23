<?php
/**
 * Мениджър описващ движенията на документи и контейнери в архива
 *
 *
 * @category  bgerp
 * @package   docarch
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Движения в архива
 */
class docarch_Movements extends core_Master
{
    public $title = 'Движения в архива';
    
    public $loadList = 'plg_Created,plg_Search,docarch_Wrapper';
    
    public $listFields = 'type,documentId,position,createdBy=Създал,createdOn=Дата';
    
    
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
        $this->FLD('documentId', 'key(mvc=doc_Containers)', 'caption=Документ/Том/Потребител,input=hidden,silent,tdClass=wideColumn');
        $this->FLD('documentDate', 'date', 'caption=Дата на документа,input=hidden,silent');
        
        //Изходящ том участващ в движението
        $this->FLD('fromVolumeId', 'key(mvc=docarch_Volumes)', 'caption=Контейнер');
        
        //Входящ том участващ в движението
        $this->FLD('toVolumeId', 'key(mvc=docarch_Volumes)', 'caption=Входящ том');
        
        //Позиция в тома
        $this->FLD('position', 'varchar()', 'caption=Позиция,input=none');
        
        
        //Потребител получил документа или контейнера
        $this->FLD('userID', 'key(mvc=core_Users)', 'caption=Потребител');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param embed_Manager $Embedder
     * @param stdClass      $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        $types = array('creating' => 'Създаване', 'archiving' => 'Архивиране', 'taking' => 'Изваждане',
            'destruction' => 'Унищожаване', 'include' => 'Включване', 'exclude' => 'Изключване');
        $form->setOptions('type', $types);
        
        //Архивиране на документ
        if (($rec->documentId && !$rec->id)) {
            $arcivesArr = array();
            $archArr = array();
            
            $Document = doc_Containers::getDocument($rec->documentId);
            
            $documentClassName = $Document->className;
            
            $documentClassId = core_Classes::getId($documentClassName);
            
            //Подготовка на масива с предложеня на том за архивиране на документа
            $volumeSuggestionsArr = array();
            
            // има ли архиви дефинирани за документи от този клас , или за всякакви документи
            $documentContainerId = ($rec->documentId);
            
            $archQuery = docarch_Archives::getQuery();
            
            $archQuery->show('documents');
            
            $archQuery->likeKeylist('documents', $documentClassId);
            
            $archQuery->orWhere('#documents IS NULL');
            
            if ($archQuery->count() > 0) {
                while ($arcives = $archQuery->fetch()) {
                    $arcivesArr[$arcives->id] = $arcives->id;
                }
            }
            if (!key_exists(0, $arcivesArr)) {
                array_unshift($arcivesArr, '0');
            }
            
            // Има ли в тези архиви томове дефинирани да архивират документи, с отговорник текущия потребител
            $volQuery = docarch_Volumes::getQuery();
            
            //В кои томове е архивиран към настоящия момент този документ.
            $balanceDocMove = self::getBalanceOfDocumentMovies($documentContainerId);
            
            if (is_array($balanceDocMove)) {
                foreach ($balanceDocMove as $val) {
                    if ($val->isInArchive == 1) {
                        $archArr[$val->archive] = $val->archive;
                    }
                }
            }
            
            if (!empty($archArr)) {
                foreach ($archArr as $v) {
                    unset($arcivesArr[$v]);
                }
            }
            
            $volQuery->in('archive', $arcivesArr);
            
            $currentUser = core_Users::getCurrent();
            
            $volQuery->where("#isForDocuments = 'yes' AND #state = 'active' AND #inCharge = '{$currentUser}'");
            
            while ($vRec = $volQuery->fetch()) {
                $classArrId = explode('|', trim(docarch_Archives::fetch($vRec->archive)->documents, '|'));
                
                if ((!is_null(docarch_Archives::fetch($vRec->archive)->documents)) && (!in_array($documentClassId, $classArrId))) {
                    continue;
                }
                
                $arch = ($vRec->archive == 0) ? 'Сборен' : docarch_Archives::fetch($vRec->archive)->name;
                
                $volName = docarch_Volumes::getVolumeTypeName($vRec->type);
                
                $volumeSuggestionsArr[$vRec->id] = $volName .'-No'.$vRec->number.' / архив: '.$arch;
            }
            
            expect($Document->haveRightFor('single'), 'Недостатъчни права за този документ.');
            
            $form->setOptions('toVolumeId', $volumeSuggestionsArr);
            
            $form->setField('fromVolumeId', 'input=none');
            
            $types = array('archiving' => 'Архивиране');
            $form->setOptions('type', $types);
            
            $form->setField('userID', 'input=hidden');
            $currentUser = core_Users::getCurrent();
            $form->setDefault('userID', "{$currentUser}");
        }
        
        if (($rec->documentId && $rec->id)) {
        }
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $form->rec->position = docarch_Volumes::fetch($form->rec->toVolumeId)->title;
        }
    }
    
    
    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc    Мениджър, в който възниква събитието
     * @param int          $id     Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec    Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields Имена на полетата, които трябва да бъдат записани
     * @param string       $mode   Режим на записа: replace, ignore
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
     * Преди показване на листовия тулбар
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->removeBtn('btnAdd');
        
        if ($data->filterCheck) {
            $documentContainerId = ($data->listFilter->rec->document);
            
            $Document = doc_Containers::getDocument($documentContainerId);
            $documentName = $Document->singleTitle.'-'.$Document->getHandle();
            $data->title = "Движение в архива на: {$documentName}";
            
            //Архивиран ли е поне в един том документа
            if ($documentContainerId) {
                $archCnt = 0;
                
                $balanceDocMove = self::getBalanceOfDocumentMovies($documentContainerId);
                
                if (is_array($balanceDocMove)) {
                    foreach ($balanceDocMove as $val) {
                        if ($val->isInVolume == 1) {
                            $archCnt++;
                        }
                    }
                }
            }
            
            //Извежда бутона "Вземане" ако докумета е в поне един том
            if ($archCnt > 0) {
                $data->toolbar->addBtn('Вземане', array($mvc, 'Taking',
                    'documentId' => $documentContainerId,
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
        
        $data->listFilter->FNC('toVolume', 'key(mvc=docarch_Volumes,allowEmpty, select=title)', 'caption=Входящ том,placeholder=Входящ том');
        
        $data->listFilter->showFields = 'toVolume';
        
        $data->listFilter->FNC('document', 'key(mvc=doc_Containers)', 'caption=Документ,input=hidden,silent');
        
        $data->listFilter->showFields .= ',search,document';
        
        $data->listFilter->input(null, true);
        
        if ($data->listFilter->isSubmitted() || $data->listFilter->rec->document) {
            if ($data->listFilter->rec->toVolume) {
                $data->query->where(array("#toVolumeId = '[#1#]'", $data->listFilter->rec->toVolume));
            }
            
            if ($data->listFilter->rec->document) {
                $data->query->where(array("#documentId = '[#1#]'", $data->listFilter->rec->document));
                
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
        $movieName = self::getMoveName($rec->type);
        
        $row->type = $movieName;
        $row->documentId = '';
        if (($rec->documentId)) {
            $Document = doc_Containers::getDocument($rec->documentId);
            
            $className = $Document->className;
            
            $handle = $Document->singleTitle.'-'.$Document->getHandle();
            
            $url = toUrl(array("${className}",'single', $Document->that));
            
            $row->documentId .= ht::createLink($handle, $url, false, array());
            
            $row->documentId .= ' » ';
        }
        
        //Ако движението е "Включване"
        if ($rec->type == 'include') {
            list($position, $volumes) = explode('»', $rec->position);
            
            list($downVol, $upVol) = explode('|', $volumes);
            
            list($downVolId, $downVolTitle) = explode('*', $downVol);
            
            list($upVolId, $upVolTitle) = explode('*', $upVol);
            
            if (docarch_Volumes::fetch($downVolId) === false) {
                $row->documentId .= $downVolTitle.'(Изтрит)'.' » ';
            } else {
                $row->documentId .= docarch_Volumes::getHyperlink($downVolId).' » ';
            }
            
            if (docarch_Volumes::fetch($upVolId) === false) {
                $row->documentId .= $upVolTitle.'(Изтрит)'.' » ';
            } else {
                $row->documentId .= docarch_Volumes::getHyperlink($upVolId);
            }
            
            $row->position = $position;
        }
        
        
        //Ако движението е "Изваждане"
        if ($rec->type == 'taking') {
            if ($rec->toVolumeId) {
                $userUrl = crm_Profiles::getUrl($rec->userID);
                $userNick = core_Users::getNick($rec->userID);
                
                if (docarch_Volumes::fetch($rec->toVolumeId) === false) {
                    $row->documentId .= $rec->position.'(Изтрит)'.'</br>';
                } else {
                    $row->documentId .= docarch_Volumes::getHyperlink($rec->toVolumeId).'</br>';
                }
                $row->documentId .= 'Получил:'.ht::createLink($userNick, $userUrl, false, array());
            }
            
            $row->position = '';
        }
        
        //Ако движението е "Архивиране"
        if ($rec->type == 'archiving') {
            if ($rec->toVolumeId) {
                if (docarch_Volumes::fetch($rec->toVolumeId) === false) {
                    $row->documentId .= $rec->position.'(Изтрит)'.'</br>';
                } else {
                    $row->documentId .= docarch_Volumes::getHyperlink($rec->toVolumeId).'</br>';
                }
            }
            $row->position = '';
        }
        
        //Ако движението е "Изтриване"
        if ($rec->type == 'deleting') {
            $row->documentId .= $rec->position;
            $row->position = '';
        }
        
        //Ако движението е "Изключване"
        if ($rec->type == 'exclude') {
            list($downVol, $upVol) = explode('|', $rec->position);
            
            list($downVolId, $downVolTitle) = explode('*', $downVol);
            
            list($upVolId, $upVolTitle) = explode('*', $upVol);
            
            if (docarch_Volumes::fetch($downVolId) === false) {
                $row->documentId .= $downVolTitle.'(Изтрит)'.' » ';
            } else {
                $row->documentId .= docarch_Volumes::getHyperlink($downVolId).' » ';
            }
            
            if (docarch_Volumes::fetch($upVolId) === false) {
                $row->documentId .= $upVolTitle.'(Изтрит)'.' » ';
            } else {
                $row->documentId .= docarch_Volumes::getHyperlink($upVolId);
            }
            
            $row->position = '';
        }
        
        //Ако движението е "Създаване"
        if ($rec->type == 'creating') {
            list($id, $className, $title) = explode('|', $rec->position);
            
            expect($className, $id);
            
            $className = cls::get($className)->className;
            if (!$className::fetch($id)) {
                $row->documentId .= $title.'(Изтрит)';
            } else {
                $row->documentId .= $className::getHyperlink($id);
            }
            $row->position = '';
        }
        
        //Ако движението е "Редактиране"
        if ($rec->type == 'edit') {
            list($id, $className, $title) = explode('|', $rec->position);
            
            expect($className, $id);
            
            $className = cls::get($className)->className;
            if (!$className::fetch($id)) {
                $row->documentId .= $title.'(Изтрит)';
            } else {
                $row->documentId .= $className::getHyperlink($id);
            }
            $row->position = '';
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc      $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param stdClass|NULL $rec
     * @param int|NULL      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
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
        
        //На колко места е архивиран този документ.
        $archCnt = 0;
        
        $balanceDocMove = self::getBalanceOfDocumentMovies($containerId);
        
        if (is_array($balanceDocMove)) {
            foreach ($balanceDocMove as $val) {
                if ($val->isInVolume == 1) {
                    $archCnt++;
                }
            }
        }
        if ($archCnt > 0) {
            $count = cls::get('type_Int')->toVerbal($archCnt);
            $actionVerbal = tr('архиви');
            $Document = doc_Containers::getDocument($containerId);
            
            if ($Document->haveRightFor('single')) {
                $linkArr = array('docarch_Movements', 'document' => $containerId, 'ret_url' => true);
            }
            
            $link = ht::createLink("<b>{$count}</b><span>{$actionVerbal}</span>", $linkArr, false, array());
            
            $html .= "<li class=\"action archiveSummary\">{$link}</li>";
        }
        
        return $html;
    }
    
    
    /**
     * Вземане документ от том
     */
    public function act_Taking()
    {
        /**
         * Установява необходима роля за да се стартира екшъна
         */
        requireRole('docarch');
        
        $form = cls::get('core_Form');
        $form->title = 'Вземане на документ';
        $form->FLD('documentId', 'int', 'input=hidden,silent');
        $form->input(null, true);
        
        $form->FLD('type', 'enum(taking=Изваждане)', 'caption=Действие');
        
        //Подготовка на масива с предложеня на том от който да се извади документа
        $volumeSuggestionsArr = array();
        
        $form->input(null, 'silent');
        
        $balanceDocMove = self::getBalanceOfDocumentMovies($form->rec->documentId);
        
        if (is_array($balanceDocMove)) {
            foreach ($balanceDocMove as $val) {
                if ($val->isInVolume == 1) {
                    $volRec = docarch_Volumes::fetch($val->toVolumeId);
                    $volumeSuggestionsArr[$val->toVolumeId] = docarch_Volumes::getRecTitle($volRec);
                }
            }
        }
        
        
        $form->FLD('toVolumeId', 'key(mvc=docarch_Volumes, select=title)', 'caption=От кой том');
        
        $form->setOptions('toVolumeId', $volumeSuggestionsArr);
        
        //Потребител получил документа или контейнера
        $form->FLD('userID', 'key(mvc=core_Users)', 'caption=Потребител');
        
        $currentUser = core_Users::getCurrent();
        $form->setDefault('userID', "{$currentUser}");
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');
        
        $form->input();
        
        if ($form->isSubmitted()) {
            $form->rec->position = docarch_Volumes::fetch($form->rec->toVolumeId)->title;
            
            $this->save($form->rec);
            
            return new Redirect(getRetUrl());
        }
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Включва един том в по-голям
     */
    public function act_Include()
    {
        /**
         * Установява необходима роля за да се стартира екшъна
         */
        requireRole('docarch');
        
        $includeRec = new stdClass();
        $mRec = new stdClass();
        
        $form = cls::get('core_Form');
        
        $thisVolId = Request::get('id');
        
        $thisVolRec = docarch_Volumes::fetch($thisVolId);
        
        $includeRec->id = $thisVolId;
        
        $thisVolName = docarch_Volumes::getVerbal($thisVolRec, 'title');
        
        $form->FLD('type', 'enum(include=Включване)', 'caption=Действие');
        
        $form->title = "Включване на том|* ' " . ' ' . $thisVolName . "' ||*";
        
        //В кой по голям том се включва
        $form->FLD('fromVolumeId', 'key(mvc=docarch_Volumes, select=title)', 'caption=Включен в,input');
        
        $form->FLD('position', 'varchar(32)', 'caption=Позиция,after=toVolumeId');
        
        $form->FLD('documentId', 'varchar(32)', 'input=hidden');
        
        $options = docarch_Volumes::getVolumePossibleForInclude($thisVolRec);
        
        $form->setOptions('fromVolumeId', $options);
        
        $form->FLD('toVolumeId', 'key(mvc=docarch_Volumes,allowEmpty, select=title)', 'caption=Включен в,input=hidden');
        
        $form->setDefault('toVolumeId', $thisVolId);
        
        $form->input(null, true);
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');
        
        
        $mRec = $form->input();
        
        $includeRec->id = $thisVolId;
        
        if ($form->isSubmitted()) {
            
            //по-малкия том
            $includeRec = (object) array(
                'id' => $thisVolId,
                'includeIn' => $mRec->fromVolumeId,
                'position' => $mRec->position,
                '_isCreated' => true
            );
            
            docarch_Volumes::save($includeRec);
            
            $upVolId = $mRec->fromVolumeId;
            $upVolTitle = $upVolTitle = docarch_Volumes::fetch($upVolId)-> title;
            
            
            $mRec->position .= '»'.$thisVolId.'*'.$thisVolRec->title.'|'.$upVolId.'*'.$upVolTitle;
            
            $this->save($mRec);
            
            return new Redirect(getRetUrl());
        }
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Изключва един том от по-голям
     */
    public function act_Exclude()
    {
        /**
         * Установява необходима роля за да се стартира екшъна
         */
        requireRole('docarch');
        
        $ExcludeRec = new stdClass();
        $mRec = new stdClass();
        
        $thisVolId = Request::get('id');
        
        $thisVolRec = docarch_Volumes::fetch($thisVolId);
        
        $upVolId = docarch_Volumes::fetch($thisVolRec->includeIn)-> id;
        $upVolTitle = docarch_Volumes::fetch($thisVolRec->includeIn)-> title;
        
        $ExcludeRec->includeIn = null;
        
        $ExcludeRec->id = $thisVolId;
        
        $ExcludeRec->_isCreated = true;
        
        $pos = $thisVolId.'*'.$thisVolRec->title.'|'.$upVolId.'*'.$upVolTitle;
        
        
        $mRec = (object) array(
            'type' => 'exclude',
            'position' => $pos,
        );
        
        $this::save($mRec);
        
        docarch_Volumes::save($ExcludeRec);
        
        return new Redirect(getRetUrl());
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
            
            case 'creating':$typeName = 'Създаване'; break;
            
            case 'archiving':$typeName = 'Архивиране'; break;
            
            case 'taking':$typeName = 'Изваждане'; break;
            
            case 'destruction':$typeName = 'Унищожаване'; break;
            
            case 'include':$typeName = 'Включване'; break;
            
            case 'exclude':$typeName = 'Изключване'; break;
            
            case 'deleting':$typeName = 'Изтриване'; break;
            
            case 'edit':$typeName = 'Редактиране'; break;
        
        }
        
        return $typeName;
    }
    
    
    /**
     * Връща баланса на движенията на документ в том / томове
     *
     * @param string $containerId -контернер Id на документа
     * @param int    $volume      -Id на тома. Ако е null връща баланса на движенията във всички томове.
     *
     * @return array
     */
    public static function getBalanceOfDocumentMovies($containerId, $arch = null)
    {
        $balanceOfDocumentMovies = array();
        
        $mQuery = self::getQuery();
        
        $mQuery->where('#documentId IS NOT NULL');
        
        $mQuery->where("#documentId = ${containerId}");
        
        $mQuery->orderBy('createdOn', 'ASC');
        
        //Ако документа никога не е архивиран връща null
        if ($mQuery->count() == 0) {
            
            return $balanceOfDocumentMovies = null;
        }
        
        while ($movie = $mQuery->fetch()) {
            if (!is_null($arch) && $arch != docarch_Volumes::fetch($movie->toVolumeId)->archive) {
                continue;
            }
            
            if (!is_null($movie->documentId) && $movie->documentId == $containerId) {
                expect(in_array($movie->type, array('archiving','taking')));
                
                if (!is_null($movie->toVolumeId)) {
                    $archive = docarch_Volumes::fetch($movie->toVolumeId)->archive;
                }
                
                $counter = $movie->type == 'archiving' ? 1 : -1;
                
                if (! array_key_exists($archive, $balanceOfDocumentMovies)) {
                    $balanceOfDocumentMovies[$archive] = (object) array(
                        'documentId' => $movie->documentId,
                        'toVolumeId' => $movie->toVolumeId,
                        'archive' => $archive,
                        'isInArchive' => $counter,
                        'isInVolume' => $counter
                    );
                } else {
                    $obj = & $balanceOfDocumentMovies[$archive];
                    $obj->isInArchive += $counter;
                    $obj->isInVolume += $counter;
                }
            }
        }
        
        return $balanceOfDocumentMovies;
    }
}
