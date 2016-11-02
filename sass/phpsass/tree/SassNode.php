<?php
/* SVN FILE: $Id$ */
/**
 * SassNode class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 * @package      PHamlP
 * @subpackage  Sass.tree
 */

#require_once 'SassContext.php';
#require_once 'SassCommentNode.php';
#require_once 'SassDebugNode.php';
#require_once 'SassDirectiveNode.php';
#require_once 'SassImportNode.php';
#require_once 'SassMixinNode.php';
#require_once 'SassMixinDefinitionNode.php';
#require_once 'SassPropertyNode.php';
#require_once 'SassRootNode.php';
#require_once 'SassRuleNode.php';
#require_once 'SassVariableNode.php';
#require_once 'SassExtendNode.php';
#require_once 'SassEachNode.php';
#require_once 'SassForNode.php';
#require_once 'SassIfNode.php';
#require_once 'SassElseNode.php';
#require_once 'SassWhileNode.php';
#require_once 'SassNodeExceptions.php';
#require_once 'SassFunctionDefinitionNode.php';
#require_once 'SassReturnNode.php';
#require_once 'SassContentNode.php';
#require_once 'SassWarnNode.php';
#require_once 'SassMediaNode.php';

/**
 * SassNode class.
 * Base class for all Sass nodes.
 * @package      PHamlP
 * @subpackage  Sass.tree
 */
class SassNode
{
  /**
   * @var SassNode parent of this node
   */
  public $parent;
  /**
   * @var SassNode root node
   */
  public $root;
  /**
   * @var array children of this node
   */
  public $children = array();
  /**
   * @var object source token
   */
  public $token;

  /**
   * Constructor.
   * @param object $token source token
   * @return SassNode
   */
  public function __construct($token)
  {
    $this->token = $token;
  }

	/**
	 * Getter.
	 *
	 * @param string $name name of property to get
	 *
	 * @throws SassNodeException
	 * @return mixed return value of getter function
	 */
  public function __get($name)
  {
    $getter = 'get' . ucfirst($name);
    if (method_exists($this, $getter)) {
      return $this->$getter();
    }
    throw new SassNodeException('No getter function for ' . $name, $this);
  }

  /**
   * Setter.
   * @param string $name name of property to set
   * @param mixed $value value of property
   * @throws SassNodeException
   * @return SassNode this node
   */
  public function __set($name, $value)
  {
    $setter = 'set' . ucfirst($name);
    if (method_exists($this, $setter)) {
      $this->$setter($value);

      return $this;
    }
    throw new SassNodeException('No setter function for ' . $name, $this);
  }

  /**
   * Resets children when cloned
   * @see parse
   */
  public function __clone()
  {
    $this->children = array();
  }

  /**
   * Return a value indicating if this node has a parent
   * @return array the node's parent
   */
  public function hasParent()
  {
    return !empty($this->parent);
  }

  /**
   * Returns the node's parent
   * @return array the node's parent
   */
  public function getParent()
  {
    return $this->parent;
  }

  /**
   * Adds a child to this node.
   */
  public function addChild($child)
  {
	/** @var $child SassNode */
    if ($child instanceof SassElseNode) {
      if (!$this->getLastChild() instanceof SassIfNode) {
        throw new SassException('@else(if) directive must come after @(else)if', $child);
      }
      $this->getLastChild()->addElse($child);
    } else {
      $this->children[] = $child;
      $child->parent = $this;
      $child->setRoot($this->root);
    }
  }
  
  /**
   * Sets a root recursively.
   * @param SassNode $root the new root node
   */
  public function setRoot($root){
    $this->root = $root;
    foreach ($this->children as $child) {
	    /** @var $child SassNode */
      $child->setRoot($this->root);
    }
  }
  
  /**
   * Returns a value indicating if this node has children
   * @return boolean true if the node has children, false if not
   */
  public function hasChildren()
  {
    return !empty($this->children);
  }

  /**
   * Returns the node's children
   * @return array the node's children
   */
  public function getChildren()
  {
    return $this->children;
  }

	/**
	 * Returns a value indicating if this node is a child of the passed node.
	 * This just checks the levels of the nodes. If this node is at a greater
	 * level than the passed node if is a child of it.
	 *
	 * @param SassNode $node
	 *
	 * @return boolean true if the node is a child of the passed node, false if not
	 */
  public function isChildOf($node)
  {
    return $this->getLevel() > $node->getLevel();
  }

  /**
   * Returns the last child node of this node.
   * @return SassNode the last child node of this node
   */
  public function getLastChild()
  {
    return $this->children[count($this->children) - 1];
  }

  /**
   * Returns the level of this node.
   * @return integer the level of this node
   */
  public function getLevel()
  {
    return $this->token->level;
  }

