<?php
/**
 * Wrapper class for building transport package.
 *
 * @package phpThumbsUp
 * @subpackage TransportBuilder
 */
class TransportBuilder {

    private $src;
    private $modx;
    private $builder;
    private $elements;


    public function __construct() {
        // define paths (makes life easy later on)
        $root = dirname(dirname(__FILE__)) . '/';
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

        $this->elements = array();
    }


    public function make() {
        $cat = $this->modx->newObject('modCategory');
        $cat->set('id', 0);
        $cat->set('category', PKG_NAME);
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
                    xPDOTransport::UNIQUE_KEY => 'name',
                    xPDOTransport::RELATED_OBJECTS => true,
                    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array(
                        'PluginEvents' => array(
                            xPDOTransport::PRESERVE_KEYS => false,
                            xPDOTransport::UPDATE_OBJECT => true,
                            xPDOTransport::UNIQUE_KEY => array('pluginid', 'event')
                        )
                    )
                )
            )
        );
        $vehicle = $this->builder->createVehicle($cat, $attr);
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
        $this->builder->setPackageAttributes($this->pkg_attributes());
        $this->builder->pack();
    }


    private function create_elements() {
        $elements = array();
        $snippets = include($this->src['data'] . 'data.snippets.php');
        $plugins = include($this->src['data'] . 'data.plugins.php');
        $events = array(
            $this->new_element('modPluginEvent', array('event' => 'OnFileManagerUpload', 'priority' => 0, 'propertyset' => 0)),
            $this->new_element('modPluginEvent', array('event' => 'OnPageNotFound', 'priority' => 0, 'propertyset' => 0)),
            $this->new_element('modPluginEvent', array('event' => 'OnHandleRequest', 'priority' => 0, 'propertyset' => 0))
        );
        $attr = array(
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
                'PluginEvents' => array(
                    xPDOTransport::PRESERVE_KEYS => true,
                    xPDOTransport::UPDATE_OBJECT => false,
                    xPDOTransport::UNIQUE_KEY => array('pluginid','event'),
                ),
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
            $plugin = $this->new_element('modPlugin', $fields, $events);
            $vehicle = $this->builder->createVehicle($plugin, $attr);
            $this->builder->putVehicle($vehicle);
            $elements[] = $plugin;
        }
        return $elements;
    }


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


    private function get_element($path) {
        return trim(str_replace(array('<?php', '?>'), '', file_get_contents($this->src['elements'] . $path)));
    }


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


    private function log($msg, $level = modX::LOG_LEVEL_INFO) {
        $this->modx->log($level, $msg);
    }

}