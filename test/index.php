<?php
header('Content-Type:text/plain; charset=utf-8');

error_reporting(E_ALL);
ini_set('html_errors', false);

chdir(dirname(__FILE__).'/..');

require_once('parsehtml.php');
require_once('html2text2.php');
require_once('test/folder.php');
require_once('test/functions.php');
require_once('test/test.class.php');

$testCases = new folder('MDTest/Markdown.mdtest');

$test = new test;

while ($testCases->read()) {
	if (substr($testCases->file, -5) != '.html') {
		continue;
	}
	$test->run(substr($testCases->file, 0, -5), substr($testCases->path, 0, -5));
}