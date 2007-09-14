<?php
/** based on: http://www.hawkee.com/snippet/2238/ **/

$orig = getcwd();
chdir('/home/milian/projects/');
require_once('Text/Diff.php');
require_once('Text/Diff/Renderer.php');
require_once('Text/Diff/Renderer/inline.php');
chdir($orig);

class CliColorDiff extends Text_MappedDiff {
	var $old;
	var $new;

	var $render_inline;
	var $render;
	public function __construct(){
		$this->render = new Text_Diff_Renderer();
		$this->render_inline = new Text_Diff_Renderer_inline();
		$this->render_inline->_ins_prefix = get_cli_color('white', 'green');
		$this->render_inline->_ins_suffix = reset_cli_color();
		$this->render_inline->_del_prefix = get_cli_color('white', 'red');
		$this->render_inline->_del_suffix = reset_cli_color();
	}
	public function &diff($old, $new) {
		$this->old =& $old;
		$this->new =& $new;

		parent::Text_Diff('shell', array(explode("\n", $old), explode("\n", $new)));
		return $this;
	}

	/**
	 * mark changes in $this->old and $this->new
	 *
	 * @param void
	 * @return void
	 */
	public function &markChanges() {
		$difference = $this->getDiff();
		$del = get_cli_color('white', 'red');
		$add = get_cli_color('white', 'green');
		$chg = get_cli_color('white', 'brown');
		$reset = reset_cli_color();

		#var_dump($difference);
		#die();

		$this->old = '';
		$this->new = '';
		foreach ($difference as $op) {
			$class = get_class($op);
			switch ($class) {
				case 'Text_Diff_Op_copy':
					$this->old .= implode("\n", $op->orig)."\n";
					$this->new .= implode("\n", $op->final)."\n";
					break;
				case 'Text_Diff_Op_delete':
					$this->old .= $del.implode("\n", $op->orig).$reset."\n";
					$this->new = substr($this->new, 0, -1).$del."\n".$reset;
					break;
				case 'Text_Diff_Op_add':
					$this->new .= $add.implode("\n", $op->final).$reset."\n";
					$this->old = substr($this->old, 0, -1).$add." ".$reset."\n";
					break;
				case 'Text_Diff_Op_change':
					$this->old .= $chg.implode("\n", $op->orig).$reset."\n";
					$this->new .= $chg.implode("\n", $op->final).$reset."\n";
					break;
				default:
					die(var_dump($class));
			}
		}
		if (substr($this->old, -1) == "\n")
		  $this->old = substr($this->old, 0, -1);
		if (substr($this->new, -1) == "\n")
		  $this->new = substr($this->new, 0, -1);
		return $this;
	}

	/**
	 * render in standard format
	 */
	public function render() {
		return $this->render->render($this);

	}
	/**
	 * render inline
	 */
	public function render_inline() {
		return $this->render_inline->render($this);
	}
}