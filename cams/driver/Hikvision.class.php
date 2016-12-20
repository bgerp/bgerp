<?php



/**
 * Драйвер за IP камера HIKVISION DS-2CD2042WD-I
 *
 *
 * @category  bgerp
 * @package   cams
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cams_driver_Hikvision extends cams_driver_IpDevice {
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        
        parent::init($params);
        
        setIfNot($this->width, 2688); //2688x1520, 1920x1080, 1280x720
        setIfNot($this->height, 1520);
        
        setIfNot($this->user, 'admin');
        setIfNot($this->password, 'Admin555');
                
        setIfNot($this->rtspPort, 554);
        
        setIfNot($this->httpPort, 80);
    }
    
    
    /**
     * Подготвя формата за настройки на камерата
     */
    function prepareSettingsForm($form)
    {
        $form->FNC('ip', 'ip',
            'caption=IP,hint=Въведете IP адреса на камерата,input, mandatory');
        $form->FNC('codec', 'enum(h264=H.264)', 'caption=Кодек,hint=Кодек на RTSP стрийма,input');
        $form->FNC('width', 'int(min=320,max=2688)', 'caption=Ширина,hint=Хоризонтална резолюция,input');
        $form->FNC('height', 'int(min=240,max=1520)', 'caption=Височина,hint=Вертикална резолюция,input');
        $form->FNC('FPS', 'int(min=1,max=30)', 'caption=Скорост,hint=Скорост на записа (fps),input');
        $form->FNC('user', 'varchar(64)', 'caption=Потребител,hint=Въведете потребителското име за администратора на камерата,input');
        $form->FNC('password', 'password(show)', 'caption=Парола,hint=Въведете паролата за администратора на камерата,input');
        $form->FNC('running', 'enum(yes=Активно,no=Спряно)', 'caption=Състояние,hint=Дали камерата да се наблюдава?,input');
        $form->FNC('rtspPort', 'int(min=1,max=65535)', 'caption=Порт->Rtsp,hint=Въведете порта за Mpeg4 потока,input');
        $form->FNC('httpPort', 'int(min=1,max=65535)', 'caption=Порт->Http,hint=Въведете порта за CGI заявките,input');
    }

    /**
     * Записва снимка от RTSP стрийма на камерата в указан файл
     * Изпълнява се в родителя - тук е само ако вадим шот-а от стрийма
     */
//     public function getPicture()
//     {
//     	if(!$this->isActive()) {
//     		$img = imagecreatefromjpeg(dirname(__FILE__) . '/setup.jpg');
//     	} else {
//     		$url = $this->getPictureUrl(); 

//     		// С тази команда вадим скрийншот от RTSP стрийма
//     		$cmd = "avconv -i ". $this->getStreamUrl() . " -vframes 1 -r 1 -f image2 " . $url;
//     		exec($cmd, $output, $return_var);

//     		// Ако имаме реален файл предполагаме, че е картинка
//     		if (is_file($url)) {
//     			$img = imagecreatefromjpeg($url);
//     		}
			
//     		if(!$img) {
    
//     			$img = imagecreatefromjpeg(dirname(__FILE__) . '/nocamera.jpg');
//     		}
//     	}
    
//     	return $img;
//     }
    
    
    /**
     * Подготвя формата за PTZ контрола
     */
    function preparePtzForm($form)
    {
    }
    
    
    /**
     * Изпълнява отдалечените команди
     */
    function applyPtzCommands($cmdArr)
    {
    	return;
    }

	function normalizeCameraId()
	{
		$res = str_replace("-", "", $this->cameraId);
		
		return $res;
	}
}