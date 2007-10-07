<?php
/** based on: http://www.hawkee.com/snippet/2238/ **/

require_once 'Text/Diff.php';

class HTMLColorDiff extends Text_Diff {
	var $old;
	var $new;
	public function __construct(){}
	public function &diff($old, $new) {
		$this->old =& $old;
		$this->new =& $new;

		parent::Text_Diff('native', array(explode("\n", $old), explode("\n", $new)));
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
					$this->old .= '<del>'.$this->getstr($op->orig)."</del>\n";
					$this->new = substr($this->new, 0, -1)."<del>\n&nbsp;</del>";
					break;
				case 'Text_Diff_Op_add':
					$this->new .= '<ins>'.$this->getstr($op->final)."</ins>\n";
					$this->old = substr($this->old, 0, -1)."<ins>&nbsp;</ins>\n";
					break;
				case 'Text_Diff_Op_change':
					$this->old .= '<span class="change">'.implode("\n", $op->orig)."</span>\n";
					$this->new .= '<span class="change">'.implode("\n", $op->final)."&nbsp;</span>\n";
					break;
				default:
					die('BAD CLASS GIVEN: '.var_dump($class));
			}
		}
		return $this;
	}
	
	private function getstr($array) {
		$str = implode("\n", $array);
		if (empty($str)) {
			$str = '&nbsp;';
		}
		return $str;
	}
}
