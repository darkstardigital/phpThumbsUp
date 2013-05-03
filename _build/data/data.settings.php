<?php
/**
 * Array of settings to be installed with package.
 *
 * Returns an array of meta information for setting for the package. Each setting
 * should be an array containing 'name', 'file', 'events', and 'description' (optional).
 *
 * Example:
 * return array(
 *     array(
 *         'key' => 'packagename.setting_name',
 *         'value' => '',
 *         'xtype' => 'textfield',
 *         'namespace' => 'packagename'
 *     ),
 *     array(
 *         'key' => 'packagename.setting2_name',
 *         'value' => 'myValue',
 *         'xtype' => 'textfield',
 *         'namespace' => 'packagename'
 *     )
 * );
 *
 * @package phpThumbsUp
 * @subpackage TransportBuilder
 */

return array(
    array(
        'key' => 'phpthumbsup.core_path',
        'value' => '',
        'xtype' => 'textfield',
        'namespace' => 'phpthumbsup',
        'area' => 'paths'
    ),
    array(
        'key' => 'phpthumbsup.cache_path',
        'value' => '',
        'xtype' => 'textfield',
        'namespace' => 'phpthumbsup',
        'area' => 'paths'
    ),
    array(
        'key' => 'phpthumbsup.base_url',
        'value' => 'phpthumbsup/',
        'xtype' => 'textfield',
        'namespace' => 'phpthumbsup',
        'area' => 'general'
    ),
    array(
        'key' => 'phpthumbsup.auto_create',
        'value' => '',
        'xtype' => 'textfield',
        'namespace' => 'phpthumbsup',
        'area' => 'general'
    ),
    array(
        'key' => 'phpthumbsup.clear_cache',
        'value' => true,
        'xtype' => 'combo-boolean',
        'namespace' => 'phpthumbsup',
        'area' => 'general'
    )
);