<?php
/**
 * parseHTML is a HTML parser which works with PHP 4 and above.
 * It tries to handle invalid HTML to some degree.
 *
 * @version 1.0 beta
 * @author Milian Wolff (mail@milianw.de, http://milianw.de)
 * @license LGPL, see LICENSE_LGPL.txt and the summary below
 * @copyright (C) 2007  Milian Wolff
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */
class parseHTML {
	/**
	 * xhtml or html 4.01 output
	 *
	 * @var bool
	 */
	var $useXhtmlSyntax = true;
	/**
	 * html to be parsed
	 *
	 * @var string
	 */
	var $html = '';
	/**
	 * node type:
	 *
	 * - tag (see isStartTag)
	 * - text (includes cdata)
	 * - comment
	 * - doctype
	 * - pi (processing instruction)
	 *
	 * @var string
	 */
	var $nodeType = '';
	/**
	 * current node content, i.e. either a
	 * simple string (text node), or something like
	 * <tag attrib="value"...>
	 *
	 * @var string
	 */
	var $node = '';
	/**
	 * wether current node is an opening tag (<a>) or not (</a>)
	 * set to NULL if current node is not a tag
	 * NOTE: empty tags (<br />) set this to true as well!
	 *
	 * @var bool | null
	 */
	var $isStartTag = null;
	/**
	 * wether current node is an empty tag (<br />) or not (<a></a>)
	 *
	 * @var bool | null
	 */
	var $isEmptyTag = null;
	/**
	 * tag name
	 *
	 * @var string | null
	 */
	var $tagName = '';
	/**
	 * attributes of current tag
	 *
	 * @var array (attribName=>value) | null
	 */
	var $tagAttributes = null;
	/**
	 * wether the current tag is a block element
	 *
	 * @var bool | null
	 */
	var $isBlockElement = null;

