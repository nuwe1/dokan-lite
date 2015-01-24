<?php

if ( !class_exists( 'WeDevs_Settings_API' ) ) {
    require_once DOKAN_LIB_DIR . '/class.settings-api.php';
}

/**
 * WordPress settings API demo class
 *
 * @author Tareq Hasan
 */
class Dokan_Admin_Settings {

    private $settings_api;

    function __construct() {
        $this->settings_api = new WeDevs_Settings_API();

        add_action( 'admin_init', array($this, 'do_updates') );
        add_action( 'admin_init', array($this, 'admin_init') );

        add_action( 'admin_menu', array($this, 'admin_menu') );

        add_action( 'admin_notices', array($this, 'update_notice' ) );
    }

    function dashboard_script() {
        wp_enqueue_style( 'dokan-admin-dash', DOKAN_PLUGIN_ASSEST . '/css/admin.css' );
        $this->report_scripts();
    }

    function report_scripts() {

        wp_enqueue_style( 'dokan-admin-report', DOKAN_PLUGIN_ASSEST . '/css/admin.css' );
        wp_enqueue_style( 'jquery-ui' );

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'jquery-flot' );
        wp_enqueue_script( 'jquery-chart' );
    }

    function admin_init() {
        Dokan_Template_Withdraw::init()->bulk_action_handler();

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() {
        $menu_position = apply_filters( 'doakn_menu_position', 17 );
        $capability    = apply_filters( 'doakn_menu_capability', 'manage_options' );
        $withdraw      = dokan_get_withdraw_count();
        $withdraw_text = __( 'Withdraw', 'dokan' );

        if ( $withdraw['pending'] ) {
            $withdraw_text = sprintf( __( 'Withdraw %s', 'dokan' ), '<span class="awaiting-mod count-1"><span class="pending-count">' . $withdraw['pending'] . '</span></span>');
        }

        $dashboard = add_menu_page( __( 'Dokan', 'dokan' ), __( 'Dokan', 'dokan' ), $capability, 'dokan', array($this, 'dashboard'), 'dashicons-vault', $menu_position );
        add_submenu_page( 'dokan', __( 'Dokan Dashboard', 'dokan' ), __( 'Dashboard', 'dokan' ), $capability, 'dokan', array($this, 'dashboard') );
        add_submenu_page( 'dokan', __( 'Withdraw', 'dokan' ), $withdraw_text, $capability, 'dokan-withdraw', array($this, 'withdraw_page') );
        
        do_action( 'dokan_admin_menu' );

        add_submenu_page( 'dokan', __( 'Settings', 'dokan' ), __( 'Settings', 'dokan' ), $capability, 'dokan-settings', array($this, 'settings_page') );
        add_submenu_page( 'dokan', __( 'Add Ons', 'dokan' ), __( 'Add-ons', 'dokan' ), $capability, 'dokan-addons', array($this, 'addon_page') );

        add_action( $dashboard, array($this, 'dashboard_script' ) );
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id'    => 'dokan_general',
                'title' => __( 'General', 'dokan' )
            ),
            array(
                'id'    => 'dokan_selling',
                'title' => __( 'Selling Options', 'dokan' )
            ),
            array(
                'id'    => 'dokan_pages',
                'title' => __( 'Page Settings', 'dokan' )
            )
        );
        return apply_filters( 'dokan_settings_sections', $sections );
    }

    function get_post_type( $post_type ) {
        $pages_array = array( '-1' => __( '- select -', 'dokan' ) );
        $pages = get_posts( array('post_type' => $post_type, 'numberposts' => -1) );

        if ( $pages ) {
            foreach ($pages as $page) {
                $pages_array[$page->ID] = $page->post_title;
            }
        }

        return $pages_array;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $pages_array = $this->get_post_type( 'page' );
        $slider_array = $this->get_post_type( 'dokan_slider' );

        $settings_fields = array(
            'dokan_general' => array(
                'admin_access' => array(
                    'name'    => 'admin_access',
                    'label'   => __( 'Admin area access', 'dokan' ),
                    'desc'    => __( 'Disable sellers and customers from accessing wp-admin area', 'dokan' ),
                    'type'    => 'checkbox',
                    'default' => 'on'
                ),
            ),
            'dokan_selling' => array(
                'new_seller_enable_selling' => array(
                    'name'    => 'new_seller_enable_selling',
                    'label'   => __( 'New Seller Enable Selling', 'dokan' ),
                    'desc'    => __( 'Make selling status enable for new registred seller', 'dokan' ),
                    'type'    => 'checkbox',
                    'default' => 'on'
                ),
                'seller_percentage' => array(
                    'name'    => 'seller_percentage',
                    'label'   => __( 'Seller Percentage', 'dokan' ),
                    'desc'    => __( 'How much amount (%) a seller will get from each order', 'dokan' ),
                    'default' => '90',
                    'type'    => 'text',
                ),
                'order_status_change' => array(
                    'name'    => 'order_status_change',
                    'label'   => __( 'Order Status Change', 'dokan' ),
                    'desc'    => __( 'Seller can change order status', 'dokan' ),
                    'type'    => 'checkbox',
                    'default' => 'on'
                ),
                'withdraw_methods' => array(
                    'name'    => 'withdraw_methods',
                    'label'   => __( 'Withdraw Methods', 'dokan' ),
                    'desc'    => __( 'Withdraw methods for sellers', 'dokan' ),
                    'type'    => 'multicheck',
                    'default' => array( 'paypal' => 'paypal' ),
                    'options' => dokan_withdraw_get_methods()
                ),
                'withdraw_limit' => array(
                    'name'    => 'withdraw_limit',
                    'label'   => __( 'Minimum Withdraw Limit', 'dokan' ),
                    'desc'    => __( 'Minimum balance required to make a withdraw request', 'dokan' ),
                    'default' => '50',
                    'type'    => 'text',
                ),
            ),
            'dokan_pages' => array(
                'dashboard' => array(
                    'name'    => 'dashboard',
                    'label'   => __( 'Dashboard', 'dokan' ),
                    'type'    => 'select',
                    'options' => $pages_array
                ),
                'my_orders' => array(
                    'name'    => 'my_orders',
                    'label'   => __( 'My Orders', 'dokan' ),
                    'type'    => 'select',
                    'options' => $pages_array
                )
            )
        );

        return apply_filters( 'dokan_settings_fields', $settings_fields );
    }

    function dashboard() {
        include dirname(__FILE__) . '/dashboard.php';
    }

    function settings_page() {
        echo '<div class="wrap">';
        settings_errors();

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }

    function withdraw_page() {
        include dirname(__FILE__) . '/withdraw.php';
    }

    function addon_page() {
        include dirname(__FILE__) . '/add-on.php';
    }

    public function do_updates() {
        if ( isset( $_GET['dokan_do_update'] ) && $_GET['dokan_do_update'] == 'true' ) {
            $installer = new Dokan_Installer();
            $installer->do_upgrades();
        }
    }

    public function is_dokan_needs_update() {
        $installed_version = get_option( 'dokan_theme_version' );

        // may be it's the first install
        if ( ! $installed_version ) {
            return false;
        }

        if ( version_compare( $installed_version, '1', '<' ) ) {
            return true;
        }

        return false;
    }

    public function update_notice() {
        if ( ! $this->is_dokan_needs_update() ) {
            return;
        }
        ?>
        <div id="message" class="updated">
            <p><?php _e( '<strong>Dokan Data Update Required</strong> &#8211; We need to update your install to the latest version', 'dokan' ); ?></p>
            <p class="submit"><a href="<?php echo add_query_arg( 'dokan_do_update', 'true', admin_url( 'admin.php?page=dokan' ) ); ?>" class="dokan-update-btn button-primary"><?php _e( 'Run the updater', 'dokan' ); ?></a></p>
        </div>

        <script type="text/javascript">
            jQuery('.dokan-update-btn').click('click', function(){
                return confirm( '<?php _e( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'dokan' ); ?>' );
            });
        </script>
    <?php
    }
}

$settings = new Dokan_Admin_Settings();
