<?php

require __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Guzzle\Http\EntityBody;

class backup_Amazon extends core_BaseClass
{

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
     * Копира файл съхраняван в сторидж на локалната файлова система в
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

        $object  = self::$s3Client->getObject(array(
            'Bucket' => self::$bucket,
            'Key'    => $sourceFile,
            'SaveAs' => $destFile,
        ));

        return $object ?  true : false;
    }


    /**
     * Записва файл в локалния архив
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

        $result = self::$s3Client->putObject([
            'Bucket' => self::$bucket,
            'Key'    => $key,
            'Body'   => EntityBody::factory(fopen( $sourceFile, 'r+'))
        ]);
        return $result ? true : false;
    }


    /**
     * Изтрива файл в локалния архив
     *
     * Част от интерфейса: backup_StorageIntf
     *
     * @param $sourceFile
     * @return bool
     *
     */
    static function removeFile($sourceFile)
    {
        $result = self::$S3Client->deleteObject(array(
            'Bucket' => self::$bucket,
            'Key' => $sourceFile,
        ));

        return $result ? true : false;
    }



    public function act_Test()
    {

    }


}