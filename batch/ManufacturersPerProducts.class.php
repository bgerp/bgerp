<?php


/**
 * Потребителски кеш на производителите на артикулите по папки
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class batch_ManufacturersPerProducts extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Производителите на артикулите по папки';


    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'batch_Wrapper,plg_RowTools2';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'folderId,productId,string';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Кой може да изтрива?
     */
    public $canDelete = 'debug';


    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('folderId', 'key(mvc=doc_Folders)', 'caption=Папка,input=none');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $this->FLD('string', 'varchar(128)', 'caption=Свойство,mandatory');

        $this->setDbIndex('folderId,productId');
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
        $row->productId = cat_Products::getHyperlink($rec->productId);
    }


    /**
     * Връща опциите за избор
     *
     * @param int $folderId
     * @param int $productId
     * @return array $options
     */
    public static function getArray($folderId, $productId)
    {
        $options = array();
        $query = static::getQuery();
        $query->where("#folderId = {$folderId} AND #productId = {$productId}");
        $query->orderBy('id', 'DESC');
        while($rec = $query->fetch()){
            $options[$rec->string] = $rec->string;
        }

        return $options;
    }
}