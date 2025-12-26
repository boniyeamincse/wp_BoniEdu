<?php

namespace BoniEdu\Admin;

/**
 * Handle the Settings Page with Tabs.
 */
class Settings
{

    private $plugin_name;
    private $version;
    private $option_group = 'boniedu_result_settings_group';
    private $option_name = 'boniedu_result_settings';

    private $tabs = array(
        'total_subjects' => 'Total Subjects',
        'fields' => 'Fields',
        'subjects' => 'Subjects',
        'fields_validation' => 'Fields Validation',
        'subject_validation' => 'Subject Validation',
        'fields_hide_show' => 'Fields Hide/Show',
        'subjects_hide_show' => 'Subjects Hide/Show',
        'edit_cysg' => 'Edit CYSG',
        'page_template' => 'Page Template',
        'search_form' => 'Search Form'
    );

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function register_settings()
    {
        register_setting(
            $this->option_group,
            $this->option_name,
            array($this, 'sanitize_settings')
        );

        // -- Total Subjects Tab --
        add_settings_section(
            'boniedu_total_subjects_section',
            'Total Subjects',
            array($this, 'section_total_subjects_cb'),
            $this->plugin_name . '_total_subjects'
        );

        add_settings_field(
            'total_subjects_count',
            'Total Subjects',
            array($this, 'render_total_subjects_field'),
            $this->plugin_name . '_total_subjects',
            'boniedu_total_subjects_section',
            array('key' => 'total_subjects_count')
        );

        // -- General Tab (Legacy support if needed, but we are moving to new structure) -- 
        // We will keep previous Logic just in case, mapped to a 'General' tab if we decide to keep it?
        // The screenshot doesn't show "General". It starts with "Total Subjects".
        // I will preserve existing "active/school" settings in the sanitize function but maybe hide them from UI 
        // unless we map them to one of these tabs? 
        // "School Info" fits better in "Page Template" or "Search Form" potentially? 
        // For now, I will strictly follow the screenshot for tabs.
    }

    public function sanitize_settings($input)
    {
        $new_input = get_option($this->option_name, array()); // Merge with existing

        if (isset($input['total_subjects_count'])) {
            $new_input['total_subjects_count'] = intval($input['total_subjects_count']);
        }

        // Preserve other keys if passed or keep old
        return $new_input;
    }

    public function section_total_subjects_cb()
    {
        echo '<p>Change the total number of subjects.</p>';
    }

    public function render_total_subjects_field($args)
    {
        $options = get_option($this->option_name);
        $val = isset($options['total_subjects_count']) ? intval($options['total_subjects_count']) : 0;
        echo "<input type='number' name='{$this->option_name}[total_subjects_count]' value='$val' class='regular-text' />";
        echo " <span class='description'>Change total subjects.</span>";
    }

    public function display_plugin_setup_page()
    {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'total_subjects';
        ?>
        <div class="wrap">
            <h1>Result Management Settings</h1>
            <?php settings_errors(); ?>

            <h2 class="nav-tab-wrapper">
                <?php foreach ($this->tabs as $key => $label): ?>
                    <a href="?page=boniedu-result-manager&tab=<?php echo $key; ?>"
                        class="nav-tab <?php echo $active_tab == $key ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </h2>

            <div class="card" style="padding: 20px; margin-top: 20px;">
                <form action="options.php" method="post">
                    <?php
                    settings_fields($this->option_group);

                    // Router for Tabs
                    switch ($active_tab) {
                        case 'total_subjects':
                            do_settings_sections($this->plugin_name . '_total_subjects');
                            break;

                        case 'fields':
                        case 'subjects':
                        case 'fields_validation':
                        case 'subject_validation':
                        case 'fields_hide_show':
                        case 'subjects_hide_show':
                        case 'edit_cysg':
                        case 'page_template':
                        case 'search_form':
                            echo '<h3>' . esc_html($this->tabs[$active_tab]) . '</h3>';
                            echo '<p>Settings for this section are coming soon.</p>';
                            break;

                        default:
                            do_settings_sections($this->plugin_name . '_total_subjects');
                            break;
                    }

                    submit_button('Save Settings');
                    ?>
                </form>
            </div>
        </div>
        <?php
    }

}
