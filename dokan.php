<?php
/*
Plugin Name: Dokan Light - Multi-vendor Marketplace
Plugin URI: http://wedevs.com/theme/dokan/
Description: An e-commerce marketplace plugin for WordPress. Powered by WooCommerce and weDevs.
Version: 1.0
Author: Tareq Hasan
Author URI: http://wedevs.com/
License: GPL2
*/


/**
 * Copyright (c) 2015 Tareq Hasan (email: info@wedevs.com). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Backwards compatibility for older than PHP 5.3.0
if ( !defined( '__DIR__' ) ) {
    define( '__DIR__', dirname( __FILE__ ) );
}

define( 'DOKAN_PLUGIN_VERSION', '1.0' );
define( 'DOKAN_DIR', __DIR__ );
define( 'DOKAN_INC_DIR', __DIR__ . '/includes' );
define( 'DOKAN_LIB_DIR', __DIR__ . '/lib' );
define( 'DOKAN_PLUGIN_ASSEST', plugins_url( 'assets', __FILE__ ) );
// give a way to turn off loading styles and scripts from parent theme

if ( !defined( 'DOKAN_LOAD_STYLE' ) ) {
    define( 'DOKAN_LOAD_STYLE', true );
}

if ( !defined( 'DOKAN_LOAD_SCRIPTS' ) ) {
    define( 'DOKAN_LOAD_SCRIPTS', true );
}


/**
 * Autoload class files on demand
 *
 * `Dokan_Installer` becomes => installer.php
 * `Dokan_Template_Report` becomes => template-report.php
 *
 * @param string  $class requested class name
 */
function dokan_autoload( $class ) {
    if ( stripos( $class, 'Dokan_' ) !== false ) {
        $class_name = str_replace( array( 'Dokan_', '_' ), array( '', '-' ), $class );
        $file_path = __DIR__ . '/classes/' . strtolower( $class_name ) . '.php';

        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        }
    }
}

spl_autoload_register( 'dokan_autoload' );

/**
 * WeDevs_Dokan class
 *
 * @class WeDevs_Dokan The class that holds the entire WeDevs_Dokan plugin
 */
class WeDevs_Dokan {

    /**
     * Constructor for the WeDevs_Dokan class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @uses register_activation_hook()
     * @uses register_deactivation_hook()
     * @uses is_admin()
     * @uses add_action()
     */
    public function __construct() {
        global $wpdb;

        $wpdb->dokan_withdraw = $wpdb->prefix . 'dokan_withdraw';
        $wpdb->dokan_orders   = $wpdb->prefix . 'dokan_orders';

        //includes file
        $this->includes();

        // init actions and filter
        $this->init_filters();
        $this->init_actions();

        // initialize classes
        $this->init_classes();

        //for reviews ajax request
        $this->init_ajax();
    }

    /**
     * Initializes the WeDevs_Dokan() class
     *
     * Checks for an existing WeDevs_WeDevs_Dokan() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new WeDevs_Dokan();
        }

        return $instance;
    }

    /**
     * Get the plugin path.
     *
     * @return string
     */
    public function plugin_path() {
        return untrailingslashit( plugin_dir_path( __FILE__ ) );
    }

    /**
     * Get the template path.
     *
     * @return string
     */
    public function template_path() {
        return apply_filters( 'dokan_template_path', 'dokan/' );
    }

    /**
     * Placeholder for activation function
     *
     * Nothing being called here yet.
     */
    public static function activate() {
        global $wpdb;

        $wpdb->dokan_withdraw = $wpdb->prefix . 'dokan_withdraw';
        $wpdb->dokan_orders   = $wpdb->prefix . 'dokan_orders';

        require_once __DIR__ . '/includes/theme-functions.php';

        $installer = new Dokan_Installer();
        $installer->do_install();
    }

    /**
     * Placeholder for deactivation function
     *
     * Nothing being called here yet.
     */
    public static function deactivate() {

    }

