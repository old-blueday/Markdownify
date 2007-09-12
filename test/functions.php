<?php
function param($name, $default = false) {
	if (!in_array('--'.$name, $_SERVER['argv']))
		return $default;
	reset($_SERVER['argv']);
	while (each($_SERVER['argv'])) {
		if (current($_SERVER['argv']) == '--'.$name)
			break;
	}
	$value = next($_SERVER['argv']);
	if ($value === false || substr($value, 0, 2) == '--')
		return true;
	else
		return $value;
}

/**
 * get a ascii color sequenz
 *
 * @param string $fgcolor (see $fgcolors array in this function)
 * @param string $bgcolor (see $bgcolors array in this function)
 * @param bool $blink
 * @return string
 *
 * @license LGPL
 * @author Milian Wolff (<mail@milianw.de>, <http://milianw.de>)
 */
function get_cli_color($fgcolor, $bgcolor = false, $blink = false) {
	$fgcolors = array(
		'black' => '0;30',
		'red' => '0;31',
		'green' => '0;32',
		'brown' => '0;33',
		'blue' => '0;34',
		'purple' => '0;35',
		'cyan' => '0;36',
		'light gray' => '0;37',
		'dark gray' => '1;30',
		'light red' => '1;31',
		'light green' => '1;32',
		'yellow' => '1;33',
		'light blue' => '1;34',
		'light purple' => '1;35',
		'light cyan' => '1;36',
		'white' => '1;37',
		'no color' => '0;0',
	);
	$bgcolors = array(
		'black' => '40',
		'red' => '41',
		'green' => '42',
		'brown' => '43',
		'blue' => '44',
		'purple' => '45',
		'cyan' => '46',
		'light gray' => '47',
	);
	$color = array();
	if ($blink) {
		array_push($color, '5');
	}
	if ($fgcolor) {
		array_push($color, $fgcolors[$fgcolor]);
	}
	if ($bgcolor) {
		array_push($color, $bgcolors[$bgcolor]);
	}
	return chr(033).'['.implode(';', $color).'m';
}
/**
 * simple wrapper for get_cli_color
 *
 * @param string $str to be colorized
 * @param string $fgcolor see get_cli_color() function
 * @param string $bgcolor
 * @param bool $blink
 * @return string
 */
function color_str($str, $fgcolor, $bgcolor = false, $blink = false) {
	return get_cli_color($fgcolor, $bgcolor, $blink).$str.reset_cli_color();
}
/**
 * get string which resets ascii color sequences
 *
 * @return string
 *
 * @license LGPL
 * @author Milian Wolff (<mail@milianw.de>, <http://milianw.de>)
 */
function reset_cli_color() {
	return chr(033).'[0m';
}

/**
 * higlight those parts marked by $diff which differr in $old and $new
 *
 * @param str $old
 * @param str $new
 * @param str $diff
 * @return string
 *
 * @license LGPL
 * @author Milian Wolff (<mail@milianw.de>, <http://milianw.de>)
 */
function highlight_diffs($old, $new, $diff) {
	preg_match_all('#^(\d+)(?:,(\d+))?([adc])(\d+)(?:,(\d+))?$#m', $diff, $diff_blocks, PREG_SET_ORDER);

	$d_start = get_cli_color(DIFF_FGCOLOR_D, DIFF_BGCOLOR_D);
	$a_start = get_cli_color(DIFF_FGCOLOR_A, DIFF_BGCOLOR_A);
	$c_start = get_cli_color(DIFF_FGCOLOR_C, DIFF_BGCOLOR_C);

	foreach ($diff_blocks as $block) {
		if (empty($block[2])) {
			$block[2] = $block[1];
		}
		if (empty($block[5])) {
			$block[5] = $block[4];
		}

		if ($block[3] == 'c') {
			color_lines(&$old, $block[1], $block[2], $c_start);
			color_lines(&$new, $block[4], $block[5], $c_start);
		} elseif ($block[3] == 'd') {
			color_lines(&$old, $block[1], $block[2], $d_start);
		} else {
			color_lines(&$new, $block[4], $block[5], $a_start);
		}
	}
	return array($old, $new);
}
/**
 * highlight some lights with a given ascii color for cli output
 *
 * @param str $str to be highlighted
 * @param int $line_start
 * @param int $line_end
 * @param str $color use my get_cli_color() function
 * @return string
 *
 * @license LGPL
 * @author Milian Wolff (<mail@milianw.de>, <http://milianw.de>)
 */
