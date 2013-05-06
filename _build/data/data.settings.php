<?php
/**
 * Array of settings to be installed with package.
 *
 * Returns an array of meta information for setting for the package. Each setting
 * should be an array containing 'key', 'value', 'xtype', 'namespace', and 'area'.
 *
 * Example:
 * return array(
 *     array(
 *         'key' => 'packagename.setting_name',
 *         'value' => '',
 *         'xtype' => 'textfield',
 *         'namespace' => 'packagename',
 *         'area' => 'general'
 *     ),
 *     array(
 *         'key' => 'packagename.setting2_name',
 *         'value' => 'myValue',
 *         'xtype' => 'textfield',
 *         'namespace' => 'packagename',
 *         'area' => 'general'
 *     )
 * );
 *
 * @package phpThumbsUp
 * @subpackage TransportBuilder
 */

return array(
    array(
        'key' => 'phpthumbsup.core_path',
        'value' => '{core_path}components/phpthumbsup/',
        'xtype' => 'textfield',
        'namespace' => 'phpthumbsup',
        'area' => 'paths'
    ),
    array(
        'key' => 'phpthumbsup.cache_path',
        'value' => '{core_path}components/phpthumbsup/cache/',
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