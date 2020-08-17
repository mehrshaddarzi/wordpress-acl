<?php

/**
 * Plugin Name: ACL in WordPress
 * Description: A Plugin For Developing ACL in WordPress
 * Plugin URI:  https://realwp.net
 * Version:     1.0.0
 * Author:      Mehrshad Darzi
 * Author URI:  https://realwp.net
 * License:     MIT
 * Text Domain: wordpress-acl
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WordPress_ACL
{
    /**
     * Minimum PHP version required
     *
     * @var string
     */
    private $min_php = '5.4.0';

    /**
     * Use plugin's translated strings
     *
     * @var string
     * @default true
     */
    public static $use_i18n = true;

    /**
     * URL to this plugin's directory.
     *
     * @type string
     * @status Core
     */
    public static $plugin_url;

    /**
     * Path to this plugin's directory.
     *
     * @type string
     * @status Core
     */
    public static $plugin_path;

    /**
     * Path to this plugin's directory.
     *
     * @type string
     * @status Core
     */
    public static $plugin_version;

    /**
     * Plugin instance.
     *
     * @see get_instance()
     * @status Core
     */
    protected static $_instance = null;

    /**
     * Access this plugin’s working instance
     *
     * @wp-hook plugins_loaded
     * @return  object of this class
     * @since   2012.09.13
     */
    public static function instance()
    {
        null === self::$_instance and self::$_instance = new self;
        return self::$_instance;
    }

    /**
     * WP_MVC constructor.
     */
    public function __construct()
    {
        /*
         * Check Require Php Version
         */
        if (version_compare(PHP_VERSION, $this->min_php, '<=')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return;
        }

        /*
         * Define Variable
         */
        $this->define_constants();

        /*
         * include files
         */
        $this->includes();

        /*
         * init Wordpress hook
         */
        $this->init_hooks();

        /*
         * Plugin Loaded Action
         */
        do_action('wordpress_acl_loaded');
    }

    /**
     * Define Constant
     */
    public function define_constants()
    {
        /*
         * Get Plugin Data
         */
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $plugin_data = get_plugin_data(__FILE__);

        /*
         * Set Plugin Version
         */
        self::$plugin_version = $plugin_data['Version'];

        /*
         * Set Plugin Url
         */
        self::$plugin_url = plugins_url('', __FILE__);

        /*
         * Set Plugin Path
         */
        self::$plugin_path = plugin_dir_path(__FILE__);
    }

    /**
     * include Plugin Require File
     */
    public function includes()
    {
        /*
        * autoload plugin files
        */
        include_once dirname(__FILE__) . '/plugin/i18n.php';
        include_once dirname(__FILE__) . '/plugin/install.php';
        include_once dirname(__FILE__) . '/plugin/uninstall.php';

        /**
         * Load Plugin Files
         */
        add_action('plugins_loaded', array($this, 'load_php_file'), 10);
    }

    /**
     * Load Gateway List
     */
    public function load_php_file()
    {
        /**
         * Basic Class
         */
        require_once self::$plugin_path . '/class/helper.php';
        require_once self::$plugin_path . '/class/email.php';
        if (get_locale() == "fa_IR") {
            include_once dirname(__FILE__) . '/class/persian.php';
        }
        require_once self::$plugin_path . '/rewrite/rewrite.php';

        /**
         * Custom Hook
         */
        require_once self::$plugin_path . '/hook/admin-screen.php';
        require_once self::$plugin_path . '/hook/role.php';
        require_once self::$plugin_path . '/hook/sign.php';

        /**
         * Additional
         */
        require_once self::$plugin_path . '/additional/avatar/avatar.php';
        require_once self::$plugin_path . '/additional/avatar/rewrite.php';
        require_once self::$plugin_path . '/additional/mobile-login/mobile-login.php';
        require_once self::$plugin_path . '/additional/mobile-login/rewrite.php';
        require_once self::$plugin_path . '/additional/favorite/favorite.php';
        require_once self::$plugin_path . '/additional/favorite/rewrite.php';

    }

    /**
     * Used for regular plugin work.
     *
     * @wp-hook init Hook
     * @return  void
     */
    public function init_hooks()
    {
        register_activation_hook(__FILE__, array('\WordPress_ACL\install', 'run_install'));
        register_deactivation_hook(__FILE__, array('\WordPress_ACL\uninstall', 'run_uninstall'));
    }

    /**
     * Show notice about PHP version
     *
     * @return void
     */
    function php_version_notice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $error = __('Your installed PHP Version is:', 'woocommerce-dev') . PHP_VERSION . '. ';
        $error .= __('The <strong>WordPress ACL</strong> plugin requires PHP version <strong>', 'woocommerce-dev') . $this->min_php . __('</strong> or greater.', 'woocommerce-dev');
        ?>
        <div class="error">
            <p><?php printf($error); ?></p>
        </div>
        <?php
    }
}

/**
 * Main instance of WP_Plugin.
 *
 * @since  1.1.0
 */
function wordpress_acl()
{
    return WordPress_ACL::instance();
}

// Global for backwards compatibility.
$GLOBALS['wordpress-acl'] = wordpress_acl();