	/**
	 * keep whitespace
	 *
	 * @var int
	 */
	var $keepWhitespace = 0;
	/**
	 * list of open tags
	 * count this to get current depth
	 *
	 * @var array
	 */
	var $openTags = array();
	/**
	 * get next node, set $this->html prior!
	 *
	 * @param void
	 * @return bool
	 */
	function nextNode() {
		if (empty($this->html)) {
			# we are done with parsing the html string
			return false;
		}
		static $skipWhitespace = true;
		# dont truncate whitespaces for <code> or <pre> contents
		if ($this->isStartTag && !$this->isEmptyTag) {
			array_push($this->openTags, $this->tagName);
			if($this->tagName == 'code' || $this->tagName == 'pre') {
				$this->keepWhitespace++;
			}
		}

		if ($this->html[0] == '<') {
			$token = substr($this->html, 0, 9);
			if (substr($token, 0, 2) == '<?') {
				# xml prolog or other pi's
				trigger_error('this might need some work', E_USER_NOTICE);
				$pos = strpos($this->html, '?>');
				$this->setNode('pi', $pos + 2);
				return true;
			}
			if (substr($token, 0, 4) == '<!--') {
				# comment
				$pos = strpos($this->html, '-->');
				if ($pos === false) {
					# could not find a closing -->, use next gt instead
					# this is firefox' behaviour
					$pos = strpos($this->html, '>') + 1;
				} else {
					$pos += 3;
				}
				$this->setNode('comment', $pos);

				$skipWhitespace = true;
				return true;
			}
			if ($token == '<!DOCTYPE') {
				# doctype
				$this->setNode('doctype', strpos($this->html, '>')+1);

				$skipWhitespace = true;
				return true;
			}
			if ($token == '<![CDATA[') {
				# cdata, use text node

				# remove leading <![CDATA[
				$this->html = substr($this->html, 9);

				$this->setNode('text', strpos($this->html, ']]>')+3);

				# remove trailing ]]> and trim
				$this->node = substr($this->node, 0, -3);
				$this->handleWhitespaces();

				$skipWhitespace = true;
				return true;
			}
			if ($this->parseTag()) {
				# seems to be a tag
				# handle whitespaces
				if ($this->isBlockElement) {
					$skipWhitespace = true;
				} else {
					$skipWhitespace = false;
				}
				return true;
			}
		}
		if ($this->keepWhitespace) {
			$skipWhitespace = false;
		}
		# when we get here it seems to be a text node
		$pos = strpos($this->html, '<');
		if ($pos === false) {
			$pos = strlen($this->html);
		}
		$this->setNode('text', $pos);
		$this->handleWhitespaces();
		if ($skipWhitespace && $this->node == ' ') {
			return $this->nextNode();
		}
		$skipWhitespace = false;
		return true;
	}
	/**
	 * parse tag, set tag name and attributes, see if it's a closing tag and so forth...
	 *
	 * @param void
	 * @return bool
	 */
	function parseTag() {
		$closePos = strpos($this->html, '>');
		$openPos = strpos($this->html, '<', 1);
		$breakPos = strpos($this->html, "\n");
		if (($openPos && $openPos < $closePos) || ($breakPos && $breakPos < $closePos)) {
			# invalid
			trigger_error('invalid tag encountered, will try to handle it gracefully', E_USER_NOTICE);
			$this->html = substr_replace($this->html, '&lt;', 0, 1);
			return false;
		}
		$node = substr($this->html, 1, $closePos);

		# defaults
		$isStartTag = true;
		$isEmptyTag = false;
		if ($node[0] == '/') {
			# closing tag
			$isStartTag = false;
			$node = substr($node, 1);
		}

		# get tag name
		if (!preg_match('#^[a-z][a-z1-6]*(?=\s|/|>)#i', $node, $matches)) {
			# not a valid tag!
			trigger_error('invalid tag encountered, will try to handle it gracefully', E_USER_NOTICE);
			$this->html = substr_replace($this->html, '&lt;', 0, 1);
			return false;
		}
		$tagName = strtolower($matches[0]);
		# update list of open tags
		if ($isStartTag) {
			# tags which could possibly be empty (<br> instead of <br />)
			$emptyTags = array(
				'br',
				'hr',
				'input',
				'img',
			);
			if (substr($node, -1) == '/' || in_array($tagName, $emptyTags)) {
				# empty tag
				$isEmptyTag = true;
				$node = substr($node, 0, -1);
			}
		} else {
			if ($tagName != $this->openTags[count($this->openTags)-1]) {
				# not a valid closing tag!
				trigger_error('invalid closing tag encountered, will try to handle it gracefully', E_USER_NOTICE);
				$this->html = substr_replace($this->html, '&lt;', 0, 1);
				return false;
			}
			array_pop($this->openTags);
		}
		# remove tag name from node copy and trim:
		$node = substr($node, strlen($tagName));
		$node = trim($node);


		$this->nodeType = 'tag';
		$this->isStartTag = $isStartTag;
		$this->isEmptyTag = $isEmptyTag;
		$this->tagName = $tagName;
		$this->tagAttributes = array();
		$this->html = substr($this->html, $closePos+1);
		$this->isBlockElement = $this->isBlockElement($tagName);


		# beautify node, merge whitespaces etc.
		$this->node = '<';
		if (!$isStartTag) {
			if ($this->tagName == 'code' || $this->tagName == 'pre') {
				# dont truncate whitespaces for <code> or <pre> contents
				$this->keepWhitespace--;
			}
			$this->node .= '/';
		}
		$this->node .= $tagName;
		if ($isStartTag && !empty($node)) {
			# get attributes
			preg_match_all('#(\w+)=("[^"]*"|\'[^\']*\')#', $node, $matches, PREG_SET_ORDER);
			for ($i = 0, $j = count($matches); $i < $j; $i++) {
				$this->tagAttributes[$matches[$i][1]] = substr($matches[$i][2], 1, -1);
				$this->node .= ' '.$matches[$i][1].'='.$matches[$i][2];
			}
		}
		if ($this->useXhtmlSyntax && $isEmptyTag) {
			$this->node .= ' /';
		}
		$this->node .= '>';
		return true;
	}
	/**
	 * update all vars and make $this->html shorter
	 *
	 * @param string $type see description for $this->nodeType
	 * @param int $pos to which position shall we cut?
	 * @return void
	 */
	function setNode($type, $pos) {
		if ($this->nodeType == 'tag') {
			# set tag specific vars to null
			# $type == tag should not be called here
			# see this::parseTag() for more
			$this->tagName = null;
			$this->tagAttributes = null;
			$this->isStartTag = null;
			$this->isEmptyTag = null;
			$this->isBlockElement = null;

		}
		$this->nodeType = $type;
		$this->node = substr($this->html, 0, $pos);
		$this->html = substr($this->html, $pos);
	}
	/**
	 * check if $this->html begins with $str
	 *
	 * @param string $str
	 * @return bool
	 */
	function match($str) {
		return substr($this->html, 0, strlen($str)) == $str;
	}
	/**
	 * check if $tagName is a block element
	 *
	 * @param string $tagName
	 * @return bool | null
	 */
	function isBlockElement($tagName) {
		static $elements = array (
			# tag name => <bool> is block
			# block elements
			'address' => true,
			'blockquote' => true,
			'center' => true,
			'del' => true,
			'dir' => true,
			'div' => true,
			'dl' => true,
			'fieldset' => true,
			'form' => true,
			'h1' => true,
			'h2' => true,
			'h3' => true,
			'h4' => true,
			'h5' => true,
			'h6' => true,
			'hr' => true,
			'ins' => true,
			'isindex' => true,
			'menu' => true,
			'noframes' => true,
			'noscript' => true,
			'ol' => true,
			'p' => true,
			'pre' => true,
			'table' => true,
			'ul' => true,
			# set table elements and list items to block as well
			'td' => true,
			'tr' => true,
			'th' => true,
			'li' => true,
			'dd' => true,
			'dt' => true,
			# header items and html / body as well
			'html' => true,
			'body' => true,
			'head' => true,
			'meta' => true,
			'style' => true,
			'title' => true,
			# inline elements
			'a' => false,
			'abbr' => false,
			'acronym' => false,
			'applet' => false,
			'b' => false,
			'basefont' => false,
			'bdo' => false,
			'big' => false,
			'br' => false,
			'button' => false,
			'cite' => false,
			'code' => false,
			'del' => false,
			'dfn' => false,
			'em' => false,
			'font' => false,
			'i' => false,
			'img' => false,
			'ins' => false,
			'input' => false,
			'iframe' => false,
			'kbd' => false,
			'label' => false,
			'map' => false,
			'object' => false,
			'q' => false,
			'samp' => false,
			'script' => false,
			'select' => false,
			'small' => false,
			'span' => false,
			'strong' => false,
			'sub' => false,
			'sup' => false,
			'textarea' => false,
			'tt' => false,
			'var' => false,
		);
		return $elements[$tagName];
	}
	/**
	 * truncate whitespaces
	 *
	 * @param void
	 * @return void
	 */
	function handleWhitespaces() {
		if ($this->keepWhitespace) {
			# <pre> or <code> before...
			return;
		}
		# truncate multiple whitespaces to a single one
		$this->node = preg_replace('#\s+#s', ' ', $this->node);
	}
}
/**
 * indent a HTML string properly
 *
 * @param string $html
 * @param string $indent optional
 * @return string
 */
