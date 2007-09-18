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
		
		} else {
		
		}
	}
	/**
	 * flush stacked abbr tags
	 * 
	 * @param void
	 * @return void
	 */
	function flushStacked_abbr() {
	
	}
}