<?php defined('SYSPATH') OR die('No direct access allowed.');

class Controller_Test extends Controller
{
	public $menu_json = array();

	public function before()
	{
		$this->menu_json = json_decode('
		{
			"1" : {
					"id" : "1",
					"slug" : "into",
					"title" : "Intro",
					"subitems" : null
			},
			"2" : { 
				"id" : "2",
				"slug" : "page1",
				"title" : "Page1",
				"subitems" : {
					"10" : {
						"id" : "10",
						"slug" : "arbodienstverlening",
						"title" : "Arbodienstverlening",
						"subitems" : {
							"11" : {
								"id" : "11",
								"slug" : "arbo",
								"title" : "Arbo",
								"subitems" : null
							} 
						}
					} 
				}
			},
			"4" : { 
				"id" : "4",
				"slug" : "page2",
				"title" : "Page2",
				"subitems" : null
			},
			"7" : { 
				"id" : "7",
				"slug" : "page3",
				"title" : "Page3",
				"subitems" : null
			},
			"8" : { 
				"id" : "8",
				"slug" : "page4",
				"title" : "Page4",
				"subitems" : null
			},
			"9" : { 
				"id" : "9",
				"slug" : "page5",
				"title" : "Page5",
				"subitems" : null
			}
		}');
	
		parent::before();
	}
	
	public function action_path() 
	{
		$page = new Model_Page;
		print_r(navigator::factory($this->menu_json)->path(11, 'string', 'slug'));
	}
	
	public function action_parents() 
	{
		$page = new Model_Page;
		print_r(navigator::factory($this->menu_json)->parents(11));
	}
	
	public function action_children() 
	{
		$page = new Model_Page;
		print_r(navigator::factory($this->menu_json)->children(2));
	}
	
	public function action_menu() 
	{
		$page = new Model_Page;
		print_r(navigator::factory($this->menu_json)->menu(2));
	}
}