<?php

class Allocator
{
	public $simpleItems;
	protected $chmodFile = 0666;
	protected $chmodDir = 0755;

	public function alloc($catid)
	{
		$this->path = IM_BUFFER_CACHE_DIR.'/'.(int)$catid.'.php';
		$this->catid = $catid;
		if(!file_exists(dirname($this->path))) {
			$this->install($this->path);
		}
		if(file_exists($this->path)) {
			$this->simpleItems = include $this->path;
			$this->total = count($this->simpleItems);
			return true;
		}
		return false;
	}


	public function save()
	{
		$export = var_export($this->simpleItems, true);
		file_put_contents($this->path, '<?php return ' . $export . '; ?>');
	}


	public function deleteSimpleItem(Item $item)
	{
		if(empty($this->simpleItems)) {
			if($this->alloc($item->categoryid) !== true) return;
		}
		if(!empty($this->simpleItems[$item->id])) {
			$this->unsetItem($item->id);
			$this->save();
		}
	}


	protected function unsetItem(int $id) { unset($this->simpleItems[$id]); }


	public function disalloc(int $catid) {
		if(file_exists(IM_BUFFER_CACHE_DIR.'/'.$catid.'.php')) { unlink(IM_BUFFER_CACHE_DIR.'/'.$catid.'.php'); }
	}


	public function getSimpleItems($stat='active=1', $offset=0, $length=0, array $items = array())
	{
		// reset offset
		$offset = ($offset > 0) ? $offset-1 : $offset;

		if($offset > 0 && $length > 0 && $offset >= $length) return false;

		// nothing to select
		if(empty($this->simpleItems)) return false;

		// all parameter have to match the data
		$treads = array();

		if(false !== strpos($stat, '&&'))
		{
			$treads = explode('&&', $stat, 2);
			$parts[] = trim($treads[0]);
			$parts[] = trim($treads[1]);

			$sepitems = array();
			foreach($parts as $part)
			{
				$sepitems[] = $this->separateSimpleItems($this->simpleItems, $part);
			}
			if(!empty($sepitems[0]) && !empty($sepitems[1]))
			{
				$arr = array_map('unserialize', array_intersect(array_map('serialize', $sepitems[0]), array_map('serialize', $sepitems[1])));

				// limited output
				if(!empty($arr) && ((int) $offset > 0 || (int) $length > 0))
				{
					if((int) $length == 0) $len = null;
					$arr = array_slice($arr, (int) $offset, (int) $length, true);
				}
				return $arr;
			}
			// only one parameter have to match the data
		} elseif(false !== strpos($stat, '||'))
		{
			$treads = explode('||', $stat, 2);
			$parts[] = trim($treads[0]);
			$parts[] = trim($treads[1]);

			$sepitems = array();
			foreach($parts as $part)
			{
				$sepitems[] = $this->separateSimpleItems($this->simpleItems, $part);
			}
			if(!empty($sepitems[0]) || !empty($sepitems[1]))
			{
				if(is_array($sepitems[0]) && is_array($sepitems[1]))
				{
					// limited output
					if(!empty($sepitems[0]) && ((int) $offset > 0 || (int) $length > 0))
					{
						if((int) $length == 0) $len = null;
						$sepitems[0] = array_slice($sepitems[0], (int) $offset, (int) $length, true);
						$sepitems[1] = array_slice($sepitems[1], (int) $offset, (int) $length, true);
						$return = array_merge($sepitems[0], $sepitems[1]);
						return array_slice($return, (int) $offset, (int) $length, true);
					}
					return array_merge($sepitems[0], $sepitems[1]);

				} elseif(is_array($sepitems[0]) && !is_array($sepitems[1]))
				{
					// limited output
					if(!empty($sepitems[0]) && ((int) $offset > 0 || (int) $length > 0))
					{
						if((int) $length == 0) $len = null;
						$sepitems[0] = array_slice($sepitems[0], (int) $offset, (int) $length, true);
					}
					return $sepitems[0];
				} else
				{
					// limited output
					if(!empty($sepitems[1]) && ((int) $offset > 0 || (int) $length > 0))
					{
						if((int) $length == 0) $len = null;
						$sepitems[1] = array_slice($sepitems[1], (int) $offset, (int) $length, true);
					}
					return $sepitems[1];
				}
			}
			// If $stat contains only one selector
		} else
		{
			$arr = $this->separateSimpleItems($this->simpleItems, $stat);
			// limited output
			if(!empty($arr) && ((int) $offset > 0 || (int) $length > 0))
			{
				if((int) $length == 0) $len = null;
				$arr = array_slice($arr, (int) $offset, (int) $length, true);
			}

			return $arr;
		}
		return false;
	}


