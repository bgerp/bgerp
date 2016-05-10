<?php

require __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;

class backup_Amazon extends core_Master{

    public function act_Test()
    {
        $s3Client = new S3Client([
            'version'     => 'latest',
            'region'      => 'eu-west-1',
            'credentials' => [
                'key'    => 'AKIAIN62LA24AWR5RMLQ',
                'secret' => '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',
            ],
        ]);
//        bp( __DIR__);
        $result = $s3Client->putObject([
            'Bucket' => 'peshoexperta',
            'Key'    => 'autoload.php',
            'Body'   => fopen( __DIR__ . '/Amazon.class.php', 'r+')
        ]);

        bp($result);
    }

}