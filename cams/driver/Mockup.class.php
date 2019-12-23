<?php


/**
 * Прототип на драйвер за IP камера UIC
 *
 *
 * @category  bgerp
 * @package   cams
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cams_driver_Mockup extends cams_driver_IpDevice
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'cams_DriverIntf';
    
    
    /**
     * Записва видео в указания файл с продължителност $duration
     */
    public function captureVideo($savePath, $duration)
    {
        copy(dirname(__FILE__) . '/example.mp4', $savePath);
    }
    
    
    /**
     * Записва снимка от камерата в указания файл;
     */
    public function getPicture()
    {
        if ($this->running == 'yes') {
            $file = 'example' . rand(1, 3) . '.jpg';
        } else {
            $file = 'setup.jpg';
        }
        $img = file_get_contents(dirname(__FILE__) . '/' . $file);
        $img = imageCreatefromString($img);
        
        return $img;
    }
    
    
    /**
     * Конвертира указания файл (записан от този драйвер) към flv файл
     */
    public function convertToFlv($mp4Path, $flvPath)
    {
        copy(dirname(__FILE__) . '/example.flv', $flvPath);
    }
    
    
    /**
     * Нулиране състоянието на камерата
     */
    public function reset()
    {
        $a = 1;
    }
    
    
    /**
     * Начално установяване на драйвера с параметрите за конкретното устройство
     */
    public function init($params = array())
    {
        parent::init($params);
        
        setIfNot($this->width, 720);
        setIfNot($this->height, 600);
    }
    
    
    /**
     * Подготвя формата за PTZ контрола
     */
    public function preparePtzForm($form)
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
    public function applyPtzCommands($cmdArr)
    {
    }
    
    
    /**
     * Подготвя формата за настройки на камерата
     */
    public function prepareSettingsForm($form)
    {
        $form->FNC('running', 'enum(yes=Активно,no=Спряно)', 'caption=Състояние,hint=Дали камерата да се наблюдава?,input');
    }
    
    
    /**
     * Проверява дали състоянието е активно
     */
    public function isActive()
    {
        return $this->running == 'yes';
    }
    
    
    /**
     * Дали има отдалечено управление?
     */
    public function havePtzControl()
    {
        return $this->ptzControl == 'yes';
    }
    
    
    /**
     * Проверява дали данните във формата са въведени правилно
     */
    public function validateSettingsForm($form)
    {
    }
}
