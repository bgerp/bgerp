<?php


/**
 * Библиотека с публично мултимедийно съдържание
 * о Картинки
 * о HTML
 * o аудио и видео
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_Library extends embed_Manager
{
    /**
     * Свойство, което указва интерфейса на вътрешните обекти
     */
    public $driverInterface = 'cms_LibraryIntf';


    /**
     * Заглавие
     */
    public $title = 'Мултимедийна библиотека';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Обект';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_State2, plg_RowTools2, cms_Wrapper, plg_Sorting, plg_Search';
    
        
    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'cms,admin,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'cms,admin,ceo';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'cms,admin,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,cms';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,cms';
    
    
    /**
     * Полета за листовия изглед
     */
    public $listFields = 'driverClass,tag';
    
    
    /**
     * Поле за инструментите на реда
     */
    public $rowToolsField = '✍';
    
    
    /**
     * По кои полета ще се търси
     */
    public $searchFields = 'description,name';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име,mandatory');
        $this->FLD('tag', 'varchar(64)', 'caption=Таг,input=none');
        $this->FLD('description', 'text(rows=2)', 'caption=Описание');

        $this->FLD('accessClassId', 'class', 'caption=Източник->Модел,input=none,column=none,single=none');
        $this->FLD('accessObjectId', 'int(min=1)', 'caption=Източник->Обект,input=none,column=none,single=none,oldFieldName=sourceDialogId');

        $this->setDbUnique('name');
        $this->setDbIndex('accessClassId,accessObjectId');
    }


    /** Тагът съдържа каноничното име, записано от on_BeforeSave. */
    public static function getEmbedTag($rec)
    {
        return isset($rec->name, $rec->tag) ? '[elm=' . $rec->name . '-' . $rec->tag . ']' : '';
    }


    /** Разрешава таг без да зарежда данните на драйвера. */
    public static function findEmbedded($code)
    {
        $separator = strrpos($code ?? '', '-');
        if ($separator === false) return null;

        return self::fetch(array("#name = '[#1#]' AND #tag = '[#2#]'", substr($code, 0, $separator),
            substr($code, $separator + 1)), 'id,accessClassId,accessObjectId,driverClass');
    }


    /** Допуска до рамката само реален драйвер от регистъра на библиотеката. */
    private static function isRegisteredMediaDriver($rec)
    {
        $drivers = core_Classes::getOptionsByInterface('cms_LibraryIntf', 'name');
        $name = $drivers[$rec->driverClass ?? 0] ?? null;

        return $name && cls::load($name, true);
    }


    /** Допълва контекста, когато извикващият код е заредил само част от записа. */
    private static function accessRecord($rec)
    {
        if (is_numeric($rec) || (!empty($rec->id)
            && (!property_exists($rec, 'accessClassId') || !property_exists($rec, 'accessObjectId') || !isset($rec->driverClass)))) {
            return self::fetch(is_object($rec) ? $rec->id : $rec, 'id,accessClassId,accessObjectId,driverClass');
        }

        return $rec;
    }


    /** И непълна връзка с източник означава ограничен достъп. */
    public static function hasAccessContext($rec)
    {
        $rec = self::accessRecord($rec);

        return isset($rec->accessClassId) || isset($rec->accessObjectId);
    }


    /**
     * Всеки доставчик посочва поддържаните библиотечни драйвери и заявка
     * за източниците, които потребителят може да отвори.
     */
    private static function getAccessQueries($userId = null)
    {
        $queries = array();
        cls::get(__CLASS__)->invoke('GetLibraryAccessQueries', array(&$queries, $userId));

        return $queries;
    }


    public static function canViewAccessContext($rec, $userId = null)
    {
        $rec = self::accessRecord($rec);
        if (!$rec) return false;
        if (!isset($rec->accessClassId) && !isset($rec->accessObjectId)) return true;
        if (empty($rec->accessClassId) || empty($rec->accessObjectId)) return false;

        $queries = self::getAccessQueries($userId);
        $access = $queries[$rec->accessClassId] ?? null;
        if (!$access || !in_array((int) ($rec->driverClass ?? 0), $access['drivers'], true)) return false;

        $query = $access['query'];
        $source = $query->fetch((int) $rec->accessObjectId);

        return $source && $query->mvc->haveRightFor('single', $source, $userId);
    }


    /** Проверка на файлов идентификатор при избор или показване на файл от ограничен обект. */
    public static function getEmbeddedFileError($fh, $bucketName = null, $userId = null)
    {
        if (!is_string($fh) || !ctype_alnum($fh) || strlen($fh) > 64) {
            return 'Очаква се валиден файлов идентификатор.';
        }
        $file = fileman_Files::fetchByFh($fh);
        if (!$file || ($file->fileHnd ?? '') !== $fh) {
            return 'Файлът с този идентификатор не е намерен.';
        }
        if (empty($file->bucketId) || !fileman_Files::haveRightFor('single', $file, $userId)
            || !fileman_Files::haveRightFor('download', $file, $userId)) {
            return 'Нямате право да използвате този файл.';
        }
        if ($bucketName) {
            $bucket = fileman_Buckets::fetchByName($bucketName, '*');
            if (empty($bucket->id)) {
                return 'Липсва файловата кофа ' . $bucketName . '.';
            }
            $data = !empty($file->dataId) ? fileman_Data::fetch($file->dataId) : null;
            $path = $data ? fileman_Data::getGoodFilePath($data, false) : '';
            if (!is_file($path) || !is_readable($path)) {
                return 'Съдържанието на файла не е достъпно.';
            }
            $errors = array();
            if (!fileman_Buckets::isValid($errors, $bucket->id, $file->name, $path)) {
                return trim(strip_tags(tr(implode(' ', $errors))));
            }
        }

        return null;
    }


    /** Полетата за единичен файл пазят handle, а за няколко файла — keylist от id. */
    private static function getDriverFileErrors($rec, $userId = null, $checkBuckets = false)
    {
        $driver = $rec ? self::getDriver($rec) : null;
        if (!$driver) return array('driverClass' => 'Драйверът не е достъпен.');

        $errors = array();
        foreach (self::getDriverFields($driver, false, true) as $name => $field) {
            if (empty($rec->{$name})) continue;
            if ($field->type instanceof fileman_FileType) {
                $handles = array($rec->{$name});
            } elseif ($field->type instanceof fileman_type_Files) {
                $handles = array();
                foreach (keylist::toArray($rec->{$name}) as $fileId) {
                    $handles[] = fileman_Files::fetchField($fileId, 'fileHnd');
                }
            } elseif ($field->type instanceof type_Richtext) {
                $handles = array_keys(fileman_RichTextPlg::getFiles($rec->{$name}));
            } else {
                continue;
            }
            $bucket = $checkBuckets && !($field->type instanceof type_Richtext) ? ($field->type->params['bucket'] ?? null) : null;
            foreach ($handles as $handle) {
                $error = self::getEmbeddedFileError((string) $handle, $bucket, $userId);
                if ($error !== null) {
                    $errors[$name] = $error;
                    break;
                }
            }
        }

        return $errors;
    }


    public function getRequiredRoles_(&$action, $rec = null, $userId = null)
    {
        $rec = $rec ? $this->fetchRec($rec) : null;
        if (!empty($rec->id) && !isset($rec->driverClass)) {
            // embed_Manager изпраща AfterGetRequiredRoles към драйвера само при подаден driverClass.
            $stored = $this->fetch($rec->id);

            return $stored && !empty($stored->driverClass) ? $this->getRequiredRoles($action, $stored, $userId) : 'no_one';
        }
        $scoped = self::hasAccessContext($rec);
        if ($scoped && !self::canViewAccessContext($rec, $userId)) {

            return 'no_one';
        }
        if ($scoped && !empty($rec->id) && in_array($action, array('read', 'single', 'edit'), true)
            && self::getDriverFileErrors($this->fetch($rec->id), $userId)) {
            return 'no_one';
        }

        return parent::getRequiredRoles_($action, $rec, $userId);
    }


    private static function restrictToVisible($query)
    {
        $where = '(#accessClassId IS NULL AND #accessObjectId IS NULL)';
        foreach (self::getAccessQueries() as $classId => $access) {
            $visible = $access['query'];
            $visible->show('id');
            $drivers = implode(',', array_map('intval', $access['drivers']));
            if ($drivers !== '') {
                $where .= ' OR (#accessClassId = ' . (int) $classId . " AND #driverClass IN ({$drivers})"
                    . ' AND #accessObjectId IN (' . $visible->buildQuery() . '))';
            }
        }
        $query->where($where);
    }


    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        self::restrictToVisible($data->query);
    }


    protected static function on_BeforeFetchOptionsQuery($mvc, &$query)
    {
        self::restrictToVisible($query);
    }


    /** Проверяваме достъпа преди зареждането на полетата в единичния изглед и формата. */
    protected static function on_BeforeAction($mvc, &$res, $act)
    {
        $id = Request::get('id', 'int');
        if (!$id || $act === 'embedded') return;

        $rec = self::accessRecord($id);
        if (self::hasAccessContext($rec) && (!self::canViewAccessContext($rec) || self::getDriverFileErrors($mvc->fetch($id)))) {
            error('403 Недостъпен ресурс');
        }
    }


    protected static function on_AfterRenderWrapping($mvc, &$tpl, $content, $data = null)
    {
        if (self::hasAccessContext($data->rec ?? $data->form->rec ?? null)) {
            $tpl->push('Cache-Control: private, no-store', 'HTTP_HEADER');
        }
    }


    /** Данните и ресурсите се зареждат отделно от страницата, която може да е кеширана. */
    public function act_Embedded()
    {
        Mode::set('wrapper', 'page_Empty');
        $tpl = new ET('<div id="libraryMedia">[#media#]</div>');
        $tpl->push('Cache-Control: private, no-store', 'HTTP_HEADER');
        $tpl->append('html, body { margin: 0; padding: 0 } #libraryMedia { display: flow-root }', 'STYLES');
        jquery_Jquery::run($tpl, 'var media = document.getElementById("libraryMedia"), frame = window.frameElement;
            if (frame && media) {
                var fit = function () { frame.style.height = Math.ceil(media.getBoundingClientRect().height) + "px"; };
                fit();
                if (window.ResizeObserver) new ResizeObserver(fit).observe(media);
            }');

        $rec = self::findEmbedded((string) Request::get('elm', 'varchar(140)'));
        $driver = self::getDriverForViewer($rec, true);
        if (!$driver) {
            http_response_code(403);
            $tpl->replace(tr('Недостъпен обект'), 'media');

            return $tpl;
        }

        $tpl->replace($driver->render($rec, 700, false), 'media');

        return $tpl;
    }


    /** За рамката получаваме само метаданни; съдържанието се чете след проверката на източника. */
    private static function getDriverForViewer(&$rec, $inFrame = false)
    {
        if (!$rec) return null;
        $rec = self::fetchRec($rec);
        if (!$rec) return null;
        $scoped = self::hasAccessContext($rec);
        if ($scoped && !self::canViewAccessContext($rec)) return null;
        if ($scoped || $inFrame) {
            if (!empty($rec->id)) $rec = self::fetch($rec->id);
            if (!$rec || !self::isRegisteredMediaDriver($rec) || ($scoped && self::getDriverFileErrors($rec))) return null;
        }

        return self::getDriver($rec);
    }


    public static function render($rec, $maxWidth, $absolute = false)
    {
        $driver = self::getDriverForViewer($rec);

        return $driver ? $driver->render($rec, $maxWidth, $absolute) : '';
    }


    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        if (empty($form->rec->id)) {
            $access = Mode::get('embeddedMediaAccess');
            if (!empty($access->classId) && !empty($access->objectId)) {
                $form->setDefault('accessClassId', $access->classId);
                $form->setDefault('accessObjectId', $access->objectId);
            }
        } elseif (self::hasAccessContext($form->rec)) {
            $form->setReadOnly('name');
        }
    }


    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if (self::hasAccessContext($form->rec) && $form->isSubmitted()) {
            foreach (self::getDriverFileErrors($form->rec, null, true) as $field => $message) {
                $form->setError($field, $message);
            }
        }
    }


    public function prepareSingleFields_($data)
    {
        parent::prepareSingleFields_($data);
        if (self::hasAccessContext($data->rec)) {
            // Файловите връзки минават през проверката на драйвера в прегледа.
            $data->singleFields = array_intersect_key($data->singleFields, $this->fields);
        }

        return $data;
    }


    /**
     * След рендиране на единичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        $maxWidth = 900;
        if (isset($data->rec)) {
            $preview = self::render($data->rec, $maxWidth);
            $tpl->append("<div style='max-width:{$maxWidth}px;padding:0px;'>");
            $tpl->append($preview);
            $tpl->append("</div>");
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->tag = type_Varchar::escape(self::getEmbedTag($rec));
    }


    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec     Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields  Имена на полетата, които трябва да бъдат записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if(!isset($rec->tag)){
            $rec->tag = str::getRand('****'); 
        }

        $rec->name = str::canonize($rec->name);
    }

    
}
