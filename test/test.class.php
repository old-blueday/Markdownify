<?php

class test {
	var $memory = 0;
	var $time = 0;

	var $html2text;

	public function __construct() {
		$this->html2text = new html2text;
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
		if (isset($_REQUEST['show']) && $_REQUEST['show'] != $i) {
			return;
		}
		echo "running testcase: $testcase ($path)\n".str_repeat('=', 75)."\n";
		$orig = file_get_contents($path.'.text');
		$html = file_get_contents($path.'.html');
		$this->memory();
		$this->time();
		$parsed = $this->html2text->parseString($html);
		echo "  input:\n".str_repeat('-', 75)."\n".$html."\n\n".
		     str_repeat('-', 75)."\n".
		     "  original:\n".str_repeat('-', 75)."\n".$orig."\n\n".
		     str_repeat('-', 75)."\n".
		     "  html2text:\n".str_repeat('-', 75)."\n".$parsed."\n\n".
		     str_repeat('-', 75)."\n".
		     "  RAM:\t".$this->memory()."\n".
			 "  TIME:\t".$this->time()."\n\n".str_repeat(':',50)."\n".
		     PHPDiff($orig, $parsed)."\n".str_repeat(':', 50)."\n";
		$this->awaitInput();
	}
	public function awaitInput($output = '...hit enter...') {
		if (!defined('STDIN'))
			return;
		echo $output;
		fgets(STDIN);
	}
}