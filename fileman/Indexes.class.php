<?php

/**
 * Информация за всички файлове във fileman_Files
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_Indexes extends core_Manager
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Информация за файловете";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'no_one';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'fileman_Wrapper, plg_RowTools, plg_Created';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('dataId', 'key(mvc=fileman_Data)', 'caption=Данни на файл,notNull');
        $this->FLD('type', 'varchar(32)', 'caption=Тип');
        $this->FLD('content', 'blob(1000000)', 'caption=Съдържание');
        
        $this->setDbUnique('dataId,type');
    }
    
    
    /**
     * Подготвя данните за информацията за файла
     */
    static function prepare_(&$data, $fh)
    {
        // Записи за текущия файл
        $data->fRec = fileman_Files::fetchByFh($fh);

        // Разширението на файла
        $ext = fileman_Files::getExt($data->fRec->name);
        
        // Вземаме уеб-драйверите за това файлово разширение
        $webdrvArr = self::getDriver($ext);

        // Обикаляме всички открити драйвери
        foreach($webdrvArr as $drv) {
            
            // Стартираме процеса за извличане на данни
            $drv->startProcessing($data->fRec);
            
            // Комбиниране всички открити табове
            $data->tabs = arr::combine($data->tabs, $drv->getTabs($data->fRec));
        }
    }
    
    
    /**
     * Рендира информацията за файла
     */
    static function render_($data)
    {
        // Масив с всички табове
        $tabsArr = $data->tabs;

        if(! count($data->tabs)) return FALSE;

        // Подреждаме масивити според order
        $tabsArr = static::orderTabs($tabsArr);

        // Текущия таб, ако не е зададен или ако няма такъв е първия
        $currentTab = $tabsArr[$data->currentTab] ? $data->currentTab : key($tabsArr);

        // Създаваме рендер на табове
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
        
        // Обикаляме всички табове
        foreach($tabsArr as $name => $rec) {
            
            // Ако е текущия таб таб
            if($name == $currentTab) {
                 $tabs->TAB($name, $rec->title,  array('currentTab' => $name, 'id' => $data->rec->fileHnd, '#' => 'fileDetail'));
                 
                 // Вземаме съдържанеито на тялот
                 $body = $rec->html;
            } else {
                
                // Създаваме таб
                $tabs->TAB($name, $rec->title, array('currentTab' => $name, 'id' => $data->rec->fileHnd, '#' => 'fileDetail'));
            }
        }
        
        // Рендираме съдържанието на таба
        $tpl = $tabs->renderHtml($body, $currentTab);
		
		$tpl->prepend("<br>");

        return $tpl;
    }
    

    /**
     * Връща масив от инстанции на уеб-драйвери за съответното разширение
     * Първоначалните уеб-драйвери на файловете се намират в директорията 'fileman_webdrv'
     */
    static function getDriver_($ext, $pathArr = array('fileman_webdrv'))
    {   
        // Разширението на файла
        $ext = strtolower($ext);

        // Масив с инстанциите на всички драйвери, които отговарят за съответното разширение
        $res = array();

        // Обхождаме масива с пътищата
        foreach($pathArr as $path) {
            
            // Към пътя добавяме разширението за да получим драйвера
            $className = $path . '_' . $ext;
            
            // Ако има такъв клас
            if(cls::load($className, TRUE)) {
                
                // Записваме инстанцията му
                $res[] = cls::get($className);
            }
        }

        // Ако не може да се намери нито един драйвер
        if(count($res) == 0) {
            
            // Създаваме инстанция на прародителя на драйверите
            $res[] = cls::get('fileman_webdrv_Generic');
        }

        // Връщаме масива
        return $res;
    }
    

    /**
     * Връща десериализараната информация за съответния файл и съответния тип
     * 
     * @param fileHandler $fileHnd - Манипулатор на файла
     * @param string $type - Типа на файла
     * 
     * @return mixed $content - Десериализирания стринг
     */
    static function getInfoContentByFh($fileHnd, $type)
    {
        // Записите за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        // Вземаме разширението на файла, от името му
        $ext = fileman_Files::getExt($fRec->name);
        
        // Масив с всички драйвери
        $drivers = static::getDriver($ext);
        
        // Обхождаме намерените драйверо
        foreach ($drivers as $driver) {
            
            // Проверяваме дали имат съответния метод
            if (method_exists($driver, 'getInfoContentByFh')) {
                
                // Вземамем съдържанието
                $content = $driver::getInfoContentByFh($fileHnd, $type);
                
                // Ако открием съдържание, връщаме него
                if ($content !== FALSE) return $content;
            }
        }
        
        // Вземаме текстовата част за съответното $dataId
        $rec = fileman_Indexes::fetch("#dataId = '{$fRec->dataId}' AND #type = '{$type}'");

        // Ако няма такъв запис
        if (!$rec) return FALSE;
        
        return static::decodeContent($rec->content);
    }
    
    
	/**
     * Декодираме подадения текст
     * 
     * @param string $content - Текста, който да декодираме
     * 
     * @return string $content - Променения текст
     */
    static function decodeContent($content)
    {
        // Вземаме конфигурацията
        $conf = core_Packs::getConfig('fileman');
        
        // Променяме мемори лимита
        ini_set("memory_limit", $conf->FILEMAN_DRIVER_MAX_ALLOWED_MEMORY_CONTENT);

        // Декодваме
        $content = base64_decode($content);
        
        // Декомпресираме
        $content = gzuncompress($content);
        
        // Десериализираме съдържанието
        $content = unserialize($content);
        
        return $content;
    }
    
    
    /**
     * Подреждане на табовете в зависимост от order
     */
    static function orderTabs($tabsArr)
    {
        // Подреждаме масива
        core_Array::orderA($tabsArr);

        return $tabsArr;
    }
    

    /**
     * Проверява дали файла е заключен или записан в БД
     * 
     * @param object $fRec - Данните за файла
     * @param array $params - Масив с допълнителни променливи
     * 
     * @return boolean - Връща TRUE ако файла е заключен или има запис в БД
     * 
     * @access protected
     */
    static function isProcessStarted($params, $trim=FALSE)
    {
        // Ако няма lockId
        if (!$params['lockId']) $params['lockId']=fileman_webdrv_Generic::getLockId($params['type'], $params['dataId']);

        // Ако процеса е заключен
        if (core_Locks::isLocked($params['lockId'])) return TRUE;
        
        // Ако има такъв запис
        if ($rec = fileman_Indexes::fetch("#dataId = '{$params['dataId']}' AND #type = '{$params['type']}'")) {
            
            $conf = core_Packs::getConfig('fileman');
            
            // Времето след което ще се изтрият
            $time = time() - ($conf->FILEMAN_WEBDRV_ERROR_CLEAN * 60);
            
            // Съдържанието
            $content = fileman_Indexes::decodeContent($rec->content);
            
            // Ако в индекса е записана грешка
            if (($content->errorProc) && (dt::mysql2timestamp($rec->createdOn) < $time)) {
                
                // Изтрива съответния запис
                fileman_Indexes::delete($rec->id); 
                
                // Връщаме FALSE, за да укажем, че няма запис
                return FALSE;   
            } else {
                
                // Ако е задедено да се провери съдържанието
                if (($trim) && (!trim($content))) return FALSE;
                
                return TRUE;
            } 
        }

        return FALSE;
    }

    
    /**
     * Подготвяме content частта за по добър запис
     * 
     * @param string $text - Текста, който да променяме
     * 
     * @return string $text - Променения текст
     */
    static function prepareContent($text)
    {
        // Вземаме конфигурацията
        $conf = core_Packs::getConfig('fileman');
        
        // Променяме мемори лимита
        ini_set("memory_limit", $conf->FILEMAN_DRIVER_MAX_ALLOWED_MEMORY_CONTENT);

        // Сериализираме
        $text = serialize($text);
        
        // Компресираме
        $text = gzcompress($text);
        
        // Енкодваме
        $text = base64_encode($text);    
                
        return $text;
    }
    
    
    /**
     * Записваме подадени параметри в модела
     * 
     * @param array $params - Подадените параметри
     * $params['dataId'] - key fileman_Data
     * $params['type'] - Типа на файла
     * $params['createdBy'] - Създадено от
     * $params['content'] - Съдържанието
     * 
     */
    static function saveContent($params)
    {
        $rec = new stdClass();
        $rec->dataId = $params['dataId'];
        $rec->type = $params['type'];
        $rec->createdBy = $params['createdBy'];
        $rec->content = static::prepareContent($params['content']);
        
        $saveId = static::save($rec);
        
        return $saveId;
    }
    
    
	/**
     * Проверява дали има грешка. Ако има грешка, записваме грешката в БД.
     * 
     * @param string $file - Пътя до файла, който ще се проверява
     * @param string $type - Типа, за който се проверява грешката
     * @param array $params - Други допълнителни параметри
     * 
     * @return boolean - Ако не открие грешка, връща FALSE
     */
    static function haveErrors($file, $type, $params)
    {
        // Ако е файл в директория
        if (strstr($file, '/')) {
            
            // Ако е валиден файл
            $isValid = is_file($file);
        } else {
            
            // Ако е манупулатор на файл
            $isValid = fileman_Files::fetchField("#fileHnd='{$file}'");
        }
        
        // Ако има файл
        if ($isValid) return FALSE;

        // Ако няма файл, записваме грешката
        $error = new stdClass();
        $error->errorProc = tr("Възникна грешка при обработка") . '...';
        
        // Текстовата част
        $params['content'] = $error;

        // Обновяваме данните за запис във fileman_Indexes
        $savedId = fileman_Indexes::saveContent($params);

        // Записваме грешката в лога
        static::createErrorLog($params['dataId'], $params['type']);
        
        return TRUE;
    }
    
    
	/**
     * Записва в лога ако възникне греша при асинхронното обработване на даден файл
     * 
     * @param fileman_Data $dataId - id' то на данните на файла
     * @param string $type - Типа на файла
     */
    static function createErrorLog($dataId, $type)
    {
        core_Logs::log(tr("|Възникна грешка при обработката на файла с данни|* {$dataId} |в тип|* {$type}"));
    }
    
    
    /**
     * Изтрива индекса за съответните данни
     * 
     * @param fileman_Data $dataId - id' то на данните
     */
    static function deleteIndexesForData($dataId)
    {
        // Изтриваме всички записи със съответното dataId
        fileman_Indexes::delete(array("#dataId = [#1#]", $dataId));
    }
 }