<?php

use ScssPhp\ScssPhp\Compiler;

/**
 * Конвертира sass файлове в css
 *
 * @category  vendors
 * @package   sass
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sass_Converter
{
    /**
     * Конвертира sass в css файл
     *
     * @param string $file   - Линк към файла или стринг от стилове
     * @param string $syntax - Синтаксиса sass или scss
     * @param string $style  - nested, expanded, compact, compressed
     *
     * @return string - Конвертиран css стринг
     */
    public static function convert($file, $syntax = false, $style = 'nested')
    {
        $isParsed = false;
        $baseName = basename($file);
        $useComposer = sass_Setup::get('USE_COMPOSER') === 'yes';
        $version = sass_Setup::get('VERSION');
        if ($useComposer) {
            $error = sass_Setup::getPhpVersionError($version);
            expect(!$error, $error);
        }
        $composerLoaded = $useComposer && core_Composer::isInUse();

        if ($useComposer && $version !== '1.0.4') {
            expect($composerLoaded && class_exists('ScssPhp\\ScssPhp\\Version')
                && version_compare(\ScssPhp\ScssPhp\Version::VERSION, $version, '=='),
                'Липсва избраната версия на scssphp. Инициализирайте пакета sass.');
        }
        
        if ($composerLoaded) {
            $dTimeName = 'sassConvert: ' . $baseName;
            core_Debug::startTimer($dTimeName);
            
            try {
                // Инстанция на класа
                $parser = new Compiler();
                // Парсираме и връщаме резултата
                // Новото API запазва пътя за относителните @import директиви.
                $res = $version !== '1.0.4'
                    ? $parser->compileFile($file)->getCss()
                    : $parser->compile(file_get_contents($file));
                $isParsed = true;
            } catch (core_exception_Redirect $e) {
                throw $e;
            } catch (Exception $e) {
                reportException($e);
            } catch (Throwable $t) {
                reportException($t);
            } finally {
                core_Debug::stopTimer($dTimeName);
            }
        }
        
        if (!$isParsed) {
            // Опциите
            $options = array(
            'style' => $style,
            'cache' => false,
            'syntax' => $syntax,
            'debug' => false,
            'callbacks' => array(
                'warn' => false,
                'debug' => false,
                ),
            );

            // Вкарваме файловете необходими за работа с програмата.
            require_once 'phpsass/SassParser.php';
            
            $dTimeName = 'sassParser: ' . $baseName;
            core_Debug::startTimer($dTimeName);
            
            // Инстанция на класа
            $parser = new SassParser($options);
            
            // Парсираме и връщаме резултата
            $res = $parser->toCss($file);
            
            core_Debug::stopTimer($dTimeName);
        }

        return $res;
    }
}
