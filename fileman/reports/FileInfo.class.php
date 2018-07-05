<?php


/**
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка за файловете
 *
 * @category  fileman
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_reports_FileInfo extends frame_BaseDriver
{

    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'fileman_FileInfoReport';
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'powerUser';
    
    
    /**
     * Заглавие
     */
    public $title = 'Файлове » Статистика';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 20;
    
    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Form $form
     */
    public function addEmbeddedFields(core_FieldSet &$form)
    {
        $form->FLD('usersSearch', 'users(rolesForAll=ceo|report|admin, rolesForTeams=ceo|report|admin|manager)', 'caption=Потребители,mandatory');
        $form->FLD('groupBy', 'enum(users=Потребители, buckets=Кофи, files=Файлове)', 'caption=Групиране по');
        $form->FLD('sorting', 'enum(,group_a=Група (възходящо),group_z=Група (низходящо),cnt_a=Брой (възходящо),cnt_z=Брой (низходящо),
    								len_a=Размер (възходящо),len_z=Размер (низходящо))', 'caption=Подреждане по');
        $form->FLD('bucketId', 'key(mvc=fileman_Buckets, select=name, allowEmpty)', 'caption=Кофа, placeholder=Всички');
        $form->FLD('from', 'date', 'caption=Начало');
        $form->FLD('to', 'date', 'caption=Край');
    }

    
    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
        $cu = core_Users::getCurrent();
        
        if (haveRole('ceo, report, admin', $cu)) {
            $form->setDefault('usersSearch', 'all_users');
        }
        
        $form->setDefault('groupBy', 'users');
    }
    
    
    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
    public function checkEmbeddedForm(core_Form &$form)
    {
    }
    
    
    /**
     * Подготвя вътрешното състояние, на база въведените данни
     *
     * @return object
     */
    public function prepareInnerState()
    {
        $data = new stdClass();
        $data->filesCnt = 0;
        $data->filesLen = 0;
        $data->files = array();
        $data->files = array();
        $fRec = $data->fRec = $this->innerForm;
        
        $this->prepareListFields($data);
        
        $query = fileman_Files::getQuery();

        $query->where("'{$fRec->usersSearch}' LIKE CONCAT('%|', #createdBy, '|%')");
        
        // Размяна, ако периодите са объркани
        if (isset($fRec->from, $fRec->to) && ($fRec->from > $fRec->to)) {
            $mid = $fRec->from;
            $fRec->from = $fRec->to;
            $fRec->to = $mid;
        }
        
        if ($fRec->from) {
            $fRec->from .= ' 00:00:00';
            $query->where("#createdOn >= '{$fRec->from}'");
        }

        if ($fRec->to) {
            $fRec->to .= ' 23:59:59';
            $query->where("#createdOn <= '{$fRec->to}'");
        }

        if ($fRec->bucketId) {
            $query->where("#bucketId = '{$fRec->bucketId}'");
        }
        
        // Ако се групира по файлове, показваме само избраните файлове
        if ($fRec->groupBy == 'files') {
            $query->limit(50);
            $query->orderBy('fileLen', 'DESC');
        }
        
        while ($rec = $query->fetch()) {
            $data->filesCnt++;
            $data->filesLen += $rec->fileLen;
            
            // В зависимост от избраната група определяме ключа за масива
            if ($fRec->groupBy == 'users') {
                $key = $rec->createdBy;
            } elseif ($fRec->groupBy == 'files') {
                $key = $rec->id;
            } else {
                $key = $rec->bucketId;
            }
            $data->files[$key]['groupId'] = $key;
            $data->files[$key]['cnt']++;
            $data->files[$key]['len'] += $rec->fileLen;
            $data->files[$key]['key'] = $key;
        }
        
        $order = array();
        if ($data->fRec->sorting) {
            list($column, $direction) = explode('_', $data->fRec->sorting);
        }
       
        foreach ((array) $data->files as $keyId => $fArr) {
            if ($data->fRec->sorting) {
                switch ($column) {
                    case 'cnt':
                        if ($direction == 'a') {
                            usort($data->files, function ($a, $b) {
                                return ($a['cnt'] > $b['cnt']) ? 1 : -1;
                            });
                        } else {
                            usort($data->files, function ($a, $b) {
                                return ($a['cnt'] > $b['cnt']) ? -1 : 1;
                            });
                        }
                        break;
            
                        case 'len':
                        if ($direction == 'a') {
                            usort($data->files, function ($a, $b) {
                                return ($a['len'] > $b['len']) ? 1 : -1;
                            });
                        } else {
                            usort($data->files, function ($a, $b) {
                                return ($a['len'] > $b['len']) ? -1 : 1;
                            });
                        }
                        break;
                }
            }
        }

        return $data;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public function on_AfterPrepareEmbeddedData($mvc, &$data)
    {
        // Ако има намерени записи
        if (count($data->files)) {
            if (!Mode::is('printing')) {
                // Подготвяме страницирането
                $pager = cls::get('core_Pager', array('itemsPerPage' => $mvc->listItemsPerPage));
                $pager->setPageVar($mvc->EmbedderRec->className, $mvc->EmbedderRec->that);
                $data->Pager = $pager;
                $data->Pager->itemsCount = count($data->files);
            }
            // За всеки запис
            foreach ($data->files as $keyId => &$fArr) {
                if (!Mode::is('printing')) {
                    // Ако не е за текущата страница не го показваме
                    if (!$data->Pager->isOnPage()) {
                        continue;
                    }
                }

                // Вербално представяне на записа
                $data->rows[] = $mvc->getVerbal($keyId, $fArr);
            }
        
            if (strpos($data->fRec->sorting, 'group') !== false) {
                usort($data->rows, function ($a, $b) {
                    return strcasecmp(mb_strtolower($a->groupId, 'UTF-8'), mb_strtolower($b->groupId, 'UTF-8'));
                });
                
                if (strpos($data->fRec->sorting, 'group_z') !== false) {
                    for ($i = count($data->rows) - 1; $i >= 0; $i--) {
                        $a[] = $data->rows[$i];
                    }
                    $data->rows = $a;
                }
            }
        }
    }
    
    
    /**
     * Вербалното представяне на ред от таблицата
     */
    protected function getVerbal_($key, $rec)
    {
        $Key = cls::get('type_Key');
        $Int = cls::get('type_Int');
        $FileSize = cls::get('fileman_FileSize');
        
        $row = new stdClass();

        if ($this->innerForm->groupBy == 'files') {
            $fileRec = fileman_Files::fetch($rec['key']);
            $row->groupId = fileman::getLinkToSingle($fileRec->fileHnd);
            $row->createdBy = crm_Profiles::createLink($fileRec->createdBy);
            $row->createdOn = dt::mysql2verbal($fileRec->createdOn, 'smartTime');
        } elseif ($this->innerForm->groupBy == 'users') {
            if (core_Users::fetchField($rec['key'], 'names') !== false) {
                $names = core_Users::fetchField($rec['key'], 'names');
                $row->groupId = $names . ' ' . crm_Profiles::createLink($rec['key']);
            } else {
                $row->groupId = ' ' . crm_Profiles::createLink($rec['key']);
            }
        } else {
            $bucketRec = fileman_Buckets::fetch($rec['key']);
            $row->groupId = $bucketRec->name;
        }
        
        $row->cnt = $Int->toVerbal($rec['cnt']);
        $row->len = $FileSize->toVerbal($rec['len']);
        $row->key = $rec['key'];

        return $row;
    }
    
    
    /**
     * Подготвя хедърите на заглавията на таблицата
     */
    protected function prepareListFields_(&$data)
    {
        // В зависимост от избраната група определяме типа на полето
        if ($data->fRec->groupBy == 'users') {
            $data->listFields['groupId'] = 'Потребител';
            $data->listFields['cnt'] = 'Брой';
            $data->listFields['len'] = 'Размер';
        } elseif ($data->fRec->groupBy == 'files') {
            $data->listFields['groupId'] = 'Файл';
            $data->listFields['len'] = 'Размер';
            $data->listFields['createdBy'] = 'Създадено->От';
            $data->listFields['createdOn'] = 'Създадено->На';
        } else {
            $data->listFields['groupId'] = 'Кофа';
            $data->listFields['cnt'] = 'Брой';
            $data->listFields['len'] = 'Размер';
        }
    }
    
    
    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     *
     * @return core_ET
     */
    public function renderEmbeddedData(&$embedderTpl, $data)
    {
        $tpl = new ET(tr('|*
            <h1>|Статистика за файловете|*</h1>
            [#FORM#]
            <div>|Брой|*: [#CNT#]</div>
            <div>|Размер|*: [#LEN#]</div>
            [#PAGER#]
            [#FILES#]
            [#PAGER#]
            |*'));
        $explodeTitle = explode(' » ', $this->title);
         
        $title = tr("|{$explodeTitle[1]}|*");
        
        $tpl->replace($title, 'TITLE');
        
        $this->prependStaticForm($tpl, 'FORM');
        
        $tpl->placeObject($data->row);

        $f = $this->getFields();

        // Рендираме таблицата
        $table = cls::get('core_TableView', array('mvc' => $f));

        $tableHtml = $table->get($data->rows, $data->listFields);
        
        // Рендираме пейджъра, ако го има
        if (isset($data->Pager)) {
            $tpl->replace($data->Pager->getHtml(), 'PAGER');
        }
        
        $tpl->append($tableHtml, 'FILES');
        $tpl->append($data->filesCnt, 'CNT');
        $tpl->append(cls::get('fileman_FileSize')->toVerbal($data->filesLen), 'LEN');
        
        $embedderTpl->append($tpl, 'data');
    }
    
    
    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     * @todo да се замести в кода по-горе
     */
    protected function getFields_()
    {
        // Кои полета ще се показват
        $f = new core_FieldSet;

        $f->FLD('cnt', 'int');
        $f->FLD('len', 'fileman_FileSize');

        // В зависимост от избраната група определяме типа на полето
        if ($this->innerState->fRec->groupBy == 'users') {
            $f->FLD('groupId', 'key(mvc=core_Users,select=names)');
        } elseif ($this->innerState->fRec->groupBy == 'files') {
            $f->FLD('groupId', 'key(mvc=fileman_Files, select=name)');
            $f->FLD('createdOn', 'datetime');
            $f->FLD('createdBy', 'key(mvc=crm_Profiles,select=createdBy)');
        } else {
            $f->FLD('groupId', 'key(mvc=fileman_Buckets, select=name)');
        }

        return $f;
    }

    
    /**
     * Създаваме csv файл с данните
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public function exportCsv()
    {
        $exportFields = $this->innerState->listFields;
        $fields = $this->getFields();
        
        $dataRec = array();

        foreach ($this->innerState->files as $id => $rec) {
            $rowC = new stdClass();
            foreach (array('len','cnt','createdOn', 'createdBy') as $fld) {
                if (!is_null($rec[$fld])) {
                    $rowC->{$fld} = $rec[$fld];
                }
            }
            $rowC->groupId = $rec['key'];
            $dataRec[] = $rowC;
        }

        /* foreach($this->prepareEmbeddedData()->rows as $k=>$v) {
             $a[$k] = $this->innerState->files[$v->key];
             $a[$k]['groupId'] = $v->key;
             unset($a[$k]['key']);
         }*/

        $csv = csv_Lib::createCsv($dataRec, $fields, $exportFields);
       
        return $csv;
    }

    
    /**
     * Скрива полетата, които потребител с ниски права не може да вижда
     */
    public function hidePriceFields()
    {
    }
      
      
    /**
     * Коя е най-ранната дата на която може да се активира документа
     */
    public function getEarlyActivation()
    {
        return $this->innerForm->to . ' 23:59:59';
    }
}
