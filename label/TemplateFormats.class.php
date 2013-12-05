<?php


/**
 * 
 *
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_TemplateFormats extends core_Detail
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Формати за параметрите';
    
    
    /**
     * 
     */
    var $singleTitle = 'Формати';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'label';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'label';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'label';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'label';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'label';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'label';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'label_Wrapper, plg_RowTools';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'templateId';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'id';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
//    var $fetchFieldsBeforeDelete = '';
    
    
    /**
     * 
     */
//    var $listFields = 'id, name, description, maintainers';
    
    
    /**
     * 
     */
//    var $currentTab = '';
    
    
    /**
     * Данни за тип
     */
    static $typeEnumOpt = 'caption=Надпис,counter=Брояч,image=Картинка';
    
    
    /**
     * 
     */
    static $bucket = 'label';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('templateId', 'key(mvc=label_Templates, select=title)', 'caption=Шаблон');
        $this->FLD('placeHolder', 'varchar', 'caption=Плейсхолдер, title=Име на плейсхолдер, mandatory');
        $this->FLD('type', 'enum(' . static::$typeEnumOpt . ')', 'caption=Тип, silent');
        $this->FLD('formatParams', 'blob(serialize, compress)', 'caption=Параметри, title=Параметри за конвертиране на шаблона, input=none');
        
        $this->setDbUnique('templateId, placeHolder');
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     * 
     * @param unknown_type $mvc
     * @param unknown_type $data
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Премахваме бутона за добавяне на нов
        $data->toolbar->removeBtn('btnAdd');
        
        // Ключа към мастъра на записа
        $masterKey = $data->masterKey;
        
        // Ако има id към мастера
        if($data->masterId) {
            
            // Създаваме запис
            $rec = new stdClass();
            
            // Добавяме мастера
            $rec->{$masterKey} = $data->masterId;
        }
        
        // Ако имаме права за добавяне
        if ($mvc->haveRightFor('add', $rec)) {
            
            // URL за добавяне
            $captionUrl = $counterUrl = $imageUrl = array(
                    $mvc,
                    'add',
                    $masterKey => $data->masterId,
                    'ret_url' => TRUE
                );
            
            // URL за добавяне на шаблон за надпис
            $captionUrl['type'] = 'caption';
            
            // Добавяме бутона
            $data->toolbar->addBtn('Нов надпис', $captionUrl,
                'id=btnAddCaption', 'ef_icon = img/16/star_2.png,title=Създаване на нов надпис'
            );
            
            // URL за добавяне на шаблон за брояч
            $counterUrl['type'] = 'counter';
            
            // Добавяме бутона
            $data->toolbar->addBtn('Нов брояч', $counterUrl,
                'id=btnAddCounter', 'ef_icon = img/16/star_2.png,title=Създаване на нов брояч'
            );
            
            // URL за добавяне  шаблон за изображение
            $imageUrl['type'] = 'image';
            
            // Добавяме бутона
            $data->toolbar->addBtn('Нова картинка', $imageUrl,
                'id=btnAddImage', 'ef_icon = img/16/star_2.png,title=Създаване на нова картинка'
            );
        }
    }
    
    
	/**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Ако не е зададен тип в записа
        if (!($type = $data->form->rec->type)) {
            
            // Типа от URL-то
            $type = Request::get('type');
        }
        
        // Масив с типовете на полето
        $typeArr = arr::make(static::$typeEnumOpt, TRUE);
        
        // Очакваме да има тип и типа да отговаря
        expect($type && $typeArr[$type]);
        
        // Задаваме да не може да се променя
        $data->form->setReadonly('type');
        
        // Вземаме масив с полетата
        $fieldsArr = static::getFieldsArrForType($type);
        
        // Показваме полетата
        $data->form->addFncFields($fieldsArr);
        
        // Вземаме данните от предишния запис
        $dataArr = $data->form->rec->formatParams;
        
        // Обхождаме масива
        foreach ((array)$dataArr as $fieldName => $value) {
            
            // Добавяме данните от записите
            $data->form->rec->$fieldName = $value;
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param label_TemplateFormats $mvc
     * @param core_Form $form
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
        // Ако формата е субмитната
        if ($form->isSubmitted()) {
            
            // Вземаме типа
            $type = $form->rec->type;
            
            // Ако редактираме записа
            if ($form->rec->id) {
                
                // Вземаме записа
                $rec = $mvc->fetch($form->rec->id);
                
                // Вземаме старите стойности
                $oldDataArr = $rec->formatParams;
            }
            
            // Масив с полетата за този тип
            $fieldsArr = static::getFieldsArrForType($type);
            
            // Обхождаме масива
            foreach ((array)$fieldsArr as $fieldName => $dummy) {
                
                // Ако има масив за старите данни и новта стойност е NULL
                if ($oldDataArr && ($form->rec->$fieldName === NULL)) {
                    
                    // Използваме старта стойност
                    $dataArr[$fieldName] = $oldDataArr[$fieldName];
                } else {
                    
                    // Добавяме данните от формата
                    $dataArr[$fieldName] = $form->rec->$fieldName;
                }
            }
            
            // Добавяме целия масив към формата
            $form->rec->formatParams = $dataArr;
        }
    }
    
    
    /**
     * Връща масив с полета за създаване в зависимост от типа
     * 
     * @param string $type - Името на типа, за което ще се търси
     * 
     * @return array - Двумерен масив с името и параметрите на тип
     */
    static function getFieldsArrForType($type)
    {
        // Масива, който ще връщаме
        $filedsArr = array();
        
        // В зависимост от типа
        switch ($type) {
            
            // Ако е плейсхолдер
            case 'caption':
                
                // Поле за максимален брой символи
                $filedsArr['MaxLength']['clsType'] = 'type_Int';
                $filedsArr['MaxLength']['type'] = 'int';
                $filedsArr['MaxLength']['caption'] = 'Макс. символи';
            break;
            
            // Ако е брояч
            case 'counter':
                
                // Поле за избор на брояч
                $filedsArr['CounterId']['clsType'] = 'type_Key';
                $filedsArr['CounterId']['type'] = 'key(mvc=label_Counters, select=name)';
                $filedsArr['CounterId']['caption'] = 'Брояч';
                
                // Вземаем всички баркодове, които можем да генерираме
                $barcodesArr = barcode_Generator::getAllowedBarcodeTypesArr();
                
                // Вземаем enum представянето
                $barcodeStr = type_Enum::fromArray($barcodesArr);
                
                // Поле за избор на баркод
                $filedsArr['BarcodeType']['clsType'] = 'type_Enum';
                $filedsArr['BarcodeType']['type'] = 'enum(' . $barcodeStr . ')';
                $filedsArr['BarcodeType']['caption'] = 'Тип баркод';
                
                // Поле за показване на баркод
                $filedsArr['Showing']['clsType'] = 'type_Enum';
                $filedsArr['Showing']['type'] = 'enum(barcodeAndStr=Баркод и стринг, string=Стринг, barcode=Баркод)';
                $filedsArr['Showing']['caption'] = 'Показване';
                $filedsArr['Showing']['title'] = 'Показване на баркод';
                
                // Поле за широчина
                $filedsArr['Width']['clsType'] = 'type_Int';
                $filedsArr['Width']['type'] = 'int';
                $filedsArr['Width']['caption'] = 'Широчина';
                $filedsArr['Width']['unit'] = 'mm';
                
                // Поле за височина
                $filedsArr['Height']['clsType'] = 'type_Int';
                $filedsArr['Height']['type'] = 'int';
                $filedsArr['Height']['caption'] = 'Височина';
                $filedsArr['Height']['unit'] = 'mm';
                
                // Поле за формат
                $filedsArr['Format']['clsType'] = 'type_Varchar';
                $filedsArr['Format']['type'] = 'varchar';
                $filedsArr['Format']['caption'] = 'Формат';
                
                // Поле дали за избор дали да се ротира
                $filedsArr['Rotation']['clsType'] = 'type_Enum';
                $filedsArr['Rotation']['type'] = 'enum(yes=Допустима, no=Недопустима, rotate=Винаги)';
                $filedsArr['Rotation']['caption'] = 'Ротация';
            break;
            
            case 'image':
                
                // Поле за широчина
                $filedsArr['Width']['clsType'] = 'type_Int';
                $filedsArr['Width']['type'] = 'int';
                $filedsArr['Width']['caption'] = 'Широчина';
                $filedsArr['Width']['unit'] = 'mm';
                
                // Поле за височина
                $filedsArr['Height']['clsType'] = 'type_Int';
                $filedsArr['Height']['type'] = 'int';
                $filedsArr['Height']['caption'] = 'Височина';
                $filedsArr['Height']['unit'] = 'mm';
                
                // Поле дали за избор дали да се ротира
                $filedsArr['Rotation']['clsType'] = 'type_Enum';
                $filedsArr['Rotation']['type'] = 'enum(yes=Допустима, no=Недопустима)';
                $filedsArr['Rotation']['caption'] = 'Ротация';
            break;
            
            default:
                expect(FALSE, $type);
            break;
        }
        
        return $filedsArr;
    }
    
    
    /**
     * Връща масив с полета за създаване за записите към masterId
     * 
     * @param integer $masterId - id на мастера
     * 
     * @return array - Двумерен масив с името и параметрите на тип
     */
    static function getFieldArrForTemplate($masterId)
    {
        // Инстанция на класа
        $me = cls::get(get_called_class());
        
        // Масив с типовете на полето
        $typeArr = arr::make(static::$typeEnumOpt, TRUE);
        
        // Резултатния масив
        $resArr =array();
        
        // Вземаме всички записи за съответния master, без броячите
        $query = $me->getQuery();
        $query->where(array("#{$me->masterKey} = '[#1#]'", $masterId));
        $query->where("#type != 'counter'");
        $query->orderBy('type', 'DESC');
        
        // Обхождаме резултатите
        while ($rec = $query->fetch()) {
            
            // Плейсхолдера
            $placeHolder = trim($rec->placeHolder);
            
            // Името на полето
            $placeHolderField = static::getPlaceholderFieldName($placeHolder);
            
            // Добавяме в масива името на полето
            $resArr[$placeHolderField]['caption'] = "Шаблони->" . $placeHolder;
            
            $resArr[$placeHolderField]['name'] = $placeHolder;
            
            // Ако типа е image
            if ($rec->type == 'image') {
                
                $resArr[$placeHolderField]['clsType'] = 'fileman_FileType';
                
                // Добавяме кофа за качване на файл
                $resArr[$placeHolderField]['type'] = 'fileman_FileType(bucket=' . static::$bucket . ')';
            } elseif ($rec->type == 'caption') {
                
                // Ако тупа е надпис
                
                $resArr[$placeHolderField]['clsType'] = 'type_Varchar';
                
                // Ако е зададена максимална дължина
                if ($maxLength = $rec->formatParams['MaxLength']) {
                    
                    // Задаваме стрингов тип с максимална дължина
                    $resArr[$placeHolderField]['type'] = "varchar({$maxLength})";
                } else {
                    
                    // Задаваме стрингов ти
                    $resArr[$placeHolderField]['type'] = 'varchar';
                }
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Връщаме името на полето
     * 
     * @param string $placeHolder - Името на плейсхолдера
     * 
     * @param string - Новото име на плейсхолдера
     */
    static function getPlaceholderFieldName($placeHolder)
    {
        
        return 'Field' . $placeHolder;
    }
    
    
    /**
     * Връща вербалното представаня на данните за плейсхолдера
     * 
     * @param integer $templateId - id на шаблона
     * @param string $place - Името на плейсхолдера
     * @param string $val - Вербалната стойност
     * @param string $labelId - id на етикета
     * 
     * @return string - Вербалното представяне на стойността
     */
    static function getVerbalTemplate($templateId, $place, $val, $labelId = NULL)
    {
        // Масив със записите
        static $recArr = array();
        
        // Масив с извлечените вербални стойности
        static $verbalValArr = array();
        
        // Уникален стринг, за да вземаме даден запис само един път
        $recStr = $place . '|' . $templateId;
        
        // Ако записа не е вземан преди
        if (!$recArr[$recStr]) {
            
            // Вземаме записа
            $recArr[$recStr] = static::fetch(array("#templateId = '[#1#]' AND #placeHolder = '[#2#]'", $templateId, $place));
        }
        
        // Записа
        $rec = $recArr[$recStr];
        
        // Типа
        $type = $rec->type;
        
        // TODO cache
        if ($type == 'caption') {
            
            // Стринга, който ще се използва в масива за ключ
            $valStr = $val;
            
            // Ако не е вземана стойността
            if (!$verbalValArr[$valStr]) {
                
                // Инстанциня на класа
                $Varchar = cls::get('type_Varchar');
                
                // Добавяме в масива
                $verbalValArr[$valStr] = $Varchar->toVerbal($val);
            }
            
            
        } elseif ($type == 'image') {
            
            // Стринга, който ще се използва в масива за ключ
            $valStr = $val . $rec->formatParams['Rotation'];
            
            // Ако не е вземана стойността
            if (!$verbalValArr[$valStr]) {
                
                // Масив за стойности
                $attr = array();
                
                // Ако има зададен стойност
                if ($val) {
                    
                    // Вземаме умалено изборажение със зададените размер
                    $thumb= new img_Thumb($val, $rec->formatParams['Width'], $rec->formatParams['Height']);
                    
                    // Ако е зададена възможна ротация
                    if ($rec->formatParams['Rotation'] == 'yes') {
                        
                        // Ако е добре да се ротира изображението
                        if ($thumb->isGoodToRotate($rec->formatParams['Width'], $rec->formatParams['Height'])) {
                            
                            // Ротираме изображението
                            // Променяме широчината и височината
                            $thumb->rotate();
                            
                            // Добавяме класа, че е ротиран
                            $attr['class'] = 'rotate';
                        }
                    }
                    
                    // Добавяме вербалната стойност
                    $verbalValArr[$valStr] = $thumb->createImg($attr);
                }
            }
        } elseif ($type == 'counter') {
            
            // Вземаме формата
            $formatVal = $rec->formatParams['Format'];
            
            // Ако има шаблон за субституиране с брояч
            if (label_Counters::haveCounterPlace($formatVal)) {
                
                // Заместваме брояча
                $formatVal = label_Counters::placeCounter($formatVal, $rec->formatParams['CounterId'], $labelId);
            }
            
            // Типа на баркода
            $barcodeType = $rec->formatParams['BarcodeType'];
            
            // Стринг за уникалност
            $valStr = $formatVal . '|' . $barcodeType;
            
            // Ако не е вземана стойността
            if (!$verbalValArr[$valStr]) {
                
                // Нилираме стойностите
                $attr = array();
                $rotate = FALSE;
                
                // Масив с размерите
                $size = array('width' => $rec->formatParams['Width'], 'height' => $rec->formatParams['Height']);
                
                // Масив с обърнатите размери
                $sizeRotate = array('height' => $rec->formatParams['Width'], 'width' => $rec->formatParams['Height']);
                
                // Вземаме минималната височина и широчината
                $minWidthAndHeight = barcode_Generator::getMinWidthAndHeight($barcodeType, $formatVal);
                
                // Ако няма да се ротира
                if ($rec->formatParams['Rotation'] == 'no') {
                    
                    // Очакваме размера на баркода да може да се вмести
                    barcode_Generator::checkSizes($barcodeType, $size, $minWidthAndHeight);
                } elseif ($rec->formatParams['Rotation'] == 'yes') {
                    
                    // TODO баркод и текст, само текст
                    
                    // Ако ще се ротира
                    try {
                        
                        // Проверяваме размера
                        barcode_Generator::checkSizes($barcodeType, $size, $minWidthAndHeight);
                    } catch (Exception $e) {
                        
                        // Ако възникне грешка със старите
                        
                        // Проверяваме с разменени размер
                        barcode_Generator::checkSizes($barcodeType, $sizeRotate, $minWidthAndHeight);
                        
                        // Вдигаме флага за ротиране
                        $rotate = TRUE;
                    }
                } elseif ($rec->formatParams['Rotation'] == 'rotate') {
                    
                    // Ако е зададено да се ротира твърдо
                    
                    // Проверяваме размера след ротиране
                    barcode_Generator::checkSizes($barcodeType, $sizeRotate, $minWidthAndHeight);
                    
                    // Вдигаме флага
                    $rotate = TRUE;
                }
                
                // Ако ще се ротира
                if ($rotate) {
                    
                    // Добавяме класа за ротиране
                    $attr = array('class' => 'rotate');
                    
                    // Променяме масива с размерите
                    $size = $sizeRotate;
                }
                
                // Вземаме вербалната стойност
                $verbalValArr[$valStr] = barcode_Generator::getLink($barcodeType, $formatVal, $size, $attr);
            }
        }
        
        return $verbalValArr[$valStr];
    }
    
    
    /**
     * 
     * 
     * @param unknown_type $mvc
     * @param unknown_type $row
     * @param unknown_type $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Масив с шаблоните
        static $fieldsArr=array();
        
        // Ако не е сетнат за този шаблон
        if(!$fieldsArr[$rec->type]) {
            
            // Вземаме полетата
            $fieldsArr[$rec->type] = static::getFieldsArrForType($rec->type);
        }
        
        // Нулираме стойността
        $row->formatParams = '';
        
        // Обхождаме масива с полетата
        foreach((array)$fieldsArr[$rec->type] as $name => $otherParams) {
            
            // Името на полето
            $fieldName = $otherParams['caption'];
            
            // Ескейпваме
            $fieldName = type_Varchar::escape($fieldName);
            $fieldName = core_Type::escape($fieldName);
            
            // Инстанция на класа
            $inst = cls::get($otherParams['clsType']);
            
            // Вербалната стойност
            $verbalVal = $inst->toVerbal($rec->formatParams[$name]);
            
            // Добавяме в полето
            $row->formatParams .= '<div>' . $fieldName . ': ' . $verbalVal . '</div>';
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     * 
     * @param unknown_type $mvc
     * @param unknown_type $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        // Инстанция на класа
        $Bucket = cls::get('fileman_Buckets');
        
        // Създаваме, кофа, където ще държим всички прикачени файлове
        $res .= $Bucket->createBucket(static::$bucket, 'Файлове в етикети', 'jpg,jpeg,png,bmp,gif,image/*', '10MB', 'user', 'user');
    }
}