  /**
   * Returns the source for this node
   * @return string the source for this node
   */
  public function getSource()
  {
    return $this->token->source;
  }

  /**
   * Returns the debug_info option setting for this node
   * @return boolean the debug_info option setting for this node
   */
  public function getDebug_info()
  {
    return $this->getParser()->debug_info;
  }

  /**
   * Returns the line number for this node
   * @return string the line number for this node
   */
  public function getLine()
  {
    return $this->token->line;
  }

  /**
   * Returns the line_numbers option setting for this node
   * @return boolean the line_numbers option setting for this node
   */
  public function getLine_numbers()
  {
    return $this->getParser()->line_numbers;
  }

  /**
   * Returns the filename for this node
   * @return string the filename for this node
   */
  public function getFilename()
  {
    return $this->token->filename;
  }

  /**
   * Returns the Sass parser.
   * @return SassParser the Sass parser
   */
  public function getParser()
  {
    return $this->root->parser;
  }

  /**
   * Returns the property syntax being used.
   * @return string the property syntax being used
   */
  public function getPropertySyntax()
  {
    return $this->root->getParser()->propertySyntax;
  }

  /**
   * Returns the SassScript parser.
   * @return SassScriptParser the SassScript parser
   */
  public function getScript()
  {
    return $this->root->script;
  }

  /**
   * Returns the renderer.
   * @return SassRenderer the renderer
   */
  public function getRenderer()
  {
    return $this->root->renderer;
  }

  /**
   * Returns the render style of the document tree.
   * @return string the render style of the document tree
   */
  public function getStyle()
  {
    return $this->root->getParser()->style;
  }

	/**
	 * Returns a value indicating whether this node is in a directive
	 *
	 * @param boolean true if the node is in a directive, false if not
	 *
	 * @return bool
	 */
  public function inDirective()
  {
    return $this->parent instanceof SassDirectiveNode ||
        $this->parent instanceof SassDirectiveNode;
  }

	/**
	 * Returns a value indicating whether this node is in a SassScript directive
	 *
	 * @param boolean true if this node is in a SassScript directive, false if not
	 *
	 * @return bool
	 */
  public function inSassScriptDirective()
  {
    return $this->parent instanceof SassEachNode ||
      $this->parent->parent instanceof SassEachNode ||
      $this->parent instanceof SassForNode ||
      $this->parent->parent instanceof SassForNode ||
      $this->parent instanceof SassIfNode ||
      $this->parent->parent instanceof SassIfNode ||
      $this->parent instanceof SassWhileNode ||
      $this->parent->parent instanceof SassWhileNode;
  }

	/**
	 * Evaluates a SassScript expression.
	 *
	 * @param string      $expression expression to evaluate
	 * @param SassContext $context    the context in which the expression is evaluated
	 * @param mixed        $x
	 *
	 * @return SassLiteral value of parsed expression
	 */
  public function evaluate($expression, $context, $x=null)
  {
    $context->node = $this;

    return $this->script->evaluate($expression, $context, $x);
  }

  /**
   * Replace interpolated SassScript contained in '#{}' with the parsed value.
   * @param string $expression the text to interpolate
   * @param SassContext $context the context in which the string is interpolated
   * @return string the interpolated text
   */
  public function interpolate($expression, $context)
  {
    $context->node = $this;

    return $this->getScript()->interpolate($expression, $context);
  }

  /**
   * Adds a warning to the node.
   * @param string $message warning message
   */
  public function addWarning($message)
  {
    $warning = new SassDebugNode($this->token, $message);
    $this->addChild($warning);
  }

  /**
   * Parse the children of the node.
   * @param SassContext $context the context in which the children are parsed
   * @return array the parsed child nodes
   */
  public function parseChildren($context)
  {
    $children = array();
    foreach ($this->children as $child) {
      # child could be a SassLiteral /or/ SassNode
      if (method_exists($child, 'parse')) {
        $kid = $child->parse($context);
      } else {
        $kid = array($child);
      }
      $children = array_merge($children, $kid);
    }

    return $children;
  }

	/**
	 * Returns a value indicating if the token represents this type of node.
	 *
	 * @param object $token token
	 *
	 * @throws SassNodeException
	 * @return boolean true if the token represents this type of node, false if not
	 */
  public static function isa($token)
  {
    throw new SassNodeException('Child classes must override this method');
  }

  public function printDebugTree($i = 0)
  {
    echo str_repeat(' ', $i*2).get_class($this)." ".$this->getSource()."\n";
    $p = $this->getParent();
    if ($p) echo str_repeat(' ', $i*2)." parent: ".get_class($p)."\n";
    foreach ($this->getChildren() as $c) {
	    /** @var $c SassNode */
        $c->printDebugTree($i+1);
    }
  }
}
