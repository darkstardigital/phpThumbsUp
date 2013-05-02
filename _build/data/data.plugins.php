<?php
 /**
 * Array of plugins to be installed with package.
 *
 * Returns an array of meta information for plugins for the package. Each plugin
 * should be an array containing 'name', 'file', 'events', and 'description' (optional).
 *
 * Example:
 * return array(
 *     array(
 *         'name' => 'MyPlugin',
 *         'file' => 'plugin.mysnippet.php',
 *         'events' => 'OnDarkstarIsAwesome:OnAnotherEvent',
 *         'description' => 'This is legit!'
 *     ),
 *     array(
 *         'name' => 'AnotherPlugin',
 *         'file' => 'plugin.anothersnippet.php',
 *         'events' => 'OnDarkstarIsAwesome'
 *     )
 * );
 *
 * @package phpThumbsUp
 * @subpackate TransportBuilder
 */

return array(
	array(
		'name' => 'phpThumbsUp',
		'file' => 'plugin.phpthumbsup.php'
	)
);