	/**
	 * Returns the array of objects of the type Item, sorted by any node your choice
	 * NOTE: If no $items argument is passed to the function, the fields
	 * must already be in the buffer: ImItem::$items. Call the ImItem::init($category_id)
	 * method before to assign the fields to the buffer.
	 *
	 * You can sort items by using any node
	 * Sample sortng by "position":
	 * ImItem::filterItems('position', 'DESC', $your_items_array)
	 *
	 * @param string $filterby
	 * @param string $key
	 * @param array $items
	 * @return boolean|array of objects of type Item
	 */
	public function filterSimpleItems($filterby='position', $option='asc',  $offset=0, $length=0, array $items=array())
	{
		// reset offset
		$offset = ($offset > 0) ? $offset-1 : $offset;

		$locitems = !empty($items) ? $items : $this->simpleItems;

		if(empty($locitems) || count($locitems) <= 0) return false;

		$itemcontainer = array();

		foreach($locitems as $item_id => $i) {
			if(!isset($i->$filterby)) continue;
			$itemcontainer[$item_id] = $locitems[$item_id];
		}

		if(empty($itemcontainer)) return false;

		$this->filterby = $filterby;
		usort($itemcontainer, array($this, 'sortObjects'));
		// sort DESCENDING
		if(strtolower($option) != 'asc') $itemcontainer = $this->reverseItems($itemcontainer);
		$itemcontainer = $this->reviseItemIds($itemcontainer);

		// limited output
		if(!empty($itemcontainer) && ((int) $offset > 0 || (int) $length > 0))
		{
			if((int) $length == 0) $len = null;
			$itemcontainer = array_slice($itemcontainer, (int) $offset, (int) $length, true);
		}

		if(!empty($items)) return $itemcontainer;
		$this->simpleItems = $itemcontainer;
		return $this->simpleItems;

	}


	protected function separateSimpleItems(array $items, $stat)
	{
		$res = array();
		$pattern = array(0 => '>=', 1 => '<=', 2 => '!=', 3 => '>', 4 => '<', 5 => '=');

		foreach($pattern as $pkey => $pval)
		{
			if(false === strpos($stat, $pval)) continue;

			$data = explode($pval, $stat, 2);
			$key = strtolower(trim($data[0]));
			$val = trim($data[1]);
			if(false !== strpos($key, ' ')) return false;

			$num = substr_count($val, '%');
			$pat = false;
			if($num == 1) {
				$pos = strpos($val, '%');
				if($pos == 0) {
					$pat = '/'.strtolower(trim(str_replace('%', '', $val))).'$/';
				} elseif($pos == (strlen($val)-1)) {
					$pat = '/^'.strtolower(trim(str_replace('%', '', $val))).'/';
				}
			} elseif($num == 2) {
				$pat = '/'.strtolower(trim(str_replace('%', '', $val))).'/';
			}

			foreach($items as $itemkey => $item)
			{
				if(!isset($item->$key)) { continue; }
				if($pkey == 0) {
					if($item->$key < $val) continue;
				} elseif($pkey == 1) {
					if($item->$key > $val) continue;
				} elseif($pkey == 2) {
					if($item->$key == $val) continue;
				} elseif($pkey == 3) {
					if($item->$key <= $val) continue;
				} elseif($pkey == 4) {
					if($item->$key >= $val) continue;
				} elseif($pkey == 5) {
					if($item->$key != $val && !$pat) { continue; }
					elseif($pat && !preg_match($pat, strtolower($item->$key))){ continue; }
				}
				$res[$item->id] = $item;
			}

			if(!empty($res)) return $res;
			return false;
		}
		return false;
	}

	protected function install($path)
	{
		$value = "# apache < 2.3\r\n";
		$value .= "<IfModule !mod_authz_core.c>\r\n";
		$value .= "Deny from all\r\n";
		$value .= "</IfModule>\r\n\r\n";
		$value .= "# apache > 2.3 with mod_access_compat\r\n";
		$value .= "<IfModule mod_access_compat.c>\r\n";
		$value .= "Deny from all\r\n";
		$value .= "</IfModule>\r\n\r\n";
		$value .= "# apache > 2.3 without mod_access_compat\r\n";
		$value .= "<IfModule mod_authz_core.c>\r\n\r\n";
		$value .= "<IfModule !mod_access_compat.c>\r\n";
		$value .= "Require all denied\r\n";
		$value .= "</IfModule>\r\n\r\n";
		$value .= "</IfModule>\r\n";
		if(!mkdir(dirname($path), $this->chmodDir, true)) echo 'Unable to create path: '.dirname($path);
		if(!$handle = fopen(dirname($path).'/.htaccess', 'w')) return false;
		fwrite($handle, $value);
		fclose($handle);
	}

	/**
	 * Simplifies standard items
	 */
	public function simplifyBunch($items)
	{
		$this->simpleItems = array();
		foreach($items as $key => $item)
		{
			$obj = new SimpleItem();

			foreach($item as $ikey => $ival)
			{
				if($ikey != 'fields') {
					$obj->{$ikey} = $ival;
				} else {
					foreach($ival as $fkey => $fval) {
						$obj->{$fkey} = $fval->value;
					}
				}
			}
			$this->simpleItems[$obj->id] = $obj;
		}
	}

	/**
	 * Simplifies a single item
	 *
	 * @param $item
	 */
	public function simplify($item)
	{
		$obj = new SimpleItem();

		foreach($item as $ikey => $ival)
		{
			if($ikey != 'fields') {
				$obj->{$ikey} = $ival;
			} else {
				foreach($ival as $fkey => $fval) {
					$obj->{$fkey} = $fval->value;
				}
			}
		}
		$this->simpleItems[$obj->id] = $obj;
	}

}


class SimpleItem
{
	public static function __set_state($an_array)
	{
		$_instance = new SimpleItem();
		foreach($an_array as $key => $val) {
			$_instance->{$key} = $val;
		}
		return $_instance;
	}
}