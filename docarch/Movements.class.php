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
        $this->FLD('documentId', 'key(mvc=doc_Containers)', 'caption=Документ/Том/Потребител,input=hidden,silent');
        
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
            
            if($rec->type == 'archiving' ){
                
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
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListRows($mvc, $data)
    {
        $recs = &$data->recs;
        $rows = &$data->rows;
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
                
                $data->toolbar->addBtn('Унищожаване', array($mvc, 'Action'));
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
        
        if (($rec->documentId)) {
            $Document = doc_Containers::getDocument($rec->documentId);
            
            $className = $Document->className;
            
            $handle = $Document->singleTitle.'-'.$Document->getHandle();
            
            $url = toUrl(array("${className}",'single', $Document->that));
            
            $row->documentId = ht::createLink($handle, $url, false, array());
            
            
            // има ли архиви дефинирани за документи от този клас , или за всякакви документи
            $docClassId = core_Classes::getId($className);
            
            $documentContainerId = $rec->documentId;
            
            $archQuery = docarch_Archives::getQuery();
            
            $archQuery->show('documents');
            
            $archQuery->likeKeylist('documents', $docClassId);
            
            $archQuery->orWhere('#documents IS NULL');
            
            if ($archQuery->count() > 0) {
                while ($arcives = $archQuery->fetch()) {
                    $arcivesArr[$arcives->id] = $arcives->id;
                }
                
                // Има ли в тези архиви томове дефинирани да архивират документи, с отговорник текущия потребител
                $volQuery = docarch_Volumes::getQuery();
                
                $volQuery->in('archive', $arcivesArr);
                
                $currentUser = core_Users::getCurrent();
                
                // Има ли в тези архиви томове дефинирани да архивират документи, с отговорник текущия потребител
                $volQuery = docarch_Volumes::getQuery();
                
                //В кои архиви е архивиран към настоящия момент този документ.
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
                
                $volQuery->where("#isForDocuments = 'yes' AND #inCharge = ${currentUser} AND #state = 'active'");
                
                $lastMovieMark = false;
                $lastMovieId = self::getLastMovieOfDocument($documentContainerId);
                if ($lastMovieId == $rec->id) {
                    $lastMovieMark = true;
                }
                
                if ((($volQuery->count() > 0) && (!empty($archArr) || is_null($archArr))) && $lastMovieMark) {
                    //       $row->type .= '<span class = fright>'.ht::createBtn('Архив', array($mvc,'Add', 'documentId' => $documentContainerId, 'ret_url' => true)).'</span>';
                }
                
                $row->documentId .= ' » ';
            }
        }
        
        //Ако движението е "Включване"
        if ($rec->type == 'include') {
            if ($rec->toVolumeId) {
                $row->documentId = docarch_Volumes::getHyperlink($rec->toVolumeId).' » ';
            }
            
            if ($rec->fromVolumeId) {
                $row->documentId .= docarch_Volumes::getHyperlink($rec->fromVolumeId);
            }
        }
        
        
        //Ако движението е "Изваждане"
        if ($rec->type == 'taking') {
            if ($rec->toVolumeId) {
                $userUrl = crm_Profiles::getUrl($rec->userID);
                $userNick = core_Users::getNick($rec->userID);
                
                
                $row->documentId .= docarch_Volumes::getHyperlink($rec->toVolumeId).'</br>';
                
                $row->documentId .= 'Получил:'.ht::createLink($userNick, $userUrl, false, array());
            }
        }
        
        
        //Ако движението е "Архивиране"
        if ($rec->type == 'archiving') {
            if ($rec->toVolumeId) {
                $row->documentId .= docarch_Volumes::getHyperlink($rec->toVolumeId);
            }
        }
        
        
        //Ако движението е "Изключване"
        if ($rec->type == 'exclude') {
            list($vol, $upvol) = explode('|', $rec->position);
            
            $row->documentId .= docarch_Volumes::getHyperlink($vol).' » ';
            $row->documentId .= docarch_Volumes::getHyperlink($upvol);
            
            $row->position = '';
        }
        
        
        //Ако движението е "Създаване"
        if ($rec->type == 'creating') {
            list($id, $className) = explode('|', $rec->position);
            
            expect($className, $id);
            
            $className = cls::get($className);
            
            $row->documentId .= $className->getHyperlink($id);
            
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
     * Изважда документ от том
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
        
        $ExcludeRec->includeIn = null;
        
        $ExcludeRec->id = $thisVolId;
        
        $ExcludeRec->_isCreated = true;
        
        $pos = $thisVolId.'|'.$upVolId;
        
        
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
                $toVolumeId = $movie->toVolumeId ;
                
                $counter = $movie->type == 'archiving' ? 1 : -1;
                
                if (($movie->type == 'taking') && ($archive == $oldArchive)) {
                    //           $archive = $oldArchive ;
                }
                
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
                
                $oldArchive = $archive;
            }
        }
        
        return $balanceOfDocumentMovies;
    }
    
    
    /**
     * Връща последното движение на документ
     *
     * @param string $containerId -контернер Id на документа
     *
     * @return array
     */
    public static function getLastMovieOfDocument($containerId)
    {
        $lastMovie = array();
        
        $mQuery = self::getQuery();
        
        $mQuery->where('#documentId IS NOT NULL');
        
        $mQuery->where("#documentId = ${containerId}");
        
        $mQuery->orderBy('createdOn', 'DESC');
        
        $mQuery->limit(1);
        
        while ($movie = $mQuery->fetch()) {
            $lastMovieId = $movie->id;
        }
        
        return $lastMovieId;
    }
}
