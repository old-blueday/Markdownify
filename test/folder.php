<?php
/**
 * the `folder` class gives the developer a more sophisticated
 * interface to file traversion. It utilizes PHP's built-in `dir`
 * class and gives you easy directory reading, recursion and so forth.
 *
 * @version 1.0
 * @author Milian Wolff (mail@milianw.de, http://milianw.de)
 * @license LGPL, see LICENSE_LGPL.txt and the summary below
 * @copyright (C) 2007  Milian Wolff
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */
class folder {
	/* options */
	/**
	 * show hidden files (hidden meens the file starts with a dot - i.e. unix like)
	 *
	 * @access public
	 * @var bool
	 */
	var $show_hidden = false;
	/**
	 * show folders
	 *
	 * @access public
	 * @var bool
	 */
	var $show_folders = true;
	/**
	 * show files
	 *
	 * @access public
	 * @var bool
	 */
	var $show_files = true;
	/**
	 * recurse into directories
	 *
	 * @access public
	 * @var bool
	 */
	var $recurse = false;
	/**
	 * maximum depth for recursion
	 * set to zero to recurse into all directories
	 *
	 * @access public
	 * @var integer
	 */
	var $depth = 0;


	/* public (read only) */
	/**
	 * path to parent folder, with trailing slash
	 *
	 * @access public
	 * @var string
	 */
	var $parent;
	/**
	 * path to current file, directories automatically get a slash appended
	 *
	 * @access public
	 * @var string
	 */
	var $path;
	/**
	 * current file name, directories automatically get a slash appended
	 *
	 * @access public
	 * @var string
	 */
	var $file;
	/**
	 * wether current file is a directory or not
	 *
	 * @access public
	 * @var bool
	 */
	var $is_dir = false;
	/**
	 * wether the directory was successfully loaded or not
	 *
	 * @access public
	 * @var bool
	 */
	var $loaded = false;
	/**
	 * current depth
	 *
	 * @access public
	 * @var integer
	 */
	var $current_depth = 0;
	/**
	 * path from original this::parent to current file
	 */
	var $recurse_path = '';

	/* private */
	/**
	 * dir class
	 * @access private
	 * @var pointer
	 */
	var $dir;
	/**
	 * array of dir classes for recursion
	 *
	 * @access private
	 * @var array
	 */
	var $dir_stack = array();
	/**
	 * error level
	 *
	 * @access private
	 * @var int
	 */
	var $error_level;
	/**
	 * load $path
	 *
	 * @param string $path a trailing slash will be appended on demand
	 * @param int $error_level optional error level
	 */
	function folder($path, $error_level = E_USER_WARNING) {
		# append trailing slash
		if (substr($path, -1) != '/') {
			$path .= '/';
		}
		# open folder
		$this->dir = @ dir($path);
		if (!$this->dir && $error_level) {
			trigger_error(sprintf(_('couldn\'t open directory <code>%s</code>', $path)), $this->error_level);
			$this->loaded = false;
			return;
		}
		$this->parent = $path;
		$this->loaded = true;
		$this->error_level = $error_level;
	}
	/**
	 * read directory
	 *
	 * @param void
	 * @return bool is there a next file or not
	 */
	function read() {
		# handle recursion
		if ( $this->recurse && $this->is_dir &&
			($this->depth == 0 || $this->depth > $this->current_depth)) {
			$this->recurse();
		}
		$file = $this->dir->read();
		if ($file === false) {
			# reached end of dir
			return $this->close();
		}

		$is_dir = is_dir($this->parent.$file);

		if (($file == '.' || $file == '..') || # prevent loops
			(!$this->show_hidden && $file[0] == '.') || # exclude hidden files
			(!$this->show_files && !$is_dir)) { # exclude files
			# skip
			return $this->read();
		}
		if ($is_dir) {
			$file .= '/';
		}
		$this->path = $this->parent.$file;
		$this->file = $file;
		$this->is_dir = $is_dir;
		if(!$this->show_folders && $is_dir) { # exclude folders
			# skip
			return $this->read();
		}
		return true;
	}
	/**
	 * recurse into current directory
	 */
	function recurse() {
		$this->recurse_path .= $this->file;
		array_push($this->dir_stack, $this->dir);
		$this->current_depth++;
		$this->folder($this->path, $this->error_level);
		if (!$this->loaded) {
			$this->close();
		}
		# reset
		$this->is_dir = false;
		$this->file = '';
		$this->path = '';
	}
	/**
	 * close the directory and end
	 */
	function close() {
		# reset
		$this->is_dir = false;
		$this->file = '';
		$this->path = '';
		if (!empty($this->dir_stack)) {
			# we recursed!
			$this->recurse_path = substr($this->recurse_path, 0, strrpos(substr($this->recurse_path, 0, -1), '/'));
			if (!empty($this->recurse_path)) {
				$this->recurse_path .= '/';
			}
			$this->dir = array_pop($this->dir_stack);
			$this->parent = $this->dir->path;
			$this->current_depth--;
			return $this->read();
		}
		$this->dir->close();
		$this->loaded = false;
		return false;
	}
}