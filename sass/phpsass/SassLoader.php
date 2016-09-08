<?php
/**
 * SASS Auto loader
 *
 * @author     Tim Lochmüller <tim@fruit-lab.de>
 */

/**
 * SASS Auto loader
 *
 * @author     Tim Lochmüller <tim@fruit-lab.de>
 */
class SassLoader {

	/**
	 * Base dir of the SASS Framework
	 *
	 * @var string
	 */
	static private $basePath = NULL;

	/**
	 * Loader file extension
	 */
	const FILE_EXTENSION = '.php';

	/**
	 * Load the given class
	 *
	 * @param string $className
	 *
	 * @todo More smarter Implementation, if the files have a smarter structure
	 */
	static public function load($className) {
		$base = self::getBasePath();
		$psrFile = self::getPsrFilePath($className);
		if (file_exists($base . $psrFile)) {
			require_once($base . $psrFile);
		} else if (file_exists($base . $className . self::FILE_EXTENSION)) {
			require_once($base . $className . self::FILE_EXTENSION);
		} else if (file_exists($base . 'tree/' . $className . self::FILE_EXTENSION)) {
			require_once($base . 'tree/' . $className . self::FILE_EXTENSION);
		} else if (file_exists($base . 'renderers/' . $className . self::FILE_EXTENSION)) {
			require_once($base . 'renderers/' . $className . self::FILE_EXTENSION);
		} else if (file_exists($base . 'script/' . $className . self::FILE_EXTENSION)) {
			require_once($base . 'script/' . $className . self::FILE_EXTENSION);
		} else if (file_exists($base . 'script/literals/' . $className . self::FILE_EXTENSION)) {
			require_once($base . 'script/literals/' . $className . self::FILE_EXTENSION);
		} else {

		}
	}

	/**
	 * Build the filename for a PSR conform className
	 *
	 * @param string $className
	 *
	 * @return string
	 */
	static private function getPsrFilePath($className) {
		$className = ltrim($className, '\\');
		$fileName = '';
		if ($lastNsPos = strrpos($className, '\\')) {
			$namespace = substr($className, 0, $lastNsPos);
			$className = substr($className, $lastNsPos + 1);
			$fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . self::FILE_EXTENSION;
		return $fileName;
	}

	/**
	 * Get the current base path
	 *
	 * @return string
	 */
	static private function getBasePath() {
		if (self::$basePath === NULL) {
			self::$basePath = dirname(__FILE__) . '/';
		}
		return self::$basePath;
	}
}

spl_autoload_register('SassLoader::load');