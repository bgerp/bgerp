<?php
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
     * Текущо инсталираните пакети
     */
    static $packs = array();
    

    /**
     * Дали трябва да се използва Composer
     */
    public static function isInUse()
    {
        $res = false;
        if(defined('EF_VENDOR_PATH')) {
            $autoloaderPath = EF_VENDOR_PATH . '/autoload.php';
            if(file_exists($autoloaderPath)) {
                require_once($autoloaderPath);
            }
            $res = true;
        }

        return $res;
    }


    /**
     * Стартира команда на Composer
     * Ако Composer не е инсталиран - прави опит да го инсталира
     */
    public static function run($command)
    {
        static $path;

        $res = false;

        if(!defined('EF_VENDOR_PATH')) {

            self::$error = 'Error: Не е дефинирана константата `EF_VENDOR_PATH`';

            return false;
        }

        if (!$path) {
            $path = core_Cache::get('COMPOSER', 'PATH');
        }
        
        $phpCmd = core_Os::getPhpCmd();
            
        if (!$phpCmd) {
            self::$error = 'Error: Невъзможно да се определи изпълнимият файл за извикване на PHP от командна линия';
                
            return false;
        }

        if (!$path || !file_exists($path)) {
            
            $composerCmd = dirname(EF_VENDOR_PATH) . '/composer.phar';
                        
            if (!file_exists($composerCmd)) {
                $sig = trim(file_get_contents('https://composer.github.io/installer.sig'));
                
                $setupPath = dirname(EF_VENDOR_PATH) . '/composer-setup.php';
                
                file_put_contents($setupPath, file_get_contents('https://getcomposer.org/installer'));
                
                if ($sig == hash_file('sha384', $setupPath)) {
                    $cmd =  '"' . $phpCmd . '" ' . $setupPath . ' --quiet --install-dir=' . dirname(EF_VENDOR_PATH);
                    putenv('COMPOSER_HOME=' . EF_VENDOR_PATH . '/.composer');
                    exec($cmd, $output, $returnvar);
                    if ($returnvar != 0) {
                        self::$error = "Грешка (${cmd}):" . implode('; ', $output);

                        return false;
                    }
                } else {
                    self::$error = 'Грешка: неточна SHA384 сигнатура';

                    return false;
                }
            }

            if (file_exists($setupPath)) {
                unlink($setupPath);
            }
            
            if (file_exists($composerCmd)) {
                $path = $composerCmd;
                exec('"' . $phpCmd . '" ' . $composerCmd . ' self-update --quiet', $output, $returnvar);
                core_Cache::set('COMPOSER', 'PATH', $path, 20);
            } else {
                self::$error = "Composer не може да бъде инсталиран";

                return false;
            }
        }
        
        if($path) {
            // Изпълняваме командата
            $dir = '--working-dir=' . dirname(EF_VENDOR_PATH);
            
            putenv('COMPOSER_HOME=' . EF_VENDOR_PATH . '/.composer');

            $cmd = "{$phpCmd} {$path} {$dir} {$command}";
 
            exec($cmd, $lines, $result);
           
            if ($result !== 0) {
                self::$error = 'Error: ' . implode('; ', $lines);
            } else {
                $res = $lines;
            }
        }
        
        return $res;
    }

    
    /**
     * Инсталира зададения пакет и версия
     *
     * @return string
     */
    public static function install($pack, $version = '')
    {
        if(self::isInstalled($pack, $version)) {

            return "<li><strong>Composer require</strong>: пакета `{$pack} {$version}` е бил инсталиран от по-рано</li>";
        }

        $lines = self::run("--apcu-autoloader require {$pack} {$version}");
        
        if ($lines === false) {
            
            return "<li class='debug-error'>" . self::$error . '</li>';
        } else {
            core_Cache::remove('COMPOSER', 'INSTALLED-PACKS');
            self::$packs = null;

            return "<li class='debug-new'><strong>Composer</strong>: инсталиран е `{$pack} {$version}`";
        }
    }


    /**
     * Премахва зададения пакет
     *
     * @return string
     */
    public static function remove($pack)
    {
        if(!self::isInstalled($pack)) {

            return "<li><strong>Composer remove</strong>: пакета {$pack} не е бил инсталиран по-рано</li>";
        }

        $lines = self::run("--apcu-autoloader remove {$pack}");
        
        if ($lines === false) {
            
            return "<li class='debug-error'>" . self::$error . '</li>';
        } else {
            core_Cache::remove('COMPOSER', 'INSTALLED-PACKS');
            self::$packs = null;

            return "<li class='debug-new'><strong>Composer</strong>: премахнат е `{$pack}`";
        }
    }


    /**
     * Проверява дали даден пакет е инсталиран
     */
    public static function isInstalled($pack, $version = null)
    {
        if(!countR(self::$packs)) {
            self::$packs =  core_Cache::get('COMPOSER', 'INSTALLED-PACKS');
        }

        if(!countR(self::$packs)) {
            
            $lines = self::run('show');
            
            if($lines === false) {

                return false;
            }

            foreach($lines as $l) { 
                $matches = array();
                preg_match_all("/^([a-z0-9\.\/\_\-]+)[ ]+([v0-9\.]+)/", $l, $matches);
 
                $p = $matches[1][0];
                
                $ver = $matches[2][0];
                self::$packs[$p] = ltrim(trim($ver), 'v');
            }
 
            core_Cache::set('COMPOSER', 'INSTALLED-PACKS', self::$packs, 20);
        }
        
        $res = false;
        if(isset(self::$packs[$pack])) {
            $res = true;
            if(strlen($version) && !version_compare(trim(self::$packs[$pack]), ltrim($version, 'v'), '>=')) {
                $res = false;
            }
        }

        return $res;
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
        
        $wd = '--working-dir=' . EF_VENDOR_PATH;
        $cmd = "{$composerHome} \"{$phpCmd}\" \"${bowerphp}\" install {$pack} {$wd}";
        putenv('COMPOSER_HOME=' . EF_VENDOR_PATH . '/.composer');
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
