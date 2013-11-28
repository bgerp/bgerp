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
    var $canRead = 'debug';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'debug';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'debug';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'debug';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'debug';
    
    
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
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('templateId', 'key(mvc=label_Templates, select=title)', 'caption=Шаблон');
        $this->FLD('placeHolder', 'varchar', 'caption=Плейсхолдер, title=Име на плейсхолдер, mandatory');
        $this->FLD('type', 'enum(' . static::$typeEnumOpt . ')', 'caption=Тип, silent');
        $this->FLD('formatParams', 'blob(serialize, compress)', 'caption=Параметри, title=Параметри за конвертиране на шаблона, input=none, column=none');
        
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
        if (!($type = $data->form->rec->type)) {
            
            // Типа
            $type = Request::get('type');
        }
        
        // Масив с типовете на полето
        $typeArr = arr::make(static::$typeEnumOpt, TRUE);
        
        // Очакваме да има тип и типа да отговаря
        expect($type && $typeArr[$type]);
        
        // Задаваме да не може да се променя
        $data->form->setReadonly('type');
        
        // Вземаме масив с полетата
        $fieldsArr = static::getFncFieldsArr($type);
        
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
    static function on_AfterInputEditForm($mvc, $form)
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
            $fieldsArr = static::getFncFieldsArr($type);
            
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
     * Връща масив с функционалните полета
     * 
     * @param string $type - Името на типа, за което ще се търси
     * 
     * @return array - Двумерен масив с името и параметрите на тип
     */
    static function getFncFieldsArr($type)
    {
        // Масива, който ще връщаме
        $filedsArr = array();
        
        // В зависимост от типа
        switch ($type) {
            
            // Ако е плейсхолдер
            case 'caption':
                
                // Поле за максимален брой символи
                $filedsArr['MaxLength']['type'] = 'int';
                $filedsArr['MaxLength']['caption'] = 'Макс. символи';
            break;
            
            // Ако е брояч
            case 'counter':
                
                // Поле за избор на брояч
                $filedsArr['CounterId']['type'] = 'key(mvc=label_Counters, select=name)';
                $filedsArr['CounterId']['caption'] = 'Брояч';
                
                // Вземаем всички баркодове, които можем да генерираме
                $barcodesArr = barcode_Generator::getAllowedBarcodeTypesArr();
                
                // Вземаем enum представянето
                $barcodeStr = type_Enum::fromArray($barcodesArr);
                
                // Поле за избор на баркод
                $filedsArr['BarcodeType']['type'] = 'enum(' . $barcodeStr . ')';
                $filedsArr['BarcodeType']['caption'] = 'Тип баркод';
                
                // Поле за показване на баркод
                $filedsArr['Showing']['type'] = 'enum(barcodeAndStr=Баркод и стринг, string=Стринг, barcode=Баркод)';
                $filedsArr['Showing']['caption'] = 'Показване';
                $filedsArr['Showing']['title'] = 'Показване на баркод';
                
                // Поле за широчина
                $filedsArr['Width']['type'] = 'int';
                $filedsArr['Width']['caption'] = 'Широчина';
                $filedsArr['Width']['unit'] = 'mm';
                
                // Поле за височина
                $filedsArr['Height']['type'] = 'int';
                $filedsArr['Height']['caption'] = 'Височина';
                $filedsArr['Height']['unit'] = 'mm';
                
                // Поле за формат
                $filedsArr['Format']['type'] = 'varchar';
                $filedsArr['Format']['caption'] = 'Формат';
            break;
            
            case 'image':
                
                // Поле за широчина
                $filedsArr['Width']['type'] = 'int';
                $filedsArr['Width']['caption'] = 'Широчина';
                $filedsArr['Width']['unit'] = 'mm';
                
                // Поле за височина
                $filedsArr['Height']['type'] = 'int';
                $filedsArr['Height']['caption'] = 'Височина';
                $filedsArr['Height']['unit'] = 'mm';
                
                // Поле дали за избор дали да се ротира
                $filedsArr['Rotation']['type'] = 'enum(yes=Допустима, no=Недопустима)';
                $filedsArr['Rotation']['caption'] = 'Ротация';
            break;
            
            default:
                expect(FALSE, $type);
            break;
        }
        
        return $filedsArr;
    }
}
