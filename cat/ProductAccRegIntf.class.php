<?php


/**
 * Интерфейс за пера - продукти
 *
 * Този интерфейс трябва да се поддържа от всички регистри, които
 * Представляват материални ценности с които се извършват покупко-продажби
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за пера, които са стоки и продукти
 */
class cat_ProductAccRegIntf extends acc_RegisterIntf
{
    /**
     * Връща id-то на основната мярка на продукта
     *
     * @param int $productId id на записа на продукта
     *
     * @return key(mvc=cat_UoM) ключ към записа на основната мярка на продукта
     */
    public function getProductUoM($productId)
    {
        return $this->class->getProductUOM($productId);
    }
    
    
    /**
     * Метод връщаш информация за продукта и неговите опаковки
     *
     * @param int $productId   - ид на продукта
     * @param int $packagingId - ид на опаковката, по дефолт NULL
     *
     * @return stdClass $res
     *                  -> productRec - записа на продукта
     *                  ->isPublic - дали е публичен или частен
     *                  ->meta - мета данни за продукта ако има
     *                  meta['canSell'] 		- дали може да се продава
     *                  meta['canBuy']         - дали може да се купува
     *                  meta['canConvert']     - дали може да се влага
     *                  meta['canStore']       - дали може да се съхранява
     *                  meta['canManifacture'] - дали може да се прозивежда
     *                  meta['fixedAsset']     - дали е ДМА
     *                  -> packagings - всички опаковки на продукта, ако не е зададена
     */
    public function getProductInfo($productId)
    {
        return $this->class->getProductInfo($productId);
    }
    
    
    /**
     * Връща масив с опаковките на, в които може да се слага даден продукт,
     * във вид подходящ за опции на key
     */
    public function getPacks($productId)
    {
        return $this->class->getPacks($productId);
    }
    
    
    /**
     * Връща продуктите опции с продукти:
     * 	 Ако е зададен клиент се връщат всички публични + частните за него
     *   Ако не е зададен клиент се връщат всички активни продукти
     *
     * @param mixed  $customerClass    - клас/ид на контрагента
     * @param int    $customerId       - ид на контрагента
     * @param string $datetime         - дата към която извличаме артикулите
     * @param mixed  $hasProperties    - мета данни, на които да отговарят артикулите
     * @param mixed  $hasnotProperties - мета данни, на които да отговарят артикулите
     * @param string $limit            - колко опции да върнем
     *
     * @return array - масив с достъпните за контрагента артикули
     */
    public function getProducts($customerClass, $customerId, $datetime = null, $hasProperties = null, $hasnotProperties = null, $limit = null)
    {
        return $this->class->getProducts($customerClass, $customerId, $datetime, $hasProperties, $hasnotProperties, $limit);
    }
    
    
    /**
     * Връща себестойноста на артикула
     *
     * @param int $productId            - ид на артикул
     * @param int $packagingId          - ид на опаковка
     * @param double $quantity          - количество
     * @param datetime $date            - към коя дата
     * @param int|null $primeCostlistId - по коя ценова политика да се смята себестойноста
     * @return double|NULL $primeCost   - себестойност
     */
    public function getPrimeCost($productId, $packagingId = null, $quantity = null, $date = null, $primeCostlistId = null)
    {
        return $this->class->getPrimeCost($productId, $packagingId, $quantity, $date, $primeCostlistId);
    }
    
    
    /**
     * Връща масив от продукти отговарящи на зададени мета данни:
     * canSell, canBuy, canManifacture, canConvert, fixedAsset, canStore
     *
     * @param mixed $properties       - комбинация на горе посочените мета
     *                                данни, на които трябва да отговарят
     * @param mixed $hasnotProperties - комбинация на горе посочените мета
     *                                които не трябва да имат
     */
    public function getByProperty($properties, $hasnotProperties = null)
    {
        return $this->class->getByProperty($properties, $hasnotProperties);
    }
    
    
    /**
     * Връща транспортното тегло за подаденото количество и опаковка
     *
     * @param int $productId - ид на продукт
     * @param int $quantity  - общо количество
     *
     * @return float|NULL - транспортното тегло за к-то на артикула
     */
    public function getTransportWeight($productId, $quantity)
    {
        return $this->class->getTransportWeight($productId, $quantity);
    }
    
    
    /**
     * Връща стойността на параметъра с това име, или
     * всички параметри с техните стойностти
     *
     * @param string $id     - ид на записа
     * @param string $name   - име на параметъра, или NULL ако искаме всички
     * @param bool   $verbal - дали да са вербални стойностите
     *
     * @return mixed - стойност или FALSE ако няма
     */
    public function getParams($id, $name = null, $verbal = false)
    {
        return $this->class->getParams($id, $name, $verbal);
    }
    
    
    /**
     * Връща транспортния обем за подаденото количество и опаковка
     *
     * @param int $productId - ид на продукт
     * @param int $quantity  - общо количество
     *
     * @return float - теглото на единица от продукта
     */
    public function getTransportVolume($productId, $quantity)
    {
        return $this->class->getTransportVolume($productId, $quantity);
    }
    
    
    /**
     * Връща последната активна рецепта на артикула
     *
     * @param mixed            $id   - ид или запис
     * @param string $type - вид работна или търговска
     *
     * @return mixed $res - записа на рецептата или FALSE ако няма
     */
    public function getLastActiveBom($id, $type = null)
    {
        return $this->class->getLastActiveBom($id, $type);
    }
    
    
    /**
     * Връща информация за какви дефолт задачи за производство могат да се създават към заданието на артикула
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
     *               o indPackagingId                 - опаковка/мярка за норма
     *               o indTimeAllocation              - начин на отчитане на нормата
     *               o showadditionalUom              - какъв е режима за изчисляване на теглото
     *               o weightDeviationNotice          - какво да е отклонението на теглото за внимание
     *               o weightDeviationWarning         - какво да е отклонението на теглото за предупреждение
     *               o weightDeviationAverageWarning  - какво да е отклонението спрямо средното
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
     * Метод позволяващ на артикула да добавя бутони към rowtools-а на документ
     *
     * @param int             $id          - ид на артикул
     * @param core_RowToolbar $toolbar     - тулбара
     * @param mixed           $detailClass - класа на детайла на документа
     * @param int             $detailId    - ид на реда от детайла на документа
     *
     * @return void
     */
    public function addButtonsToDocToolbar($id, core_RowToolbar &$toolbar, $detailClass, $detailId)
    {
        return $this->class->addButtonsToDocToolbar($id, $toolbar, $detailClass, $detailId);
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
        return $this->class->getTolerance($id, $quantity);
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
        return $this->class->getDeliveryTime($id, $quantity);
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
        return $this->class->getMoq($id = null);
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
     * @param mixed $rec - ид или запис на артикул
     *
     * @return NULL|string - Допълнителните условия за дадения продукт
     */
    public function getHash($rec)
    {
        return $this->class->getHash($rec);
    }
}
