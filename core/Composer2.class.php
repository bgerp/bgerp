<?php

/**
 * Път до директория, където ще се съхраняват записите от камерите
 */
defIfNot('COMPOSER_FILES_PATH', EF_UPLOADS_PATH . '/composer');


/**
 * 
 * @category  core
 * @package   core
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class core_Composer2
{
    
    /**
     * URL, от което се сваля композера
     */
    const COMPOSER_URL =  'http://getcomposer.org/installer';
    
    
    /**
     * Добавя пакет в композера
     * 
     * @param string  $pack
     * @param string  $version
     * @param boolean $force
     */
    public static function install($pack, $version, $force = false)
    {
        $jsonObj = self::getJsonObj();
        if (!$jsonObj->require) {
            $jsonObj->require = new stdClass();
        }
        $jsonObj->require->{$pack} = $version;
        
        self::setJsonObj($jsonObj);
        
        if ($force) {
            self::updateComposer();
        }
    }
    
    
    /**
     * Премахва пакет от композера
     * 
     * @param string  $pack
     * @param boolean $force
     */
    public static function deinstall($pack, $force = false)
    {
        $jsonObj = self::getJsonObj();
        if (!$jsonObj->require) {
            
            return ;
        }
        unset($jsonObj->require->{$pack});
        
        self::setJsonObj($jsonObj);
        
        if ($force) {
            self::updateComposer();
        }
    }
    
    
    /**
     * Автоматично зареждане на autoload.php файла
     */
    public static function enableAutoload()
    {
        $path = self::getComposerPath();
        
        $autoloadPath = $path . 'vendor/autoload.php';
        
        if (@is_file($autoloadPath)) {
            require_once($autoloadPath);
        }
    }
    
    
    /**
     * Обновява пакетите записани в `json` обекта и ги добавя във `vendor`
     */
    public static function updateComposer()
    {
        $composerPath = self::getComposerPath();
        
        $pharPath = self::getComposerPharPath();
        if (!@is_file($pharPath)) {
            self::downloadPhar();
        }
        
        expect(is_file($pharPath));
        
        $pharPath = escapeshellarg($pharPath);
        $composerPath = escapeshellarg($composerPath);
        
        $cPathHomePath = self::getComposerHomeDir();
        $cPathHomePath = escapeshellarg($cPathHomePath);
        
        $res = exec("COMPOSER_HOME={$cPathHomePath} php {$pharPath} update -d {$composerPath}", $o, $v);
        
        expect($v === 0, $o, $v, $res);
    }
    
    
    /**
     * Функция за вземана на директорията на композера
     * 
     * @return string
     */
    protected static function getComposerPath()
    {
        $path = COMPOSER_FILES_PATH;
        
        $path = rtrim($path, '/');
        
        $path .= '/';
        
        return $path;
    }
    
    
    /**
     * Функция за вземане на `json` пакета на композера
     * 
     * @return string
     */
    protected static function getJsonPath()
    {
        $path = self::getComposerPath();
        $path .= 'composer.json';
        
        return $path;
    }
    
    
    /**
     * Функция за вземане на обект от JSON файла
     * 
     * @return stdClass|mixed
     */
    protected static function getJsonObj()
    {
        $jsonPath = self::getJsonPath();
        
        $jsonStr = @file_get_contents($jsonPath);
        
        if ($jsonStr !== false) {
            $jsonObj = json_decode($jsonStr);
        }
        
        if (!is_object($jsonObj)) {
            $jsonObj = new stdClass();
        }
        
        return $jsonObj;
    }
    
    
    /**
     * Записва обекта в JSON формат
     * 
     * @param stdClass $jsonObj
     */
    protected static function setJsonObj($jsonObj)
    {
        $composerPath = self::getComposerPath();
        
        if (!is_dir($composerPath)) {
            expect(mkdir($composerPath, 0777, true), 'Не може да се създаде директория.');
        }
        
        $jsonPath = self::getJsonPath();
        $jsonStr = json_encode($jsonObj);
        
        expect(file_put_contents($jsonPath, $jsonStr));
    }
    
    
    /**
     * Връща името на архива на композера
     * 
     * @return string
     */
    protected static function getComposerPharName()
    {
        
        return 'composer.phar';
    }
    
    
    /**
     * Връща директорията на композера
     * 
     * @return string
     */
    protected static function getComposerPharPath()
    {
        
        return self::getComposerPath() . self::getComposerPharName();
    }
    
    
    /**
     * Сваля композера
     */
    protected static function downloadPhar()
    {
        $cPath = self::getComposerPath();
        
        $cTempName = 'composer-temp.php';
        
        $cTempPath = $cPath . $cTempName;
        
        $cTempPathEsc = escapeshellarg($cTempPath);
        $url = escapeshellarg(self::COMPOSER_URL);
        
        $res = exec("wget -O {$cTempPathEsc} {$url}", $o, $v);
        
        expect($v === 0, $o, $v, $res);
        
        $cPathHomePath = self::getComposerHomeDir();
        $cPathHomePath = escapeshellarg($cPathHomePath);
        
        $cPath = escapeshellarg($cPath);
        $cTempName = escapeshellarg($cTempName);
        
        unset($o);
        unset($v);
        $res = exec("COMPOSER_HOME={$cPathHomePath} php {$cTempPathEsc} --install-dir={$cPath}", $o, $v);
        expect($v === 0, $o, $v, $res);
        
        @unlink($cTempPath);
    }
    
    
    /**
     * Връща стойност за COMPOSER_HOME
     * 
     * @return string
     */
    protected static function getComposerHomeDir()
    {
        
        return self::getComposerPath() . '.composer';
    }
}
