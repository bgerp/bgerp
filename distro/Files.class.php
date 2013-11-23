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
    var $canRead = 'powerUser';
    
    
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
    var $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'powerUser';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'distro_Wrapper, plg_RowTools';
    
    
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
     * 
     */
//    var $listFields = 'id, name, description, maintainers';
    
    
    /**
     * 
     */
//    var $currentTab = '';
    
    
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
        if ($action == 'add' || $action == 'edit') {
            
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
            
            // Ако създаваме нов запис
            if (!$form->rec->id) {
                
                // Ако има манипулато на файла
                if ($form->rec->sourceFh) {
                    
                    // Сетваме името от манипулатора
                    $form->rec->name = fileman_Files::fetchByFh($form->rec->sourceFh, 'name');
                }
                
                // Вземаме масива с хранилищата
                $reposArr = type_Keylist::toArray($form->rec->repos);
                
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
                    
                    // Създаваме масив за добавяне от манипулато на файл
                    $addFromFhArr = array('title' => $title,
                    					  'fileHnd' => $form->rec->sourceFh,
                                          'reposArr' => $reposArr,
                                          'name' => $form->rec->name);
                    
                    // Добавяме масива
                    $mvc->actionWithFile['addFromFh'] = $addFromFhArr;
                }
            } else {
                
                // Ако редактираме записа
                
                // Вземаме оригиналния запис
                $rec = $mvc->fetch($form->rec->id);
                
                // Вземаме масива с различията между хранилищата в модела и във формата
                $diffArr = static::getDiffArr($rec->repos, $form->rec->repos);
                
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
                        
                        // Проверяваме дали файла съществува
                        $reposArrWithRenamedFile = static::getArrFilesExistInRepo($form->rec->name, $renamedReposArr, $title);
                        
                        // Ако файла с новото име съществува някъде
                        if ($reposArrWithRenamedFile) {
                            
                            // Сетваме грешката
                            $form->setError('name', 'Файл със същото име съществува в хранилищата|*: ' . implode(', ', $reposArrWithRenamedFile));
                        } elseif ($renamedReposArr) {
                            
                            // Сетваме масива за преименуване
                            $renameRepoArr = array(
                                'oldName' => $rec->name,
                                'newName' => $form->rec->name,
                                'repos' => $renamedReposArr,
                                'title' => $title,
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
     * Връща масив с различията между хранилищата
     * 
     * @param string $fRepos
     * @param string $lRepos
     * 
     * @return array $arr - Масив с различията
     * $arr['same'] - без промяна
     * $arr['delete'] - изтрити от първия
     * $arr['add'] - добавени към първия
     */
    static function getDiffArr($fRepos, $lRepos)
    {
        // Вземаме масива на първия
        $fReposArr = type_Keylist::toArray($fRepos);
        
        // Вземаме масива на втория
        $lReposArr = type_Keylist::toArray($lRepos);
        
        // Изчисляваме различичта
        $arr['same'] = array_intersect($fReposArr, $lReposArr);
        $arr['delete'] = array_diff($fReposArr, $lReposArr);
        $arr['add'] = array_diff($lReposArr, $fReposArr);
        
        return $arr;
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
        static::log('Възникна грешка: ' . serialize($resArr), $id);
    }
}
