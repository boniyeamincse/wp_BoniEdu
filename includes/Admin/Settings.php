<?php

namespace BoniEdu\Admin;

/**
 * Handle the Settings Page with Tabs.
 */
class Settings
{

    private $plugin_name;
    private $version;
    private $option_group = 'boniedu_settings_group';
    private $option_name = 'boniedu_settings';

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

        // General Section
        add_settings_section(
            'boniedu_general_section',
            'General Configuration',
            null,
            $this->plugin_name
        );

        add_settings_field(
            'enable_search',
            'Enable Frontend Search',
            array($this, 'render_checkbox_field'),
            $this->plugin_name,
            'boniedu_general_section',
            array('key' => 'enable_search', 'desc' => 'Allow public result search.')
        );

        // School Info Section
        add_settings_section(
            'boniedu_school_section',
            'School Information',
            null,
            $this->plugin_name . '_school'
        );

        add_settings_field(
            'school_name',
            'School Name',
            array($this, 'render_text_field'),
            $this->plugin_name . '_school',
            'boniedu_school_section',
            array('key' => 'school_name')
        );

        add_settings_field(
            'school_address',
            'School Address',
            array($this, 'render_textarea_field'),
            $this->plugin_name . '_school',
            'boniedu_school_section',
            array('key' => 'school_address')
        );

        add_settings_field(
            'school_eiin',
            'School EIIN',
            array($this, 'render_text_field'),
            $this->plugin_name . '_school',
            'boniedu_school_section',
            array('key' => 'school_eiin')
        );
    }

    public function sanitize_settings($input)
    {
        $new_input = array();
        if (isset($input['enable_search']))
            $new_input['enable_search'] = '1';
        if (isset($input['school_name']))
            $new_input['school_name'] = sanitize_text_field($input['school_name']);
        if (isset($input['school_address']))
            $new_input['school_address'] = sanitize_textarea_field($input['school_address']);
        if (isset($input['school_eiin']))
            $new_input['school_eiin'] = sanitize_text_field($input['school_eiin']);

        return $new_input;
    }

    public function render_text_field($args)
    {
        $options = get_option($this->option_name);
        $key = $args['key'];
        $val = isset($options[$key]) ? esc_attr($options[$key]) : '';
        echo "<input type='text' name='{$this->option_name}[$key]' value='$val' class='regular-text' />";
    }

    public function render_textarea_field($args)
    {
        $options = get_option($this->option_name);
        $key = $args['key'];
        $val = isset($options[$key]) ? esc_textarea($options[$key]) : '';
        echo "<textarea name='{$this->option_name}[$key]' class='large-text' rows='3'>$val</textarea>";
    }

    public function render_checkbox_field($args)
    {
        $options = get_option($this->option_name);
        $key = $args['key'];
        $checked = isset($options[$key]) ? 'checked' : '';
        echo "<input type='checkbox' name='{$this->option_name}[$key]' value='1' $checked />";
        if (isset($args['desc']))
            echo " <span class='description'>{$args['desc']}</span>";
    }

    public function display_plugin_setup_page()
    {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        ?>
        <div class="wrap">
            <h1>BoniEdu Settings</h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=boniedu-result-manager&tab=general"
                    class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General</a>
                <a href="?page=boniedu-result-manager&tab=school_info"
                    class="nav-tab <?php echo $active_tab == 'school_info' ? 'nav-tab-active' : ''; ?>">School Info</a>
            </h2>

            <form action="options.php" method="post">
                <?php
                settings_fields($this->option_group);

                if ($active_tab == 'general') {
                    do_settings_sections($this->plugin_name);
                } else {
                    do_settings_sections($this->plugin_name . '_school');
                }

                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

}
