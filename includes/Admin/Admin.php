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
    private $academic_years;
    private $classes_sections;
    private $subjects;
    private $students;
    private $results;
    private $import;
    private $export;
    private $certificates;

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

        require_once BONIEDU_PLUGIN_DIR . 'includes/Admin/Results.php';
        $this->results = new Results($this->plugin_name, $this->version);

        require_once BONIEDU_PLUGIN_DIR . 'includes/Admin/CSV_Handler.php';
        require_once BONIEDU_PLUGIN_DIR . 'includes/Admin/Import.php';
        require_once BONIEDU_PLUGIN_DIR . 'includes/Admin/Export.php';

        $this->import = new Import($this->plugin_name, $this->version);
        $this->export = new Export($this->plugin_name, $this->version);

        require_once BONIEDU_PLUGIN_DIR . 'includes/Admin/Certificates.php';
        $this->certificates = new Certificates($this->plugin_name, $this->version);

        require_once BONIEDU_PLUGIN_DIR . 'includes/Admin/DashboardData.php';
        $dashboard_data = new DashboardData($this->plugin_name, $this->version);
        $dashboard_data->register_widgets();

        // Hooks for PDF generation
        add_action('admin_post_boniedu_download_certificate', array($this, 'handle_download_certificate'));
        add_action('admin_post_boniedu_download_marksheet', array($this, 'handle_download_marksheet'));
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

        add_submenu_page(
            $this->plugin_name,
            'Import/Export',
            'Import/Export',
            'manage_options',
            'boniedu-import-export',
            array($this, 'display_import_export_page')
        );

        add_submenu_page(
            $this->plugin_name,
            'Certificates',
            'Certificates',
            'manage_options',
            'boniedu-certificates',
            array($this->certificates, 'render_page')
        );
    }

    public function register_settings()
    {
        $this->settings_page->register_settings();
        // Initialize export headers listener
        $this->export->init();
        $this->certificates->register_settings();
    }

    public function display_import_export_page()
    {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'import';
        ?>
        <div class="wrap">
            <h1>Import / Export Data</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=boniedu-import-export&tab=import"
                    class="nav-tab <?php echo $active_tab == 'import' ? 'nav-tab-active' : ''; ?>">Import</a>
                <a href="?page=boniedu-import-export&tab=export"
                    class="nav-tab <?php echo $active_tab == 'export' ? 'nav-tab-active' : ''; ?>">Export</a>
            </h2>
            <?php
            if ($active_tab == 'import') {
                $this->import->render_page();
            } else {
                $this->export->render_page();
            }
            ?>
        </div>
        <?php
    }

    public function handle_download_certificate()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user.');
        }
        $student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
        if ($student_id) {
            require_once BONIEDU_PLUGIN_DIR . 'includes/Core/PDF_Generator.php';
            $pdf_gen = new \BoniEdu\Core\PDF_Generator();
            $pdf_gen->generate_certificate($student_id);
        } else {
            wp_die('Invalid Student ID.');
        }
    }

    public function handle_download_marksheet()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user.');
        }
        $student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
        if ($student_id) {
            require_once BONIEDU_PLUGIN_DIR . 'includes/Core/PDF_Generator.php';
            $pdf_gen = new \BoniEdu\Core\PDF_Generator();
            $pdf_gen->generate_marksheet($student_id);
        } else {
            wp_die('Invalid Student ID.');
        }
    }

}
