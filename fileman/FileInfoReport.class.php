<?php


/**
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка за файловете
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_FileInfoReport extends frame_BaseDriver
{
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'powerUser';
    
    
    /**
     * Заглавие
     */
    public $title = 'Файлове->Статистика';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Form $form
     */
    public function addEmbeddedFields(core_Form &$form)
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
        
        $query = fileman_Files::getQuery();

        $query->where("'{$fRec->usersSearch}' LIKE CONCAT('%|', #createdBy, '|%')");
        
        // Размяна, ако периодите са объркани
        if (isset($fRec->from) && isset($fRec->to) && ($fRec->from > $fRec->to)) {
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
        
        while($rec = $query->fetch()) {
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
            
            $data->files[$key]['cnt']++;
            $data->files[$key]['len'] += $rec->fileLen;
        }
        
        return $data;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public function on_AfterPrepareEmbeddedData($mvc, &$res)
    {
    }
    
    
    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     * 
     * @return core_ET
     */
    public function renderEmbeddedData($data)
    {
        $tpl = new ET(tr("|*
            <h1>|Статистика за файловете|*</h1>
            [#FORM#]
            <div>|Брой|*: [#CNT#]</div>
            <div>|Размер|*: [#LEN#]</div>
            [#FILES#]|*"));

        $form = cls::get('core_Form');
        
        $this->addEmbeddedFields($form);

        $form->rec = $data->fRec;
        $form->class = 'simpleForm';
        
        Mode::push('staticFormView', TRUE);
        $tpl->prepend($form->renderHtml(), 'FORM');
        Mode::pop();

        $tpl->placeObject($data->rec);
        
        $f = cls::get('core_FieldSet');
        
        // В зависимост от избраната група определяме типа на полето
        if ($data->fRec->groupBy == 'users') {
            $type = 'key(mvc=core_Users,select=names)';
            $groupTypeName = 'Потребител';
        } elseif ($data->fRec->groupBy == 'files') {
            $type = 'key(mvc=fileman_Files, select=name)';
            $groupTypeName = 'Файл';
        } else {
            $type = 'key(mvc=fileman_Buckets, select=name)';
            $groupTypeName = 'Кофа';
        }
        
    	$f->FLD('groupType', $type, 'caption=Кофа');
    	$f->FLD('cnt', 'int', 'caption=Брой');
    	$f->FLD('len', 'fileman_FileSize', 'caption=Размер');
        
    	$ft = $f->fields;
    	$groupType = $ft['groupType']->type;
        $cntType = $ft['cnt']->type;
        $lenType = $ft['len']->type;
    	
        $total = new stdClass();
        
        if($data->fRec->sorting) {
            list($column, $direction) = explode('_', $data->fRec->sorting);
        }
        
        $order = array();
        $rows = array();
        
        foreach((array)$data->files as $keyId => $fArr) {
            
    		$row = new stdClass();
    		$row->groupId = $groupType->toVerbal($keyId);
    		$varRowGroupId = $row->groupId;
    		
    		if ($data->fRec->groupBy == 'files') {
    		    $fileRec = fileman_Files::fetch($keyId);
    		    $row->groupId = fileman::getLinkToSingle($fileRec->fileHnd);
    		    $row->createdBy = crm_Profiles::createLink($fileRec->createdBy);
    		    $row->createdOn = dt::mysql2verbal($fileRec->createdOn, 'smartTime');
    		} elseif ($data->fRec->groupBy == 'users') {
    		    $row->groupId .= ' ' . crm_Profiles::createLink($keyId);
    		}
    		
    		$row->cnt = $cntType->toVerbal($fArr['cnt']);
    		$row->len = $lenType->toVerbal($fArr['len']);
            
    		$rows[$keyId] = $row;
    		
            if($data->fRec->sorting) {
                switch ($column) {
                    case 'cnt':
                        $val = $fArr['cnt'];
                    break;
                    
                    case 'len':
                        $val = $fArr['len'];
                    break;
                    
                    case 'group':
                        $val = mb_strtolower($varRowGroupId);
                    break;
                }
                
                $order[$keyId] = $val;
            }
        }
        
        $orderArr = array();
        
        if (!empty($order)) {
            if($direction == 'a') {
                asort($order);
            } else {
                arsort($order);
            }
            
            foreach((array)$order as $keyId => $dummy) {
                $orderArr[$keyId] = $rows[$keyId];
            }
        } else {
            $orderArr = $rows;
        }
        
    	$table = cls::get('core_TableView', array('mvc' => $f));
    	$tabFields = "groupId={$groupTypeName}, cnt=Брой, len=Размер";
    	if ($data->fRec->groupBy == 'files') {
    	    $tabFields = "groupId={$groupTypeName}, len=Размер, createdBy=Създадено->От, createdOn=Създадено->На";
    	}
    	$tableTpl = $table->get($orderArr, $tabFields);
        
    	$tpl->append($tableTpl, 'FILES');
    	$tpl->append($cntType->toVerbal($data->filesCnt), 'CNT');
    	$tpl->append($lenType->toVerbal($data->filesLen), 'LEN');
    	
        return  $tpl;
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
