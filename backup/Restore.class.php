<?php


/**
 * Възстановяване на базата , файловете и конфигурацията от bgERP бекъп
 *
 *
 * @category  bgerp
 * @package   backup
 *
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Възстановяване от bgERP бекъп
 */
use Aws\Api\Serializer\JsonBody;

class backup_Restore extends core_Manager
{

    /**
     * Информация за бекъпа
     */
    const BGERP_RESTORE_ORIGIN = array (
        'Description'=>'Локална система',
        'type'=>'local',
        'path' => '/storage',
        'prefix' => 'bgerp.localhost',
    );
    //     const BGERP_RESTORE_ORIGIN = array(
    //                                             'Description'=>'FTP сървър',
    //                                             'type'=>'ftp',
    //                                             'address' => 'ftp.localhost.local',
    //                                             'port' => '21',
    //                                             'user' => 'user',
    //                                             'password' => 'pass',
    //                                             'path' => '/storage'
        
    //                                         );
    //     const BGERP_RESTORE_ORIGIN = array(
        //                                         'Description'=>'Амазон AWS S3',
    //                                         'type'=>'S3',
    //                                         'AMAZON_KEY' => '',
    //                                         'AMAZON_SECRET' => '',
    //                                         'AMAZON_BUCKET' => ''
    //                                        );
        
        
    /**
     * Стартиране на restore
     */
    public function act_Default()
    {
        $backup = json_encode(self::BGERP_RESTORE_ORIGIN); // това трябва да идва като параметър от ВЕБ или на ф-я
        
        // Заключваме цялата система
        core_SystemLock::block('Процес на въстановяване на база', $time = 1800); // 30 мин.
        
        $res = $this->restore($backup);
        core_Cache::eraseFull();
        // Освобождаваме системата
        core_SystemLock::remove();
        
        
        return '<div style=" resize: both; "><pre>' . print_r($res, true) . '</pre></div>';
    }