    /**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup() {
        load_plugin_textdomain( 'dokan', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    function init_actions() {

        // Localize our plugin
        add_action( 'admin_init', array( $this, 'load_table_prifix' ) );

        add_action( 'init', array( $this, 'localization_setup' ) );
        add_action( 'init', array( $this, 'register_scripts' ) );

        add_action( 'template_redirect', array( $this, 'redirect_if_not_logged_seller' ), 11 );

        add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_action( 'login_enqueue_scripts', array( $this, 'login_scripts' ) );

        add_action( 'admin_init', array( $this, 'block_admin_access' ) );
    }

    public function register_scripts() {
        $suffix   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        // register styles
        wp_register_style( 'jquery-ui', plugins_url( 'assets/css/jquery-ui-1.10.0.custom.css', __FILE__ ), false, null );
        wp_register_style( 'fontawesome', plugins_url( 'assets/css/font-awesome.min.css', __FILE__ ), false, null );
        wp_register_style( 'dokan-extra', plugins_url( 'assets/css/dokan-extra.css', __FILE__ ), false, null );
        wp_register_style( 'dokan-style', plugins_url( 'assets/css/style.css', __FILE__ ), false, null );

        // register scripts
        wp_register_script( 'jquery-flot', plugins_url( 'assets/js/flot-all.min.js', __FILE__ ), false, null, true );
        wp_register_script( 'jquery-chart', plugins_url( 'assets/js/Chart.min.js', __FILE__ ), false, null, true );
        wp_register_script( 'dokan-tabs-scripts', plugins_url( 'assets/js/jquery.easytabs.min.js', __FILE__ ), false, null, true );
        wp_register_script( 'dokan-hashchange-scripts', plugins_url( 'assets/js/jquery.hashchange.min.js', __FILE__ ), false, null, true );
        wp_register_script( 'chosen', plugins_url( 'assets/js/chosen.jquery.min.js', __FILE__ ), array( 'jquery' ), null, true );
        wp_register_script( 'bootstrap-tooltip', plugins_url( 'assets/js/bootstrap-tooltips.js', __FILE__ ), false, null, true );
        wp_register_script( 'form-validate', plugins_url( 'assets/js/form-validate.js', __FILE__ ), array( 'jquery' ), null, true  );

        wp_register_script( 'dokan-script', plugins_url( 'assets/js/all.js', __FILE__ ), false, null, true );
    }

    /**
     * Enqueue admin scripts
     *
     * Allows plugin assets to be loaded.
     *
     * @uses wp_enqueue_script()
     * @uses wp_localize_script()
     * @uses wp_enqueue_style
     */
    public function scripts() {
        $page_id = dokan_get_option( 'dashboard', 'dokan_pages' );

        if ( ! $page_id ) {
            return;
        }

        if ( ! function_exists( 'WC' ) ) {
            return;
        }

        $localize_script = array(
            'ajaxurl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'dokan_reviews' ),
            'ajax_loader' => plugins_url( 'assets/images/ajax-loader.gif', __FILE__ ),
            'seller'      => array(
                'available'    => __( 'Available', 'dokan' ),
                'notAvailable' => __( 'Not Available', 'dokan' )
            )
        );
        //wp_enqueue_style( 'fontawesome' );


        // load only in dokan dashboard and edit page
        if ( is_page( $page_id ) || ( get_query_var( 'edit' ) && is_singular( 'product' ) ) ) {

            if ( DOKAN_LOAD_STYLE ) {
                wp_enqueue_style( 'jquery-ui' );
                wp_enqueue_style( 'fontawesome' );
                wp_enqueue_style( 'dokan-extra' );
                wp_enqueue_style( 'dokan-style' );
            }

            if ( DOKAN_LOAD_SCRIPTS ) {

                wp_enqueue_script( 'jquery' );
                wp_enqueue_script( 'jquery-ui' );
                wp_enqueue_script( 'jquery-ui-autocomplete' );
                wp_enqueue_script( 'jquery-ui-datepicker' );
                wp_enqueue_script( 'underscore' );
                wp_enqueue_script( 'post' );
                wp_enqueue_script( 'bootstrap-tooltip' );
                wp_enqueue_script( 'form-validate' );
                wp_enqueue_script( 'dokan-tabs-scripts' );
                wp_enqueue_script( 'jquery-chart' );
                wp_enqueue_script( 'jquery-flot' );
                wp_enqueue_script( 'chosen' );
                wp_enqueue_media();

                wp_enqueue_script( 'dokan-script' );
                wp_localize_script( 'jquery', 'dokan', $localize_script );
            }
        }

        // store and my account page
        if ( get_query_var( 'store' ) || get_query_var( 'store_review' ) || is_account_page() ) {

            if ( DOKAN_LOAD_STYLE ) {
                wp_enqueue_style( 'fontawesome' );
                wp_enqueue_style( 'dokan-style' );
            }


            if ( DOKAN_LOAD_SCRIPTS ) {
                wp_enqueue_script( 'jquery-ui-sortable' );
                wp_enqueue_script( 'jquery-ui-datepicker' );
                wp_enqueue_script( 'bootstrap-tooltip' );
                wp_enqueue_script( 'chosen' );
                wp_enqueue_script( 'form-validate' );
                wp_enqueue_script( 'dokan-script' );
                wp_localize_script( 'jquery', 'dokan', $localize_script );
            }
        }

        // load dokan style on every pages. requires for shortcodes in other pages
        if ( DOKAN_LOAD_STYLE ) {
            wp_enqueue_style( 'dokan-style' );
            wp_enqueue_style( 'fontawesome' );
        }

        do_action( 'dokan_after_load_script' );
    }


    /**
     * Include all the required files
     *
     * @return void
     */
    function includes() {
        $lib_dir     = __DIR__ . '/lib/';
        $inc_dir     = __DIR__ . '/includes/';
        $classes_dir = __DIR__ . '/classes/';

        require_once $inc_dir . 'theme-functions.php';
        require_once $inc_dir . 'widgets/menu-category.php';
        require_once $inc_dir . 'widgets/store-menu-category.php';
        require_once $inc_dir . 'widgets/bestselling-product.php';
        require_once $inc_dir . 'widgets/top-rated-product.php';
        require_once $inc_dir . 'widgets/store-menu.php';

        require_once $inc_dir . 'wc-functions.php';

        if ( is_admin() ) {
            require_once $inc_dir . 'admin/admin.php';
            require_once $inc_dir . 'admin-functions.php';
        } else {
            require_once $inc_dir . 'wc-template.php';
            require_once $inc_dir . 'template-tags.php';
        }
    }

    /**
     * Initialize filters
     *
     * @return void
     */
    function init_filters() {
        add_filter( 'posts_where', array( $this, 'hide_others_uploads' ) );
        add_filter( 'body_class', array( $this, 'add_dashboard_template_class' ), 99 );
    }

    /**
     * Hide other users uploads for `seller` users
     *
     * Hide media uploads in page "upload.php" and "media-upload.php" for
     * sellers. They can see only thier uploads.
     *
     * FIXME: fix the upload counts
     *
     * @global string $pagenow
     * @global object $wpdb
     * @param string  $where
     * @return string
     */
    function hide_others_uploads( $where ) {
        global $pagenow, $wpdb;

        if ( ( $pagenow == 'upload.php' || $pagenow == 'media-upload.php' ) && current_user_can( 'dokandar' ) ) {
            $user_id = get_current_user_id();

            $where .= " AND $wpdb->posts.post_author = $user_id";
        }

        return $where;
    }

    /**
     * Init ajax classes
     *
     * @return void
     */
    function init_ajax() {
        $doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

        if ( $doing_ajax ) {
            Dokan_Ajax::init()->init_ajax();
            new Dokan_Pageviews();
        }
    }

    /**
     * Init all the classes
     *
     * @return void
     */
    function init_classes() {
        if ( is_admin() ) {
            new Dokan_Admin_User_Profile();
        } else {
            new Dokan_Pageviews();
        }

        new Dokan_Rewrites();
        Dokan_Email::init();
        Dokan_Template_Shortcodes::init();
    }

    function redirect_if_not_logged_seller() {
        global $post;

        $page_id = dokan_get_option( 'dashboard', 'dokan_pages' );

        if ( ! $page_id ) {
            return;
        }

        if ( is_page( $page_id ) ) {
            dokan_redirect_login();
            dokan_redirect_if_not_seller();
        }
    }

