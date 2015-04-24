<?php
class FieldHidden implements Fieldinterface
{
	public $properties;
	protected $tpl;

	public function __construct(ImTplEngine $tpl)
	{
		$this->tpl = $tpl;
		$this->name = null;
		$this->class = null;
		$this->id = null;
		$this->value = null;
		$this->style = null;
	}


	public function render($sanitize = false)
	{
		return '';
	}
}