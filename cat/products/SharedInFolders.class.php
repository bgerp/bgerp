<?php


/**
 * Клас 'cat_products_Params' - продуктови параметри
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class cat_products_SharedInFolders extends core_Manager
{
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    public $title = 'Споделени папки';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Споделяне';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,folderId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_RowTools, plg_SaveAndNew';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'paramId';
    
    
    /**
     * Поле за пулт-а
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    public $tabName = 'cat_Products';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'ceo,cat,sales,purchase';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да листва
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'ceo,cat,sales,purchase';


    /**
     * Кой може да променя състоянието
     */
    public $canChangepublicstate = 'ceo,cat';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products)', 'caption=Артикул,mandatory,silent,input=hidden');
        $this->FLD('folderId', 'key2(mvc=doc_Folders,select=title,allowEmpty,coverInterface=crm_ContragentAccRegIntf)', 'caption=Споделено с,mandatory');
        
        $this->setDbUnique('productId,folderId');
    }


    /**
     * Клонира споделените папки от един артикул на друг
     *
     * @param int $fromProductId - ид на артикула от който ще се клонира
     * @param int $toProductId   - ид на артикула, към който ще се клонира
     *
     * @return int|NULL - ид на клонриания запис, или NULL ако няма
     */
    public static function cloneFolders($fromProductId, $toProductId)
    {
        $query = self::getQuery();
        $query->where(array('#productId = [#1#]', $fromProductId));
        $query->orderBy('id', 'DESC');
        
        while ($rec = $query->fetch()) {
            unset($rec->id);
            $rec->productId = $toProductId;
            self::save($rec);
        }
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    public static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $title = cat_Products::getHyperlink($data->form->rec->productId, true);
        $data->form->title = "Показване на|* <b>{$title}</b> |в папка на контрагент|*";
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'delete') && isset($rec)) {
            $productRec = cat_Products::fetch($rec->productId, 'state');
            if ($productRec->state == 'rejected' || $productRec->state == 'closed') {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'changepublicstate') {
            if(isset($rec->productId) && $requiredRoles != 'no_one'){
                $pRec = cat_Products::fetch($rec->productId, 'state,folderId');
                if (!cat_Products::haveRightFor('edit', $rec->productId, $userId)) {
                    $requiredRoles = 'no_one';
                } else {
                    $folderCover = doc_Folders::fetchCoverClassName($pRec->folderId);
                    if (!cls::haveInterface('crm_ContragentAccRegIntf', $folderCover)) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }
    
    
    /**
     * Подготовка на детайла
     */
    public function prepareShared($data)
    {
        $masterRec = $data->masterData->rec;
        $data->isProto = ($masterRec->state == 'template' || $masterRec->brState == 'template');
        
        if (!Mode::isReadOnly()) {
            if ($this->haveRightFor('changepublicstate', (object) array('productId' => $masterRec->id))) {
                $data->changeStateUrl = array($this, 'changePublicState', 'productId' => $masterRec->id, 'ret_url' => true);
            }
        }
        
        $data->TabCaption = 'Достъпност';
        $data->Tab = 'top';
        
        $data->recs = $data->rows = array();
        if ($data->isProto !== true && $masterRec->isPublic != 'yes') {
            $data->recs[$masterRec->folderId] = (object) array('folderId' => $masterRec->folderId, 'productId' => $masterRec->id, '_isLive' => true);
        }
        $query = self::getQuery();
        $query->where("#productId = {$masterRec->id}");
        while ($rec = $query->fetch()) {
            $data->recs[$rec->folderId] = $rec;
        }
        
        foreach ($data->recs as $id => $rec) {
            $row = static::recToVerbal($rec);
            $row->folderId = doc_Folders::getFolderTitle($rec->folderId);
            if($rec->_isLive){
                $row->folderId = ht::createHint($row->folderId, 'Артикулът е създаден в папката и по подразбиране е споделен в нея');
                unset($row->tools);
            }
            $data->rows[$id] = $row;
        }
        
        if (!Mode::isReadOnly()) {
            if ($this->haveRightFor('add', (object) array('productId' => $masterRec->id))) {
                $data->addUrl = array($this, 'add', 'productId' => $masterRec->id, 'ret_url' => true);
            }
        }
    }
    
    
    /**
     * Промяна на състоянието на нестандартен артикул към стандартен и обратно
     */
    public function act_ChangePublicState()
    {
        $this->requireRightFor('changepublicstate');
        expect($productId = Request::get('productId', 'int'));
        expect($pRec = cat_Products::fetch($productId));
        $this->requireRightFor('changepublicstate', (object) array('productId' => $productId));
        
        $pRec->isPublic = ($pRec->isPublic == 'yes') ? 'no' : 'yes';
        $title = ($pRec->isPublic == 'yes') ? 'стандартен' : 'нестандартен';
        cls::get('cat_Products')->save($pRec, 'isPublic');
        if($pRec->isPublic == 'yes'){
            static::delete("#productId = {$productId}");
        }

        return followRetUrl(array('cat_Products', 'single', $productId), " Артикулът вече е {$title}");
    }
    
    
    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderShared($data)
    {
        if ($data->hide == true) {
            
            return;
        }
        
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $tpl->append(tr('Папки, в които артикулът е достъпен'), 'title');
        
        if (isset($data->addUrl)) {
            $ht = ht::createLink('', $data->addUrl, false, 'ef_icon=img/16/add.png,title=Добавяне папки на контрагенти');
            $tpl->append($ht, 'title');
        }
        
        if ($data->masterData->rec->isPublic == 'yes') {
            if ($data->isProto === true) {
                $tpl->append("<div style='margin-bottom:5px'><b>" . tr('Създадените на база прототипа артикули, ще са споделени в следните папки') . ':</b></div>', 'content');
            } else {
                if(countR($data->recs)){
                    $tpl->append('<div><b>' . tr('Артикулът е стандартен и е достъпен само в изброените папки.') . '</b></div>', 'content');
                } else {
                    $tpl->append('<div><b>' . tr('Артикулът е стандартен и е достъпен във всички папки.') . '</b></div>', 'content');
                }
            }
        }
        
        if (is_array($data->rows)) {
            foreach ($data->rows as $row) {
                $dTpl = new core_ET("<div>[#folderId#] <span class='custom-rowtools'>[#tools#]</span></div>");
                $dTpl->placeObject($row);
                $dTpl->removeBlocks();
                
                $tpl->append($dTpl, 'content');
            }
        }
        
        if (isset($data->changeStateUrl)) {
            $bTitle = ($data->masterData->rec->isPublic == 'no') ? 'стандартен' : 'нестандартен';
            $title = "Направи артикула {$bTitle}";
            $warning = "Наистина ли искате артикула да стане {$bTitle}";
            
            $ht = ht::createBtn(str::mbUcfirst($bTitle), $data->changeStateUrl, $warning, null, "title={$title},ef_icon=img/16/arrow_refresh.png");
            $ht->prepend('<br>');
            $tpl->append($ht, 'content');
        }
        
        return $tpl;
    }
    
    
    /**
     * Кои са споделените папки към един артикул
     *
     * @param int $productId - ид на папка
     * @return array $res - масив със споделените артикули
     */
    public static function getSharedFolders($productId)
    {
        $res = array();
        $productRec = cat_Products::fetchRec($productId, 'isPublic,folderId');
        if($productRec->isPublic == 'yes') return $res;

        $query = self::getQuery();
        $query->where(array('#productId = [#1#]', $productRec->id));
        $query->orderBy('id', 'DESC');

        $res[$productRec->folderId] = $productRec->folderId;
        while ($rec = $query->fetch()) {
            $res[$rec->folderId] = $rec->folderId;
        }
        
        return $res;
    }
    
    
    /**
     * Кои са споделените артикули към дадена папка
     *
     * @param int $folderId - ид на папка
     *
     * @return array $res - масив със споделените артикули
     */
    public static function getSharedProducts($folderId)
    {
        $res = array();
        
        expect($folderId);
        $query = self::getQuery();
        $query->where("#folderId = {$folderId}");
        while ($rec = $query->fetch()) {
            $res[$rec->productId] = $rec->productId;
        }
        
        return $res;
    }
    
    
    /**
     * Лимитира заявката за достъпни артикули в папка
     *
     * @param core_Query $query
     * @param int        $folderId
     *
     * @return void
     */
    public static function limitQuery(&$query, $folderId)
    {
        expect($query->mvc instanceof cat_Products);

        // Кои са споделените артикули към папката
        $sharedProducts = cat_products_SharedInFolders::getSharedProducts($folderId);

        // Кои са стандартните артикули споделени към конкретни папки
        $query1 = self::getQuery();
        $query1->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $query1->where("#isPublic = 'yes'");
        $query1->show('productId');
        $publicProductsWithSharedFolders = arr::extractValuesFromArray($query1->fetchAll(), 'productId');
        $publicWhere = "#isPublic = 'yes'";
        if(countR($publicProductsWithSharedFolders)){
            $publicProductsWithSharedFoldersStr = implode(',', $publicProductsWithSharedFolders);
            $publicWhere .= " AND #id NOT IN ({$publicProductsWithSharedFoldersStr})";
        }

        // Ако има споделени папки
        if (countR($sharedProducts)) {

            // Артикула се показва ако: е нестандартен и е в същата папка, ако е споделен конкретно към тази папка или ако е стандартен и не е споделен към никоя папка
            $sharedProducts = implode(',', $sharedProducts);
            $query->where("(#isPublic = 'no' AND #folderId = {$folderId}) OR #id IN ({$sharedProducts}) OR ({$publicWhere})");
        } else {

            // Артикула се показва ако: е нестандартен и е в същата папка или ако е стандартен и не е споделен към никоя папка
            $query->where("(#isPublic = 'no' AND #folderId = {$folderId}) OR {$publicWhere}");
        }
    }
}
