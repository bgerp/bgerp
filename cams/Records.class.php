<?php



/**
 * Път до директория, където ще се съхраняват записите от камерите
 */
defIfNot('CAMS_VIDEOS_PATH', EF_UPLOADS_PATH . "/cams/videos");


/**
 * Път до директория, където ще се записват jpeg превютата
 */
defIfNot('CAMS_IMAGES_PATH', EF_UPLOADS_PATH . "/cams/images");


/**
 * Директория за mp4 файловете
 */
defIfNot('SBF_CAMS_MP4_DIR', "_cams/mp4");


/**
 * Път до директория, където ще се записват конвертираните MP4 файлове
 */
defIfNot('SBF_CAMS_MP4_PATH', EF_SBF_PATH . '/' . SBF_CAMS_MP4_DIR);


/**
 * Колко е продължителността на конвертирането на един клип в секунди
 */
defIfNot('CAMS_CLIP_TO_FLV_DURATION', round(cams_CLIP_DURATION / 30));


/**
 * Клас 'cams_Records' -
 *
 *
 * @category  bgerp
 * @package   cams
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class cams_Records extends core_Master
{
    
    
    /**
     * Зареждане на използваните мениджъри
     */
    var $loadList = 'plg_RowTools, cams_Wrapper, Cameras=cams_Cameras';
    
    
    /**
     * Заглавие
     */
    var $title = 'Записи от камери';
    
    
    /**
     * Полетата, които ще се ползват
     */
    var $listFields = 'id, thumb, cameraId, startTime, duration, playedOn, marked';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'ceo,cams, admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,cams';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,cams';
    
    
    /**
     * Права за четене
     */
    var $canRead = 'ceo,cams, admin';
    
    /**
     * Права за добавяне
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Права за маркиране
     */
    var $canMark = 'ceo,cams,admin';


    /**
     * Права за размаркиране
     */
    var $canUnmark = 'ceo,cams,admin';
    
    // Ръчно не могат да се добавят записи
    //var $canEdit = 'no_one';
    //var $canAdd = 'no_one';
    
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('cameraId', 'key(mvc=cams_Cameras,select=title)', 'caption=Камера, mandatory');
        $this->FLD('startTime', 'datetime', 'caption=Начало');
        $this->FLD('duration', 'int', 'caption=Продължителност');
        $this->FLD('playedOn', 'datetime', 'caption=Гледан на');
        $this->FLD('marked', 'enum(no,yes)', 'caption=Маркиран');
        $this->FLD('params', 'text', 'caption=Параметри, input=none');
        $this->FLD('isAnalyzed', 'enum(no,yes)', 'caption=Анализиране?, input=none');

        $this->setDbIndex('cameraId');
    }
    
    
    /**
     * Връща пътища до медийните файлове за $id-тия запис
     */
    function getFilePaths($startTime, $cameraId)
    {
        $baseName = dt::mysql2verbal($startTime, "d-m-y_H-i") . '-' . $cameraId;
        
        $fp = new stdClass();
        // Видео MP4 файл - суров запис от камерата с добро качество
        $fp->videoFile = CAMS_VIDEOS_PATH . "/{$baseName}.mp4";
        
        // Картинка към началото на записа
        $fp->imageFile = CAMS_IMAGES_PATH . "/{$baseName}.jpg";
        
        // Умалена картинка към началото на записа
        $fp->thumbFile = CAMS_IMAGES_PATH . "/{$baseName}_t.jpg";
        
        // Flash Video File за записа
        $hash = substr(md5(EF_SALT . $baseName), 0, 6);
        
        $fp->mp4File = SBF_CAMS_MP4_PATH . "/{$baseName}_{$hash}.mp4";
        
        // Ако директорията за конвертираните файловете не съществува,
        // записва в лога 
        if (!is_dir(SBF_CAMS_FLV_PATH) || !is_dir(SBF_CAMS_MP4_PATH)) {
            $this->logAlert("Директорията за конвертираните файлове не съществува - преинсталирайте пакета cams.");
        }
        
        $fp->mp4Url = sbf(SBF_CAMS_MP4_DIR . "/{$baseName}_{$hash}.mp4", '');
        
        return $fp;
    }
    
    
    /**
     * Връща началната картинка за посочения запис
     * Ако параметъра от заявката thumb е сетнат - връща умалена картинка
     */
    function act_StartJpg()
    {
        requireRole('cams, admin');
        
        $id = Request::get('id', 'int');
        
        expect($rec = $this->fetch($id));
        
        // Подготвяме пътищата до различните медийни файлове
        $fp = $this->getFilePaths($rec->startTime, $rec->cameraId);
        
        if(Request::get('thumb')) {
            $img = imagecreatefromjpeg($fp->thumbFile);
        } else {
            $img = imagecreatefromjpeg($fp->imageFile);
        }
        
        // Кеширане в браузъра в рамките на 1 ч.
        $cacheTime = 60 * 60;
        
        session_cache_limiter('none');
        
        // Then send Cache-Control: max-age=number_of_seconds and
        // optionally equivalent Expires: header.
        header('Cache-control: max-age=' . $cacheTime);
        header('Expires: ' . gmdate(DATE_RFC1123, time() + $cacheTime));
        
        // To get best cacheability, send Last-Modified header and reply with 
        // status 304 and empty body if browser sends If-Modified-Since header.
        header('Last-Modified: ' . gmdate(DATE_RFC1123, filemtime($fp->imageFile)));
        
        // This is cheating a bit (doesn't verify the date), but is valid as 
        // long as you don't mind browsers keeping cached file forever:
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            header('HTTP/1.1 304 Not Modified');
            die();
        }
        
        // Set the content type header - in this case image/jpeg
        header('Content-type: image/jpeg');
        
        // Output the image
        imagejpeg($img);
        
        die();
    }
    
    
    /**
     * Плейва посоченото видео (id на записа идва от GET)
     */
    function act_Single()
    {
        requireRole('cams, admin');
        
    	$conf = core_Packs::getConfig('cams');
    	
        $id = Request::get('id', 'int');
        
        expect($rec = $this->fetch($id));
        
        // Подготвяме пътищата до различните медийни файлове
        $fp = $this->getFilePaths($rec->startTime, $rec->cameraId);
        
        $data = new stdClass();
        // Настройваме параметрите на плеъра
        $data->url = $fp->mp4Url;
        $data->image = toUrl(array($this, 'StartJpg', $id));
        $data->toolbar = cls::get('core_Toolbar');
        
        // Ако имаме предишен запис - поставяме бутон към него
        if($idPrev = $this->getPrevRec($id)) {
            $data->toolbar->addBtn('« Предишен', array($this, 'Single', $idPrev));
        }
        
        // Ако имаме следващ запис - поставяме бутон към него
        if($idNext = $this->getNextRec($id)) {
            $data->toolbar->addBtn('Следващ »', array($this, 'Single', $idNext));
        }
        
        // Ако записа е маркиран, поставяме бутон за от маркиране и обратното
        if($rec->marked == 'yes') {
            $data->toolbar->addBtn('От маркиране', array($this, 'Unmark', $id));
        } else {
            $data->toolbar->addBtn('Маркиране', array($this, 'Mark', $id));
        }

        // Подготвяме параметрите на записа        
        $params = json_decode($rec->params);
        
        $data->width = $params->width;
        $data->height = $params->height;
        
        if(!file_exists($fp->mp4File) && !self::isRecordConverting($fp->mp4File)) {
            // Стартираме конвертирането на видеото към mp4, ако това все още не е направено
            $this->convertToMp4($fp->videoFile, $fp->mp4File);
            $this->logInfo('Конвертиране към MP4', $rec->id);
        }
        
        $row = $this->recToVerbal($rec);
        
        // Получаваме класа на надписа
        $data->captionClass = $this->getCaptionClassByRec($rec);
        
        $camera = cams_Cameras::getTitleById($rec->cameraId);
        
        $data->caption = "{$camera}: $row->startTime";
        
        // Записваме, кога клипът е пуснат за разглеждане първи път
        if(empty($rec->playedOn)) {
            $rec->playedOn = dt::verbal2mysql();
            $this->save($rec, 'playedOn');
        } else {
            $data->caption .= ", видян на $row->playedOn";
        }
        
        if($rec->marked == 'yes') {
            $data->caption .= ", маркиран";
        }
        
        $data->duration = $conf->CAMS_CLIP_DURATION;
        
        // Рендираме плеъра
        $tpl = $this->renderSingle($data);
        
        $this->logRead("Разглеждане", $rec->id);
        
        return $this->renderWrapping($tpl);
    }
    
    
    /**
     * Рендиране на mp4 плеъра
     */
    function renderSingle_($data, $tpl = NULL)
    {

        $tpl = new ET ('
            <div id=toolbar style="margin-bottom:10px;">[#toolbar#]</div>
            <div class="video-rec" style="display:table">
                <div class="[#captionClass#]" style="padding:5px;font-size:0.95em;">[#caption#]</div>
                [#playerTpl#]
                <div>[#convertProgress#]</div>
            </div>
        ');
    
        $data->toolbar = $data->toolbar->renderHtml();

        if ($this->isRecordConverting(basename($data->url))) {
            $data->playerTpl = "<img src={$data->image} width={$data->width} height={$data->height} style='cursor: wait;'>";
            $data->convertProgress = "Конвертиране ...";
            $tpl->appendOnce("\n" . '<meta http-equiv="refresh" content="3">', "HEAD");
        } else {
            $data->playerTpl = mejs_Adapter::createVideo(   $data->url,
                                                            array(  'poster' => $data->image,
                                                                'width' => $data->width,
                                                                'height' => $data->height),
                                                            'url');
        }
        
        // Поставяме стойностите на плейсхолдърите
        $tpl->placeObject($data);
    
        return $tpl;
    }
    
    /**
     *  Дали записа е в процес на транскодиране
     *  
     *  @return boolean
     */
    private static function isRecordConverting($mp4File) {
        // Ако файлa е заключен за транскодиране => има процес
        $mp4File = basename($mp4File); 
        if (core_Locks::isLocked($mp4File)) {

          return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Конвертира указания файл (записан от този драйвер) към mp4 за html5 video
     */
    function convertToMp4($mp4Path, $mp4File)
    {
        
        core_Locks::get(basename($mp4File), 300, 0, FALSE);
        
        $cmdTmpl = "ffmpeg -hide_banner -loglevel panic -i [#INPUTF#] -preset fast -crf 35 -vcodec h264 -acodec aac -strict -2 [#OUTPUTF#]";
        $Script = cls::get('fconv_Script');
        
        // Инстанция на класа
        $me = get_called_class();
        
        // Параметри необходими за конвертирането
        $params = array(
            'mp4File' => $mp4File,
            'callBack' => $me . '::afterConvert',
            'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'errFilePath' => 'err_videoconv.txt'
        );
        $Script->setFile('INPUTF', $mp4Path);
        $Script->setFile('OUTPUTF', $mp4File);
        $Script->lineExec($cmdTmpl, array('errFilePath' => $params['errFilePath']));
        
        $Script->callBack($params['callBack']);
        
        $Script->params = $params;
        
        // Стартираме скрипта Aсинхронно
        if ($Script->run($params['asynch']) === FALSE) {
            $this->logError("Грешка при пускане на прекодиране на видео");
            // Добавяме съобщение
            status_Messages::newStatus('|Грешка при транскодиране на MP4', 'error');
        } else {
            // Добавяме съобщение
            status_Messages::newStatus('|Стартирано е транскодиране на MP4', 'success');
        }
        
    }
    
    /**
     * Изпълнява се след приключване на обработката
     * 
     * @param fconv_Script $script - Обект с данните
     * 
     * @param boolean  - TRUE - за да изтрие tmp скрипта и файловете
     */
    function afterConvert($script)
    {
        copy ($script->tempDir . basename($script->params['mp4File']), $script->params['mp4File']);
        core_Locks::release(basename($script->params['mp4File']));
        $err = file_get_contents($script->params['errFilePath']);
        if ($err) {
            self::logErr("Грешка при конвертиране на видео: $err");
        }
        
        return TRUE;
    }
    
    
    /**
     * Конвертира указания файл (записан от този драйвер) към flv файл
     */
    function convertToFlv($mp4Path, $flvFile, $params)
    {

        $cmd = "ffmpeg -i $mp4Path -ar 44100 -ab 96 -qmax {$params->FPS} -f flv $flvFile < /dev/null > /dev/null 2>&1 &";
        
        $out = exec($cmd);
        
        $this->logDebug("cmd = {$cmd}");
        $this->logDebug("out = {$out}");
        
        return $out;
    }
    
    
    /**
     * Конвертира указания файл (записан от този драйвер) към flv файл
     */
    function convertToOgv($mp4Path, $ogvFile)
    {
        $cmd = "ffmpeg -i $mp4Path -ar 44100 -vcodec libtheora -acodec libvorbis -ab 96 -qmax 10 -f ogv $ogvFile < /dev/null > /dev/null 2>&1 &";
        
        $out = exec($cmd);
        $this->logDebug("cmd = {$cmd}");
        $this->logDebug("out = {$out}");
        
        return $out;
    }
    
    
    /**
     * Маркира посочения в id запис
     */
    function act_Mark()
    {
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));
        
        $this->requireRightFor('mark', $rec);
        
        $rec->marked = 'yes';
        
        $this->save($rec, 'marked');
        
        return new Redirect(array($this, 'Single', $id));
    }
    
    
    /**
     * От маркира посочения в id запис
     */
    function act_Unmark()
    {
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));
        
        $this->requireRightFor('unmark', $rec);
        
        $fp = $this->getFilePaths($rec->startTime, $rec->cameraId);
        
        unlink($fp->flvFile);
        
        $rec->marked = 'no';
        
        $this->save($rec, 'marked');
        
        return new Redirect(array($this, 'Single', $id));
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_RecordVideo()
    {
        expect(isDebug());
        
        return $this->cron_RecordVideo();
    }
    
    
    /**
     * Стартира се периодично на всеки 5 минути и прави записи
     * на всички активни камери
     */
    function cron_RecordVideo()
    {
    	$conf = core_Packs::getConfig('cams');
    	
        $camsQuery = $this->Cameras->getQuery();
        
        $camsQuery->where("#state = 'active'");
        
        $startTime = dt::timestamp2Mysql(round(time() / $conf->CAMS_CLIP_DURATION) * $conf->CAMS_CLIP_DURATION);
        
        $images = $clips = 0;
        
        while($camRec = $camsQuery->fetch()) {
            
            $fp = $this->getFilePaths($startTime, $camRec->id);
            
            $driver = cls::getInterface('cams_DriverIntf', $camRec->driver, $camRec->params);
            
            if(!$driver->isActive()) continue;
			
            $driver->captureVideo($fp->videoFile, $conf->CAMS_CLIP_DURATION + 7);

            if($imageStr = $driver->getPicture()) {
                
                imagejpeg($imageStr, $fp->imageFile);
                
                // Отложено ресайзване
                $toThumb[$fp->imageFile] = $fp->thumbFile;
                
                $shots++;
            }
            
            // Подготвяме и записваме записа;
            $rec = new stdClass();
            $rec->cameraId = $camRec->id;
            $rec->startTime = $startTime;
            $rec->duration = $conf->CAMS_CLIP_DURATION;
            $rec->marked = 'no';
            $rec->isAnalyzed = 'no';
            $rec->params = json_encode(array("FPS"=>$driver->getFPS(), "width"=>$driver->getWidth(), "height"=>$driver->getHeight()));
            
            $this->save($rec);
            
            $clips++;
        }
        
        // Преоразмеряваме големите картинки
        if(count($toThumb)) {
            foreach($toThumb as $src => $dest) {
                
                $img = new thumb_Img(array($src, 280, 210, 'path', 'isAbsolute' => FALSE, 'mode' => 'small-no-change'));
                $thumb = $img->getScaledGdRes();
                
                imagejpeg($thumb, $dest, 85);
            }
        }
        
        return "Записани са {$clips} клипа.";
    }
    
    
    /**
     * Изпълнява се след подготовката на филтъра за листовия изглед
     * Обикновено тук се въвеждат филтриращите променливи от Request
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        $camOpt = $mvc->getCameraOpts();
        
        $data->listFilter->setOptions('cameraId', $camOpt);
        
        $data->listFilter->FNC('select', 'enum(all=Всички,marked=Маркираните)');
        
        $data->listFilter->showFields = 'cameraId,startTime,select';
        
        $data->listFilter->toolbar->addSbBtn('Покажи');
        
        $data->listFilter->view = 'horizontal';
        
        // 1. Трябва да определим коя камера да се показва
        // 2. Трябва да определим от кое време нататък да се показва
        // 3. Дали само маркираните или всички
        $data->listFilter->input('cameraId,select', 'silent');
        
        $fRec = $data->listFilter->rec;
        
        // Ако не е указано, селектират се всички записи
        setIfNot($fRec->select, 'all');
        
        // Ако не е указанa, залагаме последно използваната камера
        setIfNot($fRec->cameraId, Mode::get('monLastUsedCameraId'));
        
        //Ако имаме cameraId
        if (isset($fRec->cameraId) && (!$mvc->Cameras->fetch($fRec->cameraId))) {
            $fRec->cameraId = NULL;
            Mode::setPermanent('monLastUsedCameraId', NULL);
        }
        
        // Ако няма последно използвана камера, вземаме първата активна от списъка
        if(!isset($fRec->cameraId)) {
            $fRec->cameraId = $mvc->Cameras->fetchField("#state = 'active'", 'id');
        }
        
        // Ако няма активна камера, вземаме първата
        if(!isset($fRec->cameraId)) {
            $fRec->cameraId = $mvc->Cameras->fetchField("1=1", 'id');
        }
        
        // Ако няма никаква камера, редиректваме към камерите, 
        // със съобщение за въведат поне една камера
        if(!isset($fRec->cameraId)) {
            redirect(array('cams_Cameras'), TRUE, "|Моля въведете поне една камера");
        }
        
        // Задаваме, така получената камера, като последно използвана
        Mode::setPermanent('monLastUsedCameraId', $fRec->cameraId);
        
        if($fRec->select == 'marked') {
            $data->query->where("#marked = 'yes'");
        }
        
        $pageOpts = $mvc->getPageOpts($data->query, $fRec->cameraId, $firstPage);
        
        $data->listFilter->setOptions('startTime', $pageOpts);
        
        $data->listFilter->input('startTime', 'silent');
        
        setIfNot($fRec->startTime, dt::verbal2mysql($firstPage));
        
        $camTitle = $camOpt[$fRec->cameraId]->title;
        
        $startPage = dt::mysql2verbal($fRec->startTime);
        
        $startPageStamp = dt::mysql2timestamp($fRec->startTime);
        
        $startPageEndStamp = $startPageStamp + $mvc->getPageDuration();
        
        $data->startPageStamp = $startPageStamp;
        
        $startPageEnd = dt::mysql2verbal(dt::timestamp2mysql($startPageEndStamp));
        
        $camUrl = toUrl(array('cams_Cameras', 'Single', $fRec->cameraId));
        
        $data->title = "Записи на камера|* <a href='{$camUrl}'>{$camTitle}</a> |от" .
        "|* <span class=\"green\">{$startPage}</span> |до|* <span class=\"green\">{$startPageEnd}</span>";
        
        $startPageMysql = dt::verbal2mysql($startPage);
        
        $startPageEndMysql = dt::verbal2mysql($startPageEnd);
        
        $data->query->where("#startTime >=  '{$startPageMysql}' AND #startTime < '{$startPageEndMysql}'");
        
        $data->query->where("#cameraId = {$fRec->cameraId}");
    }
    
    
    /**
     * Връща масива с опции за страници с видео-записи
     */
    function getPageOpts($query, $cameraId, &$firstPage)
    {
        $query = clone($query);
        
        $query->show('startTime,cameraId');
        
        $query->orderBy('#startTime', 'DESC');
        
        $pageOpts = $pageState = array();
        while($rec = $query->fetch()) {
            $page = $this->getPageByTime($rec->startTime);

            if(!isset($pageOpts[$page])) {
                $pageOpts[$page] = $page;
            }
            
            if($cameraId == $rec->cameraId) {
                $pageState[$page] = TRUE;
            }
        }
        
        $page = $this->getPageByTime(dt::verbal2mysql());
        $pageOpts[$page] = $page;
        
        arsort($pageOpts);
        
        $pageOptsVerbal = array();
        foreach($pageOpts as $page) {
            $pageVerbal = dt::mysql2verbal($page);
            
            $pageOptsVerbal[$page] = new stdClass();
            //            $pageOptsVerbal[$pageVerbal]->title = $pageVerbal;
            $pageOptsVerbal[$page]->title = $pageVerbal;
            
            if(!$pageState[$page]) {
            	$pageOptsVerbal[$pageVerbal] = new stdClass();
                $pageOptsVerbal[$pageVerbal]->attr = array('style' => 'color:#666');
            } else {
                if(!$firstPage) {
                    $firstPage = $pageVerbal;
                }
            }
        }
        
        return $pageOptsVerbal;
    }
    
    
    /**
     * Връща страницата според началното време на записа
     */
    function getPageByTime($startTime)
    {
        $begin = dt::mysql2timestamp('2000-01-01 00:00:00');
        $pageDuration = $this->getPageDuration();
        $startTimestamp = dt::mysql2timestamp($startTime);
        
        $page = dt::timestamp2Mysql($begin +
            floor(($startTimestamp - $begin) / $pageDuration) * $pageDuration);
        
        return $page;
    }
    
    
    /**
     * Връща опциите за камерите, като тези, които не записват са посивени
     */
    function getCameraOpts()
    {
        $camQuery = $this->Cameras->getQuery();
        
        while($camRec = $camQuery->fetch()) {
            
            $obj = new stdClass();
            
            $obj->title = $this->Cameras->getVerbal($camRec, 'title');
            
            if($camRec->state != 'active') {
                $obj->attr = array('style' => 'color:#666');
            }
            $cameraOpts[$camRec->id] = $obj;
        }
        
        return $cameraOpts;
    }
    
    
    /**
     * Връща id на предходния запис за същата камера
     */
    function getPrevRec($id)
    {
    	$conf = core_Packs::getConfig('cams');
    	
        $rec = $this->fetch($id);
        $startStamp = dt::mysql2timestamp($rec->startTime);
        $prevStamp = $startStamp -$conf->CAMS_CLIP_DURATION;
        $prevTime = dt::timestamp2mysql($prevStamp);
        
        if($prevRec = $this->fetch("#startTime = '{$prevTime}' AND #cameraId = {$rec->cameraId}")) {
            return $prevRec->id;
        }
    }
    
    
    /**
     * Връща id на следващия запис за същата камера
     */
    function getNextRec($id)
    {
    	$conf = core_Packs::getConfig('cams');
    	
        $rec = $this->fetch($id);
        $startStamp = dt::mysql2timestamp($rec->startTime);
        $nextStamp = $startStamp + $conf->CAMS_CLIP_DURATION;
        $nextTime = dt::timestamp2mysql($nextStamp);
        
        if($nextRec = $this->fetch("#startTime = '{$nextTime}' AND #cameraId = {$rec->cameraId}")) {
            return $nextRec->id;
        }
    }
    
    
    /**
     * Връща броя на клиповете, които се показват на една страница
     */
    function getClipsPerPage()
    {
    	$conf = core_Packs::getConfig('cams');
    	
        return Mode::is('screenMode', 'narrow') ?
        $conf->CAMS_CLIPS_PER_NARROW_PAGE :
        $conf->CAMS_CLIPS_PER_WIDE_PAGE;
    }
    
    
    /**
     * Връща броя на клиповете, които се показват на един ред
     */
    function getClipsPerRow()
    {
    	$conf = core_Packs::getConfig('cams');
    	
        return Mode::is('screenMode', 'narrow') ?
        $conf->CAMS_CLIPS_PER_NARROW_ROW :
        $conf->CAMS_CLIPS_PER_WIDE_ROW;
    }
    
    
    /**
     * Връща периода който обхваща една страница със записи в секунди
     */
    function getPageDuration()
    {
        static $duration;

        if(!$duration) {
            $conf = core_Packs::getConfig('cams');
            $duration = $conf->CAMS_CLIP_DURATION * $this->getClipsPerPage();
        }

        return $duration;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function prepareListRecs_(&$data)
    {
    	$conf = core_Packs::getConfig('cams');
    	
        while($rec = $data->query->fetch())
        {
            $startTimeTimestamp = dt::mysql2timestamp($rec->startTime);
            $number = ($startTimeTimestamp - $data->startPageStamp) / $conf->CAMS_CLIP_DURATION;
            $row = floor($number / $this->getClipsPerRow());
            $column = $number % $this->getClipsPerRow();
            
            $data->listRecs[$row][$column] = $rec;
            $data->listRows[$row][$column] = $this->recToVerbal($rec);
        }
    }
    
    
    /**
     * Рендира съдържанието - таблицата с превютата
     */
    function renderListTable_($data)
    {
    	$conf = core_Packs::getConfig('cams');
    	
        $cols = $this->getClipsPerRow();
        $rows = $this->getClipsPerPage() / $this->getClipsPerRow();
        
        $html .= '<table cellspacing="3" bgcolor="white" class="video-rec">';
        
        for($r = 0; $r < $rows; $r++) {
            
            $html .= "<tr>";
            
            for($c = 0; $c < $cols; $c++) {
                
                if(isset($data->listRecs[$r][$c]->id)) {
                    $content = $data->listRows[$r][$c]->thumb;
                    $content = ht::createLink($content, array($this, 'Single', $data->listRecs[$r][$c]->id));
                } else {
                    $content = '';
                }
                
                if(!$data->listRows[$r][$c]->startTime) {
                    $startStamp = $data->startPageStamp + ($r * $cols + $c) * $conf->CAMS_CLIP_DURATION;
                    $startTime = dt::timestamp2mysql($startStamp);
                    $startVerbalTime = dt::mysql2verbal($startTime);
                } else {
                    $startVerbalTime = $data->listRows[$r][$c]->startTime;
                }
                
                $class = $this->getCaptionClassByRec($data->listRecs[$r][$c]);
                
                $date = "<div class='{$class}' style='border-bottom:solid 1px #ccc;'>" . $startVerbalTime . "</div>";
                
                $html .= "<td style='width:240px; height:211px; text-align:center; vertical-align:top;background-color:#e8e8e8'>{$date}{$content}</td>";
            }
            
            $html .= "</tr>";
        }
        
        $html .= "</table>";
        
        return $html;
    }
    
    
    /**
     * Връща стила на надписа за съответния запис
     */
    function getCaptionClassByRec($rec)
    {
        if($rec->marked == 'yes') {
            $class = 'marked';
        } elseif($rec->playedOn) {
            $class = 'played';
        } else {
            $class = 'normal';
        }
        
        return $class;
    }
    
    
    /**
     * Изпълнява се след конвертирането към вербални стойности
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $attr = array();
        $attr['src'] = toUrl(array($mvc, 'StartJpg', $rec->id, 'thumb' => 'yes'));
        
        $row->thumb = ht::createElement('img', $attr);
    }
    
    
    /**
     * Изтрива стари записи, ако дисковото пространство е под лимита
     */
    function cron_DeleteOldRecords()
    {
    	$conf = core_Packs::getConfig('cams');
    	
        $freeSpace = disk_free_space(CAMS_VIDEOS_PATH);
        
        if($freeSpace < $conf->CAMS_MIN_DISK_SPACE) {
            
            $query = $this->getQuery();
            
            $query->orderBy('startTime');
            
            // Тези, които са под 1 ден не ги закачаме
            $before1day = dt::addDays(-1);
            
            $query->where("#startTime < '{$before1day}' AND #marked != 'yes'");
            
            $deleted = $delFiels = 0;
            
            while(disk_free_space(CAMS_VIDEOS_PATH) < $conf->CAMS_MIN_DISK_SPACE && ($rec = $query->fetch())) {
                
                if($rec->id) {
                    $this->delete($rec->id);
                    
                    $fPaths = $this->getFilePaths($rec->startTime, $rec->cameraId);
                    
                    if(@unlink($fPaths->videoFile)) $delFils++;
                    
                    if(@unlink($fPaths->imageFile)) $delFils++;
                    
                    if(@unlink($fPaths->thumbFile)) $delFils++;
                    
                    if(@unlink($fPaths->flvFile)) $delFils++;
                    
                    $deleted++;
                }
            }
            
            return "Изтрити са {$deleted} записа в базата и {$delFils} файла";
        }
        
        return "Не са изтрити записи от камерите, място все още има";
    }
    
    
    /**
     * Изпълнява се след начално установяване(настройка) на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
    	$conf = core_Packs::getConfig('cams');
    	    	
        $dirs = array(
            CAMS_VIDEOS_PATH => "за съхраняване на записите",
            CAMS_IMAGES_PATH => "за съхраняване на JPG",
            SBF_CAMS_FLV_PATH => "за FLV за плейване",
            SBF_CAMS_MP4_PATH => "за MP4 за плейване",
        );
        
        foreach($dirs as $d => $caption) {
            
            if(!is_dir($d)) {
                if(mkdir($d, 0777, TRUE)) {
                    $msg = "<li style='color:green;'> Директорията <b>{$d}</b> е създадена ({$caption})";
                } else {
                    $msg = "<li style='color:red;'> Директорията <b>{$d}</b> не може да бъде създадена ({$caption})";
                }
            } else {
                $msg = "<li> Директорията <b>{$d}</b> съществува от преди ({$caption})";
            }
            
            $res .= $msg;
        }
        
        // Наглася Cron да стартира записването на камерите
        $rec = new stdClass();
        $rec->systemId = "record_video";
        $rec->description = "Правят се записи от камерите";
        $rec->controller = "cams_Records";
        $rec->action = "RecordVideo";
        $rec->period = (int) $conf->CAMS_CLIP_DURATION / 60;
        $rec->offset = 0;
        $res .= core_Cron::addOnce($rec);

        
        $rec = new stdClass();
        $rec->systemId = "delete_old_video";
        $rec->description = "Изтриване на старите записи от камерите";
        $rec->controller = "cams_Records";
        $rec->action = "DeleteOldRecords";
        $rec->period = (int) 2 * $conf->CAMS_CLIP_DURATION / 60;
        $rec->offset = mt_rand(0,8);
        $res .= core_Cron::addOnce($rec);

        
        $rec = new stdClass();
        $rec->systemId = "cams_Analyze";
        $rec->description = "Монтира по 4 записите с движение";
        $rec->controller = "cams_Records";
        $rec->action = "Analyze";
        $rec->period = (int) $conf->CAMS_CLIP_DURATION / 60;
        $rec->offset = 1;
        $res .= core_Cron::addOnce($rec);
    }
    
    
    /**
     * Метод за Cron за анализиране на записите
     * Извикава се на всеки 5 минути (300 секунди).
     */
    function cron_Analyze()
    {
        // Вземаме всички записи, които не са анализирани, под 100 са, и са започнати 
        // преди повече от $conf->CAMS_CLIP_DURATION + 7 сек. от най-новите към по-старите
        $query = $this->getQuery();
        
        $query->orderBy('startTime', 'DESC');
        $before5min = dt::addsecs(-5*60);
        $query->where("#startTime < '{$before5min}' AND #isAnalyzed = 'no'");
        //$query->limit(100);
        $query->limit(50);

        while ($rec = $query->fetch()) {
            $paths = $this->getFilePaths($rec->startTime, $rec->cameraId); //bp($paths);
            $Script[$rec->id] = cls::get('fconv_Script');
            $Script[$rec->id]->setFile('INPUTF', $paths->videoFile);
            $Script[$rec->id]->setFile('OUTPUTF', "/shot_" . $rec->id . "_%03d.jpg");
            $Script[$rec->id]->lineExec("ffmpeg -i [#INPUTF#] -an -vf \"select=gt(scene\,0.03),setpts=N/(2*TB)\" [#OUTPUTF#]");
            $Script[$rec->id]->callBack('cams_Records::afterAnalyze');
            $Script[$rec->id]->recId = $rec->id;
            $Script[$rec->id]->imageFile = $paths->imageFile;
            $Script[$rec->id]->thumbFile = $paths->thumbFile;
            
            $async = TRUE;
            if ($Script[$rec->id]->run($async) !== FALSE) {
            }
        }

    }
    
    /**
     * Получава обекта - скрипт, който е вадел кадри от видео 
     *
     */
    function afterAnalyze($script)
    {
        // Ако имаме получени картинки, вадим максимално до 4 от тях и викаме: montage keyframes001.png keyframes002.png keyframes003.png keyframes005.png -geometry 512x384+2+2 result.png
        // взимаме броя на jpg файловете, които са резултат от движението във видеото
        $fCnt = exec("ls -l {$script->tempDir}*.jpg | wc -l");
        $fourShots = '';
        $outpuF = str_replace("//", "/", $script->tempDir . $script->files['OUTPUTF']);
        // Ако имаме само 1 картинка - нищо не правим. Ако имаме 2 или 3, повтаряме последтата
        switch ($fCnt) {
            case 1:
                break;
            case 2:
                //$script->files['OUTPUTF']; // [OUTPUTF] => /shot_1656_%03d.jpg
                $fourShots = sprintf($outpuF, 1) . " "
                     . sprintf($outpuF, 2) . " " 
                     . sprintf($outpuF, 2) . " "
                     . sprintf($outpuF, 2);
                break;
            case 3:
                $fourShots = sprintf($outpuF, 1) . " "
                     . sprintf($outpuF, 2) . " "
                     . sprintf($outpuF, 3) . " "
                     . sprintf($outpuF, 3);
                break;
            default:
                $fourShots = sprintf($outpuF, 1) . " "
                     . sprintf($outpuF, (int)$fCnt/3) . " "
                     . sprintf($outpuF, (int)$fCnt*2/3)  . " " 
                     . sprintf($outpuF, $fCnt);
        }
        
        $cmd = "montage " . $fourShots . " -geometry 512x384+2+2 " . $script->tempDir . "result.jpg";        
        
//         file_put_contents('afterAnalizeRES.txt', print_r($script, TRUE) . PHP_EOL, FILE_APPEND);
//         file_put_contents('afterAnalizeRES.txt', $fCnt . PHP_EOL, FILE_APPEND);
        if (!empty($fourShots)) {
            exec($cmd);
            // Резултатната снимка записваме като файла за картинка
            copy($script->tempDir . "result.jpg", $script->imageFile);
            // и нейния thumb - в пътя за тъмб
            $img = new thumb_Img(array($script->tempDir . "result.jpg", 280, 210, 'path', 'isAbsolute' => FALSE, 'mode' => 'small-no-change'));
            $thumb = $img->getScaledGdRes();
            
            imagejpeg($thumb, $script->thumbFile, 85);
            
        }
        
        // Отбелязваме че записа е анализиран
        $rec = new stdClass();
        $rec->id = $script->recId;
        $rec->isAnalyzed = yes;
        $this->save($rec);
        // Ако е наближило 300 секунди от началото на процеса - излизаме иначе, продължаваме от начало
        
        // Разкарваме временната директория
        exec(sprintf("rm -rf %s", escapeshellarg($script->tempDir)));
    }
    
    /**
     * Ръчен метод за тестване на кеон метода за детектиране на движение
     * 
     */
    function act_Analyze()
    {
        requireRole('admin');
        
        $this->cron_Analyze();
    }
}