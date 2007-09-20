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
			require_once 'MDTest/Implementations/markdown-extra.php';
			$this->markdownify = new Markdownify_Extra($linksAfterEachParagraph, $bodyWidth, $keepHTML);
			echo 'Using '.MARKDOWN_PARSER_CLASS.' V'.MARKDOWNEXTRA_VERSION." with Markdownify_Extra\n\n";
		} else {
			require_once 'MDTest/Implementations/markdown.php';
			$this->markdownify = new Markdownify($linksAfterEachParagraph, $bodyWidth, $keepHTML);
			echo 'Using '.MARKDOWN_PARSER_CLASS.' V'.MARKDOWN_VERSION." with Markdownify\n\n";
		}
		$this->show = param('show');

		require_once 'test/diff.php';
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
		$html = file_get_contents($path.'.html');
		$this->memory();
		$this->time();
		$parsed = $this->markdownify->parseString($html);
		$time_parsed = $this->time();
		$mem_parsed = $this->memory();
		if (param('profile')) {
			if ($testcase == 'test') {
				return 2;
			}
			$new = Markdown($parsed);

			if (param('indented')) {
				$html = indentHTML($html);
				$new = indentHTML($new);
			}
			
			$diff = $this->diff->diff(&$html, &$new)->markChanges()->render();

			switch ($this->isRegression($diff, $testcase)) {
				case 0:
					$status = color_str('REGRESSION', 'red');
					break;
				case 1:
					$status = color_str('PASSED', 'green');
					break;
				case 2:
					$status = color_str('ACCEPTED', 'yellow');
					break;
				case 3:
					$status = color_str('PENDING', 'light gray');
					break;
			}
			printf("%2d: %-50s... %6s ms\t%6d Bytes\t%s\n", $i, $testcase, round($time_parsed * 1000, 2), $mem_parsed, $status);
			return;
		} else {
			echo "running testcase #$i: $testcase ($path)\n".str_repeat('=', COL_WIDTH)."\n";
		}
		if (param('diff-markdown')) {
			$orig = file_get_contents($path.'.text');
			$diff = $this->diff->diff(&$orig, &$parsed)->markChanges()->render();
			echo columns(array('html input' => $html, 'original markdown' => $orig, 'parsed markdown' => $parsed));

			echo "\n".
				"  RAM:\t".$mem_parsed."\n".
				"  TIME:\t".$time_parsed."\n\n";
		} elseif(param('twice')) {
			$new = Markdown($parsed);
			$parsed2 = $this->markdownify->parseString($new);
			$new2 = Markdown($parsed2);

			if (param('indented')) {
				$html = indentHTML($html);
				$new = indentHTML($new);
				$new2 = indentHTML($new2);
			}
			
			$diff = $this->diff->diff(&$html, &$new2)->markChanges()->render();
			echo columns(array('html input' => $html, 'html output 1' => $new, 'html output 2' => $new2));
			$diff .= $this->diff->diff(&$parsed, &$parsed2)->markChanges()->render();
			echo columns(array('markdown 1' => $parsed, 'markdown 2' => $parsed2));

			if (param('regressions')) {
				$this->checkRegression($diff, $testcase);
			}
		} else {
			$this->memory();
			$this->time();
			$new = Markdown($parsed);
			$time_md = $this->time();
			$mem_md = $this->memory();
			
			if (param('whitespace')) {
				$parsed = str_replace(' ', '.', $parsed);
			}
			
			if (param('indented')) {
				$new = indentHTML($new);
				$html = indentHTML($html);
			}
			
			$diff = $this->diff->diff(&$html, &$new)->markChanges()->render();

			echo columns(array('html input' => $html, 'generated markdown' => $parsed, 'html output' => $new));
			echo columns(array('', "RAMDIFF: \t$mem_parsed bytes\nTIME:    \t$time_parsed seconds", "RAMDIFF: \t$mem_md bytes\nTIME:    \t$time_md seconds"), COL_WIDTH, false);

			if (param('regressions')) {
				$this->checkRegression($diff, $testcase);
			}
		}
		if (param('diff') && !param('regressions')) {
			echo "\nDIFF\n".str_repeat(':', COL_WIDTH)."\n".
					$diff."\n".str_repeat(':', COL_WIDTH)."\n";
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
		if ($testcase == 'test') {
			return;
		}
		switch ($this->isRegression($diff, $testcase)) {
			case 1:
				echo color_str('no differences found', 'light green')."\n";
				return;
			case 2:
				echo color_str('diff was previously accepted', 'yellow')."\n";
				return;
			case 0:
				if ($this->cliConfirm(color_str('possible regression, show diffs?', 'light red'))) {
					echo columns(array('current diff' => $diff, 'old diff' => $this->old_diff));
				} elseif ($this->cliConfirm(color_str('show diff?', 'light cyan'))) {
					echo $diff."\n";
				}
				break;
		}
		if ($this->cliConfirm(color_str('accept current diff?', 'cyan'))) {
			file_put_contents($this->regressionpath.$testcase.'.diff', $diff);
		}
	}
	/**
	 * check if a regression occured
	 * 
	 * @param string $diff
	 * @param string $testcase
	 * @return int 0: regression
	 *             1: no differences
	 *             2: accepted diff
	 *             3: not yet accepted
	 */
	public function isRegression($diff, $testcase) {
		if (!isset($this->regressionpath)) {
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
			unset($args['--profile']);
			ksort($args);
			$this->regressionpath = dirname(__FILE__).'/accepted_diffs/'.md5(serialize($args)).'/';
			if (!is_dir($this->regressionpath)) {
				mkdir($this->regressionpath);
				file_put_contents($this->regressionpath.'args.txt', print_r($args, true));
			}
		}
		if (empty($diff)) {
			return 1;
		}
		if (file_exists($this->regressionpath.$testcase.'.diff')) {
			$this->old_diff = file_get_contents($this->regressionpath.$testcase.'.diff');
			if ($diff == $this->old_diff) {
				return 2;
			} else {
				return 0;
			}
		} else {
			return 3;
		}
	}
}