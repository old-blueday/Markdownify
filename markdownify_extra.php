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
	 * table data, including rows with content and the maximum width of each col
	 * 
	 * @var array
	 */
	var $table = array();
	/**
	 * current col
	 * 
	 * @var int
	 */
	var $col = -1;
	/**
	 * current row
	 * 
	 * @var int
	 */
	var $row = 0;
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
		# build RegEx lookahead to decide wether table can pe parsed or not
		$inlineTags = array_keys($this->parser->blockElements, false, true);
		$colContents = '(?:[^<]|<(?:'.implode('|', $inlineTags).'|[^a-z]))+';
		$this->tableLookaheadHeader = '{
		^\s*(?:<thead\s*>)?\s*                               # open optional thead
			<tr\s*>\s*(?:                                    # start required row with headers
				<th\s+(?:align=("|\')(?:left|center|right)\1)?\s*>   # header with optional align
				\s*'.$colContents.'\s*                       # contents
				</th>\s*                                     # close header
			)+</tr>                                          # close row with headers
		\s*(?:</thead>)?                                     # close optional thead
		}sxi';
		$this->tdSubstitute = '\s*'.$colContents.'\s*               # contents
					</td>\s*';
		$this->tableLookaheadBody = '{
			\s*(?:<tbody\s*>)?\s*                            # open optional tbody
				(?:<tr\s*>\s*                                # start row
					%s                                       # cols to be substituted
				</tr>)+                                      # close row
			\s*(?:</tbody>)?                                 # close optional tbody
		\s*</table>                                          # close table
		}sxi';
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
	function flushLinebreaks() {
		echo "flush lbr: {$this->parser->tagName} {$this->parser->lineBreaks}\n";
		parent::flushLinebreaks();
	}
	/**
	 * handle <table> tags
	 * 
	 * @param void
	 * @return void
	 */
	function handleTag_table() {
		if ($this->parser->isStartTag) {
			# check if upcoming table can be converted
			if (preg_match($this->tableLookaheadHeader, $this->parser->html, $matches)) {
				# header seems good, now check body
				# get align & number of cols
				preg_match_all('#<th\s+(?:align=("|\')(left|right|center)\1\s*)?>#si', $matches[0], $cols);
				$regEx = '';
				$i = 1;
				$aligns = array();
				foreach ($cols[2] as $align) {
					$align = strtolower($align);
					array_push($aligns, $align);
					if (empty($align)) {
						$align = 'left'; # default value
					}
					$td = '\s+align=("|\')'.$align.'\\'.$i;
					$i++;
					if ($align == 'left') {
						# look for empty align or left
						$td = '(?:'.$td.')?';
					}
					$td = '<td'.$td.'\s*>';
					$regEx .= $td.$this->tdSubstitute;
				}
				$regEx = sprintf($this->tableLookaheadBody, $regEx);
				if (preg_match($regEx, $this->parser->html, $matches, null, strlen($matches[0]))) {
					# this is a markdownable table tag!
					$this->flushLinebreaks();
					$this->table = array(
						'rows' => array(),
						'col_widths' => array(),
						'aligns' => $aligns,
					);
					$this->row = 0;
				} else {
					# non markdownable table
					$this->handleTagToText();
				}
			} else {
				# non markdownable table
				$this->handleTagToText();
			}
		} else {
			# finally build the table in Markdown Extra syntax
			$separator = array();
			# seperator with correct align identifikators
			foreach($this->table['aligns'] as $col => $align) {
				$left = ' ';
				$right = ' ';
				switch ($align) {
					case 'left':
						$left = ':';
						break;
					case 'center':
						$right = ':';
					case 'right':
						$right = ':';
						break;
				}
				array_push($separator, $left.str_repeat('-', $this->table['col_widths'][$col]).$right);
			}
			$separator = '|'.implode('|', $separator).'|';
			
			$rows = array();
			# add padding
			array_walk_recursive($this->table['rows'], array(&$this, 'alignTdContent'));
			$header = array_shift($this->table['rows']);
			array_push($rows, '| '.implode(' | ', $header).' |');
			array_push($rows, $separator);
			foreach ($this->table['rows'] as $row) {
				array_push($rows, '| '.implode(' | ', $row).' |');
			}
			$this->out(implode("\n".$this->indent, $rows));
			$this->table = array();
			$this->setLineBreaks(2);
		}
	}
	/**
	 * properly pad content so it is aligned as whished
	 * should be used with array_walk_recursive on $this->table['rows']
	 * 
	 * @param string &$content
	 * @param int $col
	 * @return void
	 */
	function alignTdContent(&$content, $col) {
		switch ($this->table['aligns'][$col]) {
			default:
			case 'left':
				$content .= str_repeat(' ', $this->table['col_widths'][$col] - strlen($content));
				break;
			case 'right':
				$content = str_repeat(' ', $this->table['col_widths'][$col] - strlen($content)).$content;
				break;
			case 'center':
				$paddingNeeded = $this->table['col_widths'][$col] - strlen($content);
				$left = floor($paddingNeeded / 2);
				$right = $paddingNeeded - $left;
				$content = str_repeat(' ', $left).$content.str_repeat(' ', $right);
				break;
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
			$this->col = -1;
		} else {
			$this->row++;
		}
	}
	/**
	 * handle <td> tags
	 * 
	 * @param void
	 * @return void
	 */
	function handleTag_td() {
		if ($this->parser->isStartTag) {
			$this->col++;
			if (!isset($this->table['col_widths'][$this->col])) {
				$this->table['col_widths'][$this->col] = 0;
			}
			$this->buffer();
		} else {
			$buffer = trim($this->unbuffer());
			$this->table['col_widths'][$this->col] = max($this->table['col_widths'][$this->col], strlen($buffer));
			$this->table['rows'][$this->row][$this->col] = $buffer;
		}
	}
	/**
	 * handle <th> tags
	 *
	 * @param void
	 * @return void
	 */
	function handleTag_th() {
		$this->handleTag_td();
	}
}