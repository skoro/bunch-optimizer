<?php

/**
 * Administrative interface.
 */
class Bunch_Optimizer_Admin {
    
    /**
     * @var Bunch_Optimizer_Admin
     */
    protected static $instance;
    
    /**
     * @var array
     */
    protected $settings;
    
    /**
     * @var string
     */
    protected $settings_page;
    
    /**
     * @return Bunch_Optimizer_Admin
     */
    public static function get_instance() {
        if ( static::$instance === null ) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    
    /**
     * Setup interface actions.
     */
    public function setup() {
        add_action( 'admin_menu', [$this, 'admin_menu'] );
        add_action( 'admin_init', [$this, 'init_settings'] );
    }
    
    /**
     * Options menu item.
     */
    public function admin_menu() {
        $this->settings_page = add_options_page(
            'Bandwidth optimizer',
            'Bandwidth',
            apply_filters( 'bandwidth_settings_capability', 'manage_options' ),
            'bw-settings',
            [$this, 'settings_page']
        );
    }
    
    /**
     * Settings initialization.
     */
    public function init_settings() {

        register_setting( 'bw_optimizer', 'bw_optimizer', [
            'sanitize_callback' => [$this, 'validate_settings'],
            'default' => [
                'assets' => '/wp-content/uploads/assets',
                'enable_js' => false,
                'debug_js' => false,
                'enable_css' => false,
                'debug_css' => false,
            ],
        ] );

        // Main options.
        add_settings_section( 'main', '', '__return_false', $this->settings_page );
        add_settings_field( 'assets', 'Assets store location', [$this, 'field_assets_store'], $this->settings_page, 'main' );
        
        // Javascript options.
        add_settings_section( 'js', 'JavaScript aggregation', '__return_false', $this->settings_page );
        add_settings_field( 'enable_js', 'JS aggregation', [$this, 'field_enable_js'], $this->settings_page, 'js' );
        add_settings_field( 'debug_js', 'Debug', [$this, 'field_debug_js'], $this->settings_page, 'js' );
        
        // CSS options.
        add_settings_section( 'css', 'CSS aggregation', '__return_false', $this->settings_page );
        add_settings_field( 'enable_css', 'CSS aggregation', [$this, 'field_enable_css'], $this->settings_page, 'css' );
        add_settings_field( 'debug_css', 'Debug', [$this, 'field_debug_css'], $this->settings_page, 'css' );
    }
    
    /**
     * Settings page callback.
     */
    public function settings_page() { ?>
        <div class="wrap">
            <h1>Bandwidth optimizer</h1>
            <form method="POST" action="options.php">
                <?php settings_fields( 'bw_optimizer' ) ?>
                <?php do_settings_sections( $this->settings_page ) ?>
                <?php submit_button( 'Update settings' ) ?>
            </form>
        </div>
    <?php }
    
    public function field_enable_js() { ?>
        <label>
            <input type="checkbox" name="bw_optimizer[enable_js]" value="true" <?php checked( $this->get_setting( 'enable_js' ) ) ?>/>
            <?php esc_html_e( 'Enabling aggregation all javascripts will concatenated to one asset bundle.' ) ?>
        </label>
    <?php }
    
    public function field_enable_css() { ?>
        <label>
            <input type="checkbox" name="bw_optimizer[enable_css]" value="true" <?php checked( $this->get_setting( 'enable_css' ) ) ?>/>
            <?php esc_html_e( 'Enabling aggregation all CSS styles will concatenated to one asset bundle.' ) ?>
        </label>
    <?php }
    
    public function field_debug_js() { ?>
        <label>
            <input type="checkbox" name="bw_optimizer[debug_js]" value="true" <?php checked( $this->get_setting( 'debug_js') ) ?>/>
            <?php esc_html_e( 'Create along with js asset file with .js.debug extension which contains script names.' ) ?>
        </label>
    <?php }
    
    public function field_debug_css() { ?>
        <label>
            <input type="checkbox" name="bw_optimizer[debug_css]" value="true" <?php checked( $this->get_setting( 'debug_css' ) ) ?>/>
            <?php esc_html_e( 'Create along with css asset file with .css.debug extension which contains styles names.' ) ?>
        </label>
    <?php }
    
    public function field_assets_store() { ?>
        <input type="text" name="bw_optimizer[assets]" value="<?= $this->get_setting( 'assets' ) ?>" />
    <?php }
    
    /**
     * Validate and sanitize settings.
     *
     * @param array $settings
     * @return array Sanitized settings.
     */
    public function validate_settings( $settings ) {
        $settings['enable_js'] = !empty( $settings['enable_js'] );
        $settings['debug_js'] = !empty( $settings['debug_js'] );
        $settings['enable_css'] = !empty( $settings['enable_css'] );
        $settings['debug_css'] = !empty( $settings['debug_css'] );
        
        if ( !$settings['enable_js'] && $settings['debug_js'] ) {
            add_settings_error( 'debug_js', 'debug_js', 'Js debug is useless when js aggregation is disabled.' );
        }
        
        if ( !$settings['enable_css'] && $settings['debug_css'] ) {
            add_settings_error( 'debug_css', 'debug_css', 'Css debug is useless when css aggregation is disabled.' );
        }

        if ( !$this->validate_assets_dir( $settings['assets'] ) ) {
            $settings['assets'] = $this->get_setting( 'assets' );
            add_settings_error( 'assets', 'assets', 'Could not create directory or directory is not writeable.' );
        }

        return $settings;
    }
    
    /**
     * Validate assets directory.
     *
     * @param string $dir Directory.
     * @return bool
     */
    protected function validate_assets_dir( $dir ) {
        // TODO: normalize path.
        $dir = ABSPATH . $dir;
        if ( !is_dir( $dir ) && !mkdir( $dir ) ) {
            return false;
        }
        if ( !is_writable( $dir ) ) {
            return false;
        }
        return true;
    }
    
    public function get_setting( $field, $default = false ) {
        if ( empty( $this->settings ) ) {
            $this->settings = get_option( 'bw_optimizer' );
        }
        return isset( $this->settings[$field] ) ? $this->settings[$field] : $default;
    }
}