<?php
/**
	Diff implemented in pure php, written from scratch.
	Copyright (C) 2003  Daniel Unterberger <diff.phpnet@holomind.de>
	Copyright (C) 2005  Nils Knappmeier next version

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

	http://www.gnu.org/licenses/gpl.html

	About:
	I searched a function to compare arrays and the array_diff()
	was not specific enough. It ignores the order of the array-values.
	So I reimplemented the diff-function which is found on unix-systems
	but this you can use directly in your code and adopt for your needs.
	Simply adopt the formatline-function. with the third-parameter of arr_diff()
	you can hide matching lines. Hope someone has use for this.

	Contact: d.u.diff@holomind.de <daniel unterberger>
**/


## PHPDiff returns the differences between $old and $new, formatted
## in the standard diff(1) output format.
function PHPDiff($old,$new)
{
   # split the source text into arrays of lines
   $t1 = explode("\n",$old);
   $x=array_pop($t1);
   if ($x>'') $t1[]="$x\n\\ No newline at end of file";
   $t2 = explode("\n",$new);
   $x=array_pop($t2);
   if ($x>'') $t2[]="$x\n\\ No newline at end of file";

   # build a reverse-index array using the line as key and line number as value
   # don't store blank lines, so they won't be targets of the shortest distance
   # search
   foreach($t1 as $i=>$x) if ($x>'') $r1[$x][]=$i;
   foreach($t2 as $i=>$x) if ($x>'') $r2[$x][]=$i;

   $a1=0; $a2=0;   # start at beginning of each list
   $actions=array();

   # walk this loop until we reach the end of one of the lists
   while ($a1<count($t1) && $a2<count($t2)) {
     # if we have a common element, save it and go to the next
     if ($t1[$a1]==$t2[$a2]) { $actions[]=4; $a1++; $a2++; continue; }

     # otherwise, find the shortest move (Manhattan-distance) from the
     # current location
     $best1=count($t1); $best2=count($t2);
     $s1=$a1; $s2=$a2;
     while(($s1+$s2-$a1-$a2) < ($best1+$best2-$a1-$a2)) {
       $d=-1;
       foreach((array)@$r1[$t2[$s2]] as $n)
         if ($n>=$s1) { $d=$n; break; }
       if ($d>=$s1 && ($d+$s2-$a1-$a2)<($best1+$best2-$a1-$a2))
         { $best1=$d; $best2=$s2; }
       $d=-1;
       foreach((array)@$r2[$t1[$s1]] as $n)
         if ($n>=$s2) { $d=$n; break; }
       if ($d>=$s2 && ($s1+$d-$a1-$a2)<($best1+$best2-$a1-$a2))
         { $best1=$s1; $best2=$d; }
       $s1++; $s2++;
     }
     while ($a1<$best1) { $actions[]=1; $a1++; }  # deleted elements
     while ($a2<$best2) { $actions[]=2; $a2++; }  # added elements
  }

  # we've reached the end of one list, now walk to the end of the other
  while($a1<count($t1)) { $actions[]=1; $a1++; }  # deleted elements
  while($a2<count($t2)) { $actions[]=2; $a2++; }  # added elements

  # and this marks our ending point
  $actions[]=8;

  # now, let's follow the path we just took and report the added/deleted
  # elements into $out.
  $op = 0;
  $x0=$x1=0; $y0=$y1=0;
  $out = array();
  foreach($actions as $act) {
    if ($act==1) { $op|=$act; $x1++; continue; }
    if ($act==2) { $op|=$act; $y1++; continue; }
    if ($op>0) {
      $xstr = ($x1==($x0+1)) ? $x1 : ($x0+1).",$x1";
      $ystr = ($y1==($y0+1)) ? $y1 : ($y0+1).",$y1";
      if ($op==1) $out[] = "{$xstr}d{$y1}";
      elseif ($op==3) $out[] = "{$xstr}c{$ystr}";
      while ($x0<$x1) { $out[] = '< '.$t1[$x0]; $x0++; }   # deleted elems
      if ($op==2) $out[] = "{$x1}a{$ystr}";
      elseif ($op==3) $out[] = '---';
      while ($y0<$y1) { $out[] = '> '.$t2[$y0]; $y0++; }   # added elems
    }
    $x1++; $x0=$x1;
    $y1++; $y0=$y1;
    $op=0;
  }
  $out[] = '';
  return join("\n",$out);
}

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
function highlight_diff($old, $new, $diff) {
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
					$col[$i+1] = $match.$col[$i+1];
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