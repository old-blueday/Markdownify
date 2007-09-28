<?php
error_reporting(E_ALL);

define('COL_WIDTH', 178);
define('DIFF_FGCOLOR_D', 'white');
define('DIFF_BGCOLOR_D', 'red');
define('DIFF_FGCOLOR_A', 'white');
define('DIFF_BGCOLOR_A', 'green');
define('DIFF_FGCOLOR_C', 'white');
define('DIFF_BGCOLOR_C', 'brown');

chdir(dirname(__FILE__));

require_once 'folder.php';
require_once 'functions.php';
require_once 'test.class.php';
require_once '../markdownify.php';
require_once '../markdownify_extra.php';


switch (param('suite')) {
	default:
		$suite = 'Markdown';
		break;
	case 2:
		$suite = 'PHP Markdown';
		break;
	case 3:
		$suite = 'PHP Markdown Extra';
		break;
}

define('TESTSUITE', 'mdtest/'.$suite.'.mdtest/');

if (param('profile')) {
	echo "\n== $suite ==\n";
}

$test = new test;

if ($tc = param('test')) {
	if (!file_exists(TESTSUITE.$tc.'.html')) {
		trigger_error('Testcase '.$tc.' could not be found!', E_USER_ERROR);
	}
	$test->run($tc, TESTSUITE.$tc);
	die();
}
$testCases = new folder(TESTSUITE);

while ($testCases->read()) {
	if (!in_array(substr($testCases->file, -5), array('.html', 'xhtml'))) {
		continue;
	}
	$test->run(substr($testCases->file, 0, -5), substr($testCases->path, 0, -5), $testCases->path);
}
