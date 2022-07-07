<?php


/**
 * Интерфейс за създаване на отчети от различни източници в системата
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
 */
class cat_ProductDriverIntf extends embed_DriverIntf
{
    /**
     * Версия на интерфейса
     */
    const VERSION = 2;


    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Връща свойствата на артикула според драйвера
     *
     * @param mixed $metas - текущи мета данни
     *
     * @return array $metas - кои са дефолтните мета данни
     */
    public function getDefaultMetas()
    {
        return $this->class->getDefaultMetas();
    }
    
    
    /**
     * Връща счетоводните свойства на обекта
     */
    public function getFeatures()
    {
        return $this->class->getFeatures();
    }
    
    
    /**
     * Кои документи са използвани в полетата на драйвера
     */
    public function getUsedDocs()
    {
        return $this->class->getUsedDocs();
    }
    
    
    /**
     * Връща задължителната основна мярка
     *
     * @param stdClass|null $rec
     * @return int|NULL - ид на мярката, или NULL ако може да е всяка
     */
    public function getDefaultUomId($rec = null)
    {
        return $this->class->getDefaultUomId($rec);
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
        return $this->class->getParams($classId, $id, $name, $verbal);
    }
    
    
    /**
     * Връща материалите, необходими за производството на посоченото количество
     *
     * @param int $id
     * @param double $quantity
     *
     * @return array()
     */
    public function getMaterialsForProduction($id, $quantity)
    {
        return $this->class->getMaterialsForProduction($id, $quantity);
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
        return $this->class->prepareProductDescription($data);
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
        return $this->class->renderProductDescription($data);
    }


