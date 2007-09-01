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
	var $tmpfile;

	var $markdownify;

	public function __construct() {
		# default params
		$this->markdownify = new markdownify;
		$this->show = param('show');
		$this->tmpfile = dirname(__FILE__).'.tmp';
	}
	public function memory() {
		$old = $this->memory;
		$this->memory = memory_get_usage(true);
		return $this->memory - $old;
	}
	public function time() {
		$old = $this->time;
		$this->time = microtime(true);
		return $this->time - $old;
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
		$orig = file_get_contents($path.'.text');
		$html = file_get_contents($path.'.html');
		$this->memory();
		$this->time();
		$parsed = $this->markdownify->parseString($html);
		$mem = $this->memory();
		$time = $this->time();
		#$diff = PHPDiff($orig, $parsed);

		file_put_contents($this->tmpfile, $parsed);
		$diff = shell_exec('diff "'.$path.'.text" "'.$this->tmpfile.'"');
		#die($diff);
		highlight_diff(&$orig, &$parsed, $diff);
		#echo $orig."\n---\n";
		#echo $parsed."\n---\n";
		#echo $diff."\n---\n";
		#die();
		echo columns(array('html input' => $html, 'original markdown' => $orig, 'parsed markdown' => $parsed))."\n".
		     "  RAM:\t".$mem."\n".
			 "  TIME:\t".$time."\n\n".
			 "  DIFF\n".str_repeat(':', COL_WIDTH)."\n".
		     $diff."\n".str_repeat(':', COL_WIDTH)."\n";
		if (!$this->show) {
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