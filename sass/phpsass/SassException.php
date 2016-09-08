<?php
/**
 * General Sass exception.
 *
 * @author       Chris Yates <chris.l.yates@gmail.com>
 * @copyright    Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 * @package      PHamlP
 * @subpackage   Sass
 */

/**
 * Sass exception class.
 *
 * @package      PHamlP
 * @subpackage   Sass
 */
class SassException extends Exception {

	/**
	 * Sass Exception.
	 *
	 * @param string $message                Exception message
	 * @param mixed  $additionalMessageMixed mixed resource for meta data
	 */
	public function __construct($message, $additionalMessageMixed = '') {
		if (is_object($additionalMessageMixed)) {
			$additionalMessageMixed = ": {$additionalMessageMixed->filename}::{$additionalMessageMixed->line}\nSource: {$additionalMessageMixed->source}";
		} else if (is_array($additionalMessageMixed)) {
			$additionalMessageMixed = var_export($additionalMessageMixed, TRUE);
		} else if (!is_scalar($additionalMessageMixed)) {
			$additionalMessageMixed = '';
		}
		parent::__construct($message . $additionalMessageMixed);
	}
}
