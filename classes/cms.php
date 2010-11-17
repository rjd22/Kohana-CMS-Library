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
	
	/**
	 * Generate a html menu from the data included on __construct
	 *
	 * @param   int			the parent id of the children that you want to use in the menu
	 * @param   int			the depth of the menu Example: 1 shows a single level, 2 shows two levels
	 * @param   string		the class you want to give to your menu
	 * @param   array		the function uses this to recurse
	 * @return  string		the generated html of the menu
	 */
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
		
		$list = '<ul class="' .$class. '">';
		
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
	
	/**
	 * Get the breadcrumbs from a page
	 *
	 * @param   int	the page id
	 * @return  mixed	you can return it as a array or a string
	 */
	public function breadcrumbs($id, $seperator = ' &raquo; ', $type = 'string', $array = null)
	{
		$slug = array();
	
		if(!$array)
		{
			$array = array($this->parents($id, $this->_data));
		}
		
		foreach($array as $key => $value)
		{
			$slug[] = '<a href="'.url::base().$this->path($value['id'], 'string', 'slug').'">'.$value['title'].'</a>';
			
			if(isset($value[$this->_config['subitem']]) && $value[$this->_config['subitem']])
			{
				$slug = array_merge($slug, $this->breadcrumbs($id, $seperator, 'array', $value[$this->_config['subitem']]));
			}
		}
		
		return ($type == 'string') ? implode($seperator, $slug) : $slug;
	}
	
	/**
	 * Generate a path string or array from an page id
	 *
	 * @param   int		the page id where you want to generate a path for
	 * @param   string		the result you want returned eg: string, array
	 * @param   string		the field you want returned. (this can be anything that exists within your array)
	 * @param   array		the function uses this to recurse
	 * @return  mixed		the path or array or the page
	 */
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
	
	/**
	 * Generate a hierarchical array of all underlying parents
	 *
	 * @param   int			the child page you want the parent data from
	 * @param   array		the function uses this to recurse
	 * @return  array		the array of parents with their values sorted hierarchically
	 */
	public function parents($id, $array = array())
	{
		if(!$array)
		{
			$array = $this->_data;
		}
	
		foreach($array as $menu_item)
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
	
	/**
	 * Generate a array from all children from this item
	 *
	 * @param   int			the page id where you want to generate the array of children for
	 * @return  array		the array of all children
	 */
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
	
	/**
	 * Get the page id from a path
	 *
	 * @param   string		the path from the page
	 * @param   array		the function uses this to recurse
	 * @return  int			the page id
	 */
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
	
	/**
	 * Get the page id from a path a bit faster than the previous function
	 *
	 * note: 	This function doesn't work on the normal array but if you set
	 * 			the array ID to use the slug you will be able to use it.
	 *
	 * @param   string		the path from the page
	 * @param   array		the function uses this to recurse
	 * @return  int			the page id
	 */
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
	
	/**
	 * Get the page id from a path part
	 *
	 * @param   int		the part of the url you want to get the id for 
	 *					(Path: one/two/tree, 1 will get the id from one. 2 will get the id from two ...etc)
	 * @return  int		the page id
	 */
	public function path_part_id($part, $path = null)
	{
		if(!$path)
		{
			$path = explode('/', trim(request::instance()->uri(), '/'));
		}
		
		if(!isset($path[$part - 1]))
		{
			return null;
		}
		
		return $this->page_id(implode('/', array_slice($path, 0, $part)));
	}
}