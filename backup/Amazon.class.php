<?php

require __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;


/**
 * Модул backUp чрез Amazon Web Services (главно S3)
 *
 * @category  bgerp
 * @package   cond
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
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

    function __construct()
    {
        self::$s3Client = new S3Client([
            'version'     => 'latest',
            'region'      => 'eu-west-1',
            'credentials' => [
                'key'    => backup_Setup::get('AMAZON_KEY',true),
                'secret' => backup_Setup::get('AMAZON_SECRET', true),
            ],
        ]);

        self::$bucket = backup_Setup::get('AMAZON_BUCKET',true);
    }


    /**
     * Копира файл съхраняван в сторидж на Amazon система в
     * посоченото в $fileName място
     *
     * Част от интерфейса: backup_StorageIntf
     *
     * @param $sourceFile
     * @param $destFile
     * @return bool
     *
     */
    static function getFile($sourceFile, $destFile)
    {

        try {
            $object = self::$s3Client->getObject(
                array (
                    'Bucket' => self::$bucket,
                    'Key'    => $sourceFile,
                    'SaveAs' => $destFile
                    )
            );
        } catch(Exception $e) {
            $object = FALSE;
        }
        
        return $object ?  TRUE : FALSE;
    }


    /**
     * Записва файл в Amazon архива
     *
     * Част от интерфейса: backup_StorageIntf
     *
     * @param $sourceFile
     * @param null $subDir
     * @return bool
     *
     */
    static function putFile($sourceFile, $subDir = NULL)
    {
        $key = $subDir ?  $subDir . '/' . basename($sourceFile) : basename($sourceFile);

        try {
            $result = self::$s3Client->putObject(
                array (
                    'Bucket' => self::$bucket,
                    'Key'    => $key,
                    'Body'   => fopen( $sourceFile, 'r+')
                    )
            );
        } catch(Exception $e) {
            $result = FALSE;
        }
        
        return $result ? TRUE : FALSE;
    }


    /**
     * Изтрива файл в Amazon архива
     *
     * Част от интерфейса: backup_StorageIntf
     *
     * @param $sourceFile
     * @return bool
     *
     */
    static function removeFile($sourceFile)
    {

        try {
            $result = self::$s3Client->deleteObject(
                array (
                    'Bucket' => self::$bucket,
                    'Key' => $sourceFile,
                    )
            );
        } catch(Exception $e) {
            $result = FALSE;
        }

        return $result ? TRUE : FALSE;
    }

}