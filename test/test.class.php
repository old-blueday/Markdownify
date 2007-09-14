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
	var $diff;
	var $diff_render;
	var $diff_render_inline;

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

		require_once('test/diff.php');
		$this->diff = new CliColorDiff();
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
		echo "running testcase #$i: $testcase ($path)\n".str_repeat('=', COL_WIDTH)."\n";
		$html = file_get_contents($path.'.html');
		$this->memory();
		$this->time();
		$parsed = $this->markdownify->parseString($html);
		$time_parsed = $this->time();
		$mem_parsed = $this->memory();
		if (param('diff-markdown')) {
			$orig = file_get_contents($path.'.text');
			$diff = $this->diff->diff(&$orig, &$parsed)->markChanges();
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
			
			if (param('indented')) {
				$new = indentHTML($new);
				$html = indentHTML($html);
			}
			
			$diff = $this->diff->diff(&$html, &$new)->markChanges();

			echo columns(array('html input' => $html, 'generated markdown' => $parsed, 'html output' => $new));
			echo columns(array('', "RAMDIFF: \t$mem_parsed bytes\nTIME:    \t$time_parsed seconds", "RAMDIFF: \t$mem_md bytes\nTIME:    \t$time_md seconds"), COL_WIDTH, false);

			if (param('regressions')) {
				$this->checkRegression($diff, $testcase);
			}
		}
		if (param('diff') && !param('regressions')) {
			echo "\nDIFF\n".str_repeat(':', COL_WIDTH)."\n".
					$diff->render()."\n".str_repeat(':', COL_WIDTH)."\n";
		}
		if (!$this->show && !param('test') && !param('regressions')) {
			$this->awaitInput();
		}
	}
	public function awaitInput($output = '...hit enter...') {
		echo $output;
		fgets(STDIN);
	}
	public function cliConfirm($question) {
		echo $question.' [Y/n] ';
		$input = strtolower(trim(fgets(STDIN)));
		if ($input == '' || $input == 'y') {
			return true;
		} elseif ($input == 'n') {
			return false;
		} else {
			echo color_str('press "n" to decline or "y" to confirm', 'red')."\n";
			return $this->cliConfirm($question);
		}
	}
	public function checkRegression($diff, $testcase) {
		static $path;
		if (!isset($path)) {
			# order args
			$args = array();
			for ($i = 0, $c = count($_SERVER['argv']); $i < $c; $i++) {
				$current = $_SERVER['argv'][$i];

				if (substr($current, 0, 2) == '--') {
					$args[$current] = '';
				} elseif (end($args) == '') {
					$args[key($args)] = $current;
				}
			}
			unset($args['--show']);
			ksort($args);
			$path = dirname(__FILE__).'/accepted_diffs/'.md5(serialize($args)).'/';
			if (!is_dir($path)) {
				mkdir($path);
				file_put_contents($path.'args.txt', print_r($args, true));
			}
		}
		if ($diff->isEmpty()) {
			echo color_str('no differences found', 'light green')."\n";
			return;
		}
		$diff = $diff->render();
		if (file_exists($path.$testcase.'.diff')) {
			$old_diff = file_get_contents($path.$testcase.'.diff');
			if ($diff == $old_diff) {
				echo color_str('diff was previously accepted', 'yellow')."\n";
				return;
			} elseif ($this->cliConfirm(color_str('possible regression, show diffs?', 'light red'))) {
				echo columns(array('current diff' => $diff, 'old diff' => $old_diff));
			}
		} elseif ($this->cliConfirm(color_str('show diff?', 'light cyan'))) {
			echo $diff."\n";
		}
		if ($this->cliConfirm(color_str('accept current diff?', 'cyan'))) {
			file_put_contents($path.$testcase.'.diff', $diff);
		}
	}
}