    /**
     * Възстановява данни от архив
     * 
     * @param string $backup
     */
    private function restore($backup)
    {
        $backup = json_decode($backup);
        
        $storage = core_Cls::get('backup_' . $backup->type, (array) $backup);
        
        // Взимаме МЕТА файла
        $metaFileName = $backup->prefix . '_bgERP_backup_META';
        $metaFileNameTmp = tempnam(sys_get_temp_dir(), 'bgerp');
        $storage->getFile($metaFileName, $metaFileNameTmp);
        $meta = file_get_contents($metaFileNameTmp);
        unlink($metaFileNameTmp);
        
        $res = array();
        $metaArr = @unserialize($meta);
        if ($metaArr === false) {
            $res['err'][] = "Не може да се вземат мета данните.";
            
            return $res;
        }
        
        // Взимаме конфиг. файла
        // $confFileName = $backup->prefix . '_' . EF_DB_NAME . '_conf.tar.gz';
        // $confFileName = $backup->prefix . '_conf.tar.gz';
        $confFileName = $metaArr['backupInfo']['confFileName'];
        
        $confFileNameTmp = tempnam(sys_get_temp_dir(), 'bgerp_tmp') . ".tar.gz";
        if (!$storage->getFile($confFileName, $confFileNameTmp)) {
            $res['err'][] = "Не може да се прочете файла: $confFileName";
            
            return $res;
        }
        $searchConsts = array('EF_SALT', 'EF_USERS_PASS_SALT', 'EF_USERS_HASH_FACTOR');
        $consts = array();
        try {
            $phar = new PharData($confFileNameTmp);
            foreach (new RecursiveIteratorIterator($phar) as $file) {
                $confRows = file($file);
                foreach ($confRows as $row) {
                    foreach ($searchConsts as $const) {
                        if (strpos($row, $const) !== false) {
                            $consts[$const] = $row;
                        }
                    }
                    
                }
            }
        } catch (Exception $e) {
            $res['err'][] = $e->getMessage();
            
            return $res;
        }
        unlink($confFileNameTmp);
        // в $consts[] са редовете, които трябва да се добавят в новия conf файл
        
        // Парсираме текущият конфиг файл, коментираме редовете с търсените константи и добавяме редовете от $consts[]
        $fRows = file(EF_CONF_PATH . '/' . EF_APP_NAME . '.cfg.php'); // масив от редовете
        foreach ($fRows as $ndx => $row) {
            foreach ($consts as $const => $repl) {
                if (stripos($row, $repl) !== false) {
                    continue; // Ако реда съвпада изцяло - не заменяме нищо
                }
                if (stripos($row, $const) !== false) {
                    $fRows[$ndx] = "// Коментирано от Restore \n //" . $fRows[$ndx];
                    $fRows[$ndx] .= $repl;
                }
            }
        }
        if (is_writable(EF_CONF_PATH . '/' . EF_APP_NAME . '.cfg.php')) {
            @file_put_contents(EF_CONF_PATH . '/' . EF_APP_NAME . '.cfg.php', implode('', $fRows));
            $res['info'][] = "Успешно подменени константи в " . EF_CONF_PATH . '/' . EF_APP_NAME . '.cfg.php';
        } else {
            $res['warn'][] = "Няма права за писане в конфиг файла. Трябва да подмените ръчно следните константи както следва: " . implode('\n', $consts);
        }
        
        // Махаме служебната за mySQL информация от МЕТА-та
        unset($metaArr['logNames']);
        
        // Взимаме последния бекъп
        $restoreArr = array_reverse($metaArr['backup'])[0];
        
        // Импортираме бекъпa
        // сваляме файловете във временната директория, разархивираме ги и попълваме командата за изпълнение
        $cmd = $cmdBin = $cmdFull = '';
        $statementsSQLTmp = tempnam(sys_get_temp_dir(), 'bgerp_tmp'); // в този файл натрупваме binLog-овете
        $forDelete = array();
        foreach ($restoreArr as $fileName) {
            // В нулевият елемент е пълния бекъп.
            
            $zippedNameTmp = tempnam(sys_get_temp_dir(), 'bgerp_tmp');
            $unzippedNameTmp = tempnam(sys_get_temp_dir(), 'bgerp_tmp');
            
            $forDelete[] = $unzippedNameTmp;
            $forDelete[] = $zippedNameTmp;
            
            $storage->getFile($fileName, $zippedNameTmp);
            // Разархивираме файла
            $cmd = "gunzip -c " . $zippedNameTmp . " > " . $unzippedNameTmp;
            exec($cmd, $output, $returnVar);
            if ($returnVar !== 0) {
                $res['err'][] = "Грешка при разархивиране: " . implode('\n', $output);
            }
            
            if (empty($cmdFull)) {
                // Команда за пълния бекъп
                $cmdFull = "mysql -u" . EF_DB_USER. " -p" . EF_DB_PASS. " " . EF_DB_NAME . " < " . $unzippedNameTmp . " 2>&1";
            } else {
                //$d[] = "cat " . $unzippedNameTmp . " >> " . $statementsSQLTmp; // За дебъг
                exec("cat " . $unzippedNameTmp . " >> " . $statementsSQLTmp);
            }
        }
        $cmdBin = "mysql -u" . EF_DB_USER. " -p" . EF_DB_PASS. " " . EF_DB_NAME . " < " . $statementsSQLTmp . " 2>&1";
        
        core_Debug::startTimer('restoreFull');
        exec($cmdFull, $output, $returnVar);
        if ($returnVar !== 0) {
            $res['err'][] = "Грешка при наливане на пълен бекъп: " . implode('\n', $output);
        }
        core_Debug::stopTimer('restoreFull');
        
        core_Debug::startTimer('restoreBin');
        exec($cmdBin, $output, $returnVar);
        if ($returnVar !== 0) {
            $res['err'][] = "Грешка при наливане на бинлог: " . implode('\n', $output);
        }
        core_Debug::stopTimer('restoreBin');
        
        $forDelete[] = $statementsSQLTmp;
        foreach ($forDelete as $f) {
            if (!unlink($f)) {
                $res['warn'][] = "Не можа да изтрие файл: $f";
            }
        }
        $res['timersRestoreFull'] = core_Debug::$timers['restoreFull']->workingTime;
        $res['timersRestoreBin'] = core_Debug::$timers['restoreBin']->workingTime;
        
        return $res;
    }
}
