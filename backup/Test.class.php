<?php

use Aws\S3\S3Client;

use CloudAtlas\Flyclone\Rclone;
use CloudAtlas\Flyclone\Providers\LocalProvider;
use CloudAtlas\Flyclone\Providers\MegaProvider;

/**
 * Четене и записване на локални файлове
 *
 *
 * @category  bgerp
 * @package   backup
 *
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Архивиране
 */
class backup_Test extends core_Manager
{
    
    private static $s3Client;
    private static $bucket;
    
    /**
     * Тестов метод
     */
    public function act_asw()
    {
        if (core_Composer::isInUse()) {
            self::$s3Client = new S3Client([
                'version' => 'latest',
                'region' => 'eu-west-1',
                'credentials' => [
                    'key' => backup_Setup::get('AMAZON_KEY', true),
                    'secret' => backup_Setup::get('AMAZON_SECRET', true),
                ],
            ]);
            
            self::$bucket = backup_Setup::get('AMAZON_BUCKET', true);
        }
        self::$s3Client->uploadDirectory('sbf', self::$bucket);
        bp(self::$bucket);
        return $this->full();
    }

    /**
     * Тестов метод 2
     */
    public function act_rclone()
    {
        
        if (core_Composer::isInUse()) {
            $left_side = new LocalProvider('mydisk'); // nickname
            $right_side = new MegaProvider('myremote',[
                'user'=>'dminekov@abv.bg',
                'pass'=> Rclone::obscure('Nz.agem72')
            ]);
            
            $rclone = new Rclone($left_side, $right_side);
            
            $res = $rclone->copy('/home/mitko/20230411T151240.mp4', '/office');
            
            // bp($rclone->ls('/home/mitko/')); // returns array
            bp($res); // returns array
        }
        die;
    }
    
}