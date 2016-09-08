<?php
/* SVN FILE: $Id$ */
/**
 * SassCharsetNode class file.
 * @author      Richard Lyon
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 * @package      PHamlP
 * @subpackage  Sass.tree
 */

/**
 * SassCharsetNode class.
 * Represents a Content.
 * @package      PHamlP
 * @subpackage  Sass.tree
 */
class SassCharsetNode extends SassNode
{
  const MATCH = '/^@charset(.*?);?$/i';
  const IDENTIFIER = 1;

  /**
   * @var mixed statement to execute and return
   */
  private $statement;

  /**
   * SassCharsetNode constructor.
   * @param object $token source token
   * @return SassCharsetNode
   */
  public function __construct($token)
  {
    parent::__construct($token);
    preg_match(self::MATCH, $token->source, $matches);

    if (empty($matches)) {
      return new SassBoolean('false');
    }
  }

  /**
   * Parse this node.
   * Set passed arguments and any optional arguments not passed to their
   * defaults, then render the children of the return definition.
   * @param SassContext $pcontext the context in which this node is parsed
   * @return array the parsed node
   */
  public function parse($pcontext)
  {
    return array($this);
  }

  public function render() {
    // print the original with a semi-colon if needed
    return $this->token->source 
      . (substr($this->token->source, -1, 1) == ';' ? '' : ';')
      . "\n";
  }

  /**
   * Contents a value indicating if the token represents this type of node.
   * @param object $token token
   * @return boolean true if the token represents this type of node, false if not
   */
  public static function isa($token)
  {
    return $token->source[0] === self::IDENTIFIER;
  }
}
