<?php defined('SYSPATH') or die('No direct access allowed.');

return array
(
	/**
	 * CMS settings
	 *
	 * string   title		menu title field name in your database
	 * string   slug		menu link slug field name in your database
	 * string   subitem  	array field name where all subitems are stored
	 */
	'fields' => array(
		'title' 		=> 'title',
		'slug' 		=> 'slug',
		'subitem' 	=> 'subitems',
	),
	'cache' 		=> 0
);