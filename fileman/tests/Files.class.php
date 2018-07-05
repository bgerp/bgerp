<?php


/**
 * Клас  'fileman_tests_Files' - Тестове
 *
 *
 * @category  vendors
 * @package   tests
 * @author    Yusein Yuseinov
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class fileman_tests_Files extends unit_Class
{

    
    /**
     * Първия файл
     */
    public static $path1 = 'fileman/tests/test1.txt';
    
    
    /**
     * Втория файл
     */
    public static $path2 = 'fileman/tests/test2.txt';
    
    
    /**
     * Името на кофата
     */
    public static $bucket = 'tests';
    
    
    /**
     * Масив с манипулаторите на качените файлове
     */
    public static $fhArr = array();
    
    
    /**
     * Масив с id' тата на качените файлове
     */
    public static $idsArr = array();
    
    
    
    public function __construct()
    {
        // Създаваме кофата
        $Bucket = cls::get('fileman_Buckets');
        $Bucket->createBucket(self::$bucket, 'Тестови файлове', null, null, 'user', 'user');
    }
    
    
    /**
     * Създаване на файл от файл в ОС. Връща fh на новосъздания файл.
     *
     * @param fileman $mvc
     */
    public static function test_absorb($mvc)
    {
        // Името на кофата
        $bucket = self::$bucket;
        
        // Пътя до файла
        $path1 = getFullPath(self::$path1);
        $path2 = getFullPath(self::$path2);
        
        // Очакваме да има такива файлове
        ut::expectEqual(($path1 && $path2), true);
        
        // Качваме файловете и вземаме манипулаторите им
        $fh1 = fileman::absorb($path1, $bucket);
        $fh1Name = fileman::absorb($path1, $bucket, 'test1.txt');
        $fh2 = fileman::absorb($path2, $bucket);
        $fh2Name = fileman::absorb($path2, $bucket, 'test2.txt');
        
        // Очакваме да има такива манипулатори
        ut::expectEqual(($fh1 && $fh2), true);
        
        // Очакваме да са еднакви
        ut::expectEqual($fh1, $fh1Name);
        ut::expectEqual($fh2, $fh2Name);
        
        // Качваме първия файл с името на втория
        $otherName = fileman::absorb($path1, $bucket, 'test2.txt');
        
        // Очакваме манипулаторите да не им са равни
        ut::expectEqual((($fh1 != $fh2) && ($fh1 != $otherName)), true);
        
        // Качваме първия файл с името на втория
        $otherNameUnd = fileman::absorb($path1, $bucket, 'test2_1.txt');
        
        // Очакваме манипулаторите им да са равни
        ut::expectEqual($otherNameUnd, $otherName);
        
        // Ако няма добавени файлове
        if (!count(self::$fhArr)) {
            
            // Добавяме в масива
            self::$fhArr[] = $fh1;
            self::$fhArr[] = $fh2;
        }
    }
    
    
    /**
     * Създаване на файл от стринг. Връща fh на новосъздания файл.
     *
     * @param fileman $mvc
     */
    public static function test_absorbStr($mvc)
    {
        // Името на кофата
        $bucket = self::$bucket;
        
        // Пътя до файла
        $path1 = getFullPath(self::$path1);
        $path2 = getFullPath(self::$path2);
        
        // Съдържанието на файла
        $content1 = file_get_contents($path1);
//        $content2 = file_get_contents($path2);

        // Генерираме съдържание, за да тестваме качването на несъществуващ файл
        $content2 = str::getRand();
        
        // Очакваме да има такива файлове
        ut::expectEqual(($path1 && $path2), true);
        
        // Очакваме да има такива файлове
        ut::expectEqual(($content1 && $content2), true);
        
        // Тестваме имената с интервал и без разширение
        // Качваме файловете и вземаме манипулаторите им
        $fh1 = fileman::absorbStr($content1, $bucket, 'test 1.txt');
        $fh1Name = fileman::absorbStr($content1, $bucket, 'test 1.txt');
        $fh2 = fileman::absorbStr($content2, $bucket, 'test 2');
        $fh2Name = fileman::absorbStr($content2, $bucket, 'test 2');
        
        // Очакваме да има такива манипулатори
        ut::expectEqual(($fh1 && $fh2), true);
        
        // Очакваме да са еднакви
        ut::expectEqual($fh1, $fh1Name);
        ut::expectEqual($fh2, $fh2Name);
        
        // Качваме първия файл с името на втория
        $otherName = fileman::absorbStr($content1, $bucket, 'test 2.txt');
        
        // Очакваме манипулаторите да не им са равни
        ut::expectEqual((($fh1 != $fh2) && ($fh1 != $otherName)), true);
        
        // Качваме първия файл с името на втория
        $otherNameUnd = fileman::absorbStr($content1, $bucket, 'test 2_UNIQ_1.txt');
        
        // Качваме първия файл с името на втория
        $otherNameUnd2 = fileman::absorbStr($content1, $bucket, 'test 2_UNIQ.txt');
        
        // Очакваме манипулаторите им да са равни
        ut::expectEqual($otherNameUnd, $otherNameUnd2);
        
        // Ако няма добавени файлове
        if (!count(self::$fhArr)) {
            
            // Добавяме в масива
            self::$fhArr[] = $fh1;
            self::$fhArr[] = $fh2;
        }
    }

    
    /**
     * Нова версия от файл в ОС
     *
     * @param fileman $mvc
     */
    public static function test_addVersion($mvc)
    {
        // Пътя до файла
        $path1 = getFullPath(self::$path1);
        $path2 = getFullPath(self::$path2);
        
        // Очакваме да има такива файлове
        ut::expectEqual(($path1 && $path2), true);
        
        // Ако няма добавен файл
        if (!count(self::$fhArr)) {
            
            // Извикваме теста за абсорбиране на файл, който добавя в масива
            self::test_absorb($mvc);
        }
        
        // Манипулатора на файла
        $fh = self::$fhArr[0];
        
        // Добавяме версии
        $id1 = fileman::addVersion($fh, $path1);
        $id2 = fileman::addVersion($fh, $path2);
        
        // Очакваме да има id
        ut::expectEqual(isset($id2), true);
        
        // Очакваме да не са равни id' тата на файлове
        ut::expectEqual($id1 != $id2, true);
    }

    
    /**
     * Нова версия от стринг
     *
     * @param fileman $mvc
     */
    public static function test_addVersionStr($mvc)
    {
        // Пътя до файла
        $path1 = getFullPath(self::$path1);
        $path2 = getFullPath(self::$path2);
        
        // Съдържанието на файла
        $content1 = file_get_contents($path1);
        $content2 = file_get_contents($path2);
        
        // Очакваме да има такива файлове
        ut::expectEqual(($path1 && $path2), true);
        
        // Очакваме да има такива файлове
        ut::expectEqual(($content1 && $content2), true);
        
        // Ако няма добавен файл
        if (!count(self::$fhArr)) {
            
            // Извикваме теста за абсорбиране на файл, който добавя в масива
            self::test_absorbStr($mvc);
        }
        
        // Манипулатора на файла
        $fh = self::$fhArr[0];
        
        // Добавяме версии
        $id1 = fileman::addVersionStr($fh, $content1);
        $id2 = fileman::addVersionStr($fh, $content2);
        
        // Очакваме да има id
        ut::expectEqual(isset($id2), true);
        
        // Очакваме да не са равни id' тата на файлове
        ut::expectEqual($id1 != $id2, true);
    }

    
    /**
     * Екстрактване на файл в ОС. Връща пълния път до новия файл
     *
     * @param fileman $mvc
     */
    public static function test_extract($mvc)
    {
        // Ако няма добавен файл
        if (!count(self::$fhArr)) {
            
            // Извикваме теста за абсорбиране на файл, който добавя в масива
            self::test_absorb($mvc);
        }
        
        // Манипулатора на файла
        $fh = self::$fhArr[0];
        
        // Екстрактваме файла и вземаме пътя до екстрактнатия файл
        $filePath = fileman::extract($fh);
        
        // Очакваме да е файл
        ut::expectEqual(is_file($filePath), true);
        
        // Изтриване на временния файл
        fileman::deleteTempPath($filePath);
        
        // Очакваме след изтриването да няма такъв файл
        ut::expectEqual(is_file($filePath), false);
    }

    
    /**
     * Екстрактване на файл в string. Връща стринга.
     *
     * @param fileman $mvc
     */
    public static function test_extractStr($mvc)
    {
        // Ако няма добавен файл
        if (!count(self::$fhArr)) {
            
            // Извикваме теста за абсорбиране на файл, който добавя в масива
            self::test_absorbStr($mvc);
        }
        
        // Манипулатора на файла
        $fh = self::$fhArr[1];
        
        // Екстрактваме файла и вземаме съдържанието
        $fileContent = fileman::extractStr($fh);
        
        // Очакваме да има съдържание
        ut::expectEqual($fileContent, true);

        // Очакваме съдържанието да е точно определено
        ut::expectEqual($fileContent, 'Test2');
    }
    
    
    /**
     * Преименуване на файл
     *
     * @param fileman $mvc
     */
    public static function test_rename($mvc)
    {
        // Ако няма добавен файл
        if (!count(self::$fhArr)) {
            
            // Извикваме теста за абсорбиране на файл, който добавя в масива
            self::test_absorb($mvc);
        }
        
        // Манипулатора на файла
        $fh = self::$fhArr[0];
        
        // Преимениваме файла
        $newName = fileman::rename($fh, 'renamed.txt');
        
        // Очакваме да върне новото име
        ut::expectEqual(isset($newName), true);
        
        // Връщаме старото име на файла
        fileman::rename($fh, 'test1.txt');
    }

    
    /**
     * Копиране на файл
     *
     * @param fileman $mvc
     */
    public static function test_copy($mvc)
    {
        // Ако няма добавен файл
        if (!count(self::$fhArr)) {
            
            // Извикваме теста за абсорбиране на файл, който добавя в масива
            self::test_absorb($mvc);
        }
        
        // Манипулатора на файла
        $fh = self::$fhArr[0];
        
        // Копираме файла
        $fhCopied = fileman::copy($fh, self::$bucket, 'copied.txt');
        
        // Очакваме манипулаторите на двата файла да не съвпадат
        ut::expectEqual($fh != $fhCopied, true);
    }

    
    /**
     * Връща id на посочения fileHnd
     *
     * @param fileman $mvc
     */
    public static function test_fhToId($mvc)
    {
        // Ако няма добавен файл
        if (!count(self::$fhArr)) {
            
            // Извикваме теста за абсорбиране на файл, който добавя в масива
            self::test_absorb($mvc);
        }
        
        // Манипулатора на файла
        $fh = self::$fhArr[0];
        
        // Вземаме id' то от манипулатора
        $id = fileman::fhToId($fh);
        
        // Очакваме да има валидно id
        ut::expectEqual($id > 0, true);
        
        // Вземаме id' то от друго място
        $idFromFetch = fileman_Files::fetchByFh($fh, 'id');
        
        // Очакваме да си съвпадат
        ut::expectEqual($id, $idFromFetch);
    }

    
    /**
     * Връща масив от id-та  на файлове. Като аргумент получава масив или keylist от fileHandles.
     *
     * @param fileman $mvc
     */
    public static function test_fhKeylistToIds($mvc)
    {
        // Ако няма добавен файл
        if (!count(self::$fhArr)) {
            
            // Извикваме теста за абсорбиране на файл, който добавя в масива
            self::test_absorb($mvc);
        }
        
        // Вземаме масива с id' та от масива с манипулаторите на файловете
        $idsArr = fileman::fhKeylistToIds(self::$fhArr);
        
        // Очакваме да има 2 id' та
        ut::expectEqual(count($idsArr), 2);
        
        // Обхождаме масива с манипуалтори и го привръщаме в keyList
        foreach (self::$fhArr as $value) {
            $keyList .= '|' . $value;
        }
        $keyList .= '|';
        
        // Вземаме масива с id' та от keylist
        $idsArrFromKList = fileman::fhKeylistToIds($keyList);
        
        // Очакваме двата масива да са равни
        ut::expectEqual($idsArr, $idsArrFromKList);
        
        $newArr = array();
        
        // Създаваме нов масив от id' тата
        foreach ($idsArr as $id) {
            $newArr[] = $id;
        }
        
        // Ако не е сетнат
        if (!count(self::$idsArr)) {
            
            // Записваме го в масива с id
            self::$idsArr = $newArr;
        }
    }

    
    /**
     * Връща fileHnd на посоченото id
     *
     * @param fileman $mvc
     */
    public static function test_idToFh($mvc)
    {
        // Ако няма добавени id' та
        if (!count(self::$idsArr)) {
            
            // Извикваме функцията за сетване на id' та
            self::test_fhKeylistToIds($mvc);
        }
        
        // id на файла
        $id = self::$idsArr[0];
        
        // Вземаме манипулатора от id' то
        $fh = fileman::idToFh($id);
        
        // Очакваме да има манипулаотор
        ut::expectEqual(isset($fh), true);
        
        // Вземаме манипулатора по друг канал
        $idFromFetch = fileman_Files::fetchByFh($fh, 'id');
        
        // Очакваме да се еднакви
        ut::expectEqual($id, $idFromFetch);
    }

    
    /**
     * Връща масив от fh-ри  на файлове. Като аргумент получава масив или keylist от id-та на файлове
     *
     * @param fileman $mvc
     */
    public static function test_idKeylistToFhs($mvc)
    {
        // Ако няма добавени id' та
        if (!count(self::$idsArr)) {
            
            // Извикваме функцията за сетване на id' та
            self::test_fhKeylistToIds($mvc);
        }

        // Вземаме от масива с id' та масива с манипулатори
        $fhsArr = fileman::idKeylistToFhs(self::$idsArr);
        
        // Очакваме броя им да е 2
        ut::expectEqual(count($fhsArr), 2);
        
        // Обхождаме масива с id' та и го превръщаме в keylist
        foreach (self::$idsArr as $value) {
            $keyList .= '|' . $value;
        }
        $keyList .= '|';
        
        // Вземаме масив с манипулатори от keylista
        $fhsArrFromKList = fileman::idKeylistToFhs($keyList);
        
        // Очакваме да са равни
        ut::expectEqual($fhsArr, $fhsArrFromKList);
    }

    
    /**
     * Връща всички мета-характеристики на файла
     *
     * @param fileman $mvc
     */
    public static function test_getMeta($mvc)
    {
        // Ако няма добавен файл
        if (!count(self::$fhArr)) {
            
            // Извикваме теста за абсорбиране на файл, който добавя в масива
            self::test_absorb($mvc);
        }
        
        // Манипуалтора на съответния файл
        $fh = self::$fhArr[0];
        
        // Вземаме мета данните
        $metaData = fileman::getMeta($fh);
        
        // Времето сега
        $now = dt::now();

        // Очакваме броя на мета данните да е 6
        ut::expectEqual(count($metaData), 6);
        
        // Времето на последното екстракване трябва да е преди сега
        ut::expectEqual($metaData['extractDate'] <= $now, true);
        
        // Времето създаване трябва да е преди сега
        ut::expectEqual($metaData['creationDate'] <= $now, true);
        
        // Времето на последно модифициране трябва да е преди сега
        ut::expectEqual($metaData['modificationDate'] <= $now, true);
        
        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fh);
        
        // Очакваме имената на файла да си съвпадат
        ut::expectEqual($fRec->name, $metaData['name']);
        
        // Очакваме кофата им да си съвпада
        ut::expectEqual($fRec->bucketId, $metaData['bucket']);
        
        // Данните на файла
        $dRec = fileman_Data::fetch($fRec->dataId);
        
        // Очакваме дължните да съвпадат
        ut::expectEqual($dRec->fileLen, $metaData['size']);
    }
    
    
    /**
     * Създава нов файл
     *
     * @param fileman $mvc
     */
    public static function test_createFile($mvc)
    {
        // Тества се в self::absorb и self::absorbStr
    }
    
    
    /**
     * Създава нова директория, където ще се записват файловете
     *
     * @param fileman $mvc
     */
    public static function test_getTempPath($mvc)
    {
        // Временната директория
        $tempPath = fileman::getTempPath();
        
        // Очакваме да е диретория
        ut::expectEqual(is_dir($tempPath), true);
        
        // Временната поддиректория
        $tempDir = fileman::getTempDir();
        
        // Очакваме временната директория да се съдържа във временната поддиректория
        ut::expectEqual(strpos($tempPath, $tempDir) === 0, true);
        
        // Премахваме временната директория
        rmdir($tempPath);
        
        // Очакваме да няма такавя директория
        ut::expectEqual(is_dir($tempPath), false);
    }
    
    
    /**
     * Връща директорията с временните файлове
     *
     * @param fileman $mvc
     */
    public static function test_getTempDir($mvc)
    {
        // Тества се в self::absorb и self::getTempPath
    }
    
    
    /**
     * Изтрива временната директория
     *
     * @param fileman $mvc
     */
    public static function test_deleteTempPath($mvc)
    {
        // Тества се в self::extract
    }

    
    /**
     * Проверява дали файла със съответните данни съществува
     *
     * @param fileman $mvc
     */
    public static function test_checkFileNameExist($mvc)
    {
        // Ако няма добавен файл
        if (!count(self::$fhArr)) {
            
            // Извикваме теста за абсорбиране на файл, който добавя в масива
            self::test_absorb($mvc);
        }
        
        // Обхождаме масива
        foreach (self::$fhArr as $fh) {
            
            // Вземаме записите за файла
            $fRec = fileman_Files::fetchByFh($fh);
            
            // Проверяваме файла дали съществува и вземаме манипуалтора им
            $fhChecked = fileman::checkFileNameExist($fRec->dataId, $fRec->bucketId, $fRec->name);
            
            // Очаквама да си съвпадат манипулаторите
            ut::expectEqual($fh, $fhChecked);
        }
        
        // Пътя до файла
        $path1 = getFullPath(self::$path1);
        $path2 = getFullPath(self::$path2);
        
        // Абсорбираме файл с path1
        $xx = fileman::absorb($path1, self::$bucket, 'xx.txt');
        
        // Абсорбираме файл с path2
        $xx_1 = fileman::absorb($path2, self::$bucket, 'xx_1.txt');
        
        // Абсорбираме файл с path2
        $xx_2 = fileman::absorb($path2, self::$bucket, 'xx_2.txt');
        
        // Абсорбираме файл с path2, но с името на path1
        $xxNew = fileman::absorb($path2, self::$bucket, 'xx.txt');
        
        // Очакваме първия файл с path2 да се върне
        ut::expectEqual($xx_1, $xxNew);
    }
}
