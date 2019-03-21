<?php


/**
 * Работни карти за произвеждане
 * 
 * @category  vendors
 * @package   peripheral
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_WorkCards extends core_Master
{
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Работни карти';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $singleTitle = 'Работна карта';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'planning_Wrapper, label_plg_Print, plg_Created, plg_State, plg_Modified, plg_Rejected, plg_RowTools';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'admin, planning, ceo';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'admin, planning, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin, planning, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin, planning, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin, planning, ceo';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'admin, planning, ceo';
    
    
    /**
     * Кой има право да оттегля?
     */
    public $canReject = 'admin, planning, ceo';
    
    
    /**
     * Кой има право да възстановява?
     */
    public $canRestore = 'admin, planning, ceo';
    
    
    /**
     * Кой има право да разглежда сингъла?
     */
    public $canSingle = 'admin, planning, ceo';
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'label_SequenceIntf';
    
    
    /**
     * @see label_plg_Print
     */
    public $canPrintlabel = 'no_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('images', 'fileman_type_Files(bucket=workCards,align=vertical)', 'caption=Снимки,mandatory');
        $this->FLD('data', 'blob(compress,serialize)', 'caption=Данни, input=none');
    }
    
    
    /**
     * Изпълнява се след подготвянето на тулбара в листовия изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->addBtn('Стойности', array('label_Prints', 'add', 'classId' => $mvc->getClassid(), 'objectId' => -1, 'ret_url' => true), null, "ef_icon = img/16/price_tag_label.png,title=Разпечатване на етикет за стойности за работните карти");
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param planning_WorkCards     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Първичния ключ на направения запис
     * @param stdClass     $rec     Всички полета, които току-що са били записани
     * @param string|array $fields  Имена на полетата, които sa записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    public static function on_AfterSave($mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        $mvc->prepareFiles($rec->images);
    }
    
    
    /**
     * Помощна функция за стартиране на обработка на файловете
     * 
     * @param string|array $images
     */
    protected function prepareFiles($images)
    {
        $iArr = type_Keylist::toArray($images);
        foreach ($iArr as $imId) {
            $fRec = fileman::fetch($imId);
            $data = new stdClass();
            fileman_Indexes::prepare($data, $fRec->fileHnd);
        }
    }
    
    
    /**
     * Връща наименованието на етикета
     *
     * @param int $id
     *
     * @return string
     * 
     * @see label_SequenceIntf
     */
    public function getLabelName($id)
    {
        if ($id === -1) {
            
            return tr('Стойност за работна карта');
        }
    }
    
    
    /**
     * Връща масив с данните за плейсхолдерите
     *
     * @param int|NULL $objId
     *
     * @return array
     *               Ключа е името на плейсхолдера и стойностите са обект:
     *               type -> text/picture - тип на данните на плейсхолдъра
     *               len -> (int) - колко символа макс. са дълги данните в този плейсхолдер
     *               readonly -> (boolean) - данните не могат да се променят от потребителя
     *               hidden -> (boolean) - данните не могат да се променят от потребителя
     *               importance -> (int|double) - тежест/важност на плейсхолдера
     *               example -> (string) - примерна стойност
     * 
     * @see label_SequenceIntf
     */
    public function getLabelPlaceholders($objId = null)
    {
        $placeholders = array();
        if ($objId === -1) {
            $placeholders['BARCODE_WORK_CARDS'] = (object) array('type' => 'text', 'hidden' => TRUE);
            $placeholders['BARCODE_VAL'] = (object) array('type' => 'text');
        }
        
        return $placeholders;
    }
    
    
    /**
     * Броя на етикетите, които могат да се отпечатат
     *
     * @param int $id
     *
     * @return int
     * 
     * @see label_SequenceIntf
     */
    public function getLabelEstimatedCnt($id)
    {
    }
    
    
    /**
     * Връща масив с всички данни за етикетите
     *
     * @param int      $id
     * @param int      $cnt
     * @param bool     $onlyPreview
     * @param stdClass $lRec
     *
     * @return array - масив от масив с ключ плейсхолдера и стойността
     * 
     * @see label_SequenceIntf
     */
    public function getLabelData($id, $cnt, $onlyPreview = false, $lRec = null)
    {
        if ($id === -1) {
            $arr = array();
            for ($i = 1; $i <= $cnt; $i++) {
                
                $arr[] = array('BARCODE_WORK_CARDS' => str::addHash($lRec->BARCODE_VAL . '_' . str::getRand('***')));
            }
        }
        
        return $arr;
    }
    
    
    /**
     * Заглавие от източника на етикета
     *
     * @param core_Mvc $mvc
     * @param string   $res
     * @param mixed    $id
     *
     * @return void
     */
    public static function on_BeforeGetLabelSourceLink($mvc, &$res, $id)
    {
        if ($id === -1) {
            $res = tr('стойност за работна карта');
            return false;
        }
    }
    
    
    /**
     * Cron функция за извличане на стойностите от работните карти
     */
    public function cron_GetWorkCardsVal()
    {
        $query = $this->getQuery();
        $query->where("#state = 'draft'");
        $query->orWhere("#state = 'active'");
        
        $query->orderBy('modifiedOn', 'DESC');
        
        while ($rec = $query->fetch()) {
            if (!$rec->images) continue;
            
            $continue = false;
            
            $allImgArr = array();
            $allBarcodesArr = array();
            $sendNotification = false;
            
            $nArr = array($this, 'single', $rec->id, 'haveErr' => 1);
            
            // Опитваме се да извлечем баркода от документа
            if ($rec->state == 'draft') {
                $iArr = type_Keylist::toArray($rec->images);
                
                foreach ($iArr as $imId) {
                    $fRec = fileman::fetch($imId);
                    $ext = fileman::getExt($fRec->name);
                    
                    // Ако в момента се извлича баркод
                    $lockId = fileman_webdrv_Generic::getLockId('barcodes', $fRec->dataId);
                    if (core_Locks::isLocked($lockId)) {
                        
                        $continue = true;
                        
                        continue;
                    }
                    
                    // Ако е PDF - всяка страница я приемаме за отделнка картина
                    if ($ext == 'pdf') {
                        // Ако в момента се конвертира към JPG
                        $lockId = fileman_webdrv_Generic::getLockId('jpg', $fRec->dataId);
                        if (core_Locks::isLocked($lockId)) {
                            
                            $continue = true;
                            
                            continue;
                        }
                        
                        $jpgArr = fileman_Indexes::getInfoContentByFh($fRec->fileHnd, 'jpg');
                        
                        // Ако все още не са извлечени JPG файловете
                        if (empty($jpgArr) || isset($jpgArr['otherPagesCnt'])) {
                            
                            $continue = true;
                            
                            continue;
                        }
                        
                        foreach ($jpgArr as $jpgFh) {
                            $jpgFRec = fileman::fetchByFh($jpgFh);
                            
                            if (!$jpgFRec) {
                                $continue = true;
                                
                                continue;
                            }
                            
                            $lockId = fileman_webdrv_Generic::getLockId('barcodes', $jpgFRec->dataId);
                            if (core_Locks::isLocked($lockId)) {
                                
                                $continue = true;
                                
                                continue;
                            }
                            
                            if ($iRec = fileman_Indexes::fetch(array("#dataId = '[#1#]' AND #type = 'barcodes'", $jpgFRec->dataId))) {
                                $content = fileman_Indexes::decodeContent($iRec->content);
                                
                                if (is_object($content) && $content->errorProc) {
                                    $continue = true;
                                    
                                    continue;
                                }
                            } else {
                                $data = new stdClass();
                                fileman_Indexes::prepare($data, $jpgFh);
                                
                                $continue = true;
                            }
                            
                            $allImgArr[$jpgFh] = $jpgFh;
                        }
                    } else {
                        $allImgArr[$fRec->fileHnd] = $fRec->fileHnd;
                    }
                }
                
                if (empty($allImgArr)) {
                    $continue = true;
                    $sendNotification = true;
                }
                
                if (!$continue && !empty($allImgArr)) {
                    foreach ($allImgArr as $imgFh) {
                        $fRec = fileman::fetchByFh($imgFh);
                        
                        if ($iRec = fileman_Indexes::fetch(array("#dataId = '[#1#]' AND #type = 'barcodes'", $fRec->dataId))) {
                            $content = fileman_Indexes::decodeContent($iRec->content);
                            
                            if (is_object($content) && $content->errorProc) {
                                $continue = true;
                                continue;
                            }
                            
                            if ($content) {
                                $allBarcodesArr[$imgFh] = $content;
                            } else {
                                $sendNotification = true;
                                $continue = true;
                            }
                        } else {
                            $continue = true;
                            continue;
                        }
                    }
                }
                
                if ($continue) {
                    if (dt::addSecs(1000, $rec->modifiedOn) < dt::now()) {
                        $sendNotification = true;
                    }
                }
                
                if ($sendNotification) {
                    bgerp_Notifications::add("|Има файл, от който не може да се извлече баркод", $nArr, $rec->createdBy, 'warning');
                }
                
                if ($continue) {
                    
                    continue;
                }
                
                if (!$sendNotification) {
                    bgerp_Notifications::clear($nArr, $rec->createdBy);
                }
                
                $resBArr = array();
                foreach ($allBarcodesArr as $fh => $barcodesArr) {
                    foreach ($barcodesArr as $bArr) {
                        foreach ($bArr as $barcode) {
                            $resBArr[$fh][] = $barcode->code;
                        }
                    }
                }
                
                $rec->data = $resBArr;
                $rec->state = 'active';
                
                $this->save($rec, 'state, data');
            }
            
            if (!$rec->data) continue;
            
            $haveErr = false;
            $valArr = array();
            foreach ((array)$rec->data as $fh => $bArr) {
                foreach ($bArr as $barcode) {
                    // Ако баркода е линк
                    if (core_Url::isValidUrl2($barcode)) {
                        if (!core_Url::isLocal($barcode)) {
                            $haveErr = true;
                            
                            self::logWarning('Не е локално URL - ' . $barcode, $rec->id);
                            
                            break;
                        }
                        
                        $urlArr = explode('/', $barcode);
                        $len = count($urlArr);
                        
                        $id = $urlArr[$len-1];
                        $cls = $urlArr[$len-3];
                        
                        if ($cls && cls::load($cls, true)) {
                            $clsInst = cls::get($cls);
                            
                            if ($id = $clsInst->unprotectId($id)) {
                                if ($clsInst instanceof crm_Persons) {
                                    $valArr[$fh]['userId'] = crm_Profiles::fetchField(array("#personId = '[#1#]'", $id), 'userId');
                                } elseif ($clsInst instanceof planning_Tasks) {
                                    $pState = planning_Tasks::fetchField($id, 'state');
                                    if (($pState == 'draft') || ($pState == 'rejected')) {
                                        $haveErr = true;
                                        break;
                                    }
                                    
                                    $valArr[$fh]['tId'] = $id;
                                } else {
                                    $haveErr = true;
                                    break;
                                }
                            } else {
                                $haveErr = true;
                                break;
                            }
                        } else {
                            $haveErr = true;
                            break;
                        }
                    } else {
                        // Ако е число, очакваме да е коректно
                        if (str::checkHash($barcode)) {
                            
                            list($bVal) = explode('_', $barcode, 2);
                            
                            if (!$bVal) {
                                $haveErr = true;
                                break;
                            }
                            
                            $valArr[$fh]['val'][] = $bVal;
                        } else {
                            $haveErr = true;
                            
                            self::logWarning('Не може да се определи стойността от баркода - ' . $barcode, $rec->id);
                            
                            break;
                        }
                    }
                }
                
                if ($haveErr) {
                    bgerp_Notifications::add("|Не може да се определи стойността от баркода", $nArr, $rec->createdBy, 'warning');
                    
                    break ;
                }
                
                // След подготвяне на всички баркодове, добавяме запис в детайла
                if (!empty($valArr)) {
                    $rec->state = 'closed';
                    $this->save($rec, 'state');
                    
                    try {
                        foreach ($valArr as $v) {
                            $pRec = planning_Tasks::fetch($v['tId'], 'productId, packagingId');
                            
                            if (!planning_ProductionTaskDetails::haveRightFor('add', (object)array('taskId' => $v['tId']), $v['userId'])) {
                                planning_Tasks::logErr('Добавяне на прозвеждане, от потребител ' . core_Users::getNick($v['userId']) . ', който няма права: ' . core_Type::mixedToString($v['val']), $v['tId']);
                                bgerp_Notifications::add('|Добавяне на прозвеждане, от потребител|* ' . core_Users::getNick($v['userId']) . ', |който няма права', $nArr, $rec->createdBy, 'warning');
                            }
                                
                            foreach ((array)$v['val'] as $qty) {
                                
                                $sRec = new stdClass();
                                $sRec->quantity = $qty;
                                $sRec->taskId = $v['tId'];
                                $sRec->employees = type_Keylist::addKey('', $v['userId']);
                                $sRec->type = 'production';
                                $sRec->productId = $pRec->productId;
                                
                                $canStore = cat_Products::fetchField($pRec->productId, 'canStore');
                                if($canStore == 'yes' && !empty($pRec->packagingId)){
                                    $sRec->_generateSerial = true;
                                }
                                
                                planning_ProductionTaskDetails::save($sRec);
                            }
                        }
                    } catch (Exception $e) {
                        self::logErr('Грешка при добавяне на запис', $rec->id);
                        reportException($e);
                        
                        $rec->state = 'waiting';
                        $this->save($rec, 'state');
                    }
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete' || $action == 'edit') {
            if ($rec && $rec->state != 'draft') {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'reject') {
            if ($rec->state != 'active') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'getWorkCardsVal';
        $rec->description = 'Извличане на стойности от работните карти';
        $rec->controller = $mvc->className;
        $rec->action = 'getWorkCardsVal';
        $rec->period = 1;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 50;
        $res .= core_Cron::addOnce($rec);
    }
}
