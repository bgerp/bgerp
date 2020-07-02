<?php


/**
 * Драйвър за универсален артикул
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Универсален артикул
 */
class cat_GeneralProductDriver extends cat_ProductDriver
{
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('infoInt', 'richtext(rows=4, bucket=Notes)', 'caption=Подробно||In detail->Описание международно||International description,autohide');
        if (!$fieldset->getField('photo', false)) {
            $fieldset->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Изображение');
        } else {
            $fieldset->setField('photo', 'input');
        }
        
        if (!$fieldset->getField('measureId', false)) {
            $fieldset->FLD('measureId', 'key(mvc=cat_UoM, select=name,allowEmpty)', 'caption=Мярка,mandatory');
        } else {
            $fieldset->setField('measureId', 'input');
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param stdClass          $data
     */
    public static function on_AfterPrepareEditForm(cat_ProductDriver $Driver, embed_Manager $Embedder, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        
        if(empty($rec->name) && !empty($rec->proto)){
            $form->setDefault('name', cat_Products::fetchField($rec->proto, 'name'));
        }
        
        if (cls::haveInterface('marketing_InquiryEmbedderIntf', $Embedder)) {
            $form->setField('photo', 'input=none');
            $form->setField('measureId', 'display=hidden');
            
            if ($Embedder instanceof marketing_Inquiries2) {
                $form->setField('inqDescription', 'mandatory');
                $form->setOptions('measureId', cat_UoM::getUomOptions());
            }
        }
        
        // Само при добавянето на нов артикул
        if (empty($rec->id) || $data->action == 'clone') {
            $refreshFields = array('param');
            
            // Имали дефолтни параметри
            $defaultParams = $Driver->getDefaultParams($rec, $Embedder->getClassId(), $data->action);
            foreach ($defaultParams as $id => $value) {
                
                // Всеки дефолтен параметър го добавяме към формата
                $paramRec = cat_Params::fetch($id);
                $name = cat_Params::getVerbal($paramRec, 'name');
                if (isset($paramRec->group)) {
                    $group = cat_Params::getVerbal($paramRec, 'group');
                    $caption = "Параметри: {$group}->{$name}";
                } else {
                    $caption = "Параметри->{$name}";
                }
                
                $form->FLD("paramcat{$id}", 'double', "caption={$caption},categoryParams,before=meta");
                $form->setFieldType("paramcat{$id}", cat_Params::getTypeInstance($id, $Embedder, $rec->id));
                
                // Ако параметъра има суфикс, добавяме го след полето
                if (!empty($paramRec->suffix)) {
                    $suffix = cat_Params::getVerbal($paramRec, 'suffix');
                    $form->setField("paramcat{$id}", "unit={$suffix}");
                }
                
                // Ако има дефолтна стойност, задаваме и нея
                if (isset($value)) {
                    $form->setDefault("paramcat{$id}", $value);
                }
            }
            
            $refreshFields = implode('|', $refreshFields);
            
            $remFields = $form->getFieldParam($Embedder->driverClassField, 'removeAndRefreshForm') . '|' . $refreshFields;
            $form->setField($Embedder->driverClassField, "removeAndRefreshForm={$remFields}");
            
            $remFields = $form->getFieldParam('proto', 'removeAndRefreshForm') . '|' . $refreshFields;
            $form->setField('proto', "removeAndRefreshForm={$remFields}");
        }
    }
    
    
    /**
     * Връща масив с дефолтните параметри за записа
     * Ако артикула има прототип взимаме неговите параметри,
     * ако няма тези от корицата му
     *
     * @param stdClass $rec
     *
     * @return array
     */
    private function getDefaultParams($rec, $classId, $action)
    {
        $res = array();
        
        // Ориджина е прототипа (ако има)
        $originRecId = $rec->proto;
        if (isset($rec->proto)) {
            $classId = cat_Products::getClassId();
        }
        
        // Ако има ордижнин и не клонираме
        if (isset($rec->originId) && $action != 'clone') {
            $document = doc_Containers::getDocument($rec->originId);
            
            // Ако е запитване
            if ($document->isInstanceOf('marketing_Inquiries2')) {
                $originRecId = $document->that;
                $classId = $document->getClassId();
            }
        }
        
        // Ако клонираме артикул
        if ($action == 'clone' && isset($rec->id)) {
            $originRecId = $rec->id;
        }
        
        // Ако има намерен ордижнин
        if ($originRecId) {
            
            // Ако артикула е прототипен, взимаме неговите параметри с техните стойностти
            $paramQuery = cat_products_Params::getQuery();
            $paramQuery->where("#classId = {$classId} AND #productId = {$originRecId}");
            while ($pRec = $paramQuery->fetch()) {
                $res[$pRec->paramId] = $pRec->paramValue;
            }
        } else {
            
            // Иначе взимаме параметрите от корицата му, ако можем
            if (isset($rec->folderId)) {
                $cover = doc_Folders::getCover($rec->folderId);
                if ($cover->haveInterface('cat_ProductFolderCoverIntf')) {
                    $res = $cover->getDefaultProductParams();
                }
            }
        }
        
        // Връщаме намерените параметри (ако има);
        return $res;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param int               $id
     * @param stdClass          $rec
     */
    public static function on_AfterSave(cat_ProductDriver $Driver, embed_Manager $Embedder, &$id, $rec)
    {
        $arr = (array) $rec;
        $classId = $Embedder->getClassId();
        
        // За всеко поле от записа
        foreach ($arr as $key => $value) {
            
            // Ако името му съдържа ключова дума
            if (strpos($key, 'paramcat') !== false) {
                $paramId = substr($key, 8);
                
                // Има стойност и е разпознато ид на параметър
                if (cat_Params::fetch($paramId) && !empty($value)) {
                    $dRec = (object) array('productId' => $rec->id,
                        'classId' => $classId,
                        'paramId' => $paramId,
                        'paramValue' => $value);
                    
                    // Записваме продуктовия параметър с въведената стойност
                    if (!cls::get('cat_products_Params')->isUnique($dRec, $fields, $exRec)) {
                        $dRec->id = $exRec->id;
                    }
                    
                    cat_products_Params::save($dRec);
                }
            }
        }
    }
    
    
    /**
     * Връща счетоводните свойства на обекта
     */
    public function getFeatures($productId)
    {
        return cat_products_Params::getFeatures('cat_Products', $productId);
    }
    
    
    /**
     * Връща стойността на параметъра с това име, или
     * всички параметри с техните стойностти
     *
     * @param int    $classId - ид на клас
     * @param string $id      - ид на записа
     * @param string $name    - име на параметъра, или NULL ако искаме всички
     * @param bool   $verbal  - дали да са вербални стойностите
     *
     * @return mixed $params - стойност или NULL ако няма
     */
    public function getParams($classId, $id, $name = null, $verbal = false)
    {
        // Ако има посочено име се посочва директно стойноста му
        if (isset($name)) {
            
            return cat_products_Params::fetchParamValue($classId, $id, $name, $verbal);
        }
        
        // Ако не искаме точен параметър връщаме всичките параметри за артикула
        $params = array();
        $classId = cat_Products::getClassId();
        $pQuery = cat_products_Params::getQuery();
        $pQuery->where("#productId = {$id}");
        $pQuery->where("#classId = {$classId}");
        $pQuery->show('paramId,paramValue');
        
        while ($pRec = $pQuery->fetch()) {
            if ($verbal === true) {
                $pRec->paramValue = cat_Params::toVerbal($pRec->paramId, $classId, $id, $pRec->paramValue);
            }
            $params[$pRec->paramId] = $pRec->paramValue;
        }
        
        return $params;
    }
    
    
    /**
     * ХТМЛ представяне на артикула (img)
     *
     * @param int   $rec     - запис на артикул
     * @param array $size    - размер на картинката
     * @param array $maxSize - макс размер на картинката
     *
     * @return string|NULL $preview - хтмл представянето
     */
    public function getPreview($rec, embed_Manager $Embedder, $size = array('280', '150'), $maxSize = array('550', '550'))
    {
        $preview = null;
        $previewHandler = cat_Products::getParams($rec->id, 'preview');
        $handler = !empty($previewHandler) ? $previewHandler : $rec->photo;
        
        if (!empty($handler)) {
            $Fancybox = cls::get('fancybox_Fancybox');
            $preview = $Fancybox->getImage($handler, $size, $maxSize);
            $preview = $preview->getContent();
        }
        
        return $preview;
    }
    
    
    /**
     * Подготвя данните за показване на описанието на драйвера
     *
     * @param stdClass $data
     *
     * @return stdClass
     */
    public function prepareProductDescription(&$data)
    {
        parent::prepareProductDescription($data);
        
        $data->masterId = $data->rec->id;
        $data->masterClassId = cls::get($data->Embedder)->getClassId();
        cat_products_Params::prepareParams($data);
    }
    
    
    /**
     * Рендира данните за показване на артикула
     *
     * @param stdClass $data
     *
     * @return core_ET
     */
    public function renderProductDescription($data)
    {
        // Вербализиране на снимката, да е готова за показване
        $data->rec->photo =  cat_Products::getParams($data->rec->id, 'preview');
        if ($data->rec->photo) {
            $size = array(280, 150);
            $Fancybox = cls::get('fancybox_Fancybox');
            $data->row->image = $Fancybox->getImage($data->rec->photo, $size, array(550, 550));
        }
        
        // @TODO ревербализиране на описанието
        $lg = core_Lg::getCurrent();
        $info = ($lg == 'en') ? (!empty($data->rec->infoInt) ? $data->rec->infoInt : $data->rec->info) : $data->rec->info;
        if (!empty($info)) {
            $data->row->info = core_Type::getByName('richtext')->toVerbal($info);
        }
        
        // Ако не е зададен шаблон, взимаме дефолтния
        $layout = ($data->isSingle !== true) ? 'cat/tpl/SingleLayoutBaseDriverShort.shtml' : 'cat/tpl/SingleLayoutBaseDriver.shtml';
        $tpl = getTplFromFile($layout);
        $tpl->placeObject($data->row);
        
        // Ако ембедъра няма интерфейса за артикул, то към него немогат да се променят параметрите
        if (!cls::haveInterface('cat_ProductAccRegIntf', $data->Embedder)) {
            $data->noChange = true;
        }
        
        // Рендираме параметрите винаги ако сме към артикул или ако има записи
        if ($data->noChange !== true || countR($data->params)) {
            $paramTpl = cat_products_Params::renderParams($data);
            $tpl->append($paramTpl, 'PARAMS');
        }
        
        if ($data->isSingle !== true) {
            $wrapTpl = new ET("<!--ET_BEGIN paramBody--><div class='general-product-description'>[#paramBody#][#COMPONENTS#]</div><!--ET_END paramBody-->");
            if (strlen(trim($tpl->getContent()))) {
                $wrapTpl->append($tpl, 'paramBody');
            }
            
            return $wrapTpl;
        }
        
        return $tpl;
    }
    
    
    /**
     * Колко е толеранса
     *
     * @param int   $id       - ид на артикул
     * @param float $quantity - к-во
     *
     * @return float|NULL - толеранс или NULL, ако няма
     */
    public function getTolerance($id, $quantity)
    {
        return $this->getParams(cat_Products::getClassId(), $id, 'tolerance');
    }
    
    
    /**
     * Колко е срока на доставка
     *
     * @param int   $id       - ид на артикул
     * @param float $quantity - к-во
     *
     * @return float|NULL - срока на доставка в секунди или NULL, ако няма
     */
    public function getDeliveryTime($id, $quantity)
    {
        return $this->getParams(cat_Products::getClassId(), $id, 'term');
    }
    
    
    /**
     * Връща масив с допълнителните плейсхолдъри при печат на етикети
     *
     * @param mixed $rec              - ид или запис на артикул
     * @param mixed $labelSourceClass - клас източник на етикета
     *
     * @return array - Допълнителните полета при печат на етикети
     *               [Плейсхолдър] => [Стойност]
     */
    public function getAdditionalLabelData($rec, $labelSourceClass = null)
    {
        $res = array();
        
        $preview = cat_Products::getParams($rec, 'preview');
        if (!empty($preview)) {
            $res['PREVIEW'] = $preview;
        }
        
        $labelText = null;
        $lg = core_Lg::getCurrent();
        if ($lg != 'bg') {
            $labelText = cat_Products::getParams($rec, 'labelTextEn');
        }
        if (empty($labelText)) {
            $labelText = cat_Products::getParams($rec, 'labelText');
        }
        
        if (!empty($labelText)) {
            $res['OTHER'] = $labelText;
        }
        
        return $res;
    }
    
    
    /**
     * Връща транспортното тегло за подаденото количество
     *
     * @param mixed $rec      - ид или запис на продукт
     * @param int   $quantity - общо количество
     *
     * @return float|NULL - транспортното тегло на общото количество
     */
    public function getTransportWeight($rec, $quantity)
    {
        $weight = $this->getParams(cat_Products::getClassId(), $rec->id, 'transportWeight');
        if ($weight) {
            $weight *= $quantity;
            
            return round($weight, 2);
        }
    }
    
    
    /**
     * Връща транспортния обем за подаденото количество
     *
     * @param mixed $rec      - ид или запис на артикул
     * @param int   $quantity - общо количество
     *
     * @return float - транспортния обем на общото количество
     */
    public function getTransportVolume($rec, $quantity)
    {
        $volume = $this->getParams(cat_Products::getClassId(), $rec->id, 'transportVolume');
        if ($volume) {
            $volume *= $quantity;
            
            return round($volume/1000, 3);
        }
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords(cat_ProductDriver $Driver, embed_Manager $Embedder, &$res, $rec)
    {
        // Добавяне на параметрите към ключовите думи
        if(isset($rec->id)){
            $params = $Driver->getParams(cat_Products::getClassId(), $rec->id, null, true);
            if(is_array($params)){
                foreach ($params as $paramId => $value){
                    $paramName = cat_Params::getTitleById($paramId, false);
                    $res .= ' ' . plg_Search::normalizeText($paramName) . ' ' . plg_Search::normalizeText($value);
                }
            }
        }
    }
}
