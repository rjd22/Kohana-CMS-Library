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

	protected $_config = array();
	protected $_data = array();
	
	public static function factory($data)
	{
		return new Cms($data);
	}
	
	public function __construct($data)
	{
		$this->_config = Kohana::config('cms');
		$this->_data = $data;
	}
	 
	public function menu($parent = null, $limit = null, $class = 'menu', $array = array())
	{
		if($parent)
		{
			$array = $this->children($parent);
		}
		elseif(!$array)
		{
			$array = $this->_data;
		}
		
		$list = '<ul id="' .$class. '" class="' .$class. '">';
		
		foreach($array as $slug => $item)
		{
		
			$path = $this->path($item['id'], 'string', $this->_config['slug']);
			$current = trim(request::instance()->uri(), '/');
			
			$active = ($current == $path) ? 'class="active"' : '';
			$list .= '<li><a '.$active.' href="'. url::base() . $path . '">' . $item[$this->_config['title']] . '</a></li>';
			
			if($item[$this->_config['subitem']] && ($limit == null OR $limit > 1))
			{
				$list .= $this->menu(null, (($limit != null) ? $limit - 1 : $limit), $class, $item[$this->_config['subitem']]);
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
			
			if(isset($value[$this->_config['subitem']]) && $value[$this->_config['subitem']])
			{
				$slug = array_merge($slug, $this->path($id, 'array', $field, $value[$this->_config['subitem']]));
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
				$menu_item[$this->_config['subitem']] = array();
				return $menu_item;
			}
			elseif($menu_item[$this->_config['subitem']])
			{
				$found = $this->parents($id, $menu_item[$this->_config['subitem']]);
				if($found)
				{
					$menu_item[$this->_config['subitem']] = array($found['id'] => $found);
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
				$array = $array[$child][$this->_config['subitem']];
			}
		}
		
		return ($array) ? $array : array();
	}
	
	public function page_id($path, $array = array())
	{
		$array 	= (!$array) ? $this->_data : $array;
		$path 	= (!is_array($path)) ? explode('/', trim($path, '/')) : $path;
		
		$path_element = array_shift($path);
		
		foreach($array as $item)
		{
			if($item[$this->_config['slug']] == $path_element && !$path)
			{
				return $item['id'];
			}
			elseif($item[$this->_config['subitem']])
			{
				$stack = $this->page_id($path, $item[$this->_config['subitem']]);
				if($stack)
				{
					return $stack;
				}
			}
		}
		return false;
	}
	
	// This function doesn't work on the normal array but if you set
	// the array ID to use the slug you will be able to use it.
	public function faster_page_id($path, $array = array())
	{
		$array		= (!$array) ? $this->_data : $array;
		$parents 	= explode('/', trim($path, '/'));
		
		$page = array_pop($parents);
	
		if(!empty($parents[0]))
		{
			foreach($parents as $parent)
			{
				$array = $array[$parent][$this->_config['subitem']];
			}
		}
		
		return $array[$page]['id'];
	}
	
	public function path_part_id($part)
	{
		$path = explode('/', trim(request::instance()->uri(), '/'));
		
		if(!isset($path[$part - 1]))
		{
			return null;
		}
		
		return $this->page_id(implode('/', array_slice($path, 0, $part)));
	}
}