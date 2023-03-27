<?php


/**
 * Драйвер за IP камера поддържаща ONVIF
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
class cams_driver_ONVIF extends cams_driver_IpDevice
{
    private $profileToken;
    
    private $mediaUri;
    
    private $mediaSnapshotUri;
    
    
    /**
     * Инициализиране на обекта
     */
    public function init($params = array())
    {
        parent::init($params);

        setIfNot($this->user, 'user');
        setIfNot($this->password, 'Admin555');
        
        require_once (EF_ROOT_PATH . '/' . EF_APP_CODE_NAME . '/cams/ponvif/lib/class.ponvif.php');
        
        $this->onvif = new Ponvif();
        
        $this->onvif->setUsername($this->user);
        $this->onvif->setPassword($this->password);
        $this->onvif->setIPAddress($this->ip);
        
        try
        {
            $this->onvif->initialize();
            
            $sources = $this->onvif->getSources();
            $this->profileToken = $sources[0][0]['profiletoken'];
            $this->mediaUri = $this->onvif->media_GetStreamUri($this->profileToken);
            $this->mediaSnapshotUri = $this->onvif->media_GetSnapshotUri($this->profileToken);
        } catch (Exception $e) {
            
        }
    }
    
    protected function getPictureUrl()
    {
        $scheme = parse_url($this->mediaSnapshotUri, PHP_URL_SCHEME);
        $this->mediaSnapshotUri = str_replace($scheme . "://", $scheme . "://" . $this->user . ":" . $this->password . "@", $this->mediaSnapshotUri);
        
        return $this->mediaSnapshotUri;
    }
    
    /**
     * Подготвя формата за настройки на камерата
     */
    public function prepareSettingsForm($form)
    {
        $form->FNC(
            'ip',
            'ip',
            'caption=IP,hint=Въведете IP адреса на камерата,input, mandatory'
        );
        $form->FNC('user', 'varchar(64)', 'caption=Потребител,hint=Въведете ONVIF потребител на камерата,input');
        $form->FNC('password', 'password(show)', 'caption=Парола,hint=Въведете паролата ,input');
        $form->FNC('ptzControl', 'enum(yes=Има,no=Няма)', 'caption=PTZ контрол,hint=Има ли камерата PTZ контрол?,input');
        $form->FNC('running', 'enum(yes=Активно,no=Спряно)', 'caption=Състояние,hint=Дали камерата да се наблюдава?,input');
    }
    
    
    /**
     * Подготвя формата за PTZ контрола
     */
    public function preparePtzForm($form)
    {
        $form->FNC('rpan', 'enum(0,наляво,надясно)', 'caption=Pan');
        $form->FNC('tilt', 'enum(0,нагоре,надолу)', 'caption=Tilt');
        $form->FNC('rzoom', 'enum(0,приближи,отдалечи)', 'caption=Zoom');
        
        $form->showFields = 'rpan,tilt,rzoom';
        //$form->view = 'horizontal';
        $form->toolbar->addSbBtn('Изпълни', 'default', 'target=rcFrame');
    }
    
    
    /**
     * Изпълнява отдалечените команди
     */
    public function applyPtzCommands($cmdObj)
    {
        switch ($cmdObj->rpan) {
            case 'наляво':
                $this->onvif->ptz_RelativeMove($this->profileToken,-0.05,0,0,-0.05,0);
                break;
            case 'надясно':
                $this->onvif->ptz_RelativeMove($this->profileToken,0.05,0,0,0.05,0);
                break;
        }
        switch ($cmdObj->tilt) {
            case 'надолу':
                $this->onvif->ptz_RelativeMove($this->profileToken,0,-0.05,0,0,-0.05);
                break;
            case 'нагоре':
                $this->onvif->ptz_RelativeMove($this->profileToken,0,0.05,0,0,0.05);
                break;
        }
        switch ($cmdObj->rzoom) {
            case 'приближи':
                $this->onvif->ptz_RelativeMoveZoom($this->profileToken,0.01,0.01);
                break;
            case 'отдалечи':
                $this->onvif->ptz_RelativeMoveZoom($this->profileToken,-0.01,0.01);
                break;
        }
    }
    
    public function normalizeCameraId()
    {
    }
}
