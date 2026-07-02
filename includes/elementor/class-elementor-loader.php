<?php
/**
 * Elementor Widgets Loader
 * Registers all Moaveze Plus widgets with Elementor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Elementor_Loader {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('elementor/widgets/register', array($this, 'register_widgets'));
        add_action('elementor/elements/categories_registered', array($this, 'register_category'));
        add_action('elementor/editor/after_enqueue_styles', array($this, 'editor_styles'));
    }

    /**
     * Register widget category
     */
    public function register_category($elements_manager) {
        $elements_manager->add_category('moaveze-plus', array(
            'title' => 'معاوضه پلاس',
            'icon'  => 'eicon-exchange',
        ));
    }

    /**
     * Register all widgets
     */
    public function register_widgets($widgets_manager) {
        // Load base widget
        require_once MOAVEZE_PLUS_PATH . 'includes/elementor/class-base-widget.php';

        // Load individual widgets
        $widgets = array(
            'listings'      => 'Moaveze_Widget_Listings',
            'submit-form'   => 'Moaveze_Widget_Submit_Form',
            'map'           => 'Moaveze_Widget_Map',
            'featured'      => 'Moaveze_Widget_Featured',
            'recent'        => 'Moaveze_Widget_Recent',
            'stats'         => 'Moaveze_Widget_Stats',
            'search'        => 'Moaveze_Widget_Search',
            'my-offers'     => 'Moaveze_Widget_My_Offers',
            'notifications' => 'Moaveze_Widget_Notifications',
            'wishlist'      => 'Moaveze_Widget_Wishlist',
            'auctions'      => 'Moaveze_Widget_Auctions',
        );

        foreach ($widgets as $file => $class) {
            $path = MOAVEZE_PLUS_PATH . "includes/elementor/widgets/widget-{$file}.php";
            if (file_exists($path)) {
                require_once $path;
                $widgets_manager->register(new $class());
            }
        }
    }

    /**
     * Editor styles
     */
    public function editor_styles() {
        wp_enqueue_style(
            'moaveze-elementor-editor',
            MOAVEZE_PLUS_URL . 'assets/css/elementor-editor.css',
            array(),
            MOAVEZE_PLUS_VERSION
        );
    }
}