function indentHTML($html, $indent = "  ") {
	$parser = new parseHTML;
	$parser->html = $html;
	$html = '';
	$last = true;
	$indent_a = array();
	while($parser->nextNode()) {
		if ($parser->nodeType == 'tag' && $parser->isBlockElement) {
			$isPreOrCode = in_array($parser->tagName, array('code', 'pre'));
			if (!$parser->keepWhitespace && !$last && !$isPreOrCode) {
				$html .= "\n";
			}
			if ($parser->isStartTag) {
				$html .= implode($indent_a);
				array_push($indent_a, $indent);
			} else {
				array_pop($indent_a);
				if (!$isPreOrCode) {
					$html .= implode($indent_a);
				}
			}
			$html .= $parser->node;
			if (!$parser->keepWhitespace && !($isPreOrCode && $parser->isStartTag)) {
				$html .= "\n";
			}
			$last = true;
		} else {
			if ($last && !$parser->keepWhitespace) {
				$html .= implode($indent_a);
			}
			$html .= $parser->node;

			if (in_array($parser->nodeType, array('comment', 'pi', 'doctype'))) {
				var_dump($parser->node);
				$html .= "\n";
			} else {
				$last = false;
			}
		}
	}
	return $html;
}
/*
# testcase / example
error_reporting(E_ALL);

$html = '<?xml version="1.0" encoding="iso-8859-1"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <title>NewsPaper</title>
</head>
<body>
	<div style=">">
		asdfasdf
	</div>
</body>
</html>
';
echo indentHTML($html);
die();
*/