function color_lines($str, $line_start, $line_end, $color) {
	$line_start--;
	$line_end--;

	$lines = explode("\n", $str);
	$reset = reset_cli_color();

	foreach ($lines as $line => $str) {
		if ($line >= $line_start) {
			$lines[$line] = $color.$str.$reset;
		}
		if ($line == $line_end) {
			break;
		}
	}
	$str = implode("\n", $lines);
	return $str;
}
/**
 * multicolumn cli, requires mb extension and assumes utf-8 input
 * ascii color sequences are ok in column texts
 *
 * @param array $columns an assoc array with the form [headertext => columntext, ...]
 * @param int $width konsole width, I usually take 180
 * @param char $h_separator1 will be repeated to build a border on top and at bottom of the columns
 * @param char $h_separator2 will be repeated to build a border below the headertext
 * @param str $v_separator the vertical separator between columns
 * @return str
 *
 * @license LGPL
 * @author Milian Wolff (<mail@milianw.de>, <http://milianw.de>)
 */
function columns($columns, $width = COL_WIDTH, $h_separator1 = '=', $h_separator2 = '-', $v_separator = ' | ') {
	if ($h_separator1 !== false) {
		$h_separator1 = str_repeat($h_separator1, $width);
	}
	$num_cols = count($columns);
	$sep_length = mb_strlen($v_separator, 'UTF-8');
	$col_width = floor(($width - ($num_cols - 1) * $sep_length) / $num_cols);

	$reset_color = reset_cli_color();

	$max_rows = 0;

	# top border
	$return = $h_separator1."\n";

	$headers = array();
	$headers_tr = array_fill(0, $num_cols, str_repeat($h_separator2, $col_width));

	foreach ($columns as $header => $col) {
		$columns[$header] = explode("\n", wordwrap(str_replace("\t", '  ', $col), $col_width, "\n", true));
		$max_rows = max($max_rows, count($columns[$header]));

		# build centered header
		if (is_string($header)) {
			$header_width = mb_strlen($header, 'UTF-8');
			$l_padding = floor(($col_width - $header_width) / 2);
			$r_padding = $col_width - $header_width - $l_padding;
			array_push($headers, str_repeat(' ', $l_padding).$header.str_repeat(' ', $r_padding));
		}
	}

	# header with border below
	if (!empty($headers)) {
		$return .= implode($v_separator, $headers)."\n".implode($v_separator, $headers_tr)."\n";
	}

	for ($i = 0; $i < $max_rows; $i++) {
		$row = array();
		foreach ($columns as &$col) {
			if (!isset($col[$i])) {
				$col[$i] = '';
			}

			$col_len = mb_strlen($col[$i], 'UTF-8');

			if (strstr($col[$i], chr(033).'[')) {
				# handle ascii color sequences
				preg_match_all('#'.chr(033).'\[\d+(?:(?:;\d+){1,2})?m#', $col[$i], $matches);
				foreach ($matches[0] as $match) {
					$col_len -= strlen($match);
				}
				if (substr($col[$i], -strlen($reset_color)) != $reset_color) {
					if (isset($col[$i+1])) {
						$col[$i+1] = $match.$col[$i+1];
					}
				} else {
					$col[$i] = substr($col[$i], 0, -strlen($reset_color));
				}
				array_push($row, $col[$i].str_repeat(' ', $col_width - $col_len).$reset_color);
				continue;
			}

			array_push($row, $col[$i].str_repeat(' ', $col_width - $col_len));
		}

		# vertical separator
		$return .= implode($v_separator, $row)."\n";
	}

	# bottom border
	return $return.$h_separator1;
}