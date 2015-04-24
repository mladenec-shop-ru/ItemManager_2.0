<?php

class Field
{
	protected $categoryid;
	protected $file;
	protected $filename;
	protected $id;
	protected $confirmed;

	public function __construct($catid)
	{
		$this->categoryid = intval($catid);
		$this->file = IM_FIELDS_DIR.$catid.IM_FIELDS_FILE_SUFFIX;
		$this->filename = $catid.IM_FIELDS_FILE_SUFFIX;
		$this->id = null;

		$this->name = '';
		$this->label = '';
		$this->type = '';
		$this->position = null;
		$this->default = '';
		$this->options = array();

		$this->info = '';
		$this->required = null;
		$this->minimum = null;
		$this->maximum = null;
		$this->areacss = '';
		$this->labelcss = '';
		$this->fieldcss = '';
		$this->confirmed = false;

		$this->created = time();
		$this->updated = null;
	}

	public function set($key, $val)
	{
		$key = strtolower($key);
		$val = safe_slash_html_input($val);

		// id is readonly
		if(!in_array($key, array('name', 'label', 'type', 'position',
			'default', 'options', 'created', 'updated', 'info', 'required',
			'minimum', 'maximum', 'areacss', 'labelcss', 'fieldcss')))
			return false;

		// save data depending on data type
		if($key == 'name' || $key == 'label' || $key == 'type' || $key == 'default'
			|| $key == 'info' || $key == 'areacss' || $key == 'labelcss' || $key == 'fieldcss')
		{
			$this->$key = $val;
		} elseif($key == 'options')
		{
			$this->options[] = $val;
		} else
			$this->$key = intval($val);
	}



	public function get($key){return $this->$key;}



	public function setProtected($key, $val) {$this->$key = intval($val);}

	/**
	 * Returns maximum field id
	 *
	 * */
	public function getMaximumId($xml)
	{
		$ids = array_map('intval', $xml->xpath('//fields/field/id'));
		return !empty($ids) ? max($ids) : 0;
	}


	public function save()
	{
		// new file
		if(!file_exists(IM_FIELDS_DIR . intval($this->categoryid) . IM_FIELDS_FILE_SUFFIX))
		{
			$newXML = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><fields><categoryid></categoryid></fields>');
			return $newXML->asXml(IM_FIELDS_DIR . intval($this->categoryid) . IM_FIELDS_FILE_SUFFIX);

		} elseif(is_null($this->id))
		{
			$xml = simplexml_load_file($this->file);

			$xml->categoryid = intval($this->categoryid);

			$id = $this->getMaximumId($xml)+1;

			$xmlfield = $xml->addChild('field');

			$xmlfield->id = $id;
			$xmlfield->name = $this->name;
			$xmlfield->label = $this->label;
			$xmlfield->type = $this->type;
			$xmlfield->position = !is_null($this->position) ? $this->position : $id;
			$xmlfield->default = $this->default;

			if(!empty($this->options))
			{
				unset($xmlfield->option);
				foreach($this->options as $option)
					$xmlfield->option[] = $option;
			} else
				$this->option = '';

			$xmlfield->info = $this->info;
			$xmlfield->required = $this->required;
			$xmlfield->minimum = $this->minimum;
			$xmlfield->maximum = $this->maximum;
			$xmlfield->areacss = $this->areacss;
			$xmlfield->labelcss = $this->labelcss;
			$xmlfield->fieldcss = $this->fieldcss;

			$xmlfield->created = $this->created;
			$xmlfield->updated = $this->updated;

			return $xml->asXml(IM_FIELDS_DIR . intval($this->categoryid) . IM_FIELDS_FILE_SUFFIX);

		} else
		{
			$xml = simplexml_load_file($this->file);

			foreach($xml as $fieldkey => $field)
			{
				// check id exists
				foreach($field as $k => $v)
				{
					if($k == 'id' && (int) $v == (int) $this->id)
					{
						$field->name = $this->name;
						$field->label = $this->label;
						$field->type = $this->type;
						$field->position = !is_null($this->position) ? $this->position : $this->id;
						$field->default = $this->default;
						if(!empty($this->options))
						{
							unset($field->option);
							foreach($this->options as $option)
								$field->option[] = $option;
						} else
							$this->option = '';

						$field->info = $this->info;
						$field->required = $this->required;
						$field->minimum = $this->minimum;
						$field->maximum = $this->maximum;
						$field->areacss = $this->areacss;
						$field->labelcss = $this->labelcss;
						$field->fieldcss = $this->fieldcss;

						$field->created = $this->created;
						$field->updated = time();
					}
				}
			}

			return $xml->asXml(IM_FIELDS_DIR . intval($this->categoryid) . IM_FIELDS_FILE_SUFFIX);
		}
	}


	public function delete()
	{
		if(is_null($this->id))
			return false;

		$params = array();
		$newXML = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><fields><categoryid></categoryid></fields>');
		$xml = simplexml_load_file($this->file);

		$newXML->categoryid = $this->categoryid;

		foreach($xml as $fieldkey => $field)
		{
			// loop through the ids to except deletion fields
			foreach($field as $k => $v)
			{
				if($k == 'id' && (int) $v != (int) $this->id)
				{
					$xmlfield = $newXML->addChild('field');
					//$xmlfield = $field;
					$xmlfield->id = $field->id;
					$xmlfield->name = $field->name;
					$xmlfield->label = $field->label;
					$xmlfield->type = $field->type;
					$xmlfield->position = !is_null($field->position) ? $field->position : $field->id;
					$xmlfield->default = $field->default;
					if(!empty($field->option))
					{
						foreach($field->option as $option)
							$xmlfield->option[] = $option;
					} else
						$xmlfield->option = '';

					$xmlfield->info = $field->info;
					$xmlfield->required = $field->required;
					$xmlfield->minimum = $field->minimum;
					$xmlfield->created = $field->maximum;
					$xmlfield->areacss = $field->areacss;
					$xmlfield->labelcss = $field->labelcss;
					$xmlfield->fieldcss = $field->fieldcss;

					$xmlfield->created = $field->created;
					$xmlfield->updated = $field->updated;

				}
			}
		}
		unset($xml);
		return $newXML->asXml(IM_FIELDS_DIR .intval($this->categoryid) . IM_FIELDS_FILE_SUFFIX);
	}


	function __destruct()
	{
		unset($this->categoryid);
		unset($this->file);
		unset($this->filename);
		unset($this->id);
		unset($this->name);
		unset($this->label);
		unset($this->type);
		unset($this->position);
		unset($this->default);
		unset($this->options);
		unset($this->info);
		unset($this->required);
		unset($this->minimum);
		unset($this->maximum);
		unset($this->areacss);
		unset($this->labelcss);
		unset($this->fieldcss);
		unset($this->created);
		unset($this->updated);
	}
}