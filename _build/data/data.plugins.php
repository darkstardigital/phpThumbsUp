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
 *         'description' => 'This is legit!'
 *         'events' => array(
 *              array('event' => 'OnDarkstarIsAwesome', 'priority' => 0, 'propertyset' => 0),
 *              array('event' => 'OnPatrickIsAwesome', 'priority' => 0, 'propertyset' => 0)
 *          )
 *     ),
 *     array(
 *         'name' => 'AnotherPlugin',
 *         'file' => 'plugin.anothersnippet.php',
 *         'events' => array(
 *              array('event' => 'OnWTF', 'priority' => 0, 'propertyset' => 0)
 *          )
 *     )
 * );
 *
 * @package phpThumbsUp
 * @subpackage TransportBuilder
 */

return array(
    array(
        'name' => 'phpThumbsUp',
        'file' => 'plugin.phpthumbsup.php',
        'events' => array(
            array('event' => 'OnFileManagerUpload', 'priority' => 0, 'propertyset' => 0),
            //array('event' => 'OnHandleRequest', 'priority' => 0, 'propertyset' => 0),
            array('event' => 'OnPageNotFound', 'priority' => 0, 'propertyset' => 0),
            array('event' => 'OnSiteRefresh', 'priority' => 0, 'propertyset' => 0)
        )
    )
);