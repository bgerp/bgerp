<?php


/**
 * Път до директория, където ще се съхраняват записите от камерите
 */
defIfNot('cams_VIDEOS_PATH', EF_UPLOADS_PATH . "/cams/videos");


/**
 * Път до директория, където ще се записват jpeg превютата
 */
defIfNot('cams_IMAGES_PATH', EF_UPLOADS_PATH . "/cams/images");


/**
 * Директория за flv файловете
 */
defIfNot('SBF_CAMS_FLV_DIR', "_cams/flv");


/**
 * Път до директория, където ще се записват flv файловете
 */
defIfNot('SBF_CAMS_FLV_PATH', EF_SBF_PATH . '/' . SBF_CAMS_FLV_DIR);


/**
 * Колко да е продължителността на един клип
 */
defIfNot('cams_CLIP_DURATION', 5*60);


/**
 * Колко е продължителността на конвертирането на един клип
 */
defIfNot('cams_CLIP_TO_FLV_DURATION', round(cams_CLIP_DURATION/30));


/**
 * Колко клипа да показва на страница при широк екран
 */
defIfNot('cams_CLIPS_PER_WIDE_PAGE', 144);


/**
 * Колко клипа да показва на страница при тесен екран
 */
defIfNot('cams_CLIPS_PER_NARROW_PAGE', 12);


/**
 * Колко клипа да показва на ред при широк екран
 */
defIfNot('cams_CLIPS_PER_WIDE_ROW', 6);


/**
 * Колко клипа да показва на ред при тесен екран
 */
defIfNot('cams_CLIPS_PER_NARROW_ROW', 1);


/**
 * Колко да е минималното дисково пространство
 */
defIfNot('cams_MIN_DISK_SPACE', 100*1024*1024*1024);


