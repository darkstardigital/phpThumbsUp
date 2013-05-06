<?php
/**
 * Wrapper class for building transport package.
 *
 * This class provides a level of abstraction for creating modX packages. It creates a package based on snippets,
 * plugins, and settings defined in the data directory. The theory to this class is that you don't have to keep
 * writing the same build.transport.php script over and over for every package.
 *
 * It does make some assumptions you should be aware of. A category based on the package namespace (defined in
 * build.config.php) and adds all plugins/snippets to this category. If you want snippets or plugins to be custom
 * categories this class won't work for you.
 *
 * @author Darkstar Design (info@darkstardesign.com)
 * @package phpThumbsUp
 * @subpackage TransportBuilder
 * @todo add support for chunks, templates and custom categories
 */
class TransportBuilder {

    private $src;
    private $modx;
    private $builder;


    /**
     * Class constructor.
     *
     * Sets some paths, instantiates an instance of modX and modPackageBuilder
     */
    public function __construct() {
        // define paths (makes life easy later on)
        $this->src = array (
            'root' => PKG_ROOT,
            'build' => PKG_ROOT . '_build/',
            'resolvers' => PKG_ROOT . '_build/resolvers/',
            'data' => PKG_ROOT . '_build/data/',
            'core' => PKG_ROOT . 'core/components/' . PKG_NAMESPACE,
            'docs' => PKG_ROOT .'core/components/' . PKG_NAMESPACE . '/docs/',
            'elements' => PKG_ROOT . 'core/components/' . PKG_NAMESPACE . '/elements/',
            'lexicon' => PKG_ROOT . 'core/components/' . PKG_NAMESPACE . '/lexicon/',
            'assets' => PKG_ROOT . 'assets/components/' . PKG_NAMESPACE
        );

        // instantiate modx object
        $this->modx = new modX();
        $this->modx->initialize('mgr');
        $this->modx->setLogLevel(modX::LOG_LEVEL_INFO);
        $this->modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

        // load package builder
        $this->modx->loadClass('transport.modPackageBuilder', '', false, true);
        $this->builder = new modPackageBuilder($this->modx);
        $this->builder->createPackage(PKG_NAME, PKG_VERSION, PKG_RELEASE);
        $this->builder->registerNamespace(PKG_NAMESPACE, false, true, MODX_CORE_PATH . 'components/' . PKG_NAMESPACE . '/');
    }


    /**
     * The main method for making the package. Instantiate TransportBuilder, call make(), and call it a day :)
     */
    public function make() {
        // create category for package
        $cat = $this->modx->newObject('modCategory');
        $cat->set('id', 0);
        $cat->set('category', PKG_NAME);

        // create elements, add them to the category, and add category to a vehicle
        $elements = $this->create_elements();
        if (!empty($elements)) {
            $cat->addMany($elements);
        }
        $attr = array(
            xPDOTransport::UNIQUE_KEY => 'category',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
                'Snippets' => array(
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'name'
                ),
                'Plugins' => array(
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'name'
                )
            )
        );
        $vehicle = $this->builder->createVehicle($cat, $attr);

        // add resolve files for components to vehicle and put vehicle to builder
        if (is_dir($this->src['core'])) {
            $vehicle->resolve('file', array(
                'source' => $this->src['core'],
                'target' => "return MODX_CORE_PATH . 'components/';"
            ));
        }
        if (is_dir($this->src['assets'])) {
            $vehicle->resolve('file', array(
                'source' => $this->src['assets'],
                'target' => "return MODX_ASSETS_PATH . 'components/';"
            ));
        }
        $this->builder->putVehicle($vehicle);

        // create settings, add them to a vehicle, and put it to builder
        $settings = $this->create_settings();
        foreach ($settings as $setting) {
            $vehicle = $this->builder->createVehicle($setting, $attr);
            $this->builder->putVehicle($vehicle);
        }

