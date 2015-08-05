<?php 


/**
 * Детайл на разпределена група файлове
 *
 * @category  bgerp
 * @package   distro
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class distro_Files extends core_Detail
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Разпределена група файлове';
    
    
    /**
     * 
     */
    var $singleTitle = 'Файл';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'distro_Wrapper, plg_Modified';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'groupId';
    
    
    /**
     * 
     */
    var $depends = 'fileman=0.1';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'id';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    var $fetchFieldsBeforeDelete = 'id, sourceFh, repos, groupId, name';
    
    
    /**
     * Флаг, който указва дали да се изтрие и файла след изтриване на хранилището
     */
    var $onlyDelRepo = FALSE;
    
    
    /**
     * 
     */
    var $currentTab = 'Групи';
    
    
    /**
     * Какво действие ще се прави с файловете
     */
    var $actionWithFile = array();
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('groupId', 'key(mvc=distro_Group, select=title)', 'caption=Група, mandatory');
        $this->FLD('sourceFh', 'fileman_FileType(bucket=' . fileman_Repositories::$bucket . ')', 'caption=Файл, mandatory');
        $this->FLD('name', 'varchar', 'caption=Име, width=100%');
        $this->FLD('repos', 'keylist(mvc=fileman_Repositories, select=verbalName)', 'caption=Хранилища, width=100%, maxColumns=3');
        $this->FLD('info', 'varchar', 'caption=Информация, width=100%');
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако ще добавяме/редактираме записа
        if ($action == 'add' || $action == 'edit' || $action == 'delete') {
            
            // Ако има master
            if (($masterKey = $mvc->masterKey) && ($rec->$masterKey)) {
                
                // Ако няма права за добавяне на детайл
                if (!$mvc->Master->canAddDetail($rec->$masterKey)) {
                    
                    // Да не може да добавя
                    $requiredRoles = 'no_one';
                }
            }
            
            // Ако все още има права
            if ($requiredRoles != 'no_one') {
                
                // Ако има дата на модифициране
                if ($rec->modifiedOn) {
                    
                    // Ако е бил променен преди разрешеното от нас
                    if (!fileman_Repositories::checkLastModified(dt::mysql2timestamp($rec->modifiedOn))) {
                        
                        // Да не може да се променя
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }
    
    
	/**
     * След подготвяне на формата
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Записа
        $rec = $data->form->rec;
        
        // Ако има мастер
        if (($masterKey = $mvc->masterKey) && ($rec->$masterKey)) {
            
            // Вземаме масива с хранилищата
            $reposArr = $mvc->Master->getReposArr($rec->$masterKey);
        }
        
        // Ако има хранилищя
        if ($reposArr) {
            
            // Сетваме масива
            $data->form->setSuggestions('repos', $reposArr);
        }
        
        // Ако създаваме нов запис
        if (!$rec->id) {
            
            // Ако има хранилища
            if ($reposArr) {
                
                // Сетваме избора на хранилища да е задължително
                $data->form->setField('repos', 'mandatory');
            } else {
                
                // Да не се показват хранилищатата
                $data->form->setField('repos', 'input=none');
            }
            
            // Името на файла да не се въвежда
            $data->form->setField('name', 'input=none');
        } else {
            
            // Ако редактираме записа
            
            // Избора на файл да е задължителен
            $data->form->setField('sourceFh', 'input=none');
            
            // Сетваме името да е задължително поле
            $data->form->setField('name', 'mandatory');
            
            // Добавяме функционално поле
            $data->form->FNC('archive', 'set(upload=Качване)', 'caption=Архивиране, input=input');
            
            // Ако има манипулатор на файла
            if ($data->form->rec->sourceFh) {
                
                // Да е избран по подразбиране
                $data->form->setDefault('archive', 'upload');
                
                // Да не може да се променя
                $data->form->addAttr('archive', array('disabled'=>'disabled'));
            }
        }
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        // Ако формата е изпратена успешно
        if ($form->isSubmitted()) {
            
            // Ако има master
            if (($masterKey = $mvc->masterKey) && ($form->rec->$masterKey)) {
                
                // Вземаме заглавието/титлата на полето
                $title = $mvc->Master->getGroupTitle($form->rec->$masterKey);
            }
            
            // Ако редактираме запис
            if ($form->rec->id) {
                
                // Вземаме оригиналния запис
                $rec = $mvc->fetch($form->rec->id);
                
                // Вземаме масива с хранилищата
                $reposArr = type_Keylist::toArray($rec->repos);
            } else {
                
                // Вземаме масива с хранилищата
                $reposArr = type_Keylist::toArray($form->rec->repos);
            }
            
            // Ако сме избрали да се качва файла
            if (($form->rec->archive == 'upload') && !$form->rec->sourceFh) {
                
                // Кофата
                $bucket = fileman_Repositories::$bucket;
                
                // Вземаме името след като се качи файла
                $possibleName = fileman_Files::getPossibleName($form->rec->name, $bucket);
                
                // Ако името след качване не отговаря на името въведено от потребителя
                if ($possibleName != $form->rec->name) {
                    
                    // Сетваме предупреждение
                    $msg = "Файлът ще се преименува на|*: " . type_Varchar::escape($possibleName);
                    $form->rec->name = $possibleName;
                    $form->setWarning('name', $msg);
                }
                
                // Масив, за качване на файл
                $uploadFileArr = array('title' => $title,
                                       'reposArr' => $reposArr,
                                       'name' => $form->rec->name,
                                       'bucket' => $bucket,
                                       );
                // Добавяме в действията
                $mvc->actionWithFile['uploadFile'] = $uploadFileArr;
            }
            
            // Ако създаваме нов запис
            if (!$form->rec->id) {
                
                // Ако има манипулато на файла
                if ($form->rec->sourceFh) {
                    
                    // Сетваме името от манипулатора
                    $form->rec->name = fileman_Files::fetchByFh($form->rec->sourceFh, 'name');
                }
                
                // Очакваме да са сетнати
                expect($reposArr);
                
                // Вземаме масива с хранилищата, които съществуват
                $reposArrWithFile = static::getArrFilesExistInRepo($form->rec->name, $reposArr, $title);
                
                // Ако някой от файловете съществува в хранилището
                if ($reposArrWithFile) {
                    
                    // Сетваме грешката
                    $form->setError('sourceFh', 'Файл със същото име съществува в хранилищата|*: ' . implode(', ', $reposArrWithFile));
                } else if ($form->rec->sourceFh) {
                    
                    // Ако файла не съществува и има манипулатор
                    
                    // Създаваме масив за добавяне от манипулатор на файл
                    $addFromFhArr = array('title' => $title,
                    					  'fileHnd' => $form->rec->sourceFh,
                                          'reposArr' => $reposArr,
                                          'name' => $form->rec->name);
                    
                    // Добавяме масива
                    $mvc->actionWithFile['addFromFh'] = $addFromFhArr;
                }
            } else {
                
                // Ако редактираме записа
                
                // Вземаме масива с различията между хранилищата в модела и във формата
                $diffArr = type_Keylist::getDiffArr($rec->repos, $form->rec->repos);
                
                // Ако няма непроменени и нови
                if (!$diffArr['same'] && !$diffArr['add']) {
                    
                    // Премахнали сме всички отметки
                    
                    // Сетваме грешка, че файлът ще бъде изтрит
                    $form->setWarning('repos', 'Файлът ще бъде изтрит');
                    
                    // Масив с id на записа, който ще бъде изтрит
                    $deleteRecArr = array('id' => $rec->id);
                    
                    // Добавяме масива
                    $mvc->actionWithFile['deleteRec'] = $deleteRecArr;
                    
                } else {
                    
                    // Ако сме изтрили
                    if ($diffArr['delete']) {
                        
                        // Ако сме "изтрили" някои хранилища
                        
                        // Масив с хранилища от които трябва да се премахне файла
                        $deleteFileInReposArr = array(
                                                     'title' => $title,
                                                     'name' => $rec->name,
                                                     'deleteReposArr' => $diffArr['delete'],
                                                     );
                        
                        // Добавяме масива
                        $mvc->actionWithFile['deleteFileInRepo'] = $deleteFileInReposArr;
                        
                    }
                    
                    // Ако сме добавили
                    if ($diffArr['add']) {
                        
                        // Ако добавяме в ново хранилище
                        
                        // Масив с изтритите и непроменените хранилища
                        $originalFileArr = (array)$diffArr['same'] + (array)$diffArr['delete'];
                        
                        // Ако е празен
                        if (!$originalFileArr) {
                            
                            // Сетваме грешка
                            $form->setError('repos', 'Няма от къде да се вземе файла за копиране');
                        } else {
                            
                            // Проверяваме файла дали съществува в масива
                            $reposArrWithOldFile = static::getArrFilesExistInRepo($rec->name, $originalFileArr, $title);
                            
                            // Ако не съществува
                            if (!$reposArrWithOldFile) {
                                
                                // Сетваме грешка
                                $form->setError('repos', 'Файлът не съществува в хранилищата|*: ' . implode(', ', $reposArrWithOldFile));
                            }
                            
                            // Масив с хранилиша, където трябва да се добави файла
                            $reposArrWithNewFileArr = (array)$diffArr['add'];
                            
                            // Проверяваме файла дали съществува в хранилищетата
                            $reposArrWithNew = static::getArrFilesExistInRepo($form->rec->name, $reposArrWithNewFileArr, $title);
                            
                            // Ако съществува в някое
                            if ($reposArrWithNew) {
                                
                                // Сетваме грешка
                                $form->setError('name', 'Файл със същото име съществува в хранилищата|*: ' . implode(', ', $reposArrWithNew));
                            }
                        }
                        
                        // Масив с файлове, които да се синхронизират
                        $syncFileInRepoArr = array(
                            'originalFileName' => $rec->name,
                            'originalFileRepoArr' => $originalFileArr,
                            'copyToFileRepoArr' => $reposArrWithNewFileArr,
                            'title' => $title,
                        );
                        
                        // Добавяме масива
                        $mvc->actionWithFile['syncFileInRepo'] = $syncFileInRepoArr;
                    }
                
                    // Ако е променено името на файла
                    if ($rec->name != $form->rec->name) {
                        
                        // Масив с хранилищата където е променено името
                        $renamedReposArr = (array)$diffArr['same'] + (array)$diffArr['add'];
                        
                        // Ако има хранилища, къдете е променено името
                        if ($renamedReposArr) {
                            
                            // Ако има манипулатор на файл
                            if ($rec->sourceFh) {
                                
                                // Вземаме записа
                                $fRec = fileman_Files::fetchByFh($rec->sourceFh);
                                
                                // Вземаме възможното име в кофата
                                $possibleName = fileman_Files::getPossibleName($form->rec->name, $fRec->bucketId);
                                
                            } else {
                                
                                // Нормализираме името
                                $possibleName = fileman_Files::normalizeFileName($form->rec->name);
                            }
                            
                            // Ако името не съвпада с промененото
                            if ($possibleName != $form->rec->name) {
                                
                                // Съобщение за грешка
                                $msg = 'Не може да се зададе|*: ' . type_Varchar::escape($form->rec->name) . '.';
                                $msg .= " " . "|Ще се използва|*: " . type_Varchar::escape($possibleName);
                                $form->setWarning('name', $msg);
                                
                                // Задаваме новото име
                                $form->rec->name = $possibleName;
                            } else {
                                
                                // Проверяваме дали файла съществува
                                $reposArrWithRenamedFile = static::getArrFilesExistInRepo($form->rec->name, $renamedReposArr, $title);
                                
                                // Ако файла с новото име съществува някъде
                                if ($reposArrWithRenamedFile) {
                                    
                                    // Сетваме грешката
                                    $form->setError('name', 'Файл със същото име съществува в хранилищата|*: ' . implode(', ', $reposArrWithRenamedFile));
                                }
                            }
                            
                            // Сетваме масива за преименуване
                            $renameRepoArr = array(
                                'oldName' => $rec->name,
                                'newName' => $form->rec->name,
                                'repos' => $renamedReposArr,
                                'title' => $title,
                                'fileHnd' => $rec->sourceFh,
                            );
                            
                            // Добавяме в масива
                            $mvc->actionWithFile['renamed'] = $renameRepoArr;
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Връща масив с хранилища, в които файлът съществува
     * 
     * @param string $fileName - Името на файла
     * @param array $repoIdArr - Масив с хранилища
     * @param string $subPath - Подпапка в хранилищата
     * 
     * @return array $resArr - Масив с хранилищя, които съществува
     */
    static function getArrFilesExistInRepo($fileName, $repoIdArr, $subPath='')
    {
        // Масив с хранилищя
        $repoIdArr = arr::make($repoIdArr);
        
        // Резултатния масив
        $resArr = array();
        
        // Обхождаме масива
        foreach ($repoIdArr as $repoId) {
            
            // Ако файла съществува в хранилището
            if (fileman_Repositories::checkFileExistInRepo($fileName, $repoId, $subPath)) {
                
                // Вземаме id-то на файла и вербалното му име
                $resArr[$repoId] = fileman_Repositories::getRepoName($repoId);
            }
        }
        
        return $resArr;
    }
    
    
	/**
	 * Изпълнява се преди запис на ред в таблицата
	 * 
	 * @param unknown_type $mvc
	 * @param unknown_type $id
	 * @param unknown_type $rec
	 * @param unknown_type $fields
	 */
    static function on_BeforeSave($mvc, &$id, &$rec, $fields = NULL)
    {
        // Ако е сетнат да се добави файл от манипулатор на файл
        if ($addFromFhArr = $mvc->actionWithFile['addFromFh']) {
            
            // Добавяме файла
            $resArr = fileman_Repositories::addFileInReposFromFh($addFromFhArr['fileHnd'], $addFromFhArr['reposArr'],
                                                                 $addFromFhArr['title'], FALSE, $addFromFhArr['name']);
            
            // Очакваме да има копирани файлове
            expect($resArr['copied']);
            
            // Премахваме копираните от масива
            unset($resArr['copied']);
            
            // Ако има запис в масива
            if ($resArr) {
                
                // Записваме масива в лога
                static::saveToLog($resArr, $rec->id);
                
                // Сетваме грешка
                expect(FALSE);
            }
        }
        
        // Ако е сетнат да се синхронизират файловете в хранилищата
        if ($syncFileInRepoArr = $mvc->actionWithFile['syncFileInRepo']) {
            
            // Очакваме да има хранилища за сетване
            expect(($syncFileInRepoArr['originalFileRepoArr'] && $syncFileInRepoArr['copyToFileRepoArr']));
            
            // Синхронизираме файловете
            $resArr = fileman_Repositories::syncFileInRepos($syncFileInRepoArr['originalFileName'], $syncFileInRepoArr['originalFileRepoArr'],
                                                            $syncFileInRepoArr['copyToFileRepoArr'], $syncFileInRepoArr['title']);
            
            // Очакваме да има копирани файлове
            expect($resArr['copied']);
            
            // Премахваме копираните
            unset($resArr['copied']);
            
            // Ако има запис в масива
            if ($resArr) {
                
                // Записваме масива в лога
                static::saveToLog($resArr, $rec->id);
                
                // Сетваме грешка
                expect(FALSE);
            }
        }
        
        // Ако е сетнат масива с преименуваните файлове
        if ($renamedArr = $mvc->actionWithFile['renamed']) {
            
            // Ако има манипулатор на файла
            if ($renamedArr['fileHnd']) {
                
                // Преименуваме
                $renamed = fileman::rename($renamedArr['fileHnd'], $renamedArr['newName']);
                
                // Ако новото име и преименуваното не съвпадата
                if ($renamed != $renamedArr['newName']) {
                    
                    // Сетваме масива за грешките
                    $renamedFileHndArr = array();
                    $renamedFileHndArr['renamed'] = $renamed;
                    $renamedFileHndArr['newName'] = $renamedArr['newName'];
                    
                    // Записваме в лога
                    static::saveToLog($renamedFileHndArr, $rec->id);
                    
                    // Сетваме грешка 
                    expect(FALSE);
                }
            }
            
            // Преименуваме файловете
            $resArr = fileman_Repositories::renameFilesInRepos($renamedArr['oldName'], $renamedArr['newName'],
                                                               $renamedArr['repos'], $renamedArr['title']);
            
            // Очакваме да има преименувани
            expect($resArr['renamed']);
            
            // Премахваме ги от масива
            unset($resArr['renamed']);
            
            // Ако има запис в масива
            if ($resArr) {
                
                // Записваме масива в лога
                static::saveToLog($resArr, $rec->id);
                
                // Сетваме грешка
                expect(FALSE);
            }
        }
        
        // Ако е зададен масива за качване на файл
        if ($uploadFileaArr = $mvc->actionWithFile['uploadFile']) {
            
            // Обхождаме масива с хранилищата
            foreach ((array)$uploadFileaArr['reposArr'] as $repoId) {
                
                // Абсорбираме файла
                $fh = fileman_Repositories::absorbFileFromId($repoId, $uploadFileaArr['name'], $uploadFileaArr['title'], $uploadFileaArr['bucket']);
                
                // Ако има манипулатор на файла, прекъсваме
                if ($fh) break;
            }
            
            // Очакваме да има манипулатор
            expect($fh);
            
            // Добавяме манипулатор
            $rec->sourceFh = $fh;
        }
        
        // Ако има записи за изтриване
        if ($deleteRecArr = $mvc->actionWithFile['deleteRec']) {
            
            // Очакмва е да има id и да е равно на id' то на файла
            expect($rec->id && ($rec->id == $deleteRecArr['id']));
            
            // Очакваме изтриването да мине успешно
            expect($mvc->delete($deleteRecArr['id']));
            
            // Прекратяваме по нататъшното извикване на тази функция,
            // защото записа вече е изтрит
            return FALSE;
        }
        
        // Ако е сетнат да се изтрие файл от хранилища
        if ($deleteFileInReposArr = $mvc->actionWithFile['deleteFileInRepo']) {
            
            // Изтриваме файла от хранилищата
            $resArr = fileman_Repositories::deleteFileInRepos($deleteFileInReposArr['name'], $deleteFileInReposArr['deleteReposArr'], $deleteFileInReposArr['title']);
            
            // Очакваме да има изтрите
            expect($resArr['deleted']);
            
            // Премахваме изтритите
            unset($resArr['deleted']);
            
            // Ако има запис в масива
            if ($resArr) {
                
                // Записваме масива в лога
                static::saveToLog($resArr, $rec->id);
                
                // Сетваме грешка
                expect(FALSE);
            }
        }
    }
    
    
	/**
     * След изтриване на записа
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param core_Query $query
     */
    static function on_AfterDelete($mvc, &$res, $query, $cond)
    {
        // Ако е зададено да се изтрие само хранилището, без файла
        if ($mvc->onlyDelRepo) return ;
        
        // Вземаме изтритите записи
        $deletedRecsArr = $query->getDeletedRecs();
        
        // Обождаме масива с изтритите записи
        foreach ((array)$deletedRecsArr as $id => $deletedRec) {
            
            // Ако има мастър
            if (($masterKey = $mvc->masterKey) && ($deletedRec->$masterKey)) {
                
                // Вземаме заглавието на документа
                $title = $mvc->Master->getGroupTitle($deletedRec->$masterKey);
            }
            
            // Вземаме хранилищата в които се е намирал файла
            $reposArr = type_Keylist::toArray($deletedRec->repos);
            
            // Изтриваме файла от хранилищата в поддиректорията
            $resArr = fileman_Repositories::deleteFileInRepos($deletedRec->name, $reposArr, $title);
            
            // Очакваме да има изтрите
            expect($resArr['deleted']);
            
            // Премахваме масива с изтритите
            unset($resArr['deleted']);
            
            // Ако има запис в масива
            if ($resArr) {
                
                // Записваме масива в лога
                static::saveToLog($resArr, $id);
                
                // Сетваме грешка
                expect(FALSE);
            }
        }
    }
    
    
    /**
     * Синхронизира съдържанието на хранилищата с модела
     * 
     * @return array - Двумерен масив с добавените файлове в хранилищата
     */
    static function syncFiles()
    {
        // Вземаме текущия
        $me = cls::get(get_called_class());
        
        // Сетваме флага да не се изтрият файловете при изтриване на записа
        // Това е необходимо, защото записа се изтрива, ако няма файлове
        $me->onlyDelRepo = TRUE;
        
        // Инстанция на мастъра
        $Master = $me->Master;
        
        // Ключа към мастъра
        $masterKey = $me->masterKey;
        
        // Вземаме пътищата на активните групи
        $pathArr = $Master->getActiveGroupArr();
        
        // Резултатания масив, който ще връщаме
        $resArr = array();
        
        // Обхождаме масива
        foreach ((array)$pathArr as $masterId => $repoArr) {
            
            // Масив с всички хранилища и файловете, за този мастър
            $orArr = array();
            
            // Вземаме всички записи за този мастър
            $query = static::getQuery();
            $query->where(array("#{$masterKey} = '{$masterId}'"));
            
            // Обхождаме резултата
            while($rec = $query->fetch()) {
                
                // Масив с хранилищата от записа
                $repoArrFromRec = type_Keylist::toArray($rec->repos);
                
                // Обхождаме масива
                foreach ((array)$repoArrFromRec as $repoId) {
                    
                    // Добавяме в масива
                    $orArr[$repoId][$rec->name] = dt::mysql2timestamp($rec->modifiedOn);
                }
            }
            
            // Обхождаме масива с хранилищата и файловете
            foreach ((array)$repoArr as $repoId => $subPath) {
                
                try {
                    
                    // Вземаме всички достъпни файлове в хранилището, само от основната директория
                    $reposFileArr = fileman_Repositories::retriveFiles($repoId, $subPath, FALSE, 0);
                } catch (core_exception_Expect $e) {
                    
                    // Ако възникне грешка
                    // Записваме грешката
                    fileman_Repositories::logErr("Възникна грешка при обхождането на хранилището", $repoId);
                    
                    // Прескачаме хранилището
                    continue;
                }
                
                // Вземаме масив с файловете само в главната директрия
                $fileNameArr = (array)$reposFileArr['/']['files'];
                
                // Всички файлове в това хранилище
                $filesArrInThisRepo = (array)$orArr[$repoId];
                
                // Ако има масив
                if ($fileNameArr && count($fileNameArr)) {
                    
                    // Обхождаме масива
                    foreach ($fileNameArr as $fileName => $modifiedTime) {
                        
                        // Проверяваме времето на последна модификация
                        if (!fileman_Repositories::checkLastModified($modifiedTime)) {
                            
                            // Ако е в рамките на зададено от нас
                            // Премахваме от масивите
                            unset($fileNameArr[$fileName]);
                            unset($filesArrInThisRepo[$fileName]);
                        }
                    }
                    
                    // Ако има файлове в масива
                    if (count($fileNameArr)) {
                        
                        // Вземаме ключовете
                        $fileNameArrKeys = array_keys((array)$fileNameArr);
                        
                        // Създаваме масив с ключовете и стойностите
                        $fileNameArr = array_combine((array)$fileNameArrKeys, (array)$fileNameArrKeys);
                    }
                }
                
            
                // Ако има масив
                if ($filesArrInThisRepo && count($filesArrInThisRepo)) {
                    
                    // Обхождаме масива
                    foreach ($filesArrInThisRepo as $fileName => $modifiedTime) {
                        
                        // Проверяваме времето на последна модификация
                        if (!fileman_Repositories::checkLastModified($modifiedTime)) {
                            
                            // Ако е в рамките на зададено от нас
                            // Премахваме от масивите
                            unset($fileNameArr[$fileName]);
                            unset($filesArrInThisRepo[$fileName]);
                        }
                    }
                    
                    // Ако има файлове в масива
                    if (count($filesArrInThisRepo)) {
                        
                        // Вземаме ключовете
                        $filesArrInThisRepoKeys = array_keys((array)$filesArrInThisRepo);
                        
                        // Създаваме масив с ключовете и стойностите
                        $filesArrInThisRepo = array_combine((array)$filesArrInThisRepoKeys, (array)$filesArrInThisRepoKeys);
                    }
                }
                
                // Вземама масива с различията
                $diffArr = type_Keylist::getDiffArr($filesArrInThisRepo, $fileNameArr);
                
                // Ако има изтрити файлове
                if ($diffArr['delete']) {
                    
                    // Обхождаме масива
                    foreach ((array)$diffArr['delete'] as $deletedFiles => $dummy) {
                        
                        // Вземаме всички записи, на мастера в които файла съществува
                        $fileRec = static::fetch(array("#{$masterKey} = '{$masterId}' AND #name = '[#1#]'", $deletedFiles));
                        
                        // Премахваме id'то на хранилището
                        $fileRec->repos = type_Keylist::removeKey($fileRec->repos, $repoId);
                        
                        // Ако няма записи
                        if (type_Keylist::isEmpty($fileRec->repos)) {
                            
                            // Изтриваме запие
                            static::delete($fileRec->id);
                            
                            // Добавяме в масива
                            $resArr['deletedRec'][$fileRec->id] = $fileRec->id;
                        } else {
                            
                            // Записваме промениете
                            static::save($fileRec);
                            
                            // Добавяме в масива
                            $resArr['deletedRepo'][$fileRec->id] = $repoId;
                        }
                    }
                }
                
                // Ако има добавени файлове, които не фигурират в модела
                if ($diffArr['add']) {
                    
                    // Обхождаме масива
                    foreach ((array)$diffArr['add'] as $addFiles => $dummy) {
                        
                        // Вземаме записа за файла
                        $fileRec = static::fetch(array("#{$masterKey} = '{$masterId}' AND #name = '[#1#]'", $addFiles));
                        
                        // Ако има запис
                        if ($fileRec) {
                            
                            // Ако файла е отбелязан в хранилището
                            if (type_Keylist::isIn($repoId, $fileRec->repos)) {
                                
                                // Прескачаме
                                continue;
                            }
                        } else {
                            // Ако няма запис
                            // Създаваме такъв
                            $fileRec = new stdClass();
                            $fileRec->name = $addFiles;
                            $fileRec->{$masterKey} = $masterId;
                        }
                        
                        // Добавяме id-то на хранилището
                        $fileRec->repos = type_Keylist::addKey($fileRec->repos, $repoId);
                        
                        // Добавяме в масива
                        $resArr['addRepo'][$fileRec->id] = $repoId;
                        
                        // Записваме
                        static::save($fileRec);
                    }
                }
            }
        }
        
        // Изтриваме всички записи, за файлове които не се намират в някое хранилище
        $resArr['delete'] = static::delete("#repos IS NULL OR #repos = '|'");
        
        return $resArr;
    }
    
    
	/**
     * 
     * 
     * @param unknown_type $mvc
     * @param unknown_type $res
     * @param unknown_type $data
     */
    function on_AfterPrepareListRecs($mvc, &$res, $data)
    {
        // Масив с хранилищата и файловете в тях
        $reposAndFilesArr = array();
        
        // Масив за мастерите
        static $masterArr;
        
        // Обхождаме записите
        foreach ((array)$data->recs as $id => $rec) {
            
            // Ако има мастер
            if (($masterKey = $mvc->masterKey) && ($rec->$masterKey)) {
                
                // Ако не е обходен този запис
                if (!$masterArr[$rec->$masterKey]) {
                    
                    // Вземаме масива с хранилищата от мастера
                    $reposArr = $mvc->Master->getReposArr($rec->$masterKey);
                    
                    // Обхождаме масива
                    foreach ((array)$reposArr as $repoId => $dummy) {
                        
                        // Ако не е добавен
                        if (!$reposAndFilesArr[$repoId]) {
                            
                            // Добавяме
                            $reposAndFilesArr[$repoId] = array();
                        }
                    }
                    
                    // Добавяме към масива
                    $masterArr[$rec->$masterKey];
                }
            }
            
            // Масива от записа
            $reposArr = type_Keylist::toArray($rec->repos);
            
            // Обхождаме хранилищата
            foreach ((array)$reposArr as $repoId) {
                
                // Добавяме в хранилищата
                $reposAndFilesArr[$repoId][$id] = $id;
            }
        }
        
        // Добавяме масива
        $data->reposAndFilesArr = $reposAndFilesArr;
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
            $row->sourceFh = fileman::getLinkToSingle($rec->sourceFh, FALSE, array(), $rec->name);
        }
    }
    
    
    /**
     * 
     * 
     * @param unknown_type $mvc
     * @param unknown_type $data
     */
    static function on_AfterPrepareListRows($mvc, &$data)
    {
        // Икона за изтриване
        $delImg = ht::createElement('img', array('src' => sbf('img/16/delete.png', '')));
        
        // Икона за редактиране
        $editImg = ht::createElement('img', array('src' => sbf('img/16/edit-icon.png', '')));
        
        // Обхождаме масива с хранилищата и файловете в тях
        foreach ((array)$data->reposAndFilesArr as $repoId => $idsArr) {
            
            // Масив със вербалните данни
            $data->rowReposAndFilesArr[$repoId] = array();
            
            // Заглавие на хранилището
            $repoTitle = fileman_Repositories::getRepoName($repoId);
            
            // Обхождаме масива с id'та
            foreach ((array)$idsArr as $id) {
                
                // Нулираме
                $delLink = $editLink = NULL;
                
                // Името на файла
                // Ако има манипулатор, да е линка към сингъла
                $file = ($data->rows[$id]->sourceFh) ? $data->rows[$id]->sourceFh : $data->rows[$id]->name;
                
                // Ако няма създаден обект, създаваме такъв
                if (!$data->rowReposAndFilesArr[$repoId][$id]) $data->rowReposAndFilesArr[$repoId][$id] = new stdClass();
                
                // Добавяме файла в масива
                $data->rowReposAndFilesArr[$repoId][$id]->file = $file;
                
                // Информация за файла
                $data->rowReposAndFilesArr[$repoId][$id]->info = $data->rows[$id]->info;
                
                // Данни за модифициране
                $data->rowReposAndFilesArr[$repoId][$id]->modified = $data->rows[$id]->modifiedOn . tr(' |от|* ') . $data->rows[$id]->modifiedBy;
                
                // Ако имаме права за изтриване
                if ($mvc->haveRightFor('delete', $data->recs[$id])) {
                    
                    // Линк за изтриване от хранилището
                    $delLink = ht::createLink($delImg, array($mvc, 'removeFromRepo', $id, 'repoId' => $repoId, 'ret_url' => TRUE),
                                       tr('Наистина ли желаете да изтриете файла от хранилището?'), array('title' => tr('Изтриване')));
                }
                
                // Ако имаме права за редактиране
                if ($mvc->haveRightFor('edit', $data->recs[$id])) {
                    
                    // Линк за редактиране
                    $editLink = ht::createLink($editImg, array($mvc, 'edit', $id, 'ret_url' => TRUE),
                                       NULL, array('title' => tr('Редактиране')));
                }
                
                // Ако има линк за редактиране
                if ($editLink) {
                    
                    // Добавяме линка
                    $data->rowReposAndFilesArr[$repoId][$id]->tools = $editLink;
                }
                
                // Ако има линк за изтриване
                if ($delLink) {
                    
                    // Добавяме линка
                    $data->rowReposAndFilesArr[$repoId][$id]->tools .= $delLink;
                }
            }
        }
    }
    
    
    /**
     * 
     * 
     * @param unknown_type $mvc
     * @param unknown_type $tpl
     * @param unknown_type $data
     */
    function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        // Вземаме таблицата
        $tpl = $mvc->renderReposAndFiles($data);
        
        // Да не се изпълнява кода
        return FALSE;
    }
    
    
    /**
     * Рендира таблицата за хранилища и файлове
     * 
     * @param object $data - Данни
     */
    static function renderReposAndFiles($data)
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
        foreach ((array)$data->rowReposAndFilesArr as $repoId => $reposArr) {
            
            // Шаблон за таблица
            $tplTable = getTplFromFile('distro/tpl/FilesRepoTable.shtml');
            
            // Обхождаме масива с хранилищата
            foreach ($reposArr as $repo) {
                
                // Шаблон за ред в таблицата
                $tplRow = getTplFromFile('distro/tpl/FilesRepoTableRow.shtml');
                
                // Заместваме данните
                $tplRow->replace($repo->modified, 'modified');
                $tplRow->replace($repo->file, 'file');
                $tplRow->replace($repo->tools, 'tools');
                
                // Ако има информация
                if ($info = trim($repo->info)) {
                    
                    // Заместваме информацията
                    $tplRow->replace($info, 'fileInfo');
                }
                
                // Премахваме незаместените блокове
                $tplRow->removeBlocks();
                
                // Добавяме към шаблона за таблиците
                $tplTable->append($tplRow, 'repoRow');
            }
            
            // Линк към хранилището
            $repoTitleLink = fileman_Repositories::getLinkToSingle($repoId);
            
            // Добавяме в шаблона
            $tplTable->append($repoTitleLink,'repoTitle');
            
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
     * Екшън за премахване на файл от дадено хранилище
     */
    function act_RemoveFromRepo()
    {
        // Трябва да имаме права за редактиране
        $this->requireRightFor('edit');
        
        // id на записа
        $id = Request::get('id', 'int');
        
        // id на хранилище, от което ще се премахне
        $repoId = Request::get('repoId', 'int');
        
        // Записа
        $rec = $this->fetch($id);
        
        // Очакваме да има права за редактиране на записа
        $this->requireRightFor('edit', $rec);
        
        // Масив с хранилищататт
        $reposArr = type_Keylist::toArray($rec->repos);
        
        
        // Очакваме хранилището да е в записа
        expect(type_Keylist::isIn($repoId, $rec->repos));
        
        // Премахваме от записа
        $rec->repos = type_Keylist::removeKey($rec->repos, $repoId);
        
        // Ако има master
        if (($masterKey = $this->masterKey) && ($rec->$masterKey)) {
            
            // Вземаме заглавието/титлата на полето
            $title = $this->Master->getGroupTitle($rec->$masterKey);
        }
        
        // Масив с хранилища от които трябва да се премахне файла
        $deleteFileInReposArr = array(
                                     'title' => $title,
                                     'name' => $rec->name,
                                     'deleteReposArr' => $repoId,
                                     );
        
        // Добавяме масива
        $this->actionWithFile['deleteFileInRepo'] = $deleteFileInReposArr;
        
        // Ако записа мине успешно
        if ($this->save($rec)) {
            
            // Съобщени
            $msg = 'Успешно премахнат от хранилището';
        } else {
            
            // Ако има грешка
            $msg = 'Грешка при изтриването';
        }
        
        // URL за редирект
        $retUrl = getRetUrl();
        
        // Ако не е зададено
        if (!$retUrl) {
            
            // Да сочи към сингъла на мастера
            $retUrl = array($this->Master, 'single', $rec->$masterKey);
        }
        
        // Редиректваме
        return new Redirect($retUrl, tr($msg));
    }
    
    
    /**
     * Функция, която връща дали има запис към мастъра
     * 
     * @param int $masterId - id на мастъра
     * 
     * @return boolean
     */
    static function haveRec($me, $masterId)
    {
        // Ако има мастер
        if ($masterKey = $me->masterKey) {
            
            // Ако има запис към мастера
            if (static::fetch(array("#{$masterKey} = '[#1#]'", $masterId))) {
                
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    
    /**
     * Функция, която се вика от крон
     * Синрхоронизира файловете в хранилищитата с модела
     */
    static function cron_SyncFiles()
    {
        
        // Извикваме функцията и връщаме резултата му
        return static::syncFiles();
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $rec = new stdClass();
        $rec->systemId = 'SyncFiles';
        $rec->description = 'Синхронизиране на файловете в хранилищата със записите в модела';
        $rec->controller = $mvc->className;
        $rec->action = 'SyncFiles';
        $rec->period = 3;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 120;
        $res .= core_Cron::addOnce($rec);
    }
    
    
    /**
     * Записва масива в лога
     * 
     * @param array $resArr - Масив с данни
     * @param integer $id - id на записа
     */
    static function saveToLog($resArr, $id)
    {
        // Ако няма масив, да не се записва
        if (!$resArr) return ;
        
        // Сетваме грешката
        self::logErr('Възникна грешка: ' . serialize($resArr), $id);
    }
    
    
    /**
     * Екшън за добавяне на файлове в хранилищата от други документи
     */
    function act_addFiles()
    {
        // Права за работа с екшън-а
        $this->requireRightFor('add');
        
        // Манипулатора на файла
        $masterKey = Request::get($this->masterKey);
        
        // Ескейпваме манипулатора
        $masterKey = $this->db->escape($masterKey);
        
        // Записа за съответния файл
        $mRec = $this->Master->fetch($masterKey);
        
        // Очакваме да има такъв запис
        expect($mRec, 'Няма такъв запис.');
        
        // Проверяваме за права сингъла
        $this->Master->requireRightFor('single', $mRec);
        
        // Проверяваме права за добавяне
        $this->Master->requireRightFor('add', $mRec);
        
        // Трябва да имаме достъп до сингъла на нишката
        doc_Threads::requireRightFor('single', $mRec->threadId);
        
        //URL' то където ще се редиректва при отказ или след запис
        $retUrl = getRetUrl();
        $retUrl = ($retUrl) ? ($retUrl) : (array($this->Master, 'single', $mRec->id));
        
        // Вземаме масива с хранилищата
        $reposArr = $this->Master->getReposArr($mRec->id);
        
        // Вземамаме масива с документите и файловете в тях
        $docAndFilesArr = $this->Master->getFilesForAdd($masterKey);
        
        // Ако флага не е вдигнат
        expect($docAndFilesArr, 'Няма файлове');
        
        // Вземаме формата към този модел
        $form = $this->getForm();
        
        $filesInReposArr = array();
        $fileHndArr = array();
        
        // Обхождаме ги
        foreach ($docAndFilesArr as $docId => $filesArr) {
            
            // Обжодаме масива файлаове
            foreach ((array)$filesArr as $fileHnd => $dummy) {
                
                // Всички хранилища
                $sReposArr = $reposArr;
                
                // Ако файла е бил извлечен преди това, прескачаме
                if ($fileHndArr[$fileHnd]) continue;
                
                // Добавяме в масива с извлечените файлове
                $fileHndArr[$fileHnd] = $fileHnd;
                
                // Премахваме хранилищата в които файла се използва
                $sReposArr = static::removeUserRepos($reposArr, $mRec->id, $fileHnd);
                
                // Ако няма останали хранилища за предложения, прескачаме
                if (!$sReposArr) continue;
                
                // Обхождаме масива с всички останали хранилища
                foreach ($sReposArr as $repoId => $repoName) {
                    
                    // Добавяме линк към хранилището и файла
                    $filesInReposArr[$repoId][$fileHnd] = fileman::getLinkToSingle($fileHnd)->getContent();
                }
                
                // Вдигаме флага
                $haveSuggRepos = TRUE;
            }
        }
        
        // Ако няма файлове за покзване
        if (!$haveSuggRepos) {
            
            // Добавяме статус съобщение
            status_Messages::newStatus(tr('Няма други файлове за добавяне'));
            
            // Редиректваме
            return new Redirect($retUrl);
        }
        
        $repoFncArr = array();
        
        // Обхощдаме всички хранилища
        foreach ((array)$filesInReposArr as $repoId => $fileArr) {
            
            // Името на хранилището
            $repoName = 'repo' . $repoId;
            
            // Добавяме в масива за имената на функционалните полета
            $repoFncArr[$repoId] = $repoName;
            
            // Линк към сингъла на хранилището
            $link = fileman_Repositories::getLinkToSingle($repoId);
            
            // Добавяме функционалните полета
            $form->FNC($repoName, cls::get(('type_Set'), array('suggestions' => $fileArr)),
            		'input, hint=Добавяне на файл в хранилище', array('caption' => '|*' . $link));
        }
        
        // Въвеждаме id-то (и евентуално други silent параметри, ако има)
        $form->input(NULL, 'silent');
        
        // Въвеждаме съдържанието на полетата
        $form->input($repoFncArr);
        
        // Ако формата е изпратена без грешки
        if($form->isSubmitted()) {
            
            // Вземаме заглавието/титлата на полето
            $title = $this->Master->getGroupTitle($masterKey);
            
            $filesArrToSave = array();
            
            // Обхождаме фунцкионалните полета
            foreach ($repoFncArr as $repoId => $fncName) {
                
                // Ако няма избрани файлове, прескачаме
                if (!$form->rec->$fncName) continue;
                
                // Масив с избраните файлове в това хранилище
                $rFilesArr = type_Set::toArray($form->rec->$fncName);
                
                // Обхождаме масива
                foreach ($rFilesArr as $fileHnd) {
                    
                    // Добавяме в масива с файлове и хранилищата
                    $filesArrToSave[$fileHnd][$repoId] = $repoId;
                }
            }
            
            // Обхождаме масива с файлове и хранилищата
            foreach ($filesArrToSave as $fileHnd => $reposArrSave) {
                
                // Вземаме записа за файла
                $fRec = fileman_Files::fetchByFh($fileHnd);
                
                // Името на файла
                $nName = $fRec->name;
                
                // Брояч за името
                $nCounter = 0;
                
                // Проверяваме дали файла съществува
                while(static::getArrFilesExistInRepo($nName, $reposArrSave, $title)) {
                    
                    // Разделяме името и разширението на файла
                    $nNameArr = fileman_Files::getNameAndExt($nName);
                    
                    // Добавяме брояча към името
                    $nName = $nNameArr['name'] . '_' . $nCounter++;
                    
                    // Ако има разширениет на файла
                    if ($nNameArr['ext']) {
                        
                        // Добавяме разширението
                        $nName .= '.' . $nNameArr['ext'];
                    }
                }
                
                // Създаваме масив за добавяне от манипулатор на файл
                $addFromFhArr = array('title' => $title,
                					  'fileHnd' => $fRec->fileHnd,
                                      'reposArr' => $reposArrSave,
                                      'name' => $nName);
                
                // Добавяме масива
                $this->actionWithFile['addFromFh'] = $addFromFhArr;
                
                // Преобразуваме масива в keylist
                $kRepos = type_Keylist::fromArray($reposArrSave);
                
                // Ако има запис за този файл
                if ($rec = static::fetch(array("#groupId = '[#1#]' AND #name = '[#2#]' AND #sourceFh = '[#3#]'", $form->rec->groupId, $nName, $fRec->fileHnd))) {
                    
                    // Добавяме избраните хранилища
                    $rec->repos = type_Keylist::merge($rec->repos, $kRepos);
                } else {
                    
                    // Създаваме записа
                    $rec = new stdClass();
                    $rec->groupId = $form->rec->groupId;
                    $rec->sourceFh = $fRec->fileHnd;
                    $rec->name = $nName;
                    $rec->repos = $kRepos;
                }
                
                // Записваме промените
                static::save($rec);
            }
            
            // Редиректваме
            return new Redirect($retUrl);
        }
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = $repoFncArr;
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close16.png');
        
        // Линк към сингъла на мастера
        $groupName = doc_Containers::getLinkForSingle($mRec->containerId);
        
        // Добавяме титлата на формата
        $form->title = "Добавяне на файл в|* {$groupName}";
        
        // Рендираме изгледа
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Премахваме хранилищата от масива, които ги има записани в модела към този запис
     * 
     * @param array $reposArr - Масив с хранилищата
     * @param integer $groupId - id на групата
     * @param string $fileHnd - Манипулатор на файла
     * 
     * @return array
     */
    static function removeUserRepos($reposArr, $groupId, $fileHnd)
    {
        // Ако има файл със същото име в групата
        $rRepos = static::fetchField(array("#groupId = '[#1#]' AND #sourceFh = '[#2#]'", $groupId, $fileHnd), 'repos');
        
        // Ако има хранилища
        if ($rRepos) {
            
            // Масив с хранилищата, в които се намира файла
            $rReposArr = type_Keylist::toArray($rRepos);
            
            // Обхождаме масива
            foreach ($rReposArr as $repoId) {
                
                // Премахваме от предложенията
                unset($reposArr[$repoId]);
            }
        }
        
        return $reposArr;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Ако има права за добавяне и ако има файлове за добавяне
        if (($mvc->Master->haveRightFor('add', $data->masterData->rec->id)) && 
            ($filesAndDocArr = $mvc->Master->getFilesForAdd($data->masterData->rec->id))) {
            
            // Вземаме масива с хранилищата
            $reposArr = $mvc->Master->getReposArr($data->masterData->rec->id);
            
            // Обхождаме всички документи и файлоаве
            foreach (($filesAndDocArr) as $docId => $filesArr) {
                
                // Ако флага е вдигнат, прекъсваме цикъла
                if ($flag) break;
                
                // Обхождаме всички файлове
                foreach ($filesArr as $fileHnd => $dummy) {
                    
                    // Премахваме всички хранилища, в които се намира файла
                    $sReposArr = static::removeUserRepos($reposArr, $data->masterData->rec->id, $fileHnd);
                        
                    // Ако е масив и има хранилища
                    if (is_array($sReposArr) && count($sReposArr)) {
                        
                        // Вдигаме флага
                        $flag = TRUE;
                        
                        // Прекъсваме цикъла
                        break;
                    }
                }
            }
            
            // Ако флага е вдигнат
            if ($flag) {
                
                // Добавяме бутона
                $data->toolbar->addBtn('Добави', array(
                        $mvc,
                        'addFiles',
                        $mvc->masterKey => $data->masterData->rec->id,
                        'ret_url' => TRUE
                    ),
                    'id=btnFiles', 'ef_icon = img/16/attach_2.png, title=Добавяне на файлове');
            }
        }
    }
}
