<?php


/**
 * Детайл на шаблоните за етикетите.
 * Съдържа типа на плейсхолдерите в шаблона
 *
 * @category  bgerp
 * @package   label
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class label_TemplateFormats extends core_Detail
{
    /**
     * Заглавие на модела
     */
    public $title = 'Формати за параметрите';
    
    
    public $singleTitle = 'Формати';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'label, admin, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'labelMaster, admin, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'label_Wrapper, plg_RowTools, plg_SaveAndNew';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'templateId';
    
    
    public $listFields = 'type, placeHolder, formatParams';
    
    
    public $rowToolsField = 'type';
    
    
    /**
     * Данни за тип
     */
    protected static $typeEnumOpt = 'caption=Надпис,counter=Брояч,image=Картинка,html=HTML,barcode=Баркод';
    
    
    /**
     * Кофа
     */
    protected static $bucket = 'label';
    
    
    /**
     * Активен таб
     */
    public $currentTab = 'Шаблони';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('templateId', 'key(mvc=label_Templates, select=title)', 'caption=Шаблон');
        $this->FLD('placeHolder', 'varchar', 'caption=Плейсхолдер, title=Име на плейсхолдер, mandatory, refreshForm');
        $this->FLD('type', 'enum(' . self::$typeEnumOpt . ')', 'caption=Тип, silent, mandatory, remember');
        $this->FLD('formatParams', 'blob(serialize, compress)', 'caption=Параметри, title=Параметри за конвертиране на шаблона, input=none');
        
        $this->setDbUnique('templateId, placeHolder');
    }
    
    
    /**
     * Добавяне на параметър към шаблон за етикети, или обновяване на съществуващ
     *
     * @param int        $templateId  - ид на шаблона
     * @param string     $placeholder - име на плейсхолдъра
     * @param string     $type        - тип на параметъра (caption, counter, image, html, barcode)
     * @param array|NULL $params      - допълнителни параметри
     *
     * @return int
     */
    public static function addToTemplate($templateId, $placeholder, $type, $params = null)
    {
        expect(label_Templates::fetchField($templateId), 'Несъществуващ шаблон');
        expect($placeholder, 'Липсва плейсхолдър');
        $types = arr::make(self::$typeEnumOpt);
        expect(array_key_exists($type, $types), "Невалиден тип '{$type}'");
        expect(is_null($params) || is_array($params));
        if ($type == 'counter' || $type == 'barcode') {
            expect(in_array($params['Showing'], array('barcodeAndStr', 'string', 'barcode')), $params['Showing']);
            setIfNot($params['Rotation'], 'no');
            expect(in_array($params['Rotation'], array('yes', 'no')), $params['Rotation']);
            expect(array_key_exists($params['BarcodeType'], barcode_Generator::getAllowedBarcodeTypesArr()), $params['BarcodeType']);
            expect(type_Int::isInt($params['Width']), $params['Width']);
            expect(type_Int::isInt($params['Height']), $params['Height']);
            setIfNot($params['Ratio'], '1');
            expect(in_array($params['Ratio'], array('1', '2', '3', '4')), $params['Ratio']);
            
            if ($type == 'counter') {
                expect(label_Counters::fetchField($params['CounterId']));
            }
        }
        
        $newRec = (object) array('templateId' => $templateId, 'placeHolder' => $placeholder, 'type' => $type, 'formatParams' => $params);
        
        $self = cls::get(get_called_class());
        if (!$self->isUnique($newRec, $fields, $exRec)) {
            $newRec->id = $exRec->id;
        }
        
        return $self->save($newRec);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     *
     * @param label_TemplateFormats $mvc
     * @param stdClass              $data
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Премахваме бутона за добавяне на нов
        $data->toolbar->removeBtn('btnAdd');
        
        // Ключа към мастъра на записа
        $masterKey = $data->masterKey;
        
        // Ако има id към мастера
        if ($data->masterId) {
            
            // Създаваме запис
            $rec = new stdClass();
            
            // Добавяме мастера
            $rec->{$masterKey} = $data->masterId;
        }
        
        // Ако имаме права за добавяне
        if ($mvc->haveRightFor('add', $rec)) {
            
            // URL за добавяне
            $captionUrl = $counterUrl = $imageUrl = $htmlUrl = $barcodeUrl = array(
                $mvc,
                'add',
                $masterKey => $data->masterId,
                'ret_url' => true
            );
            
            // URL за добавяне на шаблон за надпис
            $captionUrl['type'] = 'caption';
            
            // Добавяме бутона
            $data->toolbar->addBtn(
                'Нов надпис',
                $captionUrl,
                'id=btnAddCaption',
                'ef_icon = img/16/text.png, title=Създаване на нов надпис'
            );
            
            // URL за добавяне на шаблон за брояч
            $counterUrl['type'] = 'counter';
            
            // Добавяме бутона
            $data->toolbar->addBtn(
                'Нов брояч',
                $counterUrl,
                'id=btnAddCounter',
                'ef_icon = img/16/counter-icon.png, title=Създаване на нов брояч'
            );
            
            // URL за добавяне  шаблон за изображение
            $imageUrl['type'] = 'image';
            
            // Добавяме бутона
            $data->toolbar->addBtn(
                'Нова картинка',
                $imageUrl,
                'id=btnAddImage',
                'ef_icon = img/16/image.png, title=Създаване на нова картинка'
            );
            
            // URL за добавяне  шаблон за изображение
            $htmlUrl['type'] = 'html';
            
            // Добавяме бутона
            $data->toolbar->addBtn(
                'Нов HTML',
                $htmlUrl,
                'id=btnAddHTML',
                'ef_icon = img/16/html-icon.png, title=Създаване на нов HTML'
            );
            
            $barcodeUrl['type'] = 'barcode';
            
            // Добавяме бутона
            $data->toolbar->addBtn(
                'Нов баркод',
                $barcodeUrl,
                    'id=btnAddBarcode',
                'ef_icon = img/16/barcode-icon.png, title=Създаване на нов баркод'
            );
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Ако не е зададен тип в записа
        if (!($type = $data->form->rec->type)) {
            
            // Типа от URL-то
            $type = Request::get('type');
        }
        
        // Масив с типовете на полето
        $typeArr = arr::make(self::$typeEnumOpt, true);
        
        // Очакваме да има тип и типа да отговаря
        expect($type && $typeArr[$type]);
        
        // Задаваме да не може да се променя
        $data->form->setReadonly('type');
        
        // Вземаме данните от предишния запис
        $dataArr = $data->form->rec->formatParams;
        
        // Обхождаме масива
        foreach ((array) $dataArr as $fieldName => $value) {
            
            // Добавяме данните от записите
            $data->form->rec->$fieldName = $value;
        }
        
        // Инстанция на мастера
        $Master = $mvc->Master;
        
        // Ключа към мастер
        $masterKey = $mvc->masterKey;
        
        // id на мастер
        $masterId = $data->form->rec->$masterKey;
        
        // Добавяме функционалните полета за съответния тип
        static::addFieldsForType($data->form, $type);
        
        // Ако са сетнати
        if ($Master && $masterKey && $masterId) {
            
            // Вземаме шаблона
            $template = $Master->getTemplate($masterId);
            
            // Масив с плейсхолдерите
            $placesArr = $Master->getPlaceHolders($template);
            
            // Ключовете и стойностите да са равни
            $placesArr = arr::make($placesArr, true);
            
            $mRec = $Master->fetch($masterId);
            
            if ($mRec->classId) {
                $intfInst = cls::getInterface('label_SequenceIntf', $mRec->classId);
                $labelDataArr = $intfInst->getLabelPlaceholders(null);
                
                foreach ($labelDataArr as $lName => $v) {
                    $unset = false;
                    if ($v->type == 'text') {
                        if ($type != 'caption' && $type != 'barcode') {
                            $unset = true;
                        }
                    }
                    
                    if ($v->type == 'picture') {
                        if ($type != 'image') {
                            $unset = true;
                        }
                    }
                    
                    if ($unset) {
                        unset($placesArr[$lName]);
                    } else {
                        $placesArr[$lName] = $lName;
                    }
                }
            }
            
            // Вземаме плейсхолдерите, за които има запис
            $savedPlacesArr = (array) static::getAddededPlaceHolders($masterId);
            
            // Вземаме неизползваните
            $diffArr = array_diff($placesArr, $savedPlacesArr);
            
            // Ако редактираме запис
            if ($data->form->rec->id) {
                
                // Добавяме в масива
                $diffArr[$data->form->rec->placeHolder] = $data->form->rec->placeHolder;
            }
            
            // Добавяме предложение за пътищата
            $data->form->appendSuggestions('placeHolder', $diffArr);
            
            // Ако има неизползван
            if ($diffArr && $data->form->cmd != 'refresh') {
                $placeName = key($diffArr);
                $data->form->setDefault('placeHolder', $placeName);
            }
            
            if ($data->form->cmd == 'refresh') {
                $placeName = Request::get('placeHolder');
            }
            
            // Добавяме стойността по подразбиране
            if ($placeName && ($v = $labelDataArr[$placeName])) {
                if ($v->len) {
                    if ($type == 'caption') {
                        if ($data->form->cmd == 'refresh') {
                            Request::push(array('MaxLength' => $v->len));
                        }
                        
                        $data->form->setDefault('MaxLength', $v->len);
                    } else {
                        if ($data->form->cmd == 'refresh') {
                            Request::push(array('Width' => $v->len, 'Height' => $v->len));
                        }
                        
                        $data->form->setDefault('Width', $v->len);
                        $data->form->setDefault('Height', $v->len);
                    }
                }
            }
        }
    }
    
    
    /**
     * Връща масив с добаваните плейсхолдери за дадания шаблон
     *
     * @param int $templateId - id на шаблона
     *
     * @return array - Масив с добавените стойности
     */
    public static function getAddededPlaceHolders($templateId)
    {
        // Масива, който ще връщаме
        static $placesArr = array();
        
        // Ако не е сетнат
        if (!$placesArr[$templateId]) {
            $placesArr[$templateId] = array();
            
            // Вземамем всички плейсхолдери за шаблона
            $query = static::getQuery();
            $query->where(array("#templateId = '[#1#]'", $templateId));
            
            // Обхождаме резултатите
            while ($rec = $query->fetch()) {
                
                // Добавяме в масива
                $placesArr[$templateId][$rec->placeHolder] = $rec->placeHolder;
            }
        }
        
        return $placesArr[$templateId];
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param label_TemplateFormats $mvc
     * @param core_Form             $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        // Ако формата е субмитната
        if ($form->isSubmitted()) {
            
            // Вземаме типа
            $type = $form->rec->type;
            
            // Форма за функционалните полета
            $fncForm = cls::get('core_Form');
            
            // Вземаме функционалните полета за типа
            static::addFieldsForType($fncForm, $type);
            
            // Ако типа е брояч и няма въведен формат и има брояч
            if ($type == 'counter' && !$form->rec->Format && $form->rec->CounterId) {
                
                // Стойността по подразбиране
                $form->rec->Format = '%';
            }
            
            $dataArr = array();
            
            // Обхождаме масива
            foreach ((array) $fncForm->fields as $fieldName => $dummy) {
                
                // Добавяме данните от формата
                $dataArr[$fieldName] = $form->rec->$fieldName;
            }
            
            // Добавяме целия масив към формата
            $form->rec->formatParams = $dataArr;
        }
    }
    
    
    /**
     * Добавя функционални полета към подадената форма, за съответния тип
     *
     * @param core_Form $form - Формата
     * @param string    $type - Типа
     */
    public static function addFieldsForType(&$form, $type)
    {
        // Очакваме да е инстанция на core_Form
        expect($form instanceof core_Form);
        
        // В зависимост от типа
        switch ($type) {
            // Ако е плейсхолдер
            case 'caption':
                
                // Максимална дължина на символите
                $form->FNC('MaxLength', 'int(min=1, max=500)', 'caption=Макс. символи, input=input');
            break;
            
            // Ако е брояч
            case 'counter':
                
                // Кой брояч да се използва
                $form->FNC('CounterId', 'key(allowEmpty, mvc=label_Counters, select=name, where=#state !\\= \\\'rejected\\\' AND #state !\\= \\\'closed\\\')', 'caption=Брояч, input=input');
                
                // Вземаем всички баркодове, които можем да генерираме
                $barcodesArr = barcode_Generator::getAllowedBarcodeTypesArr();
                
                // Добавяме празен елемент
                $barcodesArr = array('' => '') + $barcodesArr;
                
                // Вид показване на баркода
                $form->FNC('Showing', 'enum(barcodeAndStr=Баркод и стринг, string=Стринг, barcode=Баркод)', 'title=Показване на баркод, caption=Показване, input=input');
                
                // Вид баркод
                $form->FNC('BarcodeType', cls::get(('type_Enum'), array('options' => $barcodesArr)), 'caption=Тип баркод, input=input');
                
                // Съотношението на баркода
                $form->FNC('Ratio', 'enum(1=1,2=2,3=3,4=4)', 'caption=Съотношение, input=input');
                
                // Широчина на баркода
                $form->FNC('Width', 'int(min=1, max=5000)', 'caption=Широчина, input=input, unit=px');
                
                // Височина на баркода
                $form->FNC('Height', 'int(min=1, max=5000)', 'caption=Височина, input=input, unit=px');
                
                // Формат на баркода
                $form->FNC('Format', 'varchar', 'caption=Формат, input=input');
                
                // Дали да се ротира или не
                $form->FNC('Rotation', 'enum(yes=Да, no=Не)', 'caption=Ротация, input=input, mandatory');
            break;
            
            case 'image':
                
                // Широчина на изображението
                $form->FNC('Width', 'int(min=1, max=5000)', 'caption=Широчина, input=input, unit=px, mandatory');
                
                // Височина на изображението
                $form->FNC('Height', 'int(min=1, max=5000)', 'caption=Височина, input=input, unit=px, mandatory');
                
                // Дали е допустима ротацията
                $form->FNC('Rotation', 'enum(yes=Допустима, no=Недопустима)', 'caption=Ротация, input=input, mandatory');
            break;
            
            case 'html':
                
                // Максимална дължина на символите
                $form->FNC('MaxLength', 'int(min=1, max=5000)', 'caption=Макс. символи, input=input');
            break;
            
            case 'barcode':
                
                // Вземаем всички баркодове, които можем да генерираме
                $barcodesArr = barcode_Generator::getAllowedBarcodeTypesArr();
                $barcodesArr = array('' => '') + $barcodesArr;
                
                // Вид показване на баркода
                $form->FNC('Showing', 'enum(barcodeAndStr=Баркод и стринг, string=Стринг, barcode=Баркод)', 'title=Показване на баркод, caption=Показване, input=input,mandatory');
                
                // Вид баркод
                $form->FNC('BarcodeType', cls::get(('type_Enum'), array('options' => $barcodesArr)), 'caption=Тип баркод, input=input,mandatory');
                $form->FNC('Ratio', 'enum(1=1,2=2,3=3,4=4)', 'caption=Съотношение, input=input,mandatory');
                $form->FNC('Width', 'int(min=1, max=5000)', 'caption=Широчина, input=input, unit=px,mandatory');
                $form->FNC('Height', 'int(min=1, max=5000)', 'caption=Височина, input=input, unit=px,mandatory');
                $form->FNC('Format', 'varchar', 'caption=Формат, input=input');
                $form->FNC('Rotation', 'enum(yes=Да, no=Не)', 'caption=Ротация, input=input, mandatory,mandatory');
            break;
            
            default:
                
                // Очакваме валиден тип
                expect(false, $type);
            break;
        }
    }
    
    
    /**
     * Добавя функционални полета към подадената форма, за всички детайли към мастер
     *
     * @param core_Form $form     - Формата
     * @param int       $masterId - id на мастер
     */
    public static function addFieldForTemplate(&$form, $masterId)
    {
        // Очакваме да е инстанция на core_Form
        expect($form instanceof core_Form);
        
        // Инстанция на класа
        $me = cls::get(get_called_class());
        
        // Вземаме всички записи за съответния master, без броячите
        $query = $me->getQuery();
        $query->where(array("#{$me->masterKey} = '[#1#]'", $masterId));
        $query->where("#type != 'counter'");
        $query->orderBy('type', 'DESC');
        
        // Обхождаме резултатите
        while ($rec = $query->fetch()) {
            
            // Плейсхолдера
            $placeHolder = trim($rec->placeHolder);
            $placeHolder = mb_strtoupper($placeHolder);
            
            // Името на полето
            $placeHolderField = static::getPlaceholderFieldName($placeHolder);
            
            // Заглавието на полета
            $caption = 'Параметри->' . $placeHolder;
            
            // Ако е image
            if ($rec->type == 'image') {
                
                // Добавяме поле за качване на изображение
                $form->FNC($placeHolderField, 'fileman_FileType(bucket=' . self::$bucket . ')', "caption={$caption}, input=input");
            } elseif ($rec->type == 'caption') {
                
                // Ако е зададена максимална дължина
                if (is_array($rec->formatParams) && ($maxLength = $rec->formatParams['MaxLength'])) {
                    
                    // Задаваме стрингов тип с максимална дължина
                    $type = "varchar({$maxLength})";
                } else {
                    
                    // Типа без максимална дължина
                    $type = 'varchar';
                }
                
                // Максимална дължина на символите
                $form->FNC($placeHolderField, $type, "caption={$caption}, input=input, silent");
            } elseif ($rec->type == 'html') {
                
                // Ако е зададена максимална дължина
                if (is_array($rec->formatParams) && ($maxLength = $rec->formatParams['MaxLength'])) {
                    
                    // Задаваме стрингов тип с максимална дължина
                    $type = "html({$maxLength})";
                } else {
                    
                    // Типа без максимална дължина
                    $type = 'html';
                }
                
                $form->FNC($placeHolderField, $type, "caption={$caption}, input=input, silent");
            } elseif ($rec->type == 'barcode') {
                $bType = 'text(rows=2)';
                if ($rec->formatParams['BarcodeType'] == 'ean13') {
                    $bType = 'gs1_TypeEan(gln)';
                } elseif ($rec->formatParams['BarcodeType'] == 'ean8') {
                    $bType = 'gs1_TypeEan()';
                }
                
                $form->FNC($placeHolderField, $bType, "caption={$caption}, input=input, silent");
            }
        }
    }
    
    
    /**
     * Връщаме името на полето
     *
     * Това име ще се използва за име на FNC поле във формата.
     * Връща шаблона с първа главна бъква.
     * Това е с цел за да няма дублирано име на поле във формата.
     * Може да се дублира с FLD полетата.
     *
     * @param string $placeHolder - Името на плейсхолдера
     * @param string - Новото име на плейсхолдера
     */
    public static function getPlaceholderFieldName($placeHolder)
    {
        return mb_strtoupper($placeHolder);
    }
    
    
    /**
     * Връща вербалното представаня на данните за плейсхолдера
     *
     * @param int    $templateId     - id на шаблона
     * @param string $place          - Името на плейсхолдера
     * @param string $val            - Вербалната стойност
     * @param string $printId        - id на етикета
     * @param bool   $updateTempData - Ако е FALSE, при вземане на данните да не се обновяват стойностите им в модела
     *
     * @return string - Вербалното представяне на стойността
     */
    public static function getVerbalTemplate($templateId, $place, $val, $printId = null, $updateTempData = true)
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
        
        // В заивисимост от типа
        if ($type == 'caption' || !$type) {
            
            // Стринга, който ще се използва в масива за ключ
            $valStr = $val . '|' . $updateTempData;
            
            // Ако не е вземана стойността
            if (!$verbalValArr[$valStr]) {
                
                // Инстанциня на класа
                $Varchar = cls::get('type_Varchar');
                
                // Добавяме в масива
                $verbalValArr[$valStr] = $Varchar->toVerbal($val);
            }
        } elseif ($type == 'image') {
            
            // Стринга, който ще се използва в масива за ключ
            $valStr = $val . $rec->formatParams['Rotation'] . '|' . $updateTempData;
            
            // Ако не е вземана стойността
            if (!$verbalValArr[$valStr]) {
                
                // Масив за стойности
                $attr = array();
                
                // Ако има зададен стойност
                if ($val) {
                    $possibleRotation = ($rec->formatParams['Rotation'] == 'yes') ? 'left' : null;
                    
                    // Вземаме умалено изборажение със зададените размер
                    $thumb = new thumb_Img(array($val, $rec->formatParams['Width'], $rec->formatParams['Height'], 'fileman', 'possibleRotation' => $possibleRotation));
                    
                    try {
                        // Добавяме вербалната стойност
                        $verbalValArr[$valStr] = $thumb->createImg($attr);
                    } catch (core_exception_Expect $e) {
                        $verbalValArr[$valStr] = "<span style='color: #c00;'>" . tr('Грешка при показване на файл') . ': ' . $val . '</span>';
                    }
                }
            }
        } elseif (($type == 'counter') || ($type == 'barcode')) {
            
            // Вземаме формата
            $formatVal = $rec->formatParams['Format'];
            
            // Ако има шаблон за субституиране с брояч
            if (label_Counters::haveCounterPlace($formatVal)) {
                if ($rec->formatParams['CounterId']) {
                    // Заместваме брояча
                    $formatVal = label_Counters::placeCounter($formatVal, $rec->formatParams['CounterId'], $printId, $updateTempData);
                } else {
                    $formatVal = str_replace(label_Counters::$counterPlace, $val, $formatVal);
                }
            } else {
                if (!strlen($formatVal) && isset($val)) {
                    $formatVal = $val;
                }
            }
            
            // Типа на баркода
            $barcodeType = $rec->formatParams['BarcodeType'];
            
            // Стринг за уникалност
            $valStr = $formatVal . '|' . $barcodeType . '|' . $rec->formatParams['Showing'] . '|' . $updateTempData;
            
            // Ако не е вземана стойността
            if (!$verbalValArr[$valStr]) {
                
                // Нилираме стойностите
                $attr = array();
                $rotate = false;
                
                // Ако е зададено да се показва само стринга без баркода
                if ($rec->formatParams['Showing'] == 'string') {
                    
                    // Ако е зададено да се ротира твърдо
                    if ($rec->formatParams['Rotation'] == 'yes') {
                        
                        // div, който ще се ротира
                        $div = "<div class='rotate'>";
                    } else {
                        
                        // div, без ротиране
                        $div = '<div>';
                    }
                    
                    // Добавяме стойността
                    $verbalValArr[$valStr] = $div . $formatVal . '</div>';
                } else {
                    // Очакваме да има въведен баркод тип
                    expect($barcodeType, 'Трябва да се избере типа на баркода');
                    
                    // Вземаме минималната височина и широчината
                    $minWidthAndHeight = barcode_Generator::getMinWidthAndHeight($barcodeType, $formatVal);
                    $width = max($minWidthAndHeight['width'], $rec->formatParams['Width']);
                    $height = max($minWidthAndHeight['height'], $rec->formatParams['Height']);
                    
                    // Масив с размерите
                    $sizeArr = array('width' => $width, 'height' => $height);
                    
                    // Ако е зададено да се ротира твърдо
                    if ($rec->formatParams['Rotation'] == 'yes') {
                        
                        // Добавяме ъгъл на завъртане
                        $attr['angle'] = 90;
                    }
                    
                    // Ако е зададено да се показва баркод и стринг
                    if ($rec->formatParams['Showing'] == 'barcodeAndStr') {
                        
                        // Добавяме параметъра
                        $attr['addText'] = array();
                    }
                    
                    // Добавяме съотношението
                    $attr['ratio'] = $rec->formatParams['Ratio'];
                    
                    // Вземаме вербалната стойност
                    $verbalValArr[$valStr] = barcode_Generator::getLink($barcodeType, $formatVal, $sizeArr, $attr);
                }
            }
        } elseif ($type == 'html') {
            
            // Стринга, който ще се използва в масива за ключ
            $valStr = $val . '|' . $updateTempData;
            
            // Ако не е вземана стойността
            if (!$verbalValArr[$valStr]) {
                
                // Инстанциня на класа
                $Html = cls::get('type_Html');
                
                // Добавяме в масива
                $verbalValArr[$valStr] = $Html->toVerbal($val);
            }
        }
        
        return $verbalValArr[$valStr];
    }
    
    
    /**
     *
     *
     * @param label_TemplateFormats $mvc
     * @param stdClass              $row
     * @param stdClass              $rec
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Масив с шаблоните
        static $fieldsArr = array();
        
        // Ако не е сетнат за този шаблон
        if (!$fieldsArr[$rec->type]) {
            
            // Форма за функционалните полета
            $fncForm = cls::get('core_Form');
            
            // Вземаме функционалните полета за тип
            static::addFieldsForType($fncForm, $rec->type);
            
            // Добавяме в масива
            $fieldsArr[$rec->type] = $fncForm->fields;
        }
        
        // Нулираме стойността
        $row->formatParams = '';
        
        // Обхождаме масива с полетата
        foreach ((array) $fieldsArr[$rec->type] as $name => $field) {
            
            // Името на полето
            $fieldName = $field->caption;
            
            // Вербалната стойност
            $verbalVal = $field->type->toVerbal($rec->formatParams[$name]);
            
            // Ако няма подадена стойност
            if (!$verbalVal) {
                
                // Задаваме стринга
                $verbalVal = '*' . tr('Няма стойност') . '*';
            }
            
            // Добавяме в полето
            $row->formatParams .= '<div>' . $fieldName . ': ' . $verbalVal . '</div>';
        }
        
        // Инстанция на мастера
        $Master = $mvc->Master;
        
        // Ключа към мастер
        $masterKey = $mvc->masterKey;
        
        // id на мастер
        $masterId = $rec->$masterKey;
        
        // Ако са сетнати
        if ($Master && $masterKey && $masterId) {
            
            // Вземаме шаблона
            $template = $Master->getTemplate($masterId);
            
            // Масив с плейсхолдерите
            $placesArr = $Master->getPlaceHolders($template);
            
            // Ключовете и стойностите да са равни
            $placesArr = arr::make($placesArr, true);
        }
        
        // Ако не се съдържа в шаблона
        if (!$placesArr[$rec->placeHolder]) {
            $row->placeHolder = ht::createHint($row->placeHolder, 'Плейсхолдера липсва в шаблона', 'error');
            
            // Добавяме клас за грешка
            $row->ROW_ATTR['class'] .= ' row-error';
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     *
     * @param label_TemplateFormats $mvc
     * @param string|NULL           $res
     */
    protected static function on_AfterSetupMvc($mvc, &$res)
    {
        // Инстанция на класа
        $Bucket = cls::get('fileman_Buckets');
        
        // Създаваме, кофа, където ще държим всички прикачени файлове
        $res .= $Bucket->createBucket(self::$bucket, 'Файлове в етикети', 'jpg,jpeg,png,bmp,gif,image/*', '10MB', 'user', 'user');
    }
    
    
    /**
     * Активира използваните броячи в шаблоните
     *
     * @param int $templateId - id на шаблона
     */
    public static function activateCounters($templateId)
    {
        // Вземаме всички броячи използвани в този шаблон
        $query = static::getQuery();
        $query->where("#templateId = '{$templateId}' AND #type = 'counter'");
        
        while ($rec = $query->fetch()) {
            
            // Активираме броячите
            label_Counters::activateCounter($rec->formatParams['CounterId']);
        }
    }
}
