<?php
/**
 * Class to convert HTML to Markdown with PHP Markdown Extra syntax support.
 *
 * @version 1.0.0 alpha
 * @author Milian Wolff (<mail@milianw.de>, <http://milianw.de>)
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

/**
 * standard Markdownify class
 */
require_once dirname(__FILE__).'/markdownify.php';

class Markdownify_Extra extends Markdownify {
	/**
	 * constructor, see Markdownify::Markdownify() for more information
	 */
	function Markdownify_Extra($linksAfterEachParagraph = false, $bodyWidth = false, $keepHTML = true) {
		parent::Markdownify($linksAfterEachParagraph, $bodyWidth, $keepHTML);
		
		### new markdownable tags & attributes
		# header ids: # foo {bar}
		$this->isMarkdownable['h1']['id'] = 'optional';
		$this->isMarkdownable['h2']['id'] = 'optional';
		$this->isMarkdownable['h3']['id'] = 'optional';
		$this->isMarkdownable['h4']['id'] = 'optional';
		$this->isMarkdownable['h5']['id'] = 'optional';
		$this->isMarkdownable['h6']['id'] = 'optional';
		# tables
		$this->isMarkdownable['table'] = array();
		$this->isMarkdownable['th'] = array(
			'align' => 'optional',
		);
		$this->isMarkdownable['td'] = array(
			'align' => 'optional',
		);
		$this->isMarkdownable['tr'] = array();
		array_push($this->ignore, 'tbody');
		array_push($this->ignore, 'thead');
		array_push($this->ignore, 'tfoot');
		# sup
		$this->isMarkdownable['sup'] = array(
			'id' => 'optional',
		);
		# abbr
		$this->isMarkdownable['abbr'] = array(
			'title' => 'required',
		);
		
		foreach ($this->isMarkdownable as $tag => $def) {
			if (in_array($tag, array('thead', 'tbody', 'tfoot', 'td', 'tr', 'th')) || !$this->parser->blockElements[$tag]) {
				$this->isMarkdownable_inTable[$tag] = $def;
			}
		}
		$this->isMarkdownable_copy = $this->isMarkdownable;
	}
	function handleTagToText() {
		if (empty($this->table) || !$this->keepHTML) {
			parent::handleTagToText();
		} else {
			$this->revertSnapshot();
			$this->isMarkdownable = $this->isMarkdownable_copy;
			var_dump($this->parser->tagAttributes);
			trigger_error('todo', E_USER_ERROR);
		}
	}
	/**
	 * handle header tags (<h1> - <h6>)
	 *
	 * @param int $level 1-6
	 * @return void
	 */
	function handleHeader($level) {
		static $id = null;
		if ($this->parser->isStartTag) {
			if (isset($this->parser->tagAttributes['id'])) {
				$id = $this->parser->tagAttributes['id'];
			}
		} else {
			if (!is_null($id)) {
				$this->out(' {#'.$id.'}');
				$id = null;
			}
		}
		parent::handleHeader($level);
	}
	/**
	 * handle <abbr> tags
	 * 
	 * @param void
	 * @return void
	 */
	function handleTag_abbr() {
		if ($this->parser->isStartTag) {
			$this->stack();
			$this->buffer();
		} else {
			$tag = $this->unstack();
			$tag['text'] = $this->unbuffer();
			$this->out($tag['text']);
			if (!in_array($tag, $this->stack['abbr'])) {
				array_push($this->stack['abbr'], $tag);
			}
		}
	}
	/**
	 * flush stacked abbr tags
	 * 
	 * @param void
	 * @return void
	 */
	function flushStacked_abbr() {
		$out = array();
		foreach ($this->stack['abbr'] as $k => $tag) {
			if (!isset($tag['unstacked'])) {
				array_push($out, ' *['.$tag['text'].']: '.$tag['title']);
				$tag['unstacked'] = true;
				$this->stack['abbr'][$k] = $tag;
			}
		}
		if (!empty($out)) {
			$this->out("\n\n".implode("\n", $out));
		}
	}
	var $snapshots = array();
	var $snapShot_conf = array(
		'parser' => array(
			'html',
			'nodeType',
			'node',
			'isStartTag',
			'isEmptyTag',
			'tagName',
			'tagAttributes',
			'isBlockElement',
			'keepWhitespace',
			'openTags',
		),
		'notConverted',
		'skipConversion',
	);
	function setSnapshot() {
		$snapshot = array();
		foreach ($this->snapShot_conf as $k => $v) {
			if (is_array($v)) {
				$snapshot[$k] = array();
				foreach ($v as $k2) {
					$snapshot[$k][$k2] = $this->$k->$k2;
				}
			} else {
				$snapshot[$v] = $this->$v;
			}
		}
		array_push($this->snapshots, $snapshot);
	}
	function revertSnapshot() {
		$snapshot = array_pop($this->snapshots);
		foreach ($this->snapShot_conf as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $k2) {
					 $this->$k->$k2 = $snapshot[$k][$k2];
				}
			} else {
				$this->$v = $snapshot[$v];
			}
		}
	}
	function dropSnapshot() {
		array_pop($this->snapshots);
	}
	var $tables = array();
	var $table;
	var $row;
	/**
	 * handle <table> tags
	 * 
	 * @param void
	 * @return void
	 */
	function handleTag_table() {
		if ($this->parser->isStartTag) {
			$this->setSnapshot();
			$this->isMarkdownable = $this->isMarkdownable_inTable;
			$i = count($this->tables);
			$this->tables[$i] = array(
				'cols' => array(),
				'col_widths' => array(),
				'first'
			);
			$this->table =& $this->tables[$i];
		} else {
			$this->dropSnapshot();
			var_dump($this->table);
			die();
			if (empty($this->tables)) {
				$this->isMarkdownable = $this->isMarkdownable_copy;
			}
		}
	}
	/**
	 * handle <tr> tags
	 * 
	 * @param void
	 * @return void
	 */
	function handleTag_tr() {
		if ($this->parser->isStartTag) {
			$i = count($this->table['cols']);
			$this->table['cols'][$i] = array();
			$this->row =& $this->table['cols'][$i];
		} else {
			$this->row = null;
		}
	}
	/**
	 * handle <th> tags
	 * 
	 * @param void
	 * @return void
	 */
	function handleTag_th() {
		if ($this->parser->isStartTag) {
			$this->buffer();
		} else {
			$buffer = $this->unbuffer();
			array_push($this->row, $buffer);
			array_push($this->row_width, strlen($buffer));
		}
	}
	/**
	 * handle <td> tags
	 *
	 * @param void
	 * @return void
	 */
	function handleTag_td() {
		if (!$this->parser->isStartTag) {
			$this->buffer();
		} else {
			
		}
	}
}