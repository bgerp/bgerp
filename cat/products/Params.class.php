<?php

/**
 * Клас 'cat_products_Params'
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
use Buzz\Client\FileGetContents;

class cat_products_Params extends cat_products_Detail
{
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    var $title = 'Параметри';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'paramId, paramValue';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'cat_Wrapper';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'cat_Products';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden');
        $this->FLD('paramId', 'key(mvc=cat_Params,select=name)', 'input,caption=Параметър');
        $this->FLD('paramValue', 'varchar(255)', 'input,caption=Стойност');
        
        $this->setDbUnique('productId,paramId');
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, $data)
    {
        $data->changeBtn = ht::createLink("<img src=" . sbf('img/16/edit.png') . " valign=bottom style='margin-left:5px;'>", array($mvc, 'edit', 'productId'=>$data->masterId));
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        $data->query->orderBy('#id');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterPrepareListRows($mvc, $data)
    {
        $recs = &$data->recs;
        
        if ($recs) {
            $rows = &$data->rows;
            
            foreach ($recs as $i=>$rec) {
                $row = $rows[$i];
                
                $paramRec = cat_Params::fetch($rec->paramId);
                
                $row->paramValue .= ' ' . cat_Params::getVerbal($paramRec, 'suffix');
            }
        }
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        $productId = Request::get('productId', "key(mvc={$mvc->Master->className})");
        $data->form = $mvc->getParamsForm($productId);
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareEditToolbar($mvc, $data)
    {
        $productId = Request::get('productId', "key(mvc={$mvc->Master->className})");
        $data->form->toolbar->addBtn('Отказ', array('cat_Products', 'single', $productId), array('class'=>'btn-cancel'));
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function &getParamsForm($productId, &$form = NULL)
    {
        $productRec = cat_Products::fetch($productId);
        $form = cat_Categories::getParamsForm($productRec->categoryId, $form);
        
        if (!$form->getField('productId', FALSE)) {
            $form->FLD('productId', 'key(mvc=cat_Products)', 'silent,input=hidden');
            $form->setDefault('productId', $productId);
        }
        
        if (!$form->title) {
            $form->title = "Параметри на |*" . cat_Products::getVerbal($productRec, 'name');
        }
        
        $query = static::getQuery();
        $query->where("#productId = {$productId}");
        
        while ($rec = $query->fetch()) {
            $form->setDefault("value_{$rec->paramId}", $rec->paramValue);
            $form->FLD("id_{$rec->paramId}", "key(mvc=cat_Products_Params)", "input=hidden");
            $form->setDefault("id_{$rec->paramId}", $rec->id);
        }
        
        return $form;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function processParamsForm($form)
    {
        $productId = $form->rec->productId;
        
        foreach ((array)$form->rec as $n=>$v) {
            list($n, $key) = explode('_', $n, 2);
            
            if ($n == 'value') {
                $paramId = $key;
                $id = $form->rec->{"id_{$paramId}"};
                $paramValue = $v;
                
                $rec = (object)compact('id', 'productId', 'paramId', 'paramValue');
                static::save($rec);
            }
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted()) {
            $mvc->processParamsForm($form);
            
            redirect(array('cat_Products', 'single', $form->rec->productId));
        }
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    function renderDetail_($data)
    {
        $tpl = new ET(getFileContent('cat/tpl/products/Params.shtml'));
        
        $tpl->append($data->changeBtn, 'TITLE');
        
        foreach((array)$data->rows as $row) {
            $block = $tpl->getBlock('param');
            $block->placeObject($row);
            
            $block->append2Master();
        }
            
        return $tpl;
    }
    

    public static function prepareParams($data)
    {
        static::prepareDetail($data);
    }
    
    
    public static function renderParams($data)
    {
        return static::renderDetail($data);
    }
}