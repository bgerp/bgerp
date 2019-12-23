<?php


/**
 * Клас 'thumb_M' - Контролер за умалени изображения
 *
 *
 * @category  vendors
 * @package   thumb
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 *
 */
class thumb_M extends core_Mvc
{
    /**
     * Масив с файлове за оптимизиране от външни програми
     *
     * $path => $type
     */
    public $forOptimization = array();
    
    
    /**
     * Имидж, който е бил зареден през екшъна
     */
    protected $thumb;
    
    
    public function act_R()
    {
        $id = Request::get('t');
        
        // Премахва фиктивното файлово разширение
        list($id, $ext) = explode('.', $id);
        
        $arguments = core_Crypt::decodeVar($id, thumb_Img::getCryptKey());
        
        $this->thumb = new thumb_Img($arguments);
        
        if (file_exists($file = $this->thumb->getThumbPath())) {
            $ext = fileman_Files::getExt($file);
            self::addTypeHeader($ext);
            header('Content-Length: ' . filesize($file));
            readfile($file);
            
            shutdown();
        } else {
            self::addTypeHeader($ext);
            
            return new Redirect($this->thumb->getUrl('forced'));
        }
    }
    
    
    /**
     * Добавя хедър за mime тип, в зависимост от разширението на графичния файл
     */
    public static function addTypeHeader($ext)
    {
        $typeByExt = array('jpg' => 'jpeg', 'jpeg' => 'jpeg', 'gif' => 'gif', 'bmp' => 'bmp', 'png' => 'png');
        if ($type = $typeByExt[strtolower($ext)]) {
            header("Content-Type: image/{$type}");
        }
    }
    
    
    /**
     * Изпълнява се на затваряне
     */
    public function on_Shutdown()
    {
        if (isset($this->thumb)) {
            $this->thumb->getUrl('forced');
        }
        
        if (count($this->forOptimization)) {
            $optmizators = arr::make(thumb_Setup::get('OPTIMIZATORS'), true);
            
            foreach ($this->forOptimization as $path => $type) {
                foreach ($optmizators as $o) {
                    list($program, $t) = explode('/', $o);
                    $program = trim($program);
                    $t = trim($t);
                    if ($t == $type) {
                        $this->execCmd($program, $path);
                    }
                }
            }
        }
    }
    
    
    /**
     *  Изпълнява команда за оптимизиране на графичен файл
     */
    private function execCmd($optimizer, $path)
    {
        $out = array();
        $status = 0;
        $oPath = $path;
        $path = escapeshellarg($path);
        $cmd = constant(strtoupper($optimizer) . '_CMD');
        $cmd = str_replace('[#path#]', $path, $cmd);
        
        static $hashArr = array();
        $cmdHash = md5($cmd);
        if ($hashArr[$cmdHash]) {
            
            return ;
        }
        $hashArr[$cmdHash] = $cmd;
        
        if (core_Locks::get($cmd, 1, 1)) {
            exec($cmd, $out, $status);
            if ($status > 0) {
                $err = implode(' | ', $out);
                log_System::add('thumb_Img', 'Грешка: ' . $cmd  . ' ' . $err, null, 'warning');
                
                wp($this, is_file($oPath), is_readable($oPath), $cmd, $out, $status);
            } else {
                log_System::add('thumb_Img', 'Оптимизирано: ' . $cmd, null, 'debug');
            }
            
            core_Locks::release($cmd);
        } else {
            log_System::add('thumb_Img', 'Прескочено оптимизиране, защото е било заключено: ' . $cmd, null, 'debug');
        }
    }
    
    
    /**
     * Изтриване на кешираните изображения
     */
    public function act_Clear()
    {
        $deleted = core_Os::deleteOldFiles(thumb_Setup::get('IMG_PATH'), 1);
        
        return followRetUrl(null, "Изтрити са|* {$deleted} |файла.");
    }
}