/**
 * Клас 'cams_Records' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    cams
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class cams_Records extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, cams_Wrapper, Cameras=cams_Cameras';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Записи от камери';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, thumb, cameraId, startTime, duration, playedOn, marked';
    
    
    /**
     * Права
     */
    var $canWrite = 'cams, admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'cams, admin';
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
    }
    
    
    /**
     * Връща пътища до медийните файлове за $id-тия запис
     */
    function getFilePaths($startTime, $cameraId)
    {
        $baseName = dt::mysql2verbal($startTime, "d-m-y_H-i") . '-' . $cameraId;
        
        // Видео MP4 файл - суров запис от камерата с добро качество
        $fp->videoFile = cams_VIDEOS_PATH . "/{$baseName}.mp4";
        
        // Картинка към началото на записа
        $fp->imageFile = cams_IMAGES_PATH . "/{$baseName}.jpg";
        
        // Умалена картинка към началото на записа
        $fp->thumbFile = cams_IMAGES_PATH . "/{$baseName}_t.jpg";
        
        // Flash Video File за записа
        $hash = substr(md5(EF_SALT . $baseName), 0, 6);

        $fp->flvFile = SBF_CAMS_FLV_PATH . "/{$baseName}_{$hash}.flv";
        
        // Ако директорията за flv файловете не съществува,
        // записва в лога 
        if(!is_dir('SBF_CAMS_FLV_PATH')) {
       		$this->log("sbf директорията за flv файловете не съществува - преинсталирайте cams.");
        }
        
        $fp->flvUrl = sbf(SBF_CAMS_FLV_DIR . "/{$baseName}_{$hash}.flv", '');
        
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
        $cacheTime = 60*60;
        
        session_cache_limiter('none');
        
        // Then send Cache-Control: max-age=number_of_seconds and
        // optionally equivalent Expires: header.
        header('Cache-control: max-age=' . $cacheTime);
        header('Expires: '.gmdate(DATE_RFC1123, time() + $cacheTime));
        
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
        $id = Request::get('id', 'int');
        
        expect($rec = $this->fetch($id));
        
        // Подготвяме пътищата до различните медийни файлове
        $fp = $this->getFilePaths($rec->startTime, $rec->cameraId);
        
        // Настройваме параметрите на плеъра
        $data->url = $fp->flvUrl;
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
        
        // Ако записа е маркиран, поставяме бутон за отмаркиране и обратното
        if($rec->marked == 'yes') {
            $data->toolbar->addBtn('Отмаркиране', array($this, 'Unmark', $id));
        } else {
            $data->toolbar->addBtn('Маркиране', array($this, 'Mark', $id));
        }
        
        // Вземаме записа за камерата и подготвяме драйвера
        $camRec = $this->Cameras->fetch($rec->cameraId);
        $driver = cls::getInterface('cams_DriverIntf', $camRec->driver, $camRec->params);
        
        $data->width  = $driver->getWidth();
        $data->height = $driver->getHeight();
        
        // След колко секунди, очакваме клипа да бъде конвертиран?
        if(isset($rec->playedOn)) {
            $secondsToEnd = dt::mysql2timestamp($rec->playedOn) +
            cams_CLIP_TO_FLV_DURATION - time();
            // Времето може да бъде само положително
            $secondsToEnd = $secondsToEnd > 0 ? $secondsToEnd : 0;
        } else {
            $secondsToEnd = NULL;
        }
        
        if(!file_exists($fp->flvFile)) {
            if(!$secondsToEnd) {
                // Стартираме конвертирането на видеото към flv, ако това все още не е направено
                $this->convertToFlv($fp->videoFile, $fp->flvFile);
                $this->log('Конвертиране към FLV', $rec->id);
                $secondsToEnd = cams_CLIP_TO_FLV_DURATION;
            }
            
            if($secondsToEnd === NULL) {
                $this->log('Правенo е конвертиране, но FLV файлът не се е появил', $rec->id);
                $secondsToEnd = cams_CLIP_TO_FLV_DURATION;
            }
        } else {
            if($secondsToEnd === NULL) {
                $this->log('Има FLV файл, без да е конвертиран', $rec->id);
                $secondsToEnd = cams_CLIP_TO_FLV_DURATION;
            }
        }
        
        $data->startDelay = $secondsToEnd*1000;
        
        $row = $this->recToVerbal($rec);
        
        // Записваме, кога клипът е пуснат за разглеждане първи път
        if(!isset($rec->playedOn)) {
            $rec->playedOn = dt::verbal2mysql();
            $this->save($rec, 'playedOn');
        }
        
        // Получаваме класа на кепшъна
        $data->captionClass = $this->getCaptionClassByRec($rec);
        
        $data->caption = "Начало: $row->startTime";
        
        if($rec->playedOn) {
            $data->caption .= ", видян на $row->startTime";
        }
        
        if($rec->marked == 'yes') {
            $data->caption .= ", маркиран";
        }
        
        $data->duration = cams_CLIP_DURATION;
        
        // Рендираме плеъра
        $tpl = $this->renderSingle($data);
        
        $this->log("Single", $rec->id);
        
        return $this->renderWrapping($tpl);
    }
    
    
    /**
     * Рендиране на плеъра
     */
    function renderSingle_($data)
    {
        $tpl = new ET ('
            <div id=toolbar style="margin-bottom:10px;">[#toolbar#]</div>
            <div class="video-rec" style="display:table">
                <div class="[#captionClass#]" style="padding:5px;font-size:0.95em;">[#caption#]</div>
                <div id="container" >[#content#]</div>
            </div>
            <script type="text/javascript">
            function start() {
                    jwplayer("container").setup({
                        autostart: false,
                        file: "[#url#]",
                        duration: [#duration#],
                        flashplayer: "[#player#]",
                        volume: 80,
                        width: [#width#],
                        height: [#height#],
                        image: "[#image#]"
                    });
            }

            setTimeout("start();", [#startDelay#]);

            </script>
        ');
        
        // Какво ще показваме, докато плеъра се зареди
        setIfNot($data->content, "<img src='{$data->image}' style='width:{$data->width}px;height:{$data->height}px'>");
        
        // По подразбиране времето за закъснение в началото е 10 сек.
        setIfNot($data->startDelay, 10000);
        
        // Кода на плеъра
        setIfNot($data->player, sbf('uniplayer/LongTail/player.swf', ''));
        
        $data->toolbar = $data->toolbar->renderHtml();
        
        // Поставяме стойностите на плейсхолдърите
        $tpl->placeObject($data);
        
        // Поставяме необходимия JS
        $tpl->push('uniplayer/LongTail/jwplayer.js', 'JS');
        
        return $tpl;
    }
    
    
    /**
     * Конвертира указания файл (записан от този драйвер) към flv файл
     */
    function convertToFlv($mp4Path, $flvFile)
    {
        $cmd = "ffmpeg -i $mp4Path -ar 44100 -ab 96 -qmax 10 -f flv $flvFile 2>&1 &";

        $out = exec($cmd);
        debug::log("cmd = {$cmd}");
        debug::log("out = {$out}");
        
        return $out;
    }
    
    
    /**
     * Конвертира указания файл (записан от този драйвер) към flv файл
     */
    function convertToOgv($mp4Path, $ogvFile)
    {
        $cmd = "ffmpeg -i $mp4Path -ar 44100 -vcodec libtheora -acodec libvorbis -ab 96 -qmax 10 -f ogv $ogvFile < /dev/null > /dev/null 2>&1 &";
        
        $out = exec($cmd);
        debug::log("cmd = {$cmd}");
        debug::log("out = {$out}");
        
        return $out;
    }
    
    
    /**
     * Маркира посочения в id запис
     */
    function act_Mark()
    {
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));
        
        $this->requireRightFor('marked', $rec);
        
        $rec->marked = 'yes';
        
        $this->save($rec, 'marked');
        
        return new Redirect(array($this, 'Single', $id));
    }
    
    
    /**
     * Отмаркира посочения в id запис
     */
    function act_Unmark()
    {
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));
        
        $this->requireRightFor('marked', $rec);
        
        $rec->marked = 'no';
        
        $this->save($rec, 'marked');
        
        return new Redirect(array($this, 'Single', $id));
    }
    
    
    /**
     *  @todo Чака за документация...
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
        $camsQuery = $this->Cameras->getQuery();
        
        $camsQuery->where("#state = 'active'");
        
        $startTime = dt::timestamp2Mysql(round(time()/cams_CLIP_DURATION) * cams_CLIP_DURATION);
        
        $images = $clips = 0;
        
        while($camRec = $camsQuery->fetch()) {
            
            $fp = $this->getFilePaths($startTime, $camRec->id);
            
            $driver = cls::getInterface('cams_DriverIntf', $camRec->driver, $camRec->params);
            
            if(!$driver->isActive()) continue;
            
            $res = $driver->captureVideo($fp->videoFile, cams_CLIP_DURATION+7);
            
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
            $rec->duration = cams_CLIP_DURATION;
            $rec->marked = 'no';
            
            $this->save($rec);
            
            $clips++;
        }
        
        // Преоразмеряваме големите картинки
        if(count($toThumb)) {
            foreach($toThumb as $src => $dest) {
                $thumb = thumbnail_Thumbnail::makeThumbnail($src, array(280, 210));
                imagejpeg($thumb, $dest, 85);
            }
        }
        
        return "Записани са {$clips} клипа.";
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $camOpt = $this->getCameraOpts();
        
        $data->listFilter->setOptions('cameraId', $camOpt);
        
        $data->listFilter->FNC('select', 'enum(all=Всички,marked=Маркираните)');
        
        $data->listFilter->showFields = 'cameraId,startTime,select';
        
        $data->listFilter->toolbar->addSbBtn('Покажи');
        
        $data->listFilter->view = 'horizontal';
        
        // 1. Трябва да определим коя камера да се показва
        // 2. Трябва да определим от кое време нататък да се показва
        // 3. Дали само маркираните или всички
        $data->listFilter->input('cameraId,startTime,select', 'silent');
        
        $fRec = $data->listFilter->rec;
        
        if(isset($fRec->startTime)) {
            $fRec->startTime = dt::mysql2verbal($fRec->startTime);
        }
        
        // Ако не е указано, селектират се всички записи
        setIfNot($fRec->select, 'all');
        
        // Ако не е указанa, залагаме последно използваната камера
        setIfNot($fRec->cameraId, Mode::get('monLastUsedCameraId'));
        
        if(!$this->Cameras->fetch($fRec->cameraId)) {
            $fRec->cameraId = NULL;
            Mode::setPermanent('monLastUsedCameraId', NULL);
        }
        
        // Ако няма последно използвана камера, вземаме първата активна от списъка
        if(!isset($fRec->cameraId)) {
            $fRec->cameraId = $this->Cameras->fetchField("#state = 'active'", 'id');
        }
        
        // Ако няма активна камера, вземаме първата
        if(!isset($fRec->cameraId)) {
            $fRec->cameraId = $this->Cameras->fetchField("1=1", 'id');
        }
        
        // Ако няма никаква камера, редиректваме към камерите, 
        // със съобщение за въведат поне една камера
        if(!isset($fRec->cameraId)) {
            core_Message::redirect("Моля въведете поне една камера", 'tpl_Error', NULL, array('cams_Cameras'));
        }
        
        // Задаваме, така получената камера, като последно използвана
        Mode::setPermanent('monLastUsedCameraId', $fRec->cameraId);
        
        if( $fRec->select == 'marked') {
            $data->query->where("#marked = 'yes'");
        }
        
        $pageOpts = $mvc->getPageOpts($data->query, $fRec->cameraId, &$firstPage);
        
        $data->listFilter->setOptions('startTime', $pageOpts);
        
        setIfNot($fRec->startTime, $firstPage);
        
        $camTitle = $camOpt[$fRec->cameraId]->title;
        
        $startPage = $fRec->startTime;
        
        $startPageStamp = dt::mysql2timestamp(dt::verbal2mysql($startPage));
        
        $startPageEndStamp = $startPageStamp + $this->getPageDuration();
        
        $data->startPageStamp = $startPageStamp;
        
        $startPageEnd = dt::mysql2verbal(dt::timestamp2mysql($startPageEndStamp));
        
        $camUrl = toUrl(array('cams_Cameras', 'Single', $fRec->cameraId));
        
        $data->title = "Записи на камера|* <a href='{$camUrl}'>{$camTitle}</a> |от" .
        "|* <font color='green'>{$startPage}</font> |до|* <font  color='green'>{$startPageEnd}</font>";
        
        $startPageMysql = dt::verbal2mysql($startPage);
        
        $startPageEndMysql = dt::verbal2mysql($startPageEnd);
        
        $data->query->where("#startTime >=  '{$startPageMysql}' && #startTime < '{$startPageEndMysql}'");
        
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
        
        while($rec = $query->fetch()) {
            $page = $this->getPageByTime($rec->startTime);
            $pageOpts[$page] = $page;
            
            if($cameraId == $rec->cameraId) {
                $pageState[$page] = TRUE;
            }
        }
        
        $page = $this->getPageByTime(dt::verbal2mysql());
        $pageOpts[$page] = $page;
        
        arsort($pageOpts);
        
        foreach($pageOpts as $page) {
            $pageVerbal = dt::mysql2verbal($page);
            $pageOptsVerbal[$pageVerbal]->title = $pageVerbal;
            
            if(!$pageState[$page]) {
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
        floor(($startTimestamp - $begin)/$pageDuration) * $pageDuration);
        
        return $page;
    }
    
    
    /**
     * Връща опциите за камерите, като тези, които не записват са посивени
     */
    function getCameraOpts()
    {
        $camQuery = $this->Cameras->getQuery();
        
        while($camRec = $camQuery->fetch()) {
            $location = $this->Cameras->getVerbal($camRec, 'location');
            
            $obj = new stdClass();
            
            $obj->title = $this->Cameras->getVerbal($camRec, 'title') . ' - ' . $location;
            
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
        $rec = $this->fetch($id);
        $startStamp = dt::mysql2timestamp($rec->startTime);
        $prevStamp = $startStamp - cams_CLIP_DURATION;
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
        $rec = $this->fetch($id);
        $startStamp = dt::mysql2timestamp($rec->startTime);
        $nextStamp = $startStamp + cams_CLIP_DURATION;
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
        return Mode::is('screenMode', 'narrow')?
        cams_CLIPS_PER_NARROW_PAGE :
        cams_CLIPS_PER_WIDE_PAGE;
    }
    
    
    /**
     * Връща броя на клиповете, които се показват на един ред
     */
    function getClipsPerRow()
    {
        return Mode::is('screenMode', 'narrow')?
        cams_CLIPS_PER_NARROW_ROW :
        cams_CLIPS_PER_WIDE_ROW;
    }
    
    
    /**
     * Пръща периода който обхваща една стеаница със записи в секунди
     */
    function getPageDuration()
    {
        return cams_CLIP_DURATION * $this->getClipsPerPage();
    }
    
    
    /**
     *
     */
    function prepareListRecs_($data)
    {
        while($rec = $data->query->fetch())
        {
            $startTimeTimestamp = dt::mysql2timestamp($rec->startTime);
            $number = ($startTimeTimestamp - $data->startPageStamp) / cams_CLIP_DURATION;
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
                    $startStamp = $data->startPageStamp + ($r * $cols + $c) * cams_CLIP_DURATION;
                    $startTime = dt::timestamp2mysql($startStamp);
                    $startVerbalTime = dt::mysql2verbal($startTime);
                } else {
                    $startVerbalTime = $data->listRows[$r][$c]->startTime;
                }
                
                $class = $this->getCaptionClassByRec($data->listRecs[$r][$c]);
                
                $date = "<div class='{$class}' style='border-bottom:solid 1px #ccc;'>" . $startVerbalTime . "</div>";
                
                $html .= "<td width=240 height=211 align=center valign=top bgcolor='#e8e8e8'>{$date}{$content}</td>";
            }
            
            $html .= "</tr>";
        }
        
        $html .= "</table>";
        
        return $html;
    }
    
    
    /**
     * Връща стила на кепшъна за съответния запис
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
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $attr['src'] = toUrl(array($this, 'StartJpg', $rec->id, 'thumb' => 'yes'));
        
        $row->thumb = ht::createElement('img', $attr);
    }
    
    
    /**
     * Изтрива стари записи, ако дисковото пространство е под лимита
     */
    function cron_DeleteOldRecords()
    {
        $freeSpace = disk_free_space(cams_VIDEOS_PATH);
        
        if($freeSpace < cams_MIN_DISK_SPACE) {
            
            $query = $this->getQuery();
            
            $query->orderBy('startTime');
            
            // Тези, които са под 1 ден не ги закачаме
            $before1day = dt::addDays(-1);
            
            $query->where("#startTime < '{$before1day}' AND #marked != 'yes'");
            
            $deleted = $delFiels = 0;
            
            while(disk_free_space(cams_VIDEOS_PATH) < cams_MIN_DISK_SPACE && ($rec = $query->fetch())) {
                
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
     * Изпълнява се след сетъп на модела
     */
    function on_AfterSetupMVC($mvc, $res)
    {
        $dirs = array(
        	cams_VIDEOS_PATH => "за съхраняване на записите",
            cams_IMAGES_PATH => "за съхраняване на JPG",
            SBF_CAMS_FLV_PATH => "за FLV за плейване",
        );
        
        foreach($dirs as $d => $caption) {
            
            if( !is_dir($d) ) {
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
        $Cron = cls::get('core_Cron');
        
        $rec->systemId = "record_video";
        $rec->description = "Записва от камерите";
        $rec->controller = "cams_Records";
        $rec->action = "RecordVideo";
        $rec->period = (int) cams_CLIP_DURATION/60;
        $rec->offset = 0;
        
        $Cron->addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = "delete_old_video";
        $rec->description = "Изтрива старите записи от камерите";
        $rec->controller = "cams_Records";
        $rec->action = "DeleteOldRecords";
        $rec->period = (int) 2 * cams_CLIP_DURATION/60;
        $rec->offset = 0;
        
        $Cron->addOnce($rec);
    }
    
    
    /**
     * Метод за Cron за почистване на таблицата
     */
    function cron_RefreshRecords()
    {
        return $this->refrefRecords();
    }
    
    
    /**
     * Почистване на таблицата със записите
     */
    function refreshRecords()
    {
    
    }
}