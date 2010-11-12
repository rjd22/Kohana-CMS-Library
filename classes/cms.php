<?php
/**
* Kohana CMS Module
*
* @package Kohana CMS Module
* @author Robert-Jan de Dreu
* @copyright (c) 2010 Robert-Jan de Dreu
* @license http://www.opensource.org/licenses/isc-license.txt
*/
class Cms {

	protected $_data = array();
	
	protected $_menu_items = array();
	
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
	
	public function parents($id, $menu_array = array())
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
				$found = $this->parents($id, $menu_item['subitems']);
				if($found)
				{
					$menu_item['subitems'] = array($found['id'] => $found);
					return $menu_item;
				}
			}
		}
		
		return false;
	}
	
	public function children($id)
	{
		$array = $this->_data;
		
		foreach($this->path($id, 'array') as $child)
		{
			if($child)
			{
				$array = $array[$child]['subitems'];
			}
		}
		
		return $array;
	}
	
	public function page_id($path, $array = array())
	{
		$array 	= (!$array) ? $this->_data : $array;
		$path 	= (!is_array($path)) ? explode('/', trim($path, '/')) : $path;
		
		$path_element = array_shift($path);
		
		foreach($array as $item)
		{
			if($item['slug'] == $path_element && !$path)
			{
				return $item['id'];
			}
			elseif($item['subitems'])
			{
				$stack = $this->page_id($path, $item['subitems']);
				if($stack)
				{
					return $stack;
				}
			}
		}
		return false;
	}
	
	//This function doesn't work on the normal array but if you set the array ID 
	//to use the slug you will be able to use it and it will work faster.
	public function faster_page_id($path, $array = array())
	{
		$array		= (!$array) ? $this->_data : $array;
		$parents 	= explode('/', trim($path, '/'));
		
		$page = array_pop($parents);
	
		if(!empty($parents[0]))
		{
			foreach($parents as $parent)
			{
				$array = $array[$parent]['subitems'];
			}
		}
		
		return $array[$page]['id'];
	}
}