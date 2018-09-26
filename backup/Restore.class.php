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
class backup_Restore extends core_Manager
{

    
    
    /**
     * Стартиране на restore
     */
    public function act_Default()
    {
        $backup = json_encode(self::BGERP_RESTORE_ORIGIN); // това трябва да идва като параметър от ВЕБ или на ф-я
        $backup = json_decode($backup);
        
        $storage = core_Cls::get('backup_' . $backup->type, (array) $backup);
        // Взимаме конфиг. файла
        $confFileName = $backup->prefix . '_' . EF_DB_NAME . '_conf.tar.gz';
        $confFileNameTmp = tempnam(sys_get_temp_dir(), 'bgerp') . ".tar.gz";
        if (!$storage->getFile($confFileName, $confFileNameTmp)) {
            
            return ("Не може да се прочете файла: $confFileName");
        }
        $searchConsts = array('EF_SALT', 'EF_USERS_PASS_SALT', 'EF_USERS_HASH_FACTOR');
        $consts = array();
        try {
            $phar = new PharData($confFileNameTmp);
            foreach (new RecursiveIteratorIterator($phar) as $file) {
                echo $file . "<br />";
                $confRows = file($file);
                foreach ($confRows as $row) {
                    foreach ($searchConsts as $const) {
                        if (strpos($row, $const) !== false) {
                            $consts[] = $row;
                        }
                    }
                    
                }
            }
        } catch (Exception $e) {
            bp($e->getMessage());
        }
        unlink($confFileNameTmp);
        // в $consts[] са редовете, които трябва да се добавят в новия conf файл 
        
        // Взимаме МЕТА файла
        $metaFileName = $backup->prefix . '_' . EF_DB_NAME . '_META';
        $metaFileNameTmp = tempnam(sys_get_temp_dir(), 'bgerp');
        $storage->getFile($metaFileName, $metaFileNameTmp);
        $meta = file_get_contents($metaFileNameTmp);
        unlink($metaFileNameTmp);
        
        $metaArr = unserialize($meta);
        
        // Махаме служебната за mySQL информация
        unset($metaArr['logNames']);
        
        // Взимаме последния бекъп
        $restoreArr = array_reverse($metaArr['backup'])[0];
        
        // Импортираме бекъпa
        // сваляме файловете във временната директория, разархивираме ги и попълваме командата за изпълнение
        $cmd = $cmdBin = $cmdFull = '';
        $statementsSQLTmp = tempnam(sys_get_temp_dir(), 'bgerp'); // в този файл наптрупваме binLog-овете
        $forDelete = array();
        foreach ($restoreArr as $fileName) {
            // В нулевият елемент е пълния бекъп.

            $zippedNameTmp = tempnam(sys_get_temp_dir(), 'bgerp');
            $unzippedNameTmp = tempnam(sys_get_temp_dir(), 'bgerp');
            
            $forDelete[] = $unzippedNameTmp;
            $forDelete[] = $zippedNameTmp;
            
            $storage->getFile($fileName, $zippedNameTmp);
            // Разархивираме файла
            $cmd = "gunzip -c " . $zippedNameTmp . " > " . $unzippedNameTmp;
            exec($cmd, $output, $returnVar);
            if (empty($cmdFull)) {
                // Команда за пълния бекъп
                $cmdFull = "mysql -u" . EF_DB_USER. " -p" . EF_DB_PASS. " " . EF_DB_NAME . " < " . EF_TEMP_PATH . "/". $unzippedNameTmp;
            } else {
                $d[] = "cat " . $unzippedNameTmp . " >> " . $statementsSQLTmp; // За дебъг
                exec("cat " . $unzippedNameTmp . " >> " . $statementsSQLTmp);
            }
        }
        $cmdBin = "mysql -u" . EF_DB_USER. " -p" . EF_DB_PASS. " " . EF_DB_NAME . " < " . $statementsSQLTmp;
        
        // Заключваме цялата система
        core_SystemLock::block('Процес на въстановяване на база', $time = 180000); // 3000 мин.
        
//        exec($cmdFull, $output, $returnVar);
  //      exec($cmdBin, $output, $returnVar);
        
        // Освобождаваме системата
        core_SystemLock::remove();
        
        $forDelete[] = $statementsSQLTmp;
        foreach ($forDelete as $f) {
            unlink($f);
        }

        bp($cmdFull, $cmdBin, $d, $forDelete, $consts);
        
        return $backup;
    }
    
}
