<?php 

/**
 * Детайл на разпределена файлова група
 *
 * @category  bgerp
 * @package   distro
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class distro_Files extends core_Detail
{
    /**
     * Заглавие на модела
     */
    public $title = 'Разпределена файлова група';
    
    
    public $singleTitle = 'Файл';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    public $canEditsysdata = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има право да го оттегли?
     */
    public $canReject = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'distro_Wrapper, plg_Modified, plg_Created, plg_RowTools2, plg_SaveAndNew';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'groupId';
    
    
    public $depends = 'fileman=0.1';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     *
     * @see plg_RowTools2
     */
    public $rowToolsSingleField = 'id';
    
    
    /**
     * При колко линка в тулбара на реда да не се показва дропдауна
     *
     * @param int
     *
     * @see plg_RowTools2
     */
    public $rowToolsMinLinksToShow = 2;
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    public $fetchFieldsBeforeDelete = 'id, sourceFh, repos, groupId, name';
    
    
    /**
     * Флаг, който указва дали да се изтрие и файла след изтриване на хранилището
     */
    public $onlyDelRepo = false;
    
    
    public $currentTab = 'Групи';
    
    
    /**
     * Какво действие ще се прави с файловете
     */
    public $actionWithFile = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('groupId', 'key(mvc=distro_Group, select=title)', 'caption=Група, mandatory');
        $this->FLD('sourceFh', 'fileman_FileType(bucket=' . distro_Group::$bucket . ')', 'caption=Файл, mandatory, remember=info');
        $this->FLD('name', 'varchar', 'caption=Име, width=100%, input=none');
        $this->FLD('repoId', 'key(mvc=distro_Repositories, select=name)', 'caption=Хранилище, width=100%, input=none');
        $this->FNC('repos', 'keylist(mvc=distro_Repositories, select=name, select2MinItems=6)', 'caption=Хранилища, width=100%, maxColumns=3, input=input');
        $this->FLD('info', 'varchar', 'caption=Информация, width=100%');
        $this->FLD('md5', 'varchar(32)', 'caption=Хеш на файла, width=100%,input=none');
        
        $this->setDbUnique('groupId, name, repoId');
    }
    
    
    /**
     * Функция, която връща дали има запис към мастъра
     *
     * @param int $masterId - id на мастъра
     *
     * @return bool
     */
    public static function haveRec($me, $masterId)
    {
        // Ако има мастер
        if ($masterKey = $me->masterKey) {
            
            // Ако има запис към мастера
            if (static::fetch(array("#{$masterKey} = '[#1#]'", $masterId))) {
                
                return true;
            }
        }
        
        return false;
    }
    
    
    /**
     * Връща пълния път до файла в хранилището
     *
     * @param int         $id
     * @param NULL|int    $repoId
     * @param NULL|int    $groupId
     * @param NULL|string $name
     */
    public function getRealPathOfFile($id, $repoId = null, $groupId = null, $name = null)
    {
        $rec = self::fetchRec((int) $id);
        
        $repoId = isset($repoId) ? $repoId : $rec->repoId;
        $groupId = isset($groupId) ? $groupId : $rec->{$this->masterKey};
        
        if ($repoId) {
            $rRec = distro_Repositories::fetch((int) $repoId);
            
            $subDirName = $this->Master->getSubDirName($groupId);
            
            $name = $name ? $name : $rec->name;
            
            $path = rtrim($rRec->path, '/') . '/' . $subDirName . '/' . $name;
        } else {
            if ($rec->sourceFh) {
                $path = fileman_Download::getDownloadUrl($rec->sourceFh);
            }
        }
        
        return $path;
    }
    
    
    /**
     * Връща уникално име за файла, който ще се добавя в хранилището
     *
     * @param int      $id
     * @param NULL|int $repoId
     *
     * @return FALSE|string
     */
    public function getUniqFileName($id, $repoId = null)
    {
        $rec = self::fetchRec($id);
        
        $repoId = isset($repoId) ? $repoId : $rec->repoId;
        
        $sshObj = distro_Repositories::connectToRepo($repoId);
        
        expect($sshObj !== false);
        
        $destFilePath = $this->getRealPathOfFile($id, $repoId);
        
        $maxCnt = 32;
        
        while (true) {
            $destFilePathE = escapeshellarg($destFilePath);
            
            $sshObj->exec("if [ ! -f {$destFilePathE} ]; then echo 'OK'; fi", $res);
            
            if (trim($res) == 'OK') {
                break;
            }
            
            $destFilePath = $this->getNextFileName($destFilePath);
            
            expect($maxCnt--);
        }
        
        return $destFilePath;
    }
    
    
    /**
     * Връща масив със записи, където се среща съответния файл
     *
     * @param int         $groupId
     * @param string|NULL $md5
     * @param string|NULL $name
     * @param bool        $group
     *
     * @return array
     */
    public static function getRepoWithFile($groupId, $md5 = null, $name = null, $group = false)
    {
        $query = self::getQuery();
        $query->where(array("#groupId = '[#1#]'", $groupId));
        
        if (isset($md5)) {
            $query->where(array("#md5 = '[#1#]'", $md5));
        }
        if (isset($name)) {
            $query->where(array("#name = '[#1#]'", $name));
        }
        
        if ($group) {
            $query->groupBy('repoId');
        }
        
        $query->where('#repoId IS NOT NULL');
        $query->where("#repoId != ''");
        
        return $query->fetchAll();
    }
    
    
    /**
     * Връща следващото име за използване на файла
     *
     * @param string $fName
     *
     * @return string
     */
    protected function getNextFileName($fName)
    {
        // Вземаме името на файла и разширението
        $nameArr = fileman_Files::getNameAndExt($fName);
        
        // Намираме името на файла до последния '_'
        if (($underscorePos = mb_strrpos($nameArr['name'], '_')) !== false) {
            $name = mb_substr($nameArr['name'], 0, $underscorePos);
            $nameId = mb_substr($nameArr['name'], $underscorePos + 1);
            
            if (is_numeric($nameId)) {
                $nameId++;
            } else {
                $nameId .= '_1';
            }
            
            $nameArr['name'] = $name . '_' . $nameId;
        } else {
            $nameArr['name'] .= '_1';
        }
        
        $fName = $nameArr['name'] . '.' . $nameArr['ext'];
        
        return $fName;
    }
    
    
    /**
     * Форсира синхронизирането на файловете в хранилището с БД
     *
     * @param int $groupId
     * @param int $repoId
     *
     * @return FALSE|array
     */
    public function forceSync($groupId, $repoId)
    {
        $conn = distro_Repositories::connectToRepo($repoId);
        
        if (!$conn) {
            
            return false;
        }
        
        $actArr = array();
        
        $subDirName = $this->Master->getSubDirName($groupId);
        
        $repoRec = distro_Repositories::fetch((int) $repoId);
        
        $path = rtrim($repoRec->path, '/');
        $path .= '/' . $subDirName;
        $path = escapeshellarg($path);
        
        // Всички файлове от това хранилище
        $conn->exec("ls -p {$path}| grep -v '/$'", $res);
        
        $resArr = explode("\n", $res);
        
        // Всички файлове в БД
        $query = distro_Files::getQuery();
        $query->where(array("#{$this->masterKey} = '[#1#]'", $groupId));
        $query->where(array("#repoId = '[#1#]'", $repoId));
        
        $dbArr = array();
        while ($rec = $query->fetch()) {
            $dbArr[$rec->name] = $rec;
        }
        
        foreach ($resArr as $fName) {
            $fName = trim($fName);
            if (!$fName) {
                continue ;
            }
            
            // Ако файла съществува в БД
            if (isset($dbArr[$fName])) {
                unset($dbArr[$fName]);
            } else {
                
                // Добавяме файла в БД
                $this->addFileToDB($groupId, $fName, $repoId);
                $actArr['addToDB']++;
            }
        }
        
        // Ако файлът съществува в БД, но не и в хранилището
        if (!empty($dbArr)) {
            foreach ($dbArr as $fRec) {
                if (!isset($fRec->sourceFh) || !trim($fRec->sourceFh)) {
                    // Ако не е архивиран, премахваме от базата и отбелязваме в лога
                    distro_Actions::addToRepo($fRec, 'distro_DeleteDriver', true);
                    $actArr['delFromDb']++;
                } else {
                    // Ако файлът е качен в системата - сваляме го в хранилището
                    distro_Actions::addToRepo($fRec, 'distro_AbsorbDriver');
                    $actArr['absorbFromDb']++;
                }
            }
        }
        
        return $actArr;
    }
    
    
    /**
     * Синхронизира съдържанието на хранилищата с модела
     *
     * @return array
     */
    protected function syncFiles()
    {
        $resArr = array();
        
        $reposArr = distro_Repositories::getReposArr();
        
        if (empty($reposArr)) {
            
            return $resArr;
        }
        
        $repoLineHash = distro_Repositories::getLinesHash();
        $repoFirstHash = array();
        
        foreach ($reposArr as $repoId) {
            $linesArr = distro_Repositories::parseLines($repoId);
            
            if (!isset($repoFirstHash[$repoId])) {
                $repoFirstHash[$repoId] = $linesArr[0]['lineHash'];
            }
            
            $repoActArr = array();
            
            foreach ($linesArr as $lArr) {
                if (!$lArr) {
                    continue ;
                }
                
                if (isset($repoLineHash[$repoId])) {
                    
                    // Вече сме достигнали до тази обработка
                    if ($repoLineHash[$repoId] == $lArr['lineHash']) {
                        break;
                    }
                }
                
                // Опитваме се да определим id на групата от пътя на директорията
                $groupId = $this->Master->getGroupIdFromFolder($lArr['rPath']);
                
                if (empty($groupId)) {
                    continue;
                }
                
                // Създадените/променени директории не ги пипаме
                if ($lArr['isDir']) {
                    continue;
                }
                
                // Ако не са в поддиректрия, не ги обработваме
                if (!trim($lArr['rPath'])) {
                    continue ;
                }
                
                if ($lArr['act'] == 'create' || $lArr['act'] == 'edit') {
                    
                    // Ако вече е бил изтрит, няма смисъл да се добавя
                    if ($repoActArr[$groupId]['delete'][$lArr['name']]) {
                        continue;
                    }
                }
                
                $repoActArr[$groupId][$lArr['act']][$lArr['name']] = $lArr['date'];
            }
            
            foreach ($repoActArr as $groupId => $actArr) {
                foreach ((array) $actArr['create'] as $name => $date) {
                    $addRes = $this->addFileToDB($groupId, $name, $repoId, $date);
                    
                    if (!isset($addRes)) {
                        continue;
                    }
                    
                    $resArr['create'][$addRes] = $addRes;
                }
                
                foreach ((array) $actArr['edit'] as $name => $date) {
                    $fRec = $this->getRecForFile($groupId, $name, $repoId);
                    
                    if ($fRec === false) {
                        $this->logNotice('Няма запис за файл, който да се редактира');
                        
                        continue;
                    }
                    
                    $fRec->modifiedBy = -1;
                    
                    if ($this->checkDate($date)) {
                        $fRec->modifiedOn = $date;
                    }
                    
                    $subDir = $this->Master->getSubDirName($groupId);
                    $newMd5 = $this->getMd5($repoId, $subDir, $name);
                    
                    if ($newMd5 != $fRec->md5) {
                        $fRec->md5 = $newMd5;
                        $fRec->sourceFh = null;
                    }
                    
                    // Прекъсваемо е за да не се промянят от плъгина
                    $this->save_($fRec, 'modifiedOn, modifiedBy, md5, sourceFh');
                    $this->Master->touchRec($groupId);
                    
                    $resArr['edit'][$fRec->id] = $fRec->id;
                }
                
                foreach ((array) $actArr['delete'] as $name => $date) {
                    $fRec = $this->getRecForFile($groupId, $name, $repoId);
                    
                    if ($fRec === false) {
                        $this->logNotice('Записът за файла е бил премахнат при изтриване');
                        
                        continue;
                    }
                    
                    $resArr['delete'][$fRec->id] = $fRec->id;
                    
                    distro_Actions::addToRepo($fRec, 'distro_DeleteDriver', true);
                }
            }
            
            // Задаваме новата стойност на линията
            distro_Repositories::setLineHash($repoId, $repoFirstHash[$repoId]);
        }
        
        return $resArr;
    }
    
    
    /**
     * Добавя запис за файла в БД
     *
     * @param int           $groupId
     * @param string        $name
     * @param int           $repoId
     * @param NULL|datetime $date
     *
     * @return NULL|int
     */
    protected function addFileToDB($groupId, $name, $repoId, $date = null)
    {
        $subDir = $this->Master->getSubDirName($groupId);
        
        $nRec = new stdClass();
        
        $nRec->md5 = $this->getMd5($repoId, $subDir, $name);
        
        $fRec = $this->getRecForFile($groupId, $name, $repoId);
        
        if ($fRec) {
            
            // Ако хешовете им съвпадат
            if ($fRec->md5 == $nRec->md5) {
                $this->logNotice('Съществуващ файл', $fRec->id);
                
                return;
            }
            
            // Ако са различни файлове, преименуваме единия
            $ssh = distro_Repositories::connectToRepo($repoId);
            if ($ssh) {
                $uniqName = $this->getUniqFileName($fRec->id, $repoId);
                $newName = escapeshellarg($uniqName);
                
                $oldName = $this->getRealPathOfFile($fRec->id, $repoId);
                $oldName = escapeshellarg($oldName);
                
                $exec = "mv {$oldName} {$newName}";
                $ssh->exec($exec);
                
                $name = pathinfo($uniqName, PATHINFO_BASENAME);
            }
        }
        
        $nRec->repoId = $repoId;
        $nRec->groupId = $groupId;
        $nRec->name = $name;
        
        $nRec->createdBy = -1;
        
        // Проверяваме дали подадената дата е коректна за използване
        if (isset($date) && $this->checkDate($date)) {
            $nRec->createdOn = $date;
        }
        
        $sId = $this->save($nRec, null, 'IGNORE');
        if ($sId) {
            distro_Actions::addNotifications($nRec->groupId);
        }
        
        return $nRec->id;
    }
    
    
    /**
     * Връща md5 стойността на файла
     *
     * @param int    $repoId
     * @param string $dir
     * @param string $name
     *
     * @return FALSE|string
     */
    protected static function getMd5($repoId, $dir, $name)
    {
        $md5 = distro_Repositories::getFileMd5($repoId, $dir, $name);
        
        return $md5;
    }
    
    
    /**
     * Връща запис за файла от съответната група
     *
     * @param int    $groupId
     * @param string $name
     * @param int    $repoId
     * @param bool   $cache
     *
     * @return stdClass|FALSE
     */
    protected function getRecForFile($groupId, $name, $repoId, $cache = false)
    {
        $rec = $this->fetch(array("#groupId = '[#1#]' AND #name = '[#2#]' AND #repoId = '[#3#]'", $groupId, $name, $repoId), null, $cache);
        
        return $rec;
    }
    
    
    /**
     *
     * @param datetime $date
     *
     * @return bool
     */
    protected function checkDate($date)
    {
        $sBetween = dt::secsBetween(dt::now(), $date);
        if ($sBetween >= 0) {
            if ($sBetween > 300) {
                $this->logNotice('Разминаване във времето - файлът е създаден много отдавна: ' . dt::mysql2verbal($date));
                
                return false;
            }
        } else {
            $this->logWarning('Разминаване във времето - файлът е създаден в бъдеще: ' . dt::mysql2verbal($date));
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param distro_Files $mvc
     * @param string       $requiredRoles
     * @param string       $action
     * @param stdClass     $rec
     * @param int          $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Ако ще добавяме/редактираме записа
        if ($action == 'add') {
            
            // Ако има master
            if (($masterKey = $mvc->masterKey) && ($rec->$masterKey)) {
                
                // Ако няма права за добавяне на детайл
                if (!$mvc->Master->canAddDetail($rec->$masterKey)) {
                    
                    // Да не може да добавя
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * След подготвяне на формата
     *
     * @param distro_Files $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Записа
        $rec = $data->form->rec;
        
        // Ако редактираме записа
        if ($rec->id) {
            // Избора на файл да е задължителен
            $data->form->setField('sourceFh', 'input=none');
            $data->form->setField('repos', 'input=none');
        } else {
            $reposArr = array();
            
            // Ако има мастер
            if (($masterKey = $mvc->masterKey) && ($rec->$masterKey)) {
                
                // Вземаме масива с хранилищата, които са зададени в мастера
                $reposArr = $mvc->Master->getReposArr($rec->$masterKey);
            }
            
            if (empty($reposArr)) {
                $data->form->setField('repos', 'input=none');
            } else {
                $data->form->setSuggestions('repos', $reposArr);
                if (count($reposArr) == 1) {
                    $data->form->setDefault('repos', '|'. key($reposArr) . '|');
                }
            }
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param distro_Files $mvc
     * @param core_Form    $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            if (isset($rec->sourceFh)) {
                $rec->name = fileman_Files::fetchByFh($form->rec->sourceFh, 'name');
                $rec->md5 = fileman_Files::fetchByFh($form->rec->sourceFh, 'md5');
                
                if (!$rec->id && $rec->repos) {
                    $rec->__addToRepo = true;
                }
            }
        }
    }
    
    
    /**
     *
     *
     * @param distro_Files $mvc
     * @param stdClass     $res
     * @param stdClass     $rec
     */
    public static function on_BeforeSave($mvc, $res, $rec)
    {
        // Опитваме се от keylist поле да направим key
        // За целта правим записи за всяко repoId, а този запис го спираме
        if (isset($rec->repos)) {
            $reposArr = type_Keylist::toArray($rec->repos);
            
            foreach ($reposArr as $repoId) {
                $rec->repos = null;
                $rec->repoId = $repoId;
                
                // Опитваме се да генерираме уникално име на файла
                $origName = $rec->name;
                $maxCnt = 64;
                while (true) {
                    if ($mvc->isUnique($rec)) {
                        break;
                    }
                    
                    $rec->name = $mvc->getNextFileName($rec->name);
                    
                    expect($maxCnt--);
                }
                
                $mvc->save($rec);
                
                $rec->name = $origName;
                
                unset($rec->id);
            }
            
            return false;
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc     $mvc
     * @param int          $id     първичния ключ на направения запис
     * @param stdClass     $rec    всички полета, които току-що са били записани
     * @param array|string $fields
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = array())
    {
        // Сваляме файла в хранилището
        if (isset($rec->__addToRepo)) {
            distro_Actions::addToRepo($rec);
        }
        
        if (!$rec->repoId && !$fields) {
            distro_Actions::addToRepo($rec, 'distro_UploadDriver');
        }
    }
    
    
    /**
     *
     *
     * @param distro_Files $mvc
     * @param stdClass     $res
     * @param stdClass     $data
     */
    public function on_AfterPrepareListRecs($mvc, &$res, $data)
    {
        // Масив с хранилищата и файловете в тях
        $reposAndFilesArr = array();
        
        $sameNameFileArr = array();
        $addMd5Arr = array();
        
        foreach ((array) $data->recs as $id => $rec) {
            
            // Разпределяме ги в масива
            $reposAndFilesArr[$rec->repoId][$id] = $id;
            
            // Ако има еднакви файлове с различен хеш, показваме хеша
            if (isset($sameNameFileArr[$rec->name])) {
                foreach ($sameNameFileArr[$rec->name] as $rId) {
                    if ($data->recs[$rId]->md5 == $rec->md5) {
                        continue;
                    }
                    $addMd5Arr[$rec->name] = $rec->name;
                }
            }
            
            $sameNameFileArr[$rec->name][] = $rec->id;
        }
        
        foreach ($addMd5Arr as $fName) {
            foreach ((array) $sameNameFileArr[$fName] as $rId) {
                $hashStr = tr('Файл|*: ') . substr($data->recs[$rId]->md5, 0, 6);
                
                $data->recs[$rId]->info = (trim($data->recs[$rId]->info)) ? $hashStr . '. ' . $data->recs[$rId]->info : $hashStr;
            }
        }
        
        // Подреждаме спрямо хранилищата - да не се разместват при всяка промяна
        if (!empty($reposAndFilesArr)) {
            ksort($reposAndFilesArr);
        }
        
        // Добавяме масива
        $data->reposAndFilesArr = $reposAndFilesArr;
    }
    
    
    /**
     *
     *
     * @param distro_Files $mvc
     * @param stdClass     $res
     * @param stdClass     $data
     */
    public static function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        // Обхождаме масива с хранилищата и файловете в тях
        foreach ((array) $data->reposAndFilesArr as $repoId => $idsArr) {
            
            // Масив с вербалните данни
            $data->rowReposAndFilesArr[$repoId] = array();
            
            // Обхождаме масива с id'та
            foreach ((array) $idsArr as $id) {
                
                // Името на файла
                // Ако има манипулатор, да е линка към сингъла
                if ($data->rows[$id]->sourceFh) {
                    $file = $data->rows[$id]->sourceFh;
                } else {
                    $file = $data->rows[$id]->name;
                }
                
                $subDirName = $mvc->Master->getSubDirName($data->recs[$id]->groupId);
                $file = distro_Repositories::getUrlForFile($repoId, $subDirName, $data->rows[$id]->name, $file);
                
                // Ако няма създаден обект, създаваме такъв
                if (!$data->rowReposAndFilesArr[$repoId][$id]) {
                    $data->rowReposAndFilesArr[$repoId][$id] = new stdClass();
                }
                
                // Добавяме файла в масива
                $data->rowReposAndFilesArr[$repoId][$id]->file = $file;
                
                // Информация за файла
                $data->rowReposAndFilesArr[$repoId][$id]->info = $data->rows[$id]->info;
                
                // Данни за модифициране
                $data->rowReposAndFilesArr[$repoId][$id]->modified = $data->rows[$id]->modifiedOn . tr(' |от|* ') . $data->rows[$id]->modifiedBy;
                
                core_RowToolbar::createIfNotExists($data->rows[$id]->_rowTools);
                
                distro_Actions::addActionToFile($data->rows[$id]->_rowTools, $data->recs[$id]);
                
                // Бутони за действия
                $data->rowReposAndFilesArr[$repoId][$id]->tools = $data->rows[$id]->_rowTools->renderHtml($mvc->rowToolsMinLinksToShow);
            }
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     *
     * @param core_Mvc $mvc
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('modifiedOn', 'DESC');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // Ако има манипулатор на файл и име на файл
        if ($rec->sourceFh && $rec->name) {
            
            // Вземаме линк с текущото име
            $row->sourceFh = fileman::getLink($rec->sourceFh, $rec->name);
        }
    }
    
    
    /**
     *
     *
     * @param distro_Files $mvc
     * @param core_ET      $tpl
     * @param stdClass     $data
     */
    public function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        // Вземаме таблицата
        $tpl = $mvc->renderReposAndFiles($data);
        
        // Да не се изпълнява кода
        return false;
    }
    
    
    /**
     * Рендира таблицата за хранилища и файлове
     *
     * @param object $data - Данни
     */
    protected static function renderReposAndFiles($data)
    {
        // Шаблон за таблиците
        $tplRes = getTplFromFile('distro/tpl/FilesAllReposTables.shtml');
        
        // Ако няма записи
        if (!$data->rowReposAndFilesArr) {
            
            // Сетваме текста
            $tplRes->append(tr('Няма записи'), 'REPORES');
            
            // Връщаме шаблона
            return $tplRes;
        }
        
        // Обхождаме масива
        foreach ((array) $data->rowReposAndFilesArr as $repoId => $reposArr) {
            
            // Шаблон за таблица
            $tplTable = getTplFromFile('distro/tpl/FilesRepoTable.shtml');
            
            if (Mode::isReadOnly()) {
                $tplTable->removeBlock('Tools');
            } else {
                $tplTable->replace(' ', 'Tools');
            }
            
            // Обхождаме масива с хранилищата
            foreach ($reposArr as $repo) {
                
                // Шаблон за ред в таблицата
                $tplRow = getTplFromFile('distro/tpl/FilesRepoTableRow.shtml');
                
                // Заместваме данните
                $tplRow->replace($repo->modified, 'modified');
                $tplRow->replace($repo->file, 'file');
                
                if (!Mode::isReadOnly()) {
                    $tplRow->replace($repo->tools, 'tools');
                }
                
                // Ако има информация
                if ($info = trim($repo->info)) {
                    if (Mode::isReadOnly()) {
                        $tplRow->replace(2, 'colspan');
                    } else {
                        $tplRow->replace(3, 'colspan');
                    }
                    
                    // Заместваме информацията
                    $tplRow->replace($info, 'fileInfo');
                }
                
                // Премахваме незаместените блокове
                $tplRow->removeBlocks();
                
                // Добавяме към шаблона за таблиците
                $tplTable->append($tplRow, 'repoRow');
            }
            
            if ($repoId) {
                // Линк към хранилището
                if (!Mode::isReadOnly()) {
                    $repoTitleLink = distro_Repositories::getLinkToSingle($repoId, 'name');
                } else {
                    $repoTitleLink = distro_Repositories::getVerbal($repoId, 'name');
                }
            } else {
                $repoTitleLink = tr('Система');
            }
            
            // Добавяме в шаблона
            $tplTable->append($repoTitleLink, 'repoTitle');
            
            // Ако няма файлове
            if (!$reposArr) {
                
                // Шаблон за ред в таблицата
                $tplRow = getTplFromFile('distro/tpl/FilesRepoTableRow.shtml');
                
                // Заместваме информацията
                $tplRow->replace(tr('Няма файлове'), 'fileInfo');
                
                // Добавяме към шаблона за таблиците
                $tplTable->append($tplRow, 'repoRow');
            }
            
            // Добавяме в резултатния шаблон
            $tplRes->append($tplTable, 'REPORES');
        }
        
        // Премахваме незаместените шаблони
        $tplRes->removePlaces();
        
        // Премахваме празните блокове
        $tplRes->removeBlocks();
        
        return  $tplRes;
    }
    
    
    /**
     * Функция, която се вика от крон
     * Синрхоронизира файловете в хранилищитата с модела
     */
    public function cron_SyncFiles()
    {
        // Извикваме функцията и връщаме резултата му
        return core_Type::mixedToString($this->syncFiles());
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     *
     * @param distro_Files $mvc
     * @param string       $res
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        $rec = new stdClass();
        $rec->systemId = 'SyncFiles';
        $rec->description = 'Синхронизиране на файловете в хранилищата със записите в модела';
        $rec->controller = $mvc->className;
        $rec->action = 'SyncFiles';
        $rec->period = 1;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 50;
        $res .= core_Cron::addOnce($rec);
    }
    
    
    /**
     * Екшън за качване на файл от нерегистирирани потребители
     */
    public function act_UploadFile()
    {
        $cId = Request::get('c', 'int');
        $mId = Request::get('m');
        
        expect($cId && $mId);
        
        $cRec = doc_Containers::fetch($cId);
        
        expect($cRec && $cRec->state != 'rejected');
        
        expect(doclog_Documents::opened($cId, $mId));
        
        $gDoc = doc_Containers::getDocument($cId);
        
        expect($gDoc && $gDoc->instance instanceof distro_Group);
        
        $gRec = $gDoc->fetch();
        
        expect($gRec && $gRec->state != 'rejected');
        
        $retUrl = array('L', 'S', $cId, 'm' => $mid);
        
        $form = $this->getForm();
        
        $form->toolbar->addSbBtn('Запис', 'save', 'id=save, ef_icon = img/16/ticket.png,title=Изпращане на сигнала');
        $form->toolbar->addBtn('Отказ', $retUrl, 'id=cancel, ef_icon = img/16/close-red.png,title=Отказ, onclick=self.close();');
        
        $form->title = 'Качване на файл';
        
        $form->setDefault('groupId', $gRec->id);
        $form->setField('groupId', 'input=none');
        
        $reposArr = array();
        
        // Вземаме масива с хранилищата, които са зададени в мастера
        $reposArr = $this->Master->getReposArr($gRec->id);
        
        if (empty($reposArr)) {
            $form->setField('repos', 'input=none');
        } else {
            $form->setSuggestions('repos', $reposArr);
            if (count($reposArr) == 1) {
                $form->setDefault('repos', '|'. key($reposArr) . '|');
            }
        }
        
        $form->input();
        
        Mode::set('wrapper', 'page_Dialog');
        
        if ($form->isSubmitted()) {
            $tpl = new ET();
            jquery_Jquery::run($tpl, 'self.close();');
            $tpl->append("window.opener.distroUploadFile{$gRec->id}()", 'SCRIPTS');
            
            $this->save($form->rec);
            
            $this->logInAct('Добавяне на файл', $form->rec);
        } else {
            $tpl = $form->renderHtml();
        }
        
        // Добавяме клас към бодито
        $tpl->append('dialog-window', 'BODY_CLASS_NAME');
        
        $tpl->append("<button onclick='javascript:window.close();' class='dialog-close'>X</button>");
        
        return $tpl;
    }
}
