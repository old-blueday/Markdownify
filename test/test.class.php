<?php
/**
 * test class for Markdownify
 *
 * @author Milian Wolff (<mail@milianw.de>, <http://milianw.de>)
 * @license GPL, see LICENSE_GPL.txt and below
 * @copyright (C) 2007  Milian Wolff
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
class test {
	var $memory = 0;
	var $time = 0;
	var $show;

	var $markdownify;

	public function __construct() {
		# default params
		$linksAfterEachParagraph = param('links');
		$bodyWidth = param('width');
		$keepHTML = param('html', true);

		if (param('extra')) {
			require_once('MDTest/Implementations/markdown-extra.php');
			echo 'Using '.MARKDOWN_PARSER_CLASS.' V'.MARKDOWNEXTRA_VERSION."\n\n";
		} else {
			require_once('MDTest/Implementations/markdown.php');
			echo 'Using '.MARKDOWN_PARSER_CLASS.' V'.MARKDOWN_VERSION."\n\n";
		}

		$this->markdownify = new Markdownify($linksAfterEachParagraph, $bodyWidth, $keepHTML);
		$this->show = param('show');
	}
	public function memory() {
		$old = $this->memory;
		$this->memory = xdebug_memory_usage(true);
		return floatval($this->memory - $old);
	}
	public function time() {
		$old = $this->time;
		$this->time = microtime(true);
		return floatval($this->time - $old);
	}
	public function dump($mixed) {
		$args = func_get_args();
		$format = '';

		foreach ($args as &$arg) {
			switch (gettype($arg)) {
				case 'boolean':
					$format .= '%-10s';
					$arg = $arg ? 'yes' : 'no';
					break;
				case 'integer':
					$format .= '%-20d';
					break;
				case 'double':
					$format .= '%-20f';
					break;
				case 'array':
					$format .= '%-10s';
					$arg = 'Array ['.count($arg).']';
					break;
				case 'NULL':
					$format .= '%-10s';
					$arg = 'NULL';
					break;
				default:
					unset($arg);
			}
		}
		vprintf($format, $args);
	}
	public function run($testcase, $path) {
		static $i = 0;
		$i++;
		if ($this->show && $this->show != $i) {
			return;
		}
		echo "running testcase: $testcase ($path)\n".str_repeat('=', COL_WIDTH)."\n";
		$html = file_get_contents($path.'.html');
		$this->memory();
		$this->time();
		$parsed = $this->markdownify->parseString($html);
		$time_parsed = $this->time();
		$mem_parsed = $this->memory();
		if (param('diff-markdown')) {
			$orig = file_get_contents($path.'.text');
			$diff = PHPDiff($orig, $parsed);
			highlight_diff(&$orig, &$parsed, $diff);
			echo columns(array('html input' => $html, 'original markdown' => $orig, 'parsed markdown' => $parsed));

			echo "\n".
				"  RAM:\t".$mem_parsed."\n".
				"  TIME:\t".$time_parsed."\n\n";
		} else {
			$this->memory();
			$this->time();
			$new = Markdown($parsed);
			$time_md = $this->time();
			$mem_md = $this->memory();
			$diff = PHPDiff($html, $new);
			highlight_diff(&$html, &$new, $diff);

			if (param('whitespace')) {
				$html = str_replace(array("\t", ' '), array('..', '.'), $html);
				$new = str_replace(array("\t", ' '), array('..', '.'), $new);
				$parsed = str_replace(array("\t", ' '), array('..', '.'), $parsed);
			}
			echo columns(array('html input' => $html, 'generated markdown' => $parsed, 'html output' => $new));
			echo columns(array('', "RAMDIFF: \t$mem_parsed bytes\nTIME:    \t$time_parsed seconds", "RAMDIFF: \t$mem_md bytes\nTIME:    \t$time_md seconds"), COL_WIDTH, false);
		}
		if (param('diff')) {
			echo "\nDIFF\n".str_repeat(':', COL_WIDTH)."\n".
					$diff."\n".str_repeat(':', COL_WIDTH)."\n";
		}
		if (!$this->show && !param('test')) {
			$this->awaitInput();
		}
	}
	public function awaitInput($output = '...hit enter...') {
		if (!defined('STDIN'))
			return;
		echo $output;
		fgets(STDIN);
	}
}