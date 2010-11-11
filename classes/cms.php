<?php defined('SYSPATH') or die('No direct script access.');

class Cms {

	protected $_data = array();
	
	protected $_menu_items = array();
	
	/**
	 * Checks if the field contains any of the set values
	 *
	 * @param   string   value
	 * @param   array    one array element required
	 * @return  boolean
	 */
	 
	public static function factory($data)
	{
		return new Cms($data);
	}
	
	public function __construct($data)
	{
		$this->_data = $data;
		$this->_menu_items = $data;
	}
	 
	public function menu($parent = null, $limit = null, $class = 'menu')
	{
		if($parent)
		{
			$this->_menu_items = $this->children($parent);
		}
		
		$list = '<ul id="' .$class. '" class="' .$class. '">';
		
		foreach($this->_menu_items as $slug => $item)
		{
			$path = $this->path($item['id'], 'string', 'slug');
			$current = trim(request::instance()->uri(), '/');
			
			$active = ($current == $path) ? 'class="active"' : '';
			$list .= '<li><a '.$active.' href="'. url::base() . $path . '">' . $item['title'] . '</a></li>';
			
			if($item['subitems'] && ($limit == null OR $limit > 1))
			{
				$this->_menu_items = $item['subitems'];
				$list .= $this->menu(null, (($limit != null) ? $limit - 1 : $limit), $class);
			}
			
			$parent_slug = null;
		}
		
		return $list.'</ul>';
	}
	
	public function path($id, $type = 'string', $field = 'id', $array = null)
	{
		$slug = array();
	
		if(!$array)
		{
			$array = array($this->parents($id, $this->_data));
		}
		
		foreach($array as $key => $value)
		{
			$slug[] = $value[$field];
			
			if(isset($value['subitems']) && $value['subitems'])
			{
				$slug = array_merge($slug, $this->path($id, 'array', $field, $value['subitems']));
			}
		}
		
		return ($type == 'string') ? implode('/', $slug) : $slug;
	}
	
	public function parents($id, $menu_array = null, $part = 1)
	{
		if(!$menu_array)
		{
			$menu_array = $this->_data;
		}
	
		foreach($menu_array as $menu_item)
		{
			if($menu_item['id'] == $id)
			{
				$menu_item['subitems'] = array();
				return $menu_item;
			}
			elseif($menu_item['subitems'])
			{
				$found = $this->parents($id, $menu_item['subitems'], $part + 1);
				if($found)
				{
					$menu_item['subitems'] = array($found['id'] => $found);
					return $menu_item;
				}
			}
		}
		
		return false;
	}
	
	public function children($parent)
	{
		$array = $this->_data;
		
		foreach($this->path($parent, 'array') as $id)
		{
			if($id)
			{
				$array = $array[$id]['subitems'];
			}
		}
		
		return $array;
	}
}