<?php


/**
 * Клас 'doc_Files' - Всички файлове в документите
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class doc_Files extends core_Manager
{


    /**
     * Разширения, които да не се показват по подразбиране
     */
    protected $exludeFilesExt = array('eml');


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'doc_Wrapper, plg_Sorting, plg_GroupByDate';
    
    
    /**
     * Заглавие
     */
    public $title = 'Файлове в папка';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го редактира
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Полетата, които ще се показват
     */
    public $listFields = 'fileHnd=Файл, threadId=Документ, date=Час';
    
    
    /**
     * По кое поле да се групира
     *
     * @see plg_GroupByDate
     */
    public $groupByDateField = 'date';
    
    
    public function description()
    {
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Контейнер,notNull,value=0');
        $this->FLD('folderId', 'key(mvc=doc_Folders)', 'caption=Папка,notNull,value=0');
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка,notNull,value=0');
        $this->FLD(
            'fileHnd',
            'varchar(' . strlen(fileman_Setup::get('HANDLER_PTR')) . ')',
            array('notNull' => true, 'caption' => 'Манипулатор')
        );
        $this->FLD('dataId', 'key(mvc=fileman_Data)', 'caption=Данни');
        $this->FLD('show', 'enum(yes,no)', 'caption=Показване');
        $this->FNC('date', 'datetime', 'caption=Дата,input=none');
        
        $this->setDbUnique('containerId, fileHnd');
        
        $this->setDbIndex('containerId');
        $this->setDbIndex('folderId');
        $this->setDbIndex('dataId, folderId');
        $this->setDbIndex('show');
        $this->setDbIndex('show, fileHnd');
    }
    
    
    /**
     * Връща най-доброто място където се намира файла
     *
     * @param string $fh
     *
     * @return array
     */
    public static function getBestContainer($fh, $fInterface = null)
    {
        $fRec = fileman::fetchByFh($fh);
        
        expect($fRec);
        
        $resArr = array();
        
        // Масив с баркодовете
        $barcodesArr = fileman_Indexes::getInfoContentByFh($fRec->fileHnd, 'barcodes');
        
        // Ако има масив и съдържанието е празно
        if (is_array($barcodesArr)) {
            foreach ($barcodesArr as $barcodesArrPage) {
                foreach ($barcodesArrPage as $barcodeObj) {
                    
                    // Вземаме cid'a на баркода
                    $cid = doclog_Documents::getDocumentCidFromURL($barcodeObj->code);
                    
                    if ($cid) {
                        $doc = doc_Containers::getDocument($cid);
                        
                        $dRec = $doc->fetch();
                        
                        if ($dRec->state == 'rejected') {
                            continue;
                        }
                        
                        if ($fInterface && $dRec->folderId) {
                            $fRec = doc_Folders::fetchRec($dRec->folderId);
                            
                            if (!cls::haveInterface($fInterface, $fRec->coverClass)) {
                                continue;
                            }
                        }
                        
                        $resArr['folderId'] = $dRec->folderId;
                        $resArr['threadId'] = $dRec->threadId;
                        $resArr['containerId'] = $dRec->containerId;
                        
                        break;
                    }
                }
            }
        }
        
        if (!empty($resArr)) {
            
            return $resArr;
        }
        
        $query = self::getQuery();
        $query->where(array("#dataId = '[#1#]'", $fRec->dataId));
        $query->orderBy('show', 'ASC');
        
        while ($rec = $query->fetch()) {
            if ($fInterface && $rec->folderId) {
                $fRec = doc_Folders::fetchRec($rec->folderId);
                
                if (!cls::haveInterface($fInterface, $fRec->coverClass)) {
                    continue;
                }
            }
            
            $resArr['folderId'] = $rec->folderId;
            $resArr['threadId'] = $rec->threadId;
            $resArr['containerId'] = $rec->containerId;
        }
        
        return $resArr;
    }

    
    /**
     * Преизчислява дали да се показват файловете или не
     *
     * @param int $cId
     */
    public static function recalcFiles($cId)
    {
        if (!$cId) {
            
            return ;
        }
        
        $query = self::getQuery();
        $query->where(array("#containerId = '[#1#]'", $cId));
        
        $updateArr = array('hide' => array(), 'show' => array());
        
        $rArr = array();
        
        // Всички файлове от контейнера
        while ($rec = $query->fetch()) {
            $dataId = $rec->dataId;
            if (!$dataId) {
                $rec->show = 'no';
                self::save($rec, 'show');
                
                continue;
            }
            
            $rArr[] = array('dataId' => $dataId, 'folderId' => $rec->folderId);
        }
        
        foreach ($rArr as $dArr) {
            $dQuery = self::getQuery();
            $dQuery->where(array("#dataId = '[#1#]'", $dArr['dataId']));
            
            if ($dArr['folderId']) {
                $dQuery->where(array("#folderId = '[#1#]'", $dArr['folderId']));
            }
            
            $hideArr = array();
            $bestRec = null;
            
            // Всички файлове, които се съдържат в същата папка
            while ($dRec = $dQuery->fetch()) {
                $containerId = $dRec->containerId;
                
                if (!$containerId) {
                    $hideArr[$dRec->id] = $dRec;
                    continue;
                }
                
                $cRec = doc_Containers::fetch($containerId);
                
                if ($cRec->state == 'rejected') {
                    $hideArr[$dRec->id] = $dRec;
                    continue;
                }
                
                if (!isset($bestRec) || ($bestRec->CreatedOn > $cRec->createdOn)) {
                    if (isset($bestRec)) {
                        $hideArr[$bestRec->id] = $bestRec;
                    }
                    
                    $bestRec = $dRec;
                    $bestRec->CreatedOn = $cRec->createdOn;
                } else {
                    $hideArr[$dRec->id] = $dRec;
                }
            }
            
            $updateArr['hide'][] = $hideArr;
            $updateArr['show'][] = $bestRec;
        }
        
        // Скриваме файлове, които не трябва да се показват
        foreach ($updateArr['hide'] as $hideArr) {
            foreach ($hideArr as $hRec) {
                if (!$hRec->show || $hRec->show != 'no') {
                    $hRec->show = 'no';
                    self::save($hRec, 'show');
                }
            }
        }
        
        // Показваме файла
        foreach ($updateArr['show'] as $bestRec) {
            if (isset($bestRec) && (!$bestRec->show || $bestRec->show != 'yes')) {
                $bestRec->show = 'yes';
                self::save($bestRec, 'show');
            }
        }
    }
    
    
    /**
     *
     *
     * @param int $cId
     */
    public static function deleteFilesForContainer($cId)
    {
        if (!$cId) {
            
            return ;
        }
        
        return self::delete(array("#containerId = '[#1#]'", $cId));
    }
    
    
    /**
     * Записваме/Обновяваме всики файлове в модела
     *
     * @param core_Mvc $invoker - Класа, за който се отнася
     * @param object   $rec     - Записите за съответния модел
     */
    public static function saveFile($invoker, $rec)
    {
        // Вземаме данните
        $id = $rec->id;
        $containerId = $rec->containerId;
        $folderId = $rec->folderId;
        $threadId = $rec->threadId;
        
        $show = 'yes';
        if ($rec->state == 'rejected') {
            $show = 'no';
        }
        
        // Очакваме да има id
        expect($id);
        
        // Ако няна контейнер
        if (!$containerId) {
            
            // Намираме контейнера
            $rec = $invoker->fetch($id);
            $containerId = $rec->containerId;
        }
        
        // Очакваме да има такъв запис
        expect($containerId);
        
        // Вземаме всички линкнати файлове
        $linked = (array) $invoker->getLinkedFiles($rec);
        
        // Ако няма папка или тред
        if (!$folderId || !$threadId) {
            
            // Записите за контейнера
            $cRec = doc_Containers::fetch($containerId);
        }
        
        // Ако няма папка
        if (!$folderId) {
            $folderId = $cRec->folderId;
        }
        
        // Ако няма нишка
        if (!$threadId) {
            $threadId = $cRec->threadId;
        }
        
        $savedFh = array();
        
        // Вземаме всички файлове, за съответния контейнер (когато редактираме запис)
        $query = static::getQuery();
        $query->where("#containerId = '{$containerId}'");
        while ($fRec = $query->fetch()) {
            
            // Всички файлове от предишния запис
            $savedFh[$fRec->fileHnd] = $fRec->fileHnd;
        }

        // Обхождаме всички линкнати файлове
        foreach ($linked as $fh => $name) {
            // Данните за файла
            $dataId = fileman_Files::fetchByFh($fh, 'dataId');
            
            // Ако няма данни, да не се записва
            if (!$dataId) {
                continue;
            }
            
            // Ако файла е бил записан
            if ($savedFh[$fh]) {
                
                // Премахваме от масива
                unset($savedFh[$fh]);
                continue;
            }
            
            // Създаваме запис
            $nRec = new stdClass();
            $nRec->containerId = $containerId;
            $nRec->folderId = $folderId;
            $nRec->threadId = $threadId;
            $nRec->fileHnd = $fh;
            $nRec->dataId = $dataId;
            $nRec->show = $show;
            
            static::save($nRec, null, 'IGNORE');
        }
        
        // Ако са останали файлоаве, които не са премахнати от записите
        if (countR($savedFh)) {
            
            // Обхождаме всики останали файлове
            foreach ($savedFh as $fileHnd => $dummy) {
                
                // Изтриваме ги от модела
                static::delete("#fileHnd = '{$fileHnd}' AND #containerId = '{$containerId}'");
            }
        }
        
        self::recalcFiles($containerId);
    }
    
    
    /**
     * Връща контейнерите с документи, в които се използва съответния файл
     *
     * @param int      $dataId
     * @param int|NULL $clsId
     * @param int      $resLimit
     * @param int      $qLimit
     * @param bool     $restrictAccess
     * @param bool     $restricViewAccess
     *
     * @return array
     */
    public static function getCidWithFile($dataId, $clsId = null, $resLimit = 5, $qLimit = 100, $restrictAccess = true, $restricViewAccess = true)
    {
        $fQuery = self::getQuery();
        $fQuery->where(array("#dataId = '[#1#]'", $dataId));
        
        if ($restrictAccess) {
            doc_Threads::restrictAccess($fQuery, null, true);
        }
        
        $fQuery->limit($qLimit);
        
        $fQuery->orderBy('id', 'DESC');
        
        $resArr = array();
        
        while ($fRec = $fQuery->fetch()) {
            if (!$fRec->containerId) {
                continue;
            }
            
            $cRec = doc_Containers::fetch($fRec->containerId);
            
            if ($cRec->state == 'rejected') {
                continue;
            }
            
            if ($clsId && $cRec->docClass == $clsId) {
                $resArr[$fRec->containerId] = $fRec->containerId;
            }
            
            if (!--$resLimit) {
                break;
            }
        }
        
        return $resArr;
    }


    /**
     * Връща `id` на папката, която ще се използва в `range`
     *
     * @param $folderId
     */
    public static function getFolderRange($folderId)
    {

        return "__folder__"  .$folderId;
    }

    
    /**
     *
     *
     * @param doc_Files $mvc
     * @param stdClass  $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->where("#show = 'yes' OR #show IS NULL");
        
        $sPrefix = '__';
        $folderPrefix = $sPrefix . 'folder__';
        
        // Подготваме масив за избор
        $suggArr = array();
        $suggArr[$sPrefix . 'myFiles'] = 'Моите файлове';
        $suggArr[$folderPrefix . 'allFolders'] = 'Всички папки';
        
        // Последните разгледани папки на текущия потребител
        $lastFoldersArr = (array) bgerp_Recently::getLastFolderIds(5);

        $lastFolderId = Mode::get('lastfolderId');

        if ($lastFolderId && !$lastFoldersArr[$lastFolderId]) {
            $lastFoldersArr[$lastFolderId] = $lastFolderId;
        }

        foreach ($lastFoldersArr as $folderId) {
            if ($folderId) {
                $fRec = doc_Folders::fetch($folderId);
                $suggArr[$folderPrefix . $folderId] = $fRec->title;
            }
        }
        
        // Показваме избор на потребители
        if (haveRole('debug')) {
//         if (haveRole('admin, manager, ceo')) {
            $Users = cls::get('type_Users', array('params' => array('rolesForTeams' => 'admin, ceo, manager', 'rolesForAll' => 'ceo')));
            $uArr = $Users->prepareOptions();
            if (is_array($uArr) && !empty($uArr)) {
                $suggArr += $uArr;
            }
        }
        
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('search', 'varchar', 'caption=Ключови думи,input,silent,recently,inputmode=search');
        $data->listFilter->FNC('range', 'varchar', 'caption=Обхват,input,silent,autoFilter');
        
        $data->listFilter->setOptions('range', $suggArr);
        $data->listFilter->setDefault('range', key($suggArr));
        
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Търсене', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->showFields = 'search,range';
        
        $data->listFilter->input(null, 'silent');
        
        $usersArr = null;
        $filter = $data->listFilter->rec;

        $fSearch = '';

        if ($filter->range) {
            // Ако се филтрира по папките на текущия потребител или файловете му
            if (stripos($filter->range, $sPrefix) === 0) {
                // Търсене по папка
                if (stripos($filter->range, $folderPrefix) === 0) {
                    $fSearch = substr($filter->range, strlen($folderPrefix));
                    
                    // Търсене по всички папки
                    if ($fSearch == 'allFolders') {
                        $data->query->isSlowQuery = true;
                        $data->query->useCacheForPager = true;
                        doc_Threads::restrictAccess($data->query);
                    } else {
                        // Показваме файловете в папката
                        // Ако има права за сингъла на папка няма нужда от restrictAccess
                        expect(is_numeric($fSearch));
                        $fRec = doc_Folders::fetch($fSearch);
                        doc_Folders::requireRightFor('single', $fRec);
                        $data->query->where(array("#folderId = '[#1#]'", $fSearch));
                    }
                    
                    // Подреждаме по последно модифициране на контейнера
                    $data->query->EXT('cModifiedOn', 'doc_Containers', 'externalKey=containerId, externalName=modifiedOn');
                    $data->query->orderBy('cModifiedOn', 'DESC');
                } else {
                    // Търсене по мои файлове
                    $usersArr = array(core_Users::getCurrent());
                }
            } else {
                // Ако се търси по потребител
                expect(isset($Users));
                $userList = $Users->fromVerbal($filter->range);
                $usersArr = type_Keylist::toArray($userList);
            }
            
            if (isset($usersArr)) {
                $data->query = fileman_Files::getQuery();
                if ($usersArr[-1]) {
                    $data->query->isSlowQuery = true;
                    $data->query->useCacheForPager = true;
                }
                
                // TODO - след JOIN може да се увеличи с restrictAccess
                fileman_Files::prepareFilesQuery($data->query, $usersArr);
            }
        }

        $fSearchStr = '';
        foreach ($mvc->exludeFilesExt as $fExt) {
            $fExtQ = preg_quote($fExt, '/');
            if (!$filter->search || !preg_match("/(\.|\s|^|\-)+({$fExtQ})(\.|\s|$)+/i", $filter->search)) {
                $fSearchStr .= " -.{$fExt}";
            }
        }

        // Скриваме html файловете
        if (!$filter->search || !preg_match("/(\.|\s|^|\-)+(html)(\.|\s|$)+/i", $filter->search)) {
            $data->query->where("#searchKeywords NOT REGEXP '([0-9]+ )+([a-f0-9]{6}) html'");
        }

        // Премахваме нашите файлове
        $ourImgArr = core_Permanent::get('ourImgEmailArr');
        if ($ourImgArr) {
            $data->query->notIn('dataId', $ourImgArr);
        }

        // Налагане на условията за търсене
        if (!empty($filter->search) || !empty($fSearchStr)) {
            $data->query->EXT('searchKeywords', 'fileman_Data', 'externalKey=dataId');

            plg_Search::applySearch($filter->search . $fSearchStr, $data->query, 'searchKeywords');
        }
    }
    
    
    /**
     *
     * @param doc_Files $mvc
     * @param stdClass  $row
     * @param stdClass  $rec
     */
    public static function on_BeforeRecToVerbal($mvc, $row, $rec)
    {
        // Определяме датата
        setIfNot($rec->date, $rec->lastUse, $rec->lastOn, $rec->cModifiedOn);
        if (!isset($rec->date)) {
            $fRec = fileman_Files::fetchByFh($rec->fileHnd);
            $rec->date = $fRec->createdOn;
        }
        
        // TODO - ще се премахне след JOIN
        // Определяме най-добрия контейнер, в който да се показва файла
        if (!isset($rec->containerId)) {
            $query = self::getQuery();
            
            $query->where("#show = 'yes'");
            $query->where(array("#fileHnd = '[#1#]'", $rec->fileHnd));
            
            $query->orderBy('id', 'DESC');
            
            while ($oRec = $query->fetch()) {
                if (!$oRec->containerId) {
                    continue ;
                }
                
                try {
                    $doc = doc_Containers::getDocument($oRec->containerId);
                } catch (ErrorException $e) {
                    continue;
                }
                
                if ($doc->haveRightFor('single')) {
                    $dRec = $doc->fetch();
                    
                    $rec->containerId = $dRec->containerId;
                    $rec->threadId = $dRec->threadId;
                    $rec->folderId = $dRec->folderId;
                    
                    break;
                }
            }
        }
    }
    
    
    /**
     * Връща броя на файловете в съответната папка
     *
     * @param doc_Folders $folderId - id' то на папката, за която търсим
     *
     * @return int $count - Броя файловете
     */
    public static function getCountInFolder($folderId = null)
    {
        // Ако не е подадено $folderId, използваме id' то на последно активната папка
        $folderId = ($folderId) ? ($folderId) : mode::get('lastfolderId');
        
        // Ако все още няма папка, връщаме 0
        if (!$folderId) {
            
            return 0;
        }
        
        // Вземаме броя на файловете в папката
        $count = static::count("#folderId = '{$folderId}'");
        
        return $count;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass     $row Това ще се покаже
     * @param stdClass     $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $url = null;
        if ($rec->containerId) {
            try {
                // Документа
                $doc = doc_Containers::getDocument($rec->containerId);
                
                // Полетата на документа във вербален вид
                $docRow = $doc->getDocumentRow();
                
                // Атрибутеите на линка
                $attr = array();
                $attr['title'] = '|*' . $docRow->title;
                
                // Документа да е линк към single' а на документа
                $row->threadId = $doc->getLink(35, $attr);
            } catch (ErrorException $e) {
                // Не се прави нищо
            }
            
            try {
                // id' то на контейнера на пъривя документ
                $firstContainerId = doc_Threads::fetchField($rec->threadId, 'firstContainerId');
                if ($firstContainerId != $rec->containerId) {
                    
                    // Първия документ в нишката
                    $docProxy = doc_Containers::getDocument($firstContainerId);
                    
                    // Полетата на документа във вербален вид
                    $docProxyRow = $docProxy->getDocumentRow();
                    
                    // Атрибутеите на линка
                    $attr['title'] = 'Първи документ|*: ' . $docProxyRow->title;
                    
                    // Темата да е линк към single' а на първиа документ документа
                    $firstContainerLink = $docProxy->getLink(35, $attr);
                    $row->threadId = $row->threadId . ' « ' . $firstContainerLink;
                }
            } catch (ErrorException $e) {
                // Не се прави нищо
            }
            
            if ($doc) {
                $fRec = fileman::fetchByFh($rec->fileHnd);
                if ($doc && $fRec && fileman::haveRightFor('single', $fRec) && $doc->haveRightFor('single')) {
                    $url = array($doc, 'viewFile', $rec->containerId, 'fh' => $fRec->fileHnd);
                }
            }
        }
        
        // Името на файла да е линк към сингъл изгледа му
        $row->fileHnd = fileman_Files::getLink($rec->fileHnd, null, $url);
    }
    
    
    /**
     * Променяме folderId на папката
     *
     * @param object $cRec - Запис от doc_Containers
     */
    public static function updateRec($cRec)
    {
        // Ако няма containerId не се прави нищо
        if (!$cRec->id || !$cRec->folderId) {
            
            return ;
        }
        
        // Вземаем всички записи от модела от съответния контейнер
        $query = static::getQuery();
        $query->where("#containerId = '{$cRec->id}'");
        
        // Обхождаме всички записи
        while ($rec = $query->fetch()) {
            
            // Ако се е променило id' то на папката
            if ($rec->folderId != $cRec->folderId) {
                
                $rec->folderId = $cRec->folderId;
                
                static::save($rec, 'folderId');
            }
        }
    }
}