        // add docs (license, readme, etc) and pack the package (we done yo!)
        $this->builder->setPackageAttributes($this->pkg_attributes());
        $this->builder->pack();
    }


    /***
     * Creates an array of modSnippet and modPlugin objects from array defined in data.plugins.php
     * and data.snippets.php.
     *
     * @return array contains modSnippet and modPlugin objects
     */
    private function create_elements() {
        $elements = array();
        $snippets = include($this->src['data'] . 'data.snippets.php');
        $plugins = include($this->src['data'] . 'data.plugins.php');
        $attr = array(
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
                'PluginEvents' => array(
                    xPDOTransport::PRESERVE_KEYS => true,
                    xPDOTransport::UPDATE_OBJECT => false,
                    xPDOTransport::UNIQUE_KEY => array('pluginid','event')
                )
            )
        );
        foreach ($snippets as $snippet) {
            $fields = array(
                'name' => $snippet['name'],
                'description' => isset($snippet['description']) ? $snippet['description'] : '',
                'snippet' => $this->get_element('snippets/' . $snippet['file'])
            );
            $elements[] = $this->new_element('modSnippet', $fields);
        }
        foreach ($plugins as $plugin) {
            $fields = array(
                'name' => $plugin['name'],
                'description' => isset($plugin['description']) ? $plugin['description'] : '',
                'plugincode' => $this->get_element('plugins/' . $plugin['file'])
            );
            $events = array();
            if (is_array($plugin['events'])) {
                foreach ($plugin['events'] as $e) {
                    $events[] = $this->new_element('modPluginEvent', $e);
                }
            }
            $plugin = $this->new_element('modPlugin', $fields, $events);
            $vehicle = $this->builder->createVehicle($plugin, $attr);
            $this->builder->putVehicle($vehicle);
            $elements[] = $plugin;
        }
        return $elements;
    }


    /**
     * Creates an array of modSetting objects from array defined in data.settings.php.
     *
     * @return array contains modSetting objects
     */
    private function create_settings() {
        $elements = array();
        $default = array(
            'value' => '',
            'xtype' => 'textfield',
            'namespace' => PKG_NAMESPACE,
            'area' => 'general'
        );
        $settings = include($this->src['data'] . 'data.settings.php');
        foreach ($settings as $setting) {
            $fields = array_merge($default, $setting);
            $elements[] = $this->new_element('modSetting', $fields);
        }
        return $elements;
    }


    /**
     * Creates a modX element and returns it.
     *
     * @param $type a modX object
     * @param $fields the fields passed to fromArray()
     * @param array $related an array of related modX object
     * @param bool $many use addMany or addOne?
     * @return mixed a modX object specified in $type
     */
    private function new_element($type, $fields, $related = array(), $many = true) {
        $element = $this->modx->newObject($type);
        $element->fromArray($fields, '', true, true);
        if (!empty($related)) {
            if ($many) {
                $element->addMany($related);
            } else {
                if (is_array($related)) {
                    $related = array_shift($related);
                }
                $element->addOne($related);
            }
        }
        return $element;
    }


    /**
     * Returns the contents of a file for a snippet or plugin after stripping the <?php ?> tags.
     *
     * @param $path path to element file relative to $this->src['elements']
     * @return string contents of the file
     */
    private function get_element($path) {
        return trim(str_replace(array('<?php', '?>'), '', file_get_contents($this->src['elements'] . $path)));
    }


    /**
     * Returns an array for package attributes (docs) find in the $this->src['docs'] directory.
     *
     * @return array key/value pair of filename/contents in the docs directory
     */
    private function pkg_attributes() {
        $attr = array();
        $docs = scandir($this->src['docs']);
        foreach ($docs as $file) {
            if ($file != '.' && $file != '..') {
                $key = preg_replace('/\.[^.]+$/', '', $file);
                $attr[$key] = file_get_contents($this->src['docs'] . $file);
            }
        }
        return $attr;
    }


    /**
     * Wrapper for modX->log() because I'm sick of typing modX::LOG_LEVEL_INFO over and over.
     *
     * @param $msg the msg to print
     * @param $level a modX::LOG_LEVEL_* const
     */
    private function log($msg, $level = modX::LOG_LEVEL_INFO) {
        $this->modx->log($level, $msg);
    }

}