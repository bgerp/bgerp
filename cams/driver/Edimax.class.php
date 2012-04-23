<?php



/**
 * Драйвер за IP камера Edimax
 *
 *
 * @category  bgerp
 * @package   cams
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cams_driver_Edimax extends cams_driver_IpDevice {
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        
        parent::init($params);
        
        setIfNot($this->width, 640);
        setIfNot($this->height, 480);
        
        setIfNot($this->user, 'root');
        setIfNot($this->password, 'root');
                
        setIfNot($this->rtspPort, 554);
        
        setIfNot($this->httpPort, 80);
    }
    
    
    /**
     * Подготвя формата за настройки на камерата
     */
    function prepareSettingsForm($form)
    {
        $form->FNC('ip', new type_Varchar(array('size' => 16, 'regexp' => '^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(/[0-9]{1,2}){0,1}$')),
            'caption=IP,hint=Въведете IP адреса на камерата,input, mandatory');
        $form->FNC('codec', 'enum(mpeg4=MPEG-4)', 'caption=Кодек,hint=Кодек на RTSP стрийма,input');
        $form->FNC('width', 'int(min=320,max=1280)', 'caption=Ширина,hint=Хоризонтална резолюция,input');
        $form->FNC('height', 'int(min=240,max=1024)', 'caption=Височина,hint=Вертикална резолюция,input');
        $form->FNC('FPS', 'int(min=1,max=30)', 'caption=Скорост,hint=Скорост на записа (fps),input');
        $form->FNC('user', 'varchar(64)', 'caption=Потребител,hint=Въведете потребителското име за администратора на камерата,input');
        $form->FNC('password', 'password(64)', 'caption=Парола,hint=Въведете паролата за администратора на камерата,input');
        $form->FNC('ptzControl', 'enum(yes=Има,no=Няма)', 'caption=PTZ контрол,hint=Има ли камерата PTZ контрол?,input');
        $form->FNC('running', 'enum(yes=Активно,no=Спряно)', 'caption=Състояние,hint=Дали камерата да се наблюдава?,input');
        $form->FNC('rtspPort', 'int(min=1,max=65535)', 'caption=Порт->Rtsp,hint=Въведете порта за Mpeg4 потока,input');
        $form->FNC('httpPort', 'int(min=1,max=65535)', 'caption=Порт->Http,hint=Въведете порта за CGI заявките,input');
    }

    
    /**
     * Подготвя формата за PTZ контрола
     */
    function preparePtzForm($form)
    {
        $form->FNC('rpan', 'enum(0,-45,-30,-15,-10,-5,-1,0.0,1,5,10,15,30,45)', 'caption=Pan');
        $form->FNC('tilt', 'enum(0,3,9,12,15,18,21,24,27,30,35,40,45,50,55,60,65,70,75,80,85,90)', 'caption=Tilt');
        $form->FNC('rzoom', 'enum(0,-5,-4,-3,-1,1,2,3,4,5)', 'caption=Zoom');
        
        $form->showFields = 'rpan,tilt,rzoom';
        $form->view = 'horizontal';
        $form->toolbar->addSbBtn('Изпълни', 'default', 'target=rcFrame');
    }
    
    
    /**
     * Изпълнява отдалечените команди
     */
    function applyPtzCommands($cmdArr)
    {
    	return;
    }


    /**
     * Вземане на картинка от MotionJped
     */
    function getImageFromMjpeg($url)
    {
        $f = fopen($url, "r");
        
        if(!$f) return FALSE;
        
        while ((substr_count(strtolower($r), "content-type") != 2) && strlen($r) < 200000) $r .= fread($f, 512);
        
        if(!$r || (strlen($r) >= 200000)) return FALSE;
        
        $boundary = "\r\n--";
        
        $soi = chr(0xff) . chr(0xd8);
        $soi = strpos($r, $soi);
        
        if(!$soi) return FALSE;
        
        $end = strpos($r, $boundary, $soi) ;
        
        if(!$end) return FALSE;
        
        $frame = substr("$r", $soi, $end - $soi);
        
        $eoi = chr(0xff) . chr(0xd9);
        $eoi = strrpos($frame, $eoi);
        
        if(!$eoi) return FALSE;
        
        $frame = substr($frame, 0, $eoi + 2);
        
        fclose($f);
        
        return $frame;
    }
}