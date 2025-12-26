<?php

namespace BoniEdu\Admin;

/**
 * The admin-specific functionality of the plugin.
 */
class Admin
{

    private $plugin_name;
    private $version;
    private $settings_page;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        require_once BONIEDU_PLUGIN_DIR . 'includes/Admin/Settings.php';
        $this->settings_page = new Settings($this->plugin_name, $this->version);

        require_once BONIEDU_PLUGIN_DIR . 'includes/Admin/AcademicYears.php';
        $this->academic_years = new AcademicYears($this->plugin_name, $this->version);

        require_once BONIEDU_PLUGIN_DIR . 'includes/Admin/ClassesSections.php';
        $this->classes_sections = new ClassesSections($this->plugin_name, $this->version);

        require_once BONIEDU_PLUGIN_DIR . 'includes/Admin/Subjects.php';
        $this->subjects = new Subjects($this->plugin_name, $this->version);

        require_once BONIEDU_PLUGIN_DIR . 'includes/Admin/Students.php';
        $this->students = new Students($this->plugin_name, $this->version);
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles()
    {
        // wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/boniedu-admin.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts()
    {
        wp_enqueue_media();
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     */
    public function add_plugin_admin_menu()
    {
        add_menu_page(
            'BoniEdu Result Manager',
            'BoniEdu',
            'manage_options',
            $this->plugin_name,
            array($this->settings_page, 'display_plugin_setup_page'),
            'dashicons-welcome-learn-more',
            20
        );

        add_submenu_page(
            $this->plugin_name,
            'Settings',
            'Settings',
            'manage_options',
            $this->plugin_name,
            array($this->settings_page, 'display_plugin_setup_page')
        );

        $this->academic_years->add_submenu();
    }

    public function register_settings()
    {
        $this->settings_page->register_settings();
    }

}
