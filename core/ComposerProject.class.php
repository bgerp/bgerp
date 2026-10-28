<?php

/**
 * Инсталиране на отделен Composer проект от пакетен lock файл.
 *
 * @category core
 * @package core
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
class core_ComposerProject
{
    /**
     * Използва общия Composer executable, без да променя общия manifest, lock или vendor.
     *
     * @param string $sourceDir Директория с composer.json и composer.lock
     * @param string $projectDir Отделна директория за инсталиране
     * @return array|false
     */
    public static function install($sourceDir, $projectDir)
    {
        cls::load('core_Composer');
        core_Os::requireDir($projectDir, 0755);
        foreach (array('composer.json', 'composer.lock') as $file) {
            expect(copy($sourceDir . '/' . $file, $projectDir . '/' . $file), 'Не може да се запише Composer manifest', $file);
        }

        core_App::setTimeLimit(600);
        $environment = array('COMPOSER' => $projectDir . '/composer.json', 'COMPOSER_VENDOR_DIR' => $projectDir . '/vendor');
        $previousEnvironment = array();
        foreach ($environment as $name => $value) {
            $previousEnvironment[$name] = getenv($name);
        }
        try {
            // Само manifest не е достатъчен: vendor също трябва да е извън общия проект.
            foreach ($environment as $name => $value) {
                expect(putenv($name . '=' . $value), 'Не може да се зададе Composer средата', $name);
            }

            return core_Composer::run('install --no-interaction --no-dev --no-plugins --no-scripts --prefer-dist');
        } finally {
            foreach ($previousEnvironment as $name => $value) {
                putenv($value === false ? $name : $name . '=' . $value);
            }
        }
    }
}
