<?php
/**
 * @package plugins.drm
 * @subpackage Admin
 */
class Form_AdminApiConfigure extends Infra_Form
{
	protected $partnerId;
	protected $drmType;
	protected $readOnly;
	protected $actionApi;

	public function __construct($partnerId, $drmType, $actionApi)
	{
		$this->partnerId = $partnerId;
		$this->drmType = $drmType;
		$this->actionApi = $actionApi;
		$this->readOnly = !($actionApi == 'Add');

		parent::__construct();
	}


	public function init()
	{
		$this->setAttrib('id', 'frmAdminApiConfigure');
		$this->setMethod('post');

		$this->addTextElement('pIdFrm', 'Partner ID:', $this->partnerId);
		$this->addTextElement('drmTypeFrm', 'DRM Type:', $this->drmType);
		
		$this->addDocFields();
	}

	private function addDocFields()
	{
		$this->addTextElement('keyFrm', 'key:', '', $this->readOnly);
	}
	
	public function populate($values = array()) {
		foreach($values as $key => $val)
			$this->getElement($key)->setValue($val);
	}

	private function addTextElement($id, $label, $value = '', $readOnly = true) {
		$option = array('label'	=> $label, 'filters'=> array('StringTrim'),'value'=> $value);
		if ($readOnly)
			$option['readonly'] = true;
		$this->addElement('text', $id, $option);
	}

	private function addComment($name, $msg) {
		$element = new Zend_Form_Element_Hidden($name);
		$element->setLabel($msg);
		$element->setDecorators(array('ViewHelper', array('Label', array('placement' => 'append')), array('HtmlTag',  array('tag' => 'dd', 'class' => 'comment'))));
		$this->addElements(array($element));
	}



}