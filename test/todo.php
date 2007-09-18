<?php
$md = file_get_contents(dirname(__FILE__).'/../markdownify.php');

preg_match_all('#/\*\* TODO: (.+) \*\*/#Us', $md, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

foreach ($matches as $a) {
	# line:
	$line = substr_count(substr($md, 0, $a[0][1]), "\n");
	echo "Line $line: {$a[1][0]}\n";
}