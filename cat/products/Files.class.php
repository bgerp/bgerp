<?php

/**
 * Клас 'cat_products_Files' 
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_products_Files extends cat_products_Detail
{
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    var $title = 'Файлове';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'file,description,modifiedOn,modifiedBy,tools=Пулт';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'cat_Wrapper, plg_RowTools, plg_Created, plg_Modified';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'cat_Products';
    
    
    /**
     * Поле за редактиране на ред
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canAdd = 'ceo,cat';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canDelete = 'ceo,cat';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden,silent');
        $this->FLD('file', 'fileman_FileType(bucket=productsFiles)', 'caption=Файл, notSorting');
        $this->FLD('description', 'varchar', 'caption=Описание,input');
    }
    
    
    /**
     * Създаваме кофа
     *
     * @param core_MVC $mvc
     * @param stdClass $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('productsFiles', 'Файлове към продукта', '', '100MB', 'user', 'every_one');
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar(core_Manager $mvc, $data)
    {
        if ($mvc->haveRightFor('add')) {
            $data->addUrl = array(
                $mvc,
                'add',
                'productId' => $data->masterId,
                'ret_url' => getCurrentUrl() + array('#' => get_class($mvc))
            );
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        $productRec = cat_Products::fetch($form->rec->productId);
        $productName = cat_Products::getVerbal($productRec, 'name');
        
        $form->title = "Файл към|* {$productName}";
    }
    
    
    /**
     * Преди подготовка на списъчния изглед
     */
    static function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
    	$data->query->orderBy('modifiedOn', 'DESC');
    }

    
    /**
     * Подготовка на файловете
     */
    public static function prepareFiles($data)
    {   
        $data->TabCaption = 'Файлове';
        $data->Tab = 'top';
        $data->Order = 10;

        static::prepareDetail($data);
    }
    
    
    /**
     * Рендиране на файловете
     */
    public static function renderFiles($data)
    {
        return static::renderDetail($data);
    }
    
    
    /**
     * Връща подходящ манипулатор на файла
     * 
     * @param integer $masterId - id на мастъра
     * 
     * @return fileHnd - Манипулатора на файла
     */
    static function getImgFh($masterId)
    {
        // Вземаме всички файлове за този мастър по дата на създаване
        $query = static::getQuery();
        $query->where(array("#productId = '[#1#]'", $masterId));
        $query->orderBy('createdOn', 'DESC');
        
        // Обхождаме резулатите
        while ($rec = $query->fetch()) {
            
            // Ако няма файл прескачаме
            if (!$rec->file) continue;
            
            // Вземаме информацията за файла
            $fRec = fileman_Files::fetchByFh($rec->file);
            
            // Вземаме разширението
            $ext = fileman_Files::getExt($fRec->name);
            
            // В долен регистър
            $ext = strtolower($ext);
            
            // Масив с позволените разширения
            $allowedExtArr['jpg'] = 'jpg';
            $allowedExtArr['jpeg'] = 'jpeg';
            $allowedExtArr['png'] = 'png';
            $allowedExtArr['gif'] = 'gif';
            $allowedExtArr['bmp'] = 'bmp';
            
            // Ако разширението е в позволените, връщаме манипулатора на файла
            if ($allowedExtArr[$ext]) return $rec->file; 
        }
    }
}