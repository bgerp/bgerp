<?php
/* SVN FILE: $Id$ */
/**
 * SassCompactRenderer class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 * @package      PHamlP
 * @subpackage  Sass.renderers
 */

require_once 'SassCompressedRenderer.php';

/**
 * SassCompactRenderer class.
 * Each CSS rule takes up only one line, with every property defined on that
 * line. Nested rules are placed next to each other with no newline, while
 * groups of rules have newlines between them.
 * @package      PHamlP
 * @subpackage  Sass.renderers
 */
class SassCompactRenderer extends SassCompressedRenderer
{
  const DEBUG_INFO_RULE = '@media -sass-debug-info';
  const DEBUG_INFO_PROPERTY = 'font-family';

  /**
   * Renders the brace between the selectors and the properties
   * @return string the brace between the selectors and the properties
   */
  protected function between()
  {
    return ' { ';
  }

  /**
   * Renders the brace at the end of the rule
   * @return string the brace between the rule and its properties
   */
  protected function end()
  {
    return " }\n";
  }

  /**
   * Renders a comment.
   * Comments preceeding a rule are on their own line.
   * Comments within a rule are on the same line as the rule.
   * @param SassNode $node the node being rendered
   * @return string the rendered commnt
   */
  public function renderComment($node)
  {
    $nl = ($node->parent instanceof SassRuleNode?'':"\n");

    return "$nl/* " . join("\n * ", $node->children) . " */$nl" ;
  }

  /**
   * Renders a directive.
   * @param SassNode $node the node being rendered
   * @param array $properties properties of the directive
   * @return string the rendered directive
   */
  public function renderDirective($node, $properties)
  {
    return str_replace("\n", '', parent::renderDirective($node, $properties)) .
      "\n\n";
  }

  /**
   * Renders properties.
   * @param SassNode $node the node being rendered
   * @param array $properties properties to render
   * @return string the rendered properties
   */
  public function renderProperties($node, $properties)
  {
    return join(' ', $properties);
  }

  /**
   * Renders a property.
   * @param SassNode $node the node being rendered
   * @return string the rendered property
   */
  public function renderProperty($node)
  {
    $node->important = $node->important ? ' !important' : '';

    return "{$node->name}: {$node->value}{$node->important};";
  }

  /**
   * Renders a rule.
   * @param SassNode $node the node being rendered
   * @param array $properties rule properties
   * @param string $rules rendered rules
   * @return string the rendered rule
   */
  public function renderRule($node, $properties, $rules)
  {
    return $this->renderDebug($node) . parent::renderRule($node, $properties,
      str_replace("\n\n", "\n", $rules)) . "\n";
  }

  /**
   * Renders debug information.
   * If the node has the debug_info options set true the line number and filename
   * are rendered in a format compatible with
   * {@link https://addons.mozilla.org/en-US/firefox/addon/firecompass-for-firebug/ FireCompass}.
   * Else if the node has the line_numbers option set true the line number and
   * filename are rendered in a comment.
   * @param SassNode $node the node being rendered
   * @return string the debug information
   */
  protected function renderDebug($node)
  {
    $indent = $this->getIndent($node);
    $debug = '';
    if ($node->getDebug_info() || $node->getLine_numbers()) {
      $debug .= "$indent/* line {$node->line}, {$node->filename} */\n";
    }

    return $debug;
  }

  /**
   * Renders rule selectors.
   * @param SassNode $node the node being rendered
   * @return string the rendered selectors
   */
    protected function renderSelectors($node)
    {
      $selectors = array();
      foreach ($node->selectors as $selector) {
        if (!$node->isPlaceholder($selector)) {
          $selectors[] = $selector;
        }
      }

      return join(', ', $selectors);
    }
}
