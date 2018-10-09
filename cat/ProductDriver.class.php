<?php


/**
 * Базов драйвер за драйвер на артикул
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Базов драйвер за драйвер на артикул
 */
abstract class cat_ProductDriver extends core_BaseClass
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, cat, sales, purchase';
    
    
    /**
     * Интерфейси които имплементира
     */
    public $interfaces = 'cat_ProductDriverIntf';
    
    
    /**
     * Мета данни по подразбиране
     *
     * @param strint $defaultMetaData
     */
    protected $defaultMetaData;
    
    
    /**
     * Икона за единичния изглед
     */
    protected $icon = 'img/16/wooden-box.png';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function canSelectDriver($userId = null)
    {
        $roles = core_Users::haveRole('partner', $userId) ? 'partner' : $this->canSelectDriver;
        
        return core_Users::haveRole($roles, $userId);
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
        $driverFields = array_keys($Embedder->getDriverFields($Driver));
        
        $driverRefreshedFields = $form->getFieldParam($Embedder->driverClassField, 'removeAndRefreshForm');
        $driverRefreshedFields = explode('|', $driverRefreshedFields);
        
        $refreshFieldsDriver = array_unique(array_merge($driverFields, $driverRefreshedFields));
        $driverRefreshFields = implode('|', $refreshFieldsDriver);
        
        if ($unIndex = array_search('proto', $refreshFieldsDriver)) {
            unset($refreshFieldsDriver[$unIndex]);
        }
        
        $protoRefreshFields = implode('|', $refreshFieldsDriver);
        
        // Добавяме при смяна на драйвева или на прототип полетата от драйвера да се рефрешват и те
        $form->setField($Embedder->driverClassField, "removeAndRefreshForm={$driverRefreshFields}");
        $form->setField('proto', "removeAndRefreshForm={$protoRefreshFields}");
        
        // Намираме полетата на формата
        $fields = $form->selectFields();
        
        // Ако има полета
        if (count($fields)) {
            
            // За всички полета
            foreach ($fields as $name => $fld) {
                
                // Ако има атрибут display
                $display = $form->getFieldParam($name, 'display');
                
                // Ако е 'hidden' и има зададена стойност, правим полето скрито
                if ($display === 'hidden') {
                    if (!is_null($form->rec->{$name})) {
                        $form->setField($name, 'input=hidden');
                    }
                } elseif ($display === 'readOnly') {
                    
                    // Ако е 'readOnly' и има зададена стойност, правим го 'само за четене'
                    if (!is_null($form->rec->{$name})) {
                        $form->setReadOnly($name);
                    }
                }
            }
        }
    }
    
    
    /**
     * Връща счетоводните свойства на обекта
     */
    public function getFeatures($productId)
    {
        return array();
    }
    
    
    /**
     * Връща свойствата на артикула според драйвера
     *
     * @param mixed $metas - текущи мета данни
     *
     * @return array $metas - кои са дефолтните мета данни
     */
    public function getDefaultMetas($metas = null)
    {
        if (isset($metas)) {
            
            return arr::make($metas, true);
        }
        
        // Ако за драйвера има дефолтни мета данни, добавяме ги към тези от ембедъра
        $metas = array();
        if (!empty($this->defaultMetaData)) {
            $metas = $metas + arr::make($this->defaultMetaData, true);
        }
        
        return $metas;
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
        if ($name) {
            
            return false;
        }
        
        return array();
    }
    
    
    /**
     * Подготовка за рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param stdClass          $res
     * @param stdClass          $data
     */
    public static function on_AfterPrepareSingle(cat_ProductDriver $Driver, embed_Manager $Embedder, &$res, &$data)
    {
        $data->Embedder = $Embedder;
        $data->isSingle = true;
        $data->documentType = 'internal';
        $Driver->prepareProductDescription($data);
    }
    
    
    /**
     * Подготвя данните за показване на описанието на драйвера
     *
     * @param stdClass $data
     *
     * @return void
     */
    public function prepareProductDescription(&$data)
    {
    }
    
    
    /**
     * Кои документи са използвани в полетата на драйвера
     */
    public function getUsedDocs()
    {
        return false;
    }
    
    
    /**
     * Връща задължителната основна мярка
     *
     * @return int|NULL - ид на мярката, или NULL ако може да е всяка
     */
    public function getDefaultUomId()
    {
    }
    
    
    /**
     * Връща иконата на драйвера
     *
     * @return string - пътя към иконата
     */
    public function getIcon()
    {
        return $this->icon;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    public static function on_AfterRenderSingle(cat_ProductDriver $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $nTpl = $Driver->renderProductDescription($data);
        $tpl->append($nTpl, 'innerState');
    }
    
    
    /**
     * Рендиране на описанието на драйвера
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderProductDescription($data)
    {
        $title = tr($this->singleTitle);
        
        $tpl = new ET(tr("|*
                    <div class='groupList'>
                        <div class='richtext' style='margin-top: 5px; font-weight:bold;'>{$title}</div>
                        <!--ET_BEGIN info-->
                        <div style='margin-top:5px;'>[#info#]</div>
                        <!--ET_END info-->
						<table class = 'no-border small-padding' style='margin-bottom: 5px;'>
							[#INFO#]
						</table>
					</div>
					[#ROW_AFTER#]
					[#COMPONENTS#]
				"));
        
        $form = cls::get('core_Form');
        $this->addFields($form);
        $driverFields = $form->fields;
        $tpl->replace($data->row->info, 'info');
        
        if (is_array($driverFields)) {
            $usedGroups = core_Form::getUsedGroups($form, $driverFields, $data->rec, $data->row, 'single');
            
            foreach ($driverFields as $name => $field) {
                if ($field->single != 'none' && isset($data->row->{$name})) {
                    $caption = $field->caption;
                    
                    if (strpos($caption, '->')) {
                        list($group, $caption) = explode('->', $caption);
                        
                        // Групите, които не се използват - не се показват
                        if (!isset($usedGroups[$group])) {
                            continue;
                        }
                        
                        $group = tr($group);
                        if ($group != $lastGroup) {
                            $dhtml = "<tr><td colspan='3' class='productGroupInfo'>{$group}</td></tr>";
                            $tpl->append($dhtml, 'INFO');
                        }
                        
                        $lastGroup = $group;
                    }
                    
                    $caption = tr($caption);
                    $unit = tr($field->unit);
                    
                    if ($field->inlineTo) {
                        $dhtml = new ET(" {$caption} " . $data->row->{$name} . " {$unit}");
                        $tpl->prepend($dhtml, $field->inlineTo);
                    } else {
                        if ($field->singleCaption == '@') {
                            $dhtml = new ET("<tr><td>&nbsp;&nbsp;</td><td colspan=2 style='padding-left:5px; font-weight:bold;vertical-align:bottom;'>" . $data->row->{$name} . " {$unit}[#${name}#]</td></tr>");
                        } elseif ($field->singleCaption) {
                            $caption = tr($field->singleCaption);
                        } else {
                            $dhtml = new ET("<tr><td>&nbsp;-&nbsp;</td> <td> {$caption}:</td><td style='padding-left:5px; font-weight:bold;vertical-align:bottom;'>" . $data->row->{$name} . " {$unit}[#${name}#]</td></tr>");
                        }
                        $tpl->append($dhtml, 'INFO');
                    }
                }
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Връща информация за какви дефолт задачи за производство могат да се създават по артикула
     *
     * @param mixed $id       - ид или запис на артикул
     * @param float $quantity - к-во за произвеждане
     *
     * @return array $drivers - масив с информация за драйверите, с ключ името на масива
     *               o title           - дефолт име на задачата, най добре да е името на крайния артикул / името заготовката
     *               o plannedQuantity - планирано к-во в основна опаковка
     *               o productId       - ид на артикул
     *               o packagingId     - ид на опаковка
     *               o quantityInPack  - к-во в 1 опаковка
     *               o products        - масив от масиви с продуктите за влагане/произвеждане/отпадане
     *               - array input           - материали за влагане
     *               o productId      - ид на материал
     *               o packagingId    - ид на опаковка
     *               o quantityInPack - к-во в 1 опаковка
     *               o packQuantity   - общо количество от опаковката
     *               - array production      - артикули за произвеждане
     *               * 							o productId      - ид на заготовка
     *               o packagingId    - ид на опаковка
     *               o quantityInPack - к-во в 1 опаковка
     *               o packQuantity   - общо количество от опаковката
     *               - array waste           - отпадъци
     *               * 							o productId      - ид на отпадък
     *               o packagingId    - ид на опаковка
     *               o quantityInPack - к-во в 1 опаковка
     *               o packQuantity   - общо количество от опаковката
     */
    public function getDefaultProductionTasks($id, $quantity = 1)
    {
        return array();
    }
    
    
    /**
     * Връща дефолтното име на артикула
     *
     * @param stdClass $rec
     *
     * @return NULL|string
     */
    public function getProductTitle($rec)
    {
    }
    
    
    /**
     * Връща данни за дефолтната рецепта за артикула
     *
     * @param stdClass $rec - запис
     *
     * @return FALSE|array
     *                     ['quantity'] - К-во за което е рецептата
     *                     ['expenses'] - % режийни разходи
     *                     ['materials'] array
     *                     o code              string          - Код на материала
     *                     o baseQuantity      double          - Начално количество на вложения материал
     *                     o propQuantity      double          - Пропорционално количество на вложения материал
     *                     o type              input|pop|stage - вида на записа материал|отпадък|етап
     *                     o parentResourceId  string          - ид на артикула на етапа
     *                     o expenses          double          - % режийни разходи
     *
     */
    public function getDefaultBom($rec)
    {
        return false;
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения клиент на посочената дата
     *
     * @param mixed                                                                              $productId - ид на артикул
     * @param int                                                                                $quantity  - к-во
     * @param float                                                                              $minDelta  - минималната отстъпка
     * @param float                                                                              $maxDelta  - максималната надценка
     * @param datetime                                                                           $datetime  - дата
     * @param float                                                                              $rate      - валутен курс
     * @param enum(yes=Включено,no=Без,separate=Отделно,export=Експорт) $chargeVat - начин на начисляване на ддс
     * @param int|NULL                                                                           $listId    - ценова политика
     *
     * @return float|NULL $price  - цена
     */
    public function getPrice($productId, $quantity, $minDelta, $maxDelta, $datetime = null, $rate = 1, $chargeVat = 'no', $listId = null)
    {
        // Ако има рецепта връщаме по нея
        if ($bomRec = $this->getBomForPrice($productId)) {
            if ($listId == price_ListRules::PRICE_LIST_CATALOG) {
                $listId = price_ListRules::PRICE_LIST_COST;
            }
            
            return cat_Boms::getBomPrice($bomRec, $quantity, $minDelta, $maxDelta, $datetime, $listId);
        }
        
        return null;
    }
    
    
    /**
     * Записа на рецептата на артикула, ако няма на прототипния, ако има
     * 
     * @param int $productId - ид на артикул
     * 
     * @return boolean|stdClass $bomRec - запис на рецепта
     */
    public function getBomForPrice($productId)
    {
        // Търсим първо активната търговска рецепта, ако няма търсим активната работна
        $protoId = cat_Products::fetchField($productId, 'proto');
        
        $bomRec = cat_Products::getLastActiveBom($productId, 'sales');
        if (empty($bomRec)) {
            $bomRec = cat_Products::getLastActiveBom($productId, 'production');
        }
        
        if (empty($bomRec) && isset($protoId)) {
            $bomRec = $this->getBomForPrice($protoId);
        }
        
        return $bomRec;
    }
    
    
    /**
     * Може ли драйвера автоматично да си изчисли себестойността
     *
     * @param mixed $productId - запис или ид
     *
     * @return bool
     */
    public function canAutoCalcPrimeCost($productId)
    {
        return false;
    }
    
    
    /**
     * Връща дефолтната дефиниция за шаблон на партидна дефиниция
     *
     * @param mixed $id - ид или запис на артикул
     *
     * @return int - ид към batch_Templates
     */
    public function getDefaultBatchTemplate($id)
    {
    }
    
    
    /**
     * ХТМЛ представяне на артикула (img)
     *
     * @param int           $rec      - запис на артикул
     * @param embed_Manager $Embedder
     * @param array         $size     - размер на картинката
     * @param array         $maxSize  - макс размер на картинката
     *
     * @return string|NULL $preview - хтмл представянето
     */
    public function getPreview($rec, embed_Manager $Embedder, $size = array('280', '150'), $maxSize = array('550', '550'))
    {
    }
    
    
    /**
     * Добавя полетата на задачата за производство на артикула
     *
     * @param int           $id       - ид на артикул
     * @param core_Fieldset $fieldset - форма на задание
     */
    public function addTaskFields($id, core_Fieldset &$fieldset)
    {
    }
    
    
    /**
     * Метод позволяващ на артикула да добавя бутони към rowtools-а на документ
     *
     * @param int             $id          - ид на артикул
     * @param core_RowToolbar $toolbar     - тулбара
     * @param mixed           $detailClass - класа детаила на документа
     * @param int             $detailId    - ид на детайла на документа
     *
     * @return void
     */
    public function addButtonsToDocToolbar($id, core_RowToolbar &$toolbar, $detailClass, $detailId)
    {
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
    }
    
    
    /**
     * Връща минималното количество за поръчка
     *
     * @param int|NULL $id - ид на артикул
     *
     * @return float|NULL - минималното количество в основна мярка, или NULL ако няма
     */
    public function getMoq($id = null)
    {
    }
    
    
    /**
     * Връща броя на количествата, които ще се показват в запитването
     *
     * @return int|NULL - броя на количествата в запитването
     */
    public function getInquiryQuantities()
    {
    }
    
    
    /**
     * Връща дефолтните опаковки за артикула
     *
     * @param mixed $rec - запис на артикула
     *
     * @return array - масив с дефолтни данни за опаковките
     *
     * 		o boolean justGuess   - дали е задължителна
     * 		o int     packagingId - ид на мярка/опаковка
     * 		o double  quantity    - количество
     * 		o boolean isBase      - дали опаковката е основна
     * 		o double  tareWeight  - тара тегло
     * 		o double  sizeWidth   - широчина на опаковката
     * 		o double  sizeHeight  - височина на опаковката
     * 		o double  sizeDepth   - дълбочина на опаковката
     */
    public function getDefaultPackagings($rec)
    {
        return array();
    }
    
    
    /**
     * Допълнителните условия за дадения продукт,
     * които автоматично се добавят към условията на договора
     *
     * @param stdClass    $rec     - ид/запис на артикул
     * @param string      $docType - тип на документа sale/purchase/quotation
     * @param string|NULL $lg      - език
     */
    public function getConditions($rec, $docType, $lg = null)
    {
        return array();
    }
    
    
    /**
     * Връща хеша на артикула (стойност която показва дали е уникален)
     *
     * @param embed_Manager $Embedder - Ембедър
     * @param mixed         $rec      - Ид или запис на артикул
     *
     * @return NULL|varchar - Допълнителните условия за дадения продукт
     */
    public function getHash(embed_Manager $Embedder, $rec)
    {
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
        return array();
    }
    
    
    /**
     * Връща допълнителен текст, който да се показва към забележките на показването на артикула в документ
     *
     * @param mixed  $productId    - ид или запис на артикул
     * @param string $documentType - public или internal или invoice
     *
     * @return string - допълнителния текст, специфичен за документа
     */
    public function getAdditionalNotesToDocument($productId, $documentType)
    {
    }
    
    
    /**
     * Може ли в артикула да се начислява транспорт към цената му
     *
     * @param mixed $productId - ид или запис на артикул
     *
     * @return bool
     */
    public function canCalcTransportFee($productId)
    {
        $bomRec = $this->getBomForPrice($productId);
        
        return is_object($bomRec);
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
    }
    
    
    /**
     * Връща сериен номер според източника
     *
     * @param mixed $id             - ид или запис на артикул
     * @param mixed $sourceClassId  - клас
     * @param mixed $sourceObjectId - ид на обект
     *
     * @return string $serial       - генериран сериен номер
     */
    public function generateSerial($id, $sourceClassId = null, $sourceObjectId = null)
    {
        return cat_Serials::generateSerial($sourceClassId, $sourceObjectId);
    }
    
    
    /**
     * Регистрира дадения сериен номер, към обекта (ако има)
     *
     * @param mixed    $id             - ид или запис на артикул
     * @param mixed    $serial         - сериен номер
     * @param mixed    $sourceClassId  - клас на обекта
     * @param int|NULL $sourceObjectId - ид на обекта
     */
    public function assignSerial($id, $serial, $sourceClassId = null, $sourceObjectId = null)
    {
        return cat_Serials::assignSerial($serial, $sourceClassId, $sourceObjectId);
    }
    
    
    /**
     * Записа на артикула отговарящ на серийния номер
     *
     * @param int $serial
     *
     * @return stdClass|NULL
     */
    public function getRecBySerial($serial)
    {
        if ($sRec = cat_Serials::getRecBySerial($serial)) {
            if (cls::load($sRec->sourceClassId, true)) {
                $Source = cls::get($sRec->sourceClassId);
                if ($Source->getField('productId', false)) {
                    if ($productId = $Source->fetchField($sRec->sourceObjectId, 'productId')) {
                        
                        return cat_Products::fetch($productId);
                    }
                }
            }
        }
    }
    
    
    /**
     * Канонизиране генерирания номер
     *
     * @param string $serial
     *
     * @return string
     */
    public function canonizeSerial($id, $serial)
    {
        return cat_Serials::canonize($serial);
    }
    
    
    /**
     * Проверяване на серийния номер
     *
     * @param string $serial
     *
     * @return string
     */
    public function checkSerial($id, $serial, &$error)
    {
        return cat_Serials::check($serial, $error);
    }
    
    
    /**
     * Връща сложността на артикула
     *
     * @param mixed $rec
     *
     * @return int
     */
    public function getDifficulty($rec)
    {
        return 1;
    }
    
    
    /**
     * Надценка на делтата
     *
     * @param mixed $rec
     *
     * @return int
     */
    public function getDeltaSurcharge($rec)
    {
        return 1;
    }
}
