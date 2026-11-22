<?php


/**
 * Клас 'cat_EanCodes' - Заетите наши EAN номера, които не стоят в опаковка на артикул
 *
 * Стари номера от историята, запазени номера и оттеглени номера (махнати от опаковка).
 * Номер оттук не се дава повторно. Щом номерът влезе в опаковка, редът изчезва -
 * активните номера се пазят само в опаковките.
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_EanCodes extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Заети EAN номера извън артикулите';


    /**
     * Единично заглавие
     */
    public $singleTitle = 'Зает EAN номер';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'barcode_SearchIntf';


    /**
     * Мастър ключ
     */
    public $masterKey = 'prefixId';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_RowTools2, plg_Created, plg_Modified, plg_Rejected, plg_Sorting, plg_Search';


    /**
     * Полета в списъчния изглед
     */
    public $listFields = 'code,status,productId,packagingId,note,prefixId,createdOn,createdBy';


    /**
     * Полета за търсене
     */
    public $searchFields = 'code,note';


    /**
     * Шаблон за заглавието на записа
     */
    public $recTitleTpl = '[#code#]';


    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;


    /**
     * Кой може да вижда списъка
     */
    public $canList = 'ceo,packEdit,sales,purchase';


    /**
     * Кой може да добавя
     */
    public $canAdd = 'ceo,packEdit';


    /**
     * Кой може да редактира
     */
    public $canEdit = 'ceo,packEdit';


    /**
     * Кой може да оттегля (освобождава номера)
     */
    public $canReject = 'ceo,packEdit';


    /**
     * Кой може да възстановява
     */
    public $canRestore = 'ceo,packEdit';


    /**
     * Кой може да изтрива
     */
    public $canDelete = 'no_one';


    /**
     * Кой може да внася стари номера
     */
    public $canImport = 'ceo,packEdit';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('prefixId', 'key(mvc=cat_EanPrefixes,select=prefix)', 'caption=Диапазон,input=hidden,silent,mandatory');
        $this->FLD('code', 'gs1_TypeEan', 'caption=EAN,mandatory');
        $this->FLD('status', 'enum(reserved=Запазен,legacy=Стар,retired=Оттеглен)', 'caption=Вид,mandatory');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax)', 'caption=Артикул,class=w100');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Опаковка/Мярка');
        $this->FLD('note', 'text(rows=2)', 'caption=Бележка');

        $this->setDbUnique('code');
    }


    /**
     * Преди показване на формата
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec = $data->form->rec;
        if (isset($rec->id)) {
            $data->form->setReadOnly('code');
        } else {
            $data->form->setDefault('status', 'reserved');
            if (isset($rec->prefixId) && ($pRec = cat_EanPrefixes::fetch($rec->prefixId))) {
                $data->form->setDefault('code', cat_EanPrefixes::getNextCode($pRec));
            }
        }
    }


    /**
     * Проверка на въведения номер
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if (!$form->isSubmitted() || $form->gotErrors('code')) {

            return;
        }

        $rec = $form->rec;
        $pRec = cat_EanPrefixes::getByCode($rec->code);
        if (!$pRec || $pRec->id != $rec->prefixId) {
            $form->setError('code', 'Номерът не е от този диапазон');

            return;
        }

        if ($packRec = cat_products_Packagings::fetch(array("#eanCode = '[#1#]'", $rec->code), 'productId')) {
            $form->setError('code', 'Номерът вече е на|* ' . cat_Products::getHyperlink($packRec->productId, true));
        } elseif ($mvc->fetchField(array("#code = '[#1#]' AND #id != '[#2#]'", $rec->code, $rec->id ?? 0), 'id')) {
            $form->setError('code', 'Номерът вече е записан');
        }
    }


    /**
     * Права за действията
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Номерата се добавят само в конкретен диапазон
        if ($action == 'add' && isset($rec) && empty($rec->prefixId)) {
            $requiredRoles = 'no_one';
        }

        if ($action == 'edit' && isset($rec) && ($rec->state ?? null) == 'rejected') {
            $requiredRoles = 'no_one';
        }

        // Номер, който е бил в обращение, се освобождава само от ръководството
        if ($action == 'reject' && isset($rec) && ($rec->status ?? null) != 'reserved') {
            $requiredRoles = 'ceo';
        }

        if ($action == 'import' && isset($rec->prefixId)) {
            $state = cat_EanPrefixes::fetchField($rec->prefixId, 'state');
            if (empty($state)) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Добавя бутона за внасяне на стари номера
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        $masterId = $data->masterId ?? null;
        $urlParams = $masterId ? array('prefixId' => $masterId) : array();
        if ($mvc->haveRightFor('import', (object) $urlParams) && countR(cat_EanPrefixes::getAll())) {
            $data->toolbar->addBtn('Импорт', array($mvc, 'import', 'ret_url' => true) + $urlParams, 'ef_icon=img/16/import.png,title=Внасяне на стари номера от текст или нишка');
        }
    }


    /**
     * Филтър на списъка
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('code', 'DESC');
        if (!empty($data->masterMvc)) {

            return;
        }

        $data->listFilter->FLD('statusFilter', 'enum(all=Всички,reserved=Запазени,legacy=Стари,retired=Оттеглени,noProduct=Без артикул)', 'caption=Вид');
        $data->listFilter->showFields = 'search,statusFilter';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input('search,statusFilter');

        $filter = $data->listFilter->rec->statusFilter ?? 'all';
        if ($filter == 'noProduct') {
            $data->query->where('#productId IS NULL');
        } elseif (in_array($filter, array('reserved', 'legacy', 'retired'))) {
            $data->query->where(array("#status = '[#1#]'", $filter));
        }
    }


    /**
     * След преобразуване към вербални стойности
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($rec->productId)) {
            $row->productId = cat_Products::getHyperlink($rec->productId, true);
        }
    }


    /**
     * Заглавие над детайла в диапазона
     */
    protected static function on_AfterRenderDetail($mvc, &$tpl, $data)
    {
        $tpl->prepend('<h3>' . tr('Заети извън артикулите') . '</h3>');
    }


    /**
     * Записва номер, който вече не стои в опаковка - остава зает завинаги
     *
     * @param string   $code
     * @param int|null $productId
     * @param int|null $packagingId
     * @param string   $note
     *
     * @return void
     */
    public static function retire($code, $productId, $packagingId, $note)
    {
        $pRec = cat_EanPrefixes::getByCode($code);
        if (!$pRec || cat_products_Packagings::fetchField(array("#eanCode = '[#1#]'", $code), 'id')) {

            return;
        }

        // Уникалният код може да е зает и от освободен (оттеглен) ред - той се преизползва
        $rec = self::fetch(array("#code = '[#1#]'", $code));
        if (!is_object($rec)) {
            $rec = (object) array('code' => $code);
        }
        $rec->prefixId = $pRec->id;
        $rec->status = 'retired';
        $rec->productId = $productId;
        $rec->packagingId = $packagingId;
        $rec->note = $note;
        $rec->state = 'active';

        self::save($rec);
        self::logWrite('Оттеглен от опаковка', $rec->id);
    }


    /**
     * Номерът влезе в опаковка - вече не е нужен ред тук
     *
     * @param string $code
     *
     * @return void
     */
    public static function releaseToPackaging($code)
    {
        if (!cat_EanPrefixes::getByCode($code)) {

            return;
        }

        self::delete(array("#code = '[#1#]'", $code));
    }


    /**
     * Внасяне на стари номера от текст или от нишки с документи
     */
    public function act_Import()
    {
        $prefixId = Request::get('prefixId', 'key(mvc=cat_EanPrefixes)');
        $this->requireRightFor('import', (object) array('prefixId' => $prefixId));

        $form = cls::get('core_Form');
        $form->title = 'Внасяне на стари EAN номера';
        $form->info = tr('Записват се всички 13-цифрени номера от нашите диапазони, които ги няма в опаковките на артикулите. Редът около номера става бележка, а [Art...] в него - артикул.');
        $form->FLD('threads', 'varchar', 'caption=Нишки (хендъли на документи),placeholder=#Not123; #C456');
        $form->FLD('text', 'text(rows=15)', 'caption=Текст');
        $form->input();

        if ($form->isSubmitted()) {
            $threads = trim($form->rec->threads ?? '');
            $text = trim($form->rec->text ?? '');
            if ($threads === '' && $text === '') {
                $form->setError('threads,text', 'Посочете нишка или текст');
            }

            $lines = array();
            if (!$form->gotErrors() && $threads !== '') {
                $lines = $this->getThreadLines($threads, $form);
            }

            if (!$form->gotErrors()) {
                if ($text !== '') {
                    foreach (preg_split('/\R/u', $text) as $line) {
                        $lines[] = $line;
                    }
                }

                $res = $this->importLines($lines);
                $Int = core_Type::getByName('int');
                $msg = tr('Добавени') . ': ' . $Int->toVerbal($res['added']) . ', ' .
                    tr('вече в артикулите') . ': ' . $Int->toVerbal($res['inProducts']) . ', ' .
                    tr('вече записани') . ': ' . $Int->toVerbal($res['known']);
                if ($res['conflicts']) {
                    $msg .= ', ' . tr('с повече от един артикул') . ': ' . $Int->toVerbal($res['conflicts']);
                }
                $this->logWrite('Импорт на стари номера');

                followRetUrl(array($this, 'list'), '|*' . $msg);
            }
        }

        $form->toolbar->addSbBtn('Импорт', 'save', 'ef_icon = img/16/import.png');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        return $this->renderWrapping($form->renderHtml());
    }


    /**
     * Редовете с текста на всички документи от посочените нишки
     *
     * @param string    $threads - хендъли, разделени с интервал, запетая или точка и запетая
     * @param core_Form $form
     *
     * @return array
     */
    protected function getThreadLines($threads, $form)
    {
        core_App::setTimeLimit(600);

        $lines = array();
        foreach (preg_split('/[\s,;]+/', $threads, -1, PREG_SPLIT_NO_EMPTY) as $handle) {
            $Doc = doc_Containers::getDocumentByHandle(ltrim($handle, '#'));
            if (!$Doc || !$Doc->haveRightFor('single')) {
                $form->setError('threads', "Няма достъп до документ|* {$handle}");

                return array();
            }

            $cQuery = doc_Containers::getQuery();
            $cQuery->where(array("#threadId = '[#1#]' AND #state != 'rejected'", $Doc->fetchField('threadId')));
            $cQuery->orderBy('id', 'ASC');
            $cQuery->show('id');
            while ($cRec = $cQuery->fetch()) {
                try {
                    $D = doc_Containers::getDocument($cRec->id);
                    $text = $this->getDocumentText($D);
                } catch (Throwable $e) {
                    self::logWarning('Документът не се прочете при импорт на номера: ' . $e->getMessage(), null);
                    continue;
                }

                // Един номер в документа - целият текст е бележка (името често е на следващия ред)
                $docHandle = '#' . $D->getHandle();
                if (preg_match_all('/(?<![0-9])[0-9]{13}(?![0-9])/', $text) == 1) {
                    $lines[] = trim(preg_replace('/\s+/u', ' ', $text)) . " [{$docHandle}]";
                    continue;
                }

                foreach (preg_split('/\R/u', $text) as $line) {
                    if (preg_match('/[0-9]{13}/', $line)) {
                        $lines[] = trim($line) . " [{$docHandle}]";
                    }
                }
            }
        }

        return $lines;
    }


    /**
     * Текстовите полета на документа, без да се изобразява
     *
     * @param core_ObjectReference $D
     *
     * @return string
     */
    protected function getDocumentText($D)
    {
        $rec = $D->rec();
        $text = '';
        foreach ($D->getInstance()->selectFields("#kind == 'FLD'") as $name => $fld) {
            if ($name == 'searchKeywords' || !($fld->type instanceof type_Richtext || $fld->type instanceof type_Text)) {
                continue;
            }

            $val = $rec->{$name} ?? null;
            if (is_string($val) && preg_match('/[0-9]{13}/', $val)) {
                $val = str_ireplace(array('<br>', '<br/>', '<br />', '</p>', '</tr>', '</div>'), "\n", $val);
                $val = preg_replace('/\[\/?[a-z]+(=[^\]]*)?\]/i', '', $val);
                $text .="\n" . html_entity_decode(strip_tags($val), ENT_QUOTES, 'UTF-8');
            }
        }

        return $text;
    }


    /**
     * Записва номерата от нашите диапазони, които ги няма никъде
     *
     * @param array $lines
     *
     * @return array - броячи added, inProducts, known, conflicts
     */
    public function importLines($lines)
    {
        $Ean = cls::get('gs1_TypeEan');
        $found = array();
        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line));
            if (!preg_match_all('/(?<![0-9])([0-9]{13})(?![0-9])/', $line, $m)) {
                continue;
            }

            $productId = null;
            if (preg_match_all('/\bArt([0-9]+)\b/i', $line, $a) && countR(array_unique($a[1])) == 1) {
                $productId = cat_Products::fetchField((int) $a[1][0], 'id') ?: null;
            }

            foreach (array_unique($m[1]) as $code) {
                if (!$Ean->isValidEan($code) || !cat_EanPrefixes::getByCode($code)) {
                    continue;
                }

                if (!isset($found[$code])) {
                    $found[$code] = (object) array('productIds' => array(), 'notes' => array());
                }
                if ($productId) {
                    $found[$code]->productIds[$productId] = $productId;
                }
                $found[$code]->notes[] = $line;
            }
        }

        $res = array('added' => 0, 'inProducts' => 0, 'known' => 0, 'conflicts' => 0);
        foreach ($found as $code => $obj) {
            if (cat_products_Packagings::fetchField(array("#eanCode = '[#1#]'", $code), 'id')) {
                $res['inProducts']++;
                continue;
            }

            $rec = self::fetch(array("#code = '[#1#]'", $code));
            if (is_object($rec) && $rec->state != 'rejected') {
                $res['known']++;
                continue;
            }

            // Освободен номер, който се среща в историята, пак става зает
            if (!is_object($rec)) {
                $rec = (object) array('code' => $code);
            }
            $rec->prefixId = cat_EanPrefixes::getByCode($code)->id;
            $rec->status = 'legacy';
            $rec->productId = (countR($obj->productIds) == 1) ? reset($obj->productIds) : null;
            $rec->packagingId = null;
            $rec->note = str::limitLen(implode("\n", array_unique($obj->notes)), 2000);
            $rec->state = 'active';
            if (countR($obj->productIds) > 1) {
                $res['conflicts']++;
            }

            self::save($rec);
            $res['added']++;
        }

        return $res;
    }


    /**
     * Търсене по баркод
     *
     * @param string $str
     *
     * @return array
     */
    public function searchByCode($str)
    {
        $rec = self::fetch(array("#code = '[#1#]' AND #state != 'rejected'", trim($str ?? '')));
        if (!is_object($rec) || !$this->haveRightFor('list')) {

            return array();
        }

        $row = $this->recToVerbal($rec, 'status,productId,note');
        $comment = (isset($rec->productId) ? $row->productId . '<br>' : '') . $row->note;

        return array((object) array(
            'title' => tr('Зает EAN номер') . ': ' . $row->status,
            'url' => array(),
            'priority' => 0,
            'comment' => $comment,
        ));
    }


    /**
     * Номерата от диапазона, които стоят в опаковки на артикули
     */
    public function prepareInProducts($data)
    {
        $data->recs = $data->rows = array();
        $prefix = $data->masterData->rec->prefix;

        $query = cat_products_Packagings::getQuery();
        $query->where(array("#eanCode LIKE '[#1#]%' AND CHAR_LENGTH(#eanCode) = 13", $prefix));
        $query->orderBy('eanCode', 'DESC');

        $data->Pager = cls::get('core_Pager', array('itemsPerPage' => 20));
        $data->Pager->setPageVar('cat_EanPrefixes', $data->masterId, 'InProducts');
        $data->Pager->setLimit($query);

        $Packagings = cls::get('cat_products_Packagings');
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $row = $Packagings->recToVerbal($rec, 'eanCode,packagingId,quantity,state');
            $row->productId = cat_Products::getHyperlink($rec->productId, true);
            $data->rows[$rec->id] = $row;
        }
    }


    /**
     * Таблицата с номерата в артикулите
     */
    public function renderInProducts($data)
    {
        $tpl = new ET('<h3>[#title#]</h3>[#pagerTop#][#table#][#pagerBottom#]');
        $tpl->replace(tr('В артикулите'), 'title');

        $table = cls::get('core_TableView', array('mvc' => cls::get('cat_products_Packagings')));
        $tpl->replace($table->get($data->rows, 'eanCode=EAN,productId=Артикул,packagingId=Опаковка/Мярка,quantity=К-во'), 'table');
        $pagerHtml = $data->Pager->getHtml();
        $tpl->append($pagerHtml, 'pagerTop');
        $tpl->append($pagerHtml, 'pagerBottom');

        return $tpl;
    }
}