    /**
     * Връща информация за какви дефолт задачи за производство могат да се създават по артикула
     *
     * @param mixed $jobRec   - ид или запис на задание
     * @param float $quantity - к-во за произвеждане
     *
     * @return array $drivers - масив с информация за драйверите, с ключ името на масива
     *               o title                          - дефолт име на задачата, най добре да е името на крайния артикул / името заготовката
     *               o plannedQuantity                - планирано к-во в основна опаковка
     *               o productId                      - ид на артикул
     *               o packagingId                    - ид на опаковка
     *               o quantityInPack                 - к-во в 1 опаковка
     *               o products                       - масив от масиви с продуктите за влагане/произвеждане/отпадане
     *               o timeStart                      - начало
     *               o timeDuration                   - продължителност
     *               o timeEnd                        - край
     *               o fixedAssets                    - списък (кейлист) от оборудвания
     *               o employees                      - списък (кейлист) от служители
     *               o storeId                        - склад
     *               o indTime                        - норма
     *               o centerId                       - център на производство
     *               o indPackagingId                 - опаковка/мярка за норма
     *               o indTimeAllocation              - начин на отчитане на нормата
     *               o showadditionalUom              - какъв е режима за изчисляване на теглото
     *               o weightDeviationNotice          - какво да е отклонението на теглото за внимание
     *               o weightDeviationWarning         - какво да е отклонението на теглото за предупреждение
     *               o weightDeviationAverageWarning  - какво да е отклонението спрямо средното
     *               o description                    - забележки
     *
     *               - array input        - масив отматериали за влагане
     *                  o productId      - ид на материал
     *                  o packagingId    - ид на опаковка
     *                  o quantityInPack - к-во в 1 опаковка
     *                  o packQuantity   - общо количество от опаковката
     *               - array production   - масив от производими артикули
     *                  o productId      - ид на заготовка
     *                  o packagingId    - ид на опаковка
     *                  o quantityInPack - к-во в 1 опаковка
     *                  o packQuantity   - общо количество от опаковката
     *               - array waste        - масив от отпадъци
     *                  o productId      - ид на отпадък
     *                  o packagingId    - ид на опаковка
     *                  o quantityInPack - к-во в 1 опаковка
     *                  o packQuantity   - общо количество от опаковката
     */
    public function getDefaultProductionTasks($jobRec, $quantity = 1)
    {
        return $this->class->getDefaultProductionTasks($jobRec, $quantity);
    }
    
    
    /**
     * Връща иконата на драйвера
     *
     * @return string - пътя към иконата
     */
    public function getIcon()
    {
        return $this->class->getIcon();
    }
    
    
    /**
     * Рендиране на описанието на драйвера в еденичния изглед на артикула
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderSingleDescription($data)
    {
        return $this->class->renderSingleDescription($data);
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
        return $this->class->getProductTitle($rec);
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
     *                     ['code']         string  - Код на материала
     *                     ['baseQuantity'] double  - Начално количество на вложения материал
     *                     ['propQuantity'] double  - Пропорционално количество на вложения материал
     *                     ['waste']        boolean - Дали материала е отпадък
     *                     ['stageName']    string  - Име на производствения етап
     *
     */
    public function getDefaultBom($rec)
    {
        return $this->class->getDefaultBom($rec);
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
     * @param string $chargeVat - начин на начисляване на ддс
     * 
     * @return stdClass|float|NULL $price  - обект с цена и отстъпка, или само цена, или NULL ако няма
     */
    public function getPrice($productId, $quantity, $minDelta, $maxDelta, $datetime = null, $rate = 1, $chargeVat = 'no')
    {
        return $this->class->getPrice($productId, $quantity, $minDelta, $maxDelta, $datetime, $rate, $chargeVat);
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
        return $this->class->canAutoCalcPrimeCost($productId);
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
        return $this->class->getDefaultBatchTemplate($id);
    }
    
    
    /**
     * ХТМЛ представяне на артикула (img)
     *
     * @param int           $rec      - запис на артикул
     * @param array         $size     - размер на картинката
     * @param array         $maxSize  - макс размер на картинката
     * @param embed_Manager $Embedder
     *
     * @return string|NULL $preview - хтмл представянето
     */
    public function getPreview($rec, embed_Manager $Embedder, $size = array('280', '150'), $maxSize = array('550', '550'))
    {
        return $this->class->getPreview($rec, $Embedder, $size, $maxSize);
    }
    
    
    /**
     * Добавя полетата на задачата за производство на артикула
     *
     * @param int           $id       - ид на артикул
     * @param core_Fieldset $fieldset - форма на задание
     */
    public function addTaskFields($id, core_Fieldset &$fieldset)
    {
        return $this->class->addTaskFields($id, $fieldset);
    }
    
    
    /**
     * Метод позволяващ на артикула да добавя бутони към rowtools-а на документ
     *
     * @param int             $id          - ид на артикул
     * @param core_RowToolbar $toolbar     - тулбара
     * @param mixed           $detailClass - класа на детаила в документа
     * @param int             $detailId    - ид на реда от документа
     *
     * @return void
     */
    public function addButtonsToDocToolbar($id, core_RowToolbar &$toolbar, $detailClass, $detailId)
    {
        return $this->class->addButtonsToDocToolbar($id, $toolbar, $detailClass, $detailId);
    }
    
    
    /**
     * Връща минималното количество за поръчка
     *
     * @param int|NULL $id   - ид на артикул
     * @param string $action - дали да е за продажба или покупка
     *
     * @return float|NULL - минималното количество в основна мярка, или NULL ако няма
     */
    public function getMoq($id = null, $action = 'sell')
    {
        return $this->class->getMoq($id, $action);
    }
    
    
    /**
     * Връща броя на количествата, които ще се показват в запитването
     *
     * @return int|NULL - броя на количествата в запитването
     */
    public function getInquiryQuantities()
    {
        return $this->class->getInquiryQuantities();
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
        return $this->class->getDefaultPackagings($rec);
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
        return $this->class->getConditions($rec, $docType, $lg);
    }
    
    
    /**
     * Връща хеша на артикула (стойност която показва дали е уникален)
     *
     * @param embed_Manager $Embedder - Ембедър
     * @param mixed         $rec      - Ид или запис на артикул
     *
     * @return NULL|string - Допълнителните условия за дадения продукт
     */
    public function getHash(embed_Manager $Embedder, $rec)
    {
        return $this->class->getHash($Embedder, $rec);
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
        return $this->class->getAdditionalLabelData($rec, $labelSourceClass);
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
        return $this->class->getAdditionalNotesToDocument($productId, $documentType);
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
        return $this->class->canCalcTransportFee($productId);
    }
    
    
    /**
     * Връща транспортното тегло за подаденото количество
     *
     * @param mixed $rec      - ид или запис на артикул
     * @param int   $quantity - общо количество
     *
     * @return float|NULL - транспортното тегло на общото количество
     */
    public function getTransportWeight($rec, $quantity)
    {
        return $this->class->getTransportWeight($rec, $quantity);
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
        return $this->class->getTransportVolume($rec, $quantity);
    }
    
    
    /**
     * Връща сериен номер според източника
     *
     * @param mixed $id             - ид или запис на артикул
     * @param mixed $sourceClassId  - клас
     * @param mixed $sourceObjectId - ид на обект
     *
     * @return string|null $serial  - генериран сериен номер или null ако не може
     */
    public function generateSerial($id, $sourceClassId = null, $sourceObjectId = null)
    {
        return $this->class->generateSerial($id, $sourceClassId, $sourceObjectId);
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
        return $this->class->assignSerial($id, $serial, $sourceClassId, $sourceObjectId);
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
        return $this->class->getRecBySerial($serial);
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
        return $this->class->canonizeSerial($id, $serial);
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
        return $this->class->checkSerial($id, $serial, $error);
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
        return $this->class->getDifficulty($rec);
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
        return $this->class->getDeltaSurcharge($rec);
    }
    
    
    /**
     * Какви са дефолтните количества на артикула за офертата
     *
     * @param embed_Manager $Embedder
     * @param stdClass $rec
     * @return array $res
     */
    public function getQuantitiesForQuotation($Embedder, $rec)
    {
        return $this->class->getQuantitiesForQuotation($Embedder, $rec);
    }
    
    
    /**
     * Кои са свързаните задания за други артикули с този артикул
     *
     * @param mixed $rec
     *
     * @return array
     */
    public function getLinkedJobRecs($id)
    {
        return $this->class->getLinkedJobRecs($id);
    }


    /**
     * Добавя полета към формата за запитване
     *
     * @param int $protoProductId
     * @param core_FieldSet $fieldset
     * @param array $existingFields
     *
     * @return void
     */
    public function addInquiryFields($protoProductId, core_FieldSet &$fieldset, $existingFields = array())
    {
        return $this->class->addInquiryFields($protoProductId, $fieldset);
    }
    
    
    /**
     * Взима шаблона за показване на допълнителните данни от запитването
     *
     * @param stdClass $rec
     *
     * @return core_ET
     */
    public function getInquiryDataTpl($rec)
    {
        return $this->class->getInquiryDataTpl($rec);
    }


    /**
     * Коя е втората мярка на артикула, ако има
     *
     * @param int $id   - ид
     * @return int|null - ид на втора мяркя или null, ако няма
     */
    public function getSecondMeasureId($id)
    {
        return $this->class->getSecondMeasureId($id);
    }


    /**
     * Какъв текст да излиза в имейла за изходяща оферта за артикула
     *
     * @param int $productId
     * @param int $quotationId
     * @param string|null $lang
     * @return null|string
     */
    public function getQuotationEmailText($productId, $quotationId, $lang = null)
    {
        return $this->class->getQuotationEmailText($productId, $quotationId, $lang);
    }


    /**
     * Връща информация за данните за производството на артикула
     *
     * @param int $productId
     * @return array
     *          int|null    ['name']                 - наименование
     *          int|null    ['centerId']             - ид на център на дейност
     *          int|null    ['storeIn']              - ид на склад за засклаждане (ако е складируем)
     *          int|null    ['inputStores']          - ид на складове за влагане (ако е складируем)
     *          array|null  ['fixedAssets']          - масив от ид-та на оборудвания (@see planning_AssetResources)
     *          array|null  ['employees']            - масив от ид-та на оператори (@see planning_Hr)
     *          int|null    ['norm']                 - норма за производство
     *          int|null    ['labelPackagingId']     - ид на опаковка за етикет
     *          double|null ['labelQuantityInPack']  - к-во в опаковка за етикет
     *          string|null ['labelType']            - тип на етикета
     *          int|null    ['labelTemplate']        - шаблон за етикет
     *          array|null  ['planningParams']       - параметри за планиране
     *          array|null  ['actions']              - операции за планиране
     *          string      ['isFinal']              - дали е финална
     *          string      ['showPreviousJobField'] - дали да се изисква предходно задание
     *          string      ['wasteProductId']       - ид на отпадък
     *          string      ['wasteStart']           - начално количество отпадък
     *          string      ['wastePercent']         - процент отпадък
     */
    public function getProductionData($productId)
    {
        return $this->class->getProductionData($productId);
    }
}
