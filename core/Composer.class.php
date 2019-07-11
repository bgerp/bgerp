<?php


defIfNot('EF_VENDOR_PATH', rtrim(dirname(EF_APP_PATH), '/\\') . '/vendor');


/**
 * Клас 'core_Composer' - Управление на външни пакети чрез Composer
 *
 *
 * @category  bgerp
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Composer extends core_Mvc
{
    /**
     * Описание на последната грешка
     */
    public static $error;
    
    
    /**
     * Връща пътя към изпълнимия файл на Composer.
     * Ако Composer не е инсталиран - прави опит да го инсталира
     */
    public static function getComposerPath()
    {
        static $path;
        
        if (!$path) {
            $path = core_Cache::get('COMPOSER', 'PATH');
        }
        
        if (!$path || !file_exists($path)) {
            $phpCmd = core_Os::getPhpCmd();
            
            if (!$phpCmd) {
                self::$error = 'Error: Невъзможно да се определи изпълнимият файл за извикване на PHP от командна линия';
                
                return;
            }
            
            $composerCmd = dirname(EF_VENDOR_PATH) . '/composer.phar';
            
            $composerHome = self::getComposerHome();
            
            if (!file_exists($composerCmd)) {
                $sig = trim(file_get_contents('https://composer.github.io/installer.sig'));
                
                $setupPath = dirname(EF_VENDOR_PATH) . '/composer-setup.php';
                
                file_put_contents($setupPath, file_get_contents('https://getcomposer.org/installer'));
                
                if ($sig == hash_file('sha384', $setupPath)) {
                    $cmd = $composerHome . ' "' . $phpCmd . '" ' . $setupPath . ' --quiet --install-dir=' . dirname(EF_VENDOR_PATH);
                    exec($cmd, $output, $returnvar);
                    if ($returnvar != 0) {
                        self::$error = "Грешка (${cmd}):" . implode('; ', $output);
                    }
                } else {
                    self::$error = 'Грешка: неточна SHA384 сигнатура';
                }
            } else {
                exec($composerHome . ' "' . $phpCmd . '" ' . $composerCmd . ' self-update --quiet', $output, $returnvar);
            }
            
            if (file_exists($setupPath)) {
                unlink($setupPath);
            }
            
            if (file_exists($composerCmd)) {
                $path = $composerCmd;
                
                core_Cache::set('COMPOSER', 'PATH', $path, 20);
            } else {
                $path = false;
            }
        }
        
        return $path;
    }
    
    
    /**
     * Инсталира зададения пакет и версия
     */
    public static function install($pack, $version = '')
    {
        $phpCmd = core_Os::getPhpCmd();
        if (!$phpCmd) {
            
            return 'Грешка: Невъзможно да се определи изпълнимият файл за извикване на PHP от командна линия';
        }
        
        $composerPath = self::getComposerPath();
        if (!$composerPath) {
            
            return self::$error;
        }
        
        $dir = '--working-dir=' . dirname(EF_VENDOR_PATH);
        
        $composerHome = self::getComposerHome();
        
        $cmd = "{$composerHome} {$phpCmd} {$composerPath} {$dir} --apcu-autoloader require {$pack} {$version}";
        
        exec($cmd, $lines, $result);
        
        if ($result !== 0) {
            
            return "Грешка (${cmd}): " . implode('; ', $lines);
        }
    }
    
    
    /**
     * Връща местоположението на COMPOSER_HOME
     *
     * @return string
     */
    protected static function getComposerHome()
    {
        $composerHome = EF_VENDOR_PATH . '/.composer';
        $composerHome = 'COMPOSER_HOME=' . escapeshellarg($composerHome);
        
        return $composerHome;
    }
    
    
    /**
     * Инсталиране на пакет, чрез bowerphp
     */
    public static function installBower($pack, $version = null)
    {
        $bowerphp = EF_VENDOR_PATH . '/beelab/bowerphp/bin/bowerphp';
        if (!is_dir($bowerphp)) {
            $res = self::install('beelab/bowerphp');
            if (strlen($res)) {
                
                return $res;
            }
        }
        
        $phpCmd = core_Os::getPhpCmd();
        if (!$phpCmd) {
            
            return 'Грешка: Невъзможно да се определи изпълнимият файл за извикване на PHP от командна линия';
        }
        
        if ($version) {
            $pack = "{$pack}#{$version}";
        }
        
        $composerHome = self::getComposerHome();
        
        $wd = '--working-dir=' . EF_VENDOR_PATH;
        $cmd = "{$composerHome} \"{$phpCmd}\" \"${bowerphp}\" install {$pack} {$wd}";
        
        exec($cmd, $lines, $result);
        
        if ($result != 0) {
            
            return "Грешка (${cmd}):" . implode('; ', $lines);
        }
    }
    
    
    /**
     * Помощна функция за тестване
     */
    public function act_Test()
    {
        requireRole('debug');
        
        $pack = Request::get('pack');
        
        if (Request::get('bower')) {
            $res = self::installBower($pack);
        } else {
            $res = self::install($pack);
        }
        
        return $res;
    }
}