    /**
     * Block user access to admin panel for specific roles
     *
     * @global string $pagenow
     */
    function block_admin_access() {
        global $pagenow, $current_user;

        // bail out if we are from WP Cli
        if ( defined( 'WP_CLI' ) ) {
            return;
        }

        $no_access   = dokan_get_option( 'admin_access', 'dokan_general', 'on' );
        $valid_pages = array( 'admin-ajax.php', 'admin-post.php', 'async-upload.php', 'media-upload.php' );
        $user_role   = reset( $current_user->roles );

        if ( ( $no_access == 'on' ) && ( !in_array( $pagenow, $valid_pages ) ) && in_array( $user_role, array( 'seller', 'customer' ) ) ) {
            wp_redirect( home_url() );
            exit;
        }
    }

    function login_scripts() {
        wp_enqueue_script( 'jquery' );
    }

    /**
     * Scripts and styles for admin panel
     */
    function admin_enqueue_scripts() {
        wp_enqueue_script( 'dokan_slider_admin', DOKAN_PLUGIN_ASSEST.'/js/admin.js', array( 'jquery' ) );
    }

    function load_table_prifix() {
        global $wpdb;

        $wpdb->dokan_withdraw = $wpdb->prefix . 'dokan_withdraw';
        $wpdb->dokan_orders   = $wpdb->prefix . 'dokan_orders';
    }

    /**
     * Add body class for dokan-dashboard
     *
     * @param array $classes
     */
    function add_dashboard_template_class( $classes ) {
        $page_id = dokan_get_option( 'dashboard', 'dokan_pages' );

        if ( ! $page_id ) {
            return $classes;
        }

        if ( is_page( $page_id ) || ( get_query_var( 'edit' ) && is_singular( 'product' ) ) ) {
            $classes[] = 'dokan-dashboard';
        }

        return $classes;
    }

} // WeDevs_Dokan

function dokan_load_plugin() {
    $dokan = WeDevs_Dokan::init();
}

add_action( 'plugins_loaded', 'dokan_load_plugin', 5 );

register_activation_hook( __FILE__, array( 'WeDevs_Dokan', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WeDevs_Dokan', 'deactivate' ) );

/**
 * Adds seller email to the new order notification email
 *
 * @param string  $admin_email
 * @param WC_Order $order
 * @return array
 */
function dokan_wc_email_recipient_add_seller( $admin_email, $order ) {
    $emails = array( $admin_email );

    $seller_id = dokan_get_seller_id_by_order( $order->id );

    if ( $seller_id ) {
        $seller_email = get_user_by( 'id', $seller_id )->user_email;

        if ( $admin_email != $seller_email ) {
            array_push( $emails, $seller_email );
        }
    }

    return $emails;
}

add_filter( 'woocommerce_email_recipient_new_order', 'dokan_wc_email_recipient_add_seller', 10, 2 );

// Add Toolbar Menus
function dokan_admin_toolbar() {
    global $wp_admin_bar;

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $args = array(
        'id'     => 'dokan',
        'title'  => __( 'Dokan', 'admin' ),
        'href'   => admin_url( 'admin.php?page=dokan' )
    );

    $wp_admin_bar->add_menu( $args );

    $wp_admin_bar->add_menu( array(
        'id'     => 'dokan-dashboard',
        'parent' => 'dokan',
        'title'  => __( 'Dokan Dashboard', 'dokan' ),
        'href'   => admin_url( 'admin.php?page=dokan' )
    ) );

    $wp_admin_bar->add_menu( array(
        'id'     => 'dokan-withdraw',
        'parent' => 'dokan',
        'title'  => __( 'Withdraw', 'dokan' ),
        'href'   => admin_url( 'admin.php?page=dokan-withdraw' )
    ) );

    $wp_admin_bar->add_menu( array(
        'id'     => 'dokan-settings',
        'parent' => 'dokan',
        'title'  => __( 'Settings', 'dokan' ),
        'href'   => admin_url( 'admin.php?page=dokan-settings' )
    ) );
}

// Hook into the 'wp_before_admin_bar_render' action
add_action( 'wp_before_admin_bar_render', 'dokan_admin_toolbar' );
