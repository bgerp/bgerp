<?php


use Aws\S3\S3Client;

/**
 * Модул backUp чрез Amazon Web Services (главно S3)
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 *
 * @uses      Composer and Amazon SDK
 */
class backup_Amazon extends core_BaseClass
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'backup_StorageIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Архивиране в Amazon';
    
    private static $s3Client;
    private static $bucket;
    
    /** Зарежда избраната съвместима SDK версия преди създаване на S3 клиента. */
    public function __construct()
    {
        $error = backup_Setup::getPhpVersionError(backup_Setup::get('AWS_VERSION'));
        expect(!$error, $error);
        expect(core_Composer::isInUse() && class_exists(S3Client::class),
            'Липсва AWS SDK. Инициализирайте пакета backup с избрано S3 съхранение.');
        self::$s3Client = new S3Client([
            'version' => 'latest',
            'region' => 'eu-west-1',
            // PHP 7.4 клонът е избран съзнателно; статусът е описан в CHANGES-BGERP.txt.
            'suppress_php_deprecation_warning' => PHP_VERSION_ID < 80100,
            'credentials' => [
                'key' => backup_Setup::get('AMAZON_KEY', true),
                'secret' => backup_Setup::get('AMAZON_SECRET', true),
            ],
        ]);

        self::$bucket = backup_Setup::get('AMAZON_BUCKET', true);
    }
    
    
    /**
     * Копира файл съхраняван в сторидж на Amazon система в
     * посоченото в $fileName място
     *
     * Част от интерфейса: backup_StorageIntf
     *
     * @param $sourceFile
     * @param $destFile
     *
     * @return bool
     *
     */
    public static function getFile($sourceFile, $destFile)
    {
        try {
            $object = self::$s3Client->getObject(
                array(
                    'Bucket' => self::$bucket,
                    'Key' => $sourceFile,
                    'SaveAs' => $destFile
                )
            );
        } catch (core_exception_Redirect $e) {
            throw $e;
        } catch (Exception $e) {
            $object = false;
        }
        
        return $object ?  true : false;
    }
    
    
    /**
     * Записва файл в Amazon архива
     *
     * Част от интерфейса: backup_StorageIntf
     *
     * @param $sourceFile
     * @param null $subDir
     *
     * @return bool
     *
     */
    public static function putFile($sourceFile, $subDir = null)
    {
        if (!is_string($sourceFile) || $sourceFile === '' || !is_file($sourceFile)) {
            return false;
        }
        $key = $subDir ?  $subDir . '/' . basename($sourceFile) : basename($sourceFile);
        $stream = @fopen($sourceFile, 'rb');
        if (!$stream) {
            return false;
        }
        
        try {
            $result = self::$s3Client->putObject(
                array(
                    'Bucket' => self::$bucket,
                    'Key' => $key,
                    'Body' => $stream
                )
            );
        } catch (core_exception_Redirect $e) {
            throw $e;
        } catch (Exception $e) {
            $result = false;
        } finally {
            if (is_resource($stream)) fclose($stream);
        }
        
        return $result ? true : false;
    }
    
    
    /**
     * Изтрива файл в Amazon архива
     *
     * Част от интерфейса: backup_StorageIntf
     *
     * @param $sourceFile
     *
     * @return bool
     *
     */
    public static function removeFile($sourceFile)
    {
        try {
            $result = self::$s3Client->deleteObject(
                array(
                    'Bucket' => self::$bucket,
                    'Key' => $sourceFile,
                )
            );
        } catch (core_exception_Redirect $e) {
            throw $e;
        } catch (Exception $e) {
            $result = false;
        }
        
        return $result ? true : false;
    }
}
