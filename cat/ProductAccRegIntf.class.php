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
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Стоки и продукти
 */
class cat_ProductAccRegIntf extends acc_RegisterIntf
{
    
    
    /**
     * Връща id-то на основната мярка на продукта
     *
     * @param int $productId id на записа на продукта
     * @return key(mvc=cat_UoM) ключ към записа на основната мярка на продукта
     */
    function getProductUoM($productId)
    {
        return $this->class->getProductUOM($productId);
    }
    
    
    /**
     * Връща запис, съдържащ ценова информация за продукта
     *
     * @param int $productId id на записа на продукта
     * @param key(mvc=acc_Items) id на перото на контрагента (NULL = незивестен контрагент)
     * @param date дата към която се изисква да е актуална информацията  (NULL = сега)
     *
     * o int    id    ид на записа
     * o string title име на продукта
     * о int    code  код на продукта
     * о int    uomId ключ към основната мярка на продукта в cat_UoM
     * o float  price цена на дребно на продукта за основната мярка
     * o float  cost  себестойност на продукта за основната мярка
     * о
     * о array  packs масив с key(mvc=cat_Packaging) => (stdClass) packInfo
     * - string  packInfo->eanCode EAN код на продукта
     * - string  packInfo->customerCode код на клиента
     * - float   packInfo->quantity  количество от основната мярка
     * - float   цена за тази разфасовка
     * - float   отстъпка за тази разфасовка
     */
    function getProductInfo($productId, $contragentId = NULL, $date = NULL)
    {
        return $this->class->getProductInfo($productId, $contragentId, $date);
    }
    
    
    /**
     * Цена на продукт към дата в зависимост от пакет отстъпки.
     *
     * @param int $productId
     * @param string $date Ако е NULL връща масив с историята на цените на продукта: [дата] => цена
     * @param int $discountId key(mvc=catpr_Discounts) пакет отстъпки. Ако е NULL - цена без отстъпка.
     */
    function getProductPrice($productId, $date = NULL, $discountId = NULL)
    {
        return $this->class->getProductPrice($productId, $date, $discountId);
    }
    
    /**
     * @todo Чака за документация...
     */
    function isDimensional()
    {
        return TRUE;
    }
}
