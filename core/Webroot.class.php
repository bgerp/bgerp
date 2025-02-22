<?php


/**
 * Път до директория, където ще се съхраняват записите от камерите
 */
defIfNot('WEBROOT_FILES_PATH', EF_UPLOADS_PATH . '/wrfiles');


/**
 * Клас 'core_Webroot' - Виртуални статични файлове в коренната директория на уеб-сървъра
 *
 *
 * @category  bgerp
 * @package   core
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Webroot
{
    /**
     * Регистрира статичен файл в корена на посочения домейн
     * Този файл ще се сервира при търсене в корена на посочения домейн
     */
    public static function register($contents, $headers, $filename, $domain = null)
    {
        $path = self::getPath($filename, $domain);
        
        file_put_contents($path, $contents);
        
        if (strpos($headers, 'Pragma:') === false) {
            $headers .= "\nPragma: public";
        }
        
        if (strpos($headers, 'Cache-Control:') === false) {
            $headers .= "\nCache-Control: max-age=10800";
        }
        
        if (strpos($headers, 'Expires:') === false) {
            $headers .= "\nExpires: {{time_10800}}";
        }
        
        if (strpos($headers, 'Content-Type:') === false) {
            $headers .= "\nContent-Type: " . fileman_Mimes::getMimeByExt(fileman_Files::getExt($filename));
        }
        
        if (strpos($headers, 'Content-Length:') === false) {
            $headers .= "\nContent-Length: " . filesize($path);
        }
        
        file_put_contents($path . '.headers', $headers);
    }
    
    
    /**
     * Регистрира статичен файл в корена на посочения домейн
     * Този файл ще се сервира при търсене в корена на посочения домейн
     */
    public static function remove($filename, $domain = null)
    {
        $path = self::getPath($filename, $domain);
        
        if (file_exists($path)) {
            @unlink($path);
        }
        
        $path = $path . '.headers';
        if (file_exists($path)) {
            @unlink($path);
        }
    }
    
    
    /**
     * Проверява дали посоченият файл съществува
     */
    public static function isExists($filename, $domain = null)
    {
        $path = self::getPath($filename, $domain);
        
        if (file_exists($path)) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Сервира посочения файл за посочения домей (ако не е посочен - текущия)
     */
    public static function serve($filename, $domain = null)
    {
        $path = self::getPath($filename, $domain);
        
        if (file_exists($path)) {
            // Показваме хедърите
            $headers = @file_get_contents($path . '.headers');
            if ($headers) {
                $headers = preg_replace_callback('/\{\{(.*?)\}\}/', array(get_called_class(), 'replaceHeaderPattern'), $headers);
                
                $hArr = explode("\n", $headers);
                foreach ($hArr as $h) {
                    if (strlen($h) > 0) {
                        header($h);
                    }
                }
            }
            
            // Сервираме файла
            readfile($path);
            
            // Прекъсваме изпълнението
            shutdown();
        } else {
            error('404 @Липсващ файл', $filename, $_GET, $_POST, $domain);
        }
    }
    
    
    /**
     * Помощна функция за заместване на шаблоните в хедърите
     * 
     * @param array $match
     * 
     * @return string
     */
    protected static function replaceHeaderPattern($match)
    {
        $res = '';
        try {
            list($f, $v) = explode('_', $match[1]);
            if ($f == 'time') {
                $res = gmdate('D, d M Y H:i:s \G\M\T', time() + $v);
            } else {
                expect(false, $match);
            }
        } catch (core_exception_Expect $e) {
            reportException($e);
        }
        
        return $res;
    }
    

    /**
     * Връща съдържанието на посочения файл
     */
    public static function getContents($filename, $domain = null)
    {
        $path = self::getPath($filename, $domain);
        
        $res = file_get_contents($path);

        return $res;
    }
    

    /**
     * Връща път до посочения файл в UPLOADS
     */
    private static function getPath($filename, $domain)
    {
        $filename = strtolower($filename);

        core_Os::requireDir(WEBROOT_FILES_PATH);
        
        expect(is_writable(WEBROOT_FILES_PATH));
        
        if (is_numeric($domain)) {
            $dRec = cms_Domains::fetch($domain);
            if ($dRec) {
                $domain = cms_Domains::getReal($dRec->domain);
            } else {
                $domain = '';
            }
        }
        
        if (!$domain) {
            $domain = cms_Domains::getReal(cms_Domains::getPublicDomain('domain'));
        }
        
        $domain = trim(strtolower(preg_replace('/[^a-z0-9]+/', '_', $domain)), '_');
        
        $path = rtrim(WEBROOT_FILES_PATH, '/') . '/' . $domain . '_' . $filename;

        return $path;
    }
}
