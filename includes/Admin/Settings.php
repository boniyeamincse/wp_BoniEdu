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

        // -- Fields Tab (Renaming) --
        add_settings_section(
            'boniedu_fields_section',
            'Rename Fields',
            function () {
                echo '<p>Rename the labels for the student information fields.</p>'; },
            $this->plugin_name . '_fields'
        );

        $default_fields = $this->get_default_fields();
        foreach ($default_fields as $key => $label) {
            add_settings_field(
                'field_label_' . $key,
                $label,
                array($this, 'render_field_text_input'),
                $this->plugin_name . '_fields',
                'boniedu_fields_section',
                array('key' => $key, 'type' => 'fields')
            );
        }

        // -- Fields Hide/Show Tab --
        add_settings_section(
            'boniedu_fields_visibility_section',
            'Fields Visibility',
            function () {
                echo '<p>Check the box to HIDE the field.</p>'; },
            $this->plugin_name . '_fields_hide_show'
        );

        foreach ($default_fields as $key => $label) {
            add_settings_field(
                'field_vis_' . $key,
                $label,
                array($this, 'render_field_checkbox_input'),
                $this->plugin_name . '_fields_hide_show',
                'boniedu_fields_visibility_section',
                array('key' => $key, 'type' => 'visibility')
            );
        }

        // -- Subjects Tab (Dynamic Labels) --
        add_settings_section(
            'boniedu_subjects_section',
            'Subject Configurations',
            function () {
                echo '<p>Configure labels for each subject column.</p>'; },
            $this->plugin_name . '_subjects'
        );

        $options = get_option($this->option_name);
        $total_subjects = isset($options['total_subjects_count']) ? intval($options['total_subjects_count']) : 0;

        if ($total_subjects > 0) {
            for ($i = 1; $i <= $total_subjects; $i++) {
                add_settings_field(
                    'subject_label_' . $i,
                    'Subject ' . $i,
                    array($this, 'render_subject_label_input'),
                    $this->plugin_name . '_subjects',
                    'boniedu_subjects_section',
                    array('index' => $i)
                );
            }
        } else {
            add_settings_field(
                'no_subjects_warning',
                'No Subjects Configured',
                function () {
                    echo '<p style="color:red;">Please set "Total Subjects" in the Total Subjects tab first.</p>'; },
                $this->plugin_name . '_subjects',
                'boniedu_subjects_section'
            );
        }
    }

    private function get_default_fields()
    {
        return array(
            'name' => 'Student Name',
            'roll' => 'Roll No',
            'reg_no' => 'Registration No',
            'father_name' => 'Father Name',
            'mother_name' => 'Mother Name',
            'dob' => 'Date of Birth',
            'gender' => 'Gender',
            'religion' => 'Religion',
            'session' => 'Session',
            'class' => 'Class',
            'section' => 'Section',
            'group' => 'Group'
        );
    }

    public function sanitize_settings($input)
    {
        $current_options = get_option($this->option_name, array());
        $new_input = $current_options; // Start with existing to preserve keys not submitted

        // 1. Total Subjects
        if (isset($input['total_subjects_count'])) {
            $new_input['total_subjects_count'] = intval($input['total_subjects_count']);
        }

        // 2. Fields (Renaming)
        if (isset($input['fields']) && is_array($input['fields'])) {
            if (!isset($new_input['fields']))
                $new_input['fields'] = array();
            foreach ($input['fields'] as $key => $val) {
                $new_input['fields'][$key] = sanitize_text_field($val);
            }
        }

        // 3. Visibility
        // Checkboxes only send data if checked. We need to handle unchecked state.
        // Logic: specific visibility input array.
        if (isset($input['visibility']) && is_array($input['visibility'])) {
            $new_input['visibility'] = $input['visibility']; // Assuming val=1 means Hidden
        } else {
            // If array exists in _POST but empty, it clears. 
            // But Settings API passes full input array. 
            // If tab is 'fields_hide_show', we should reset visibility if not present?
            // To be safe, we merge.
            if (isset($_POST['option_page']) && $_POST['option_page'] == $this->option_group && isset($_GET['tab']) && $_GET['tab'] == 'fields_hide_show') {
                // If we are on this tab and no visibility array sent, it means all unchecked (Visible)
                $new_input['visibility'] = array();
            }
        }

        // 4. Subjects (Dynamic)
        if (isset($input['subject_labels']) && is_array($input['subject_labels'])) {
            $new_input['subject_labels'] = array();
            foreach ($input['subject_labels'] as $idx => $label) {
                $new_input['subject_labels'][$idx] = sanitize_text_field($label);
            }
        }

        return $new_input;
    }

    // Callbacks

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

    public function render_field_text_input($args)
    {
        $options = get_option($this->option_name);
        $key = $args['key'];
        // Default Value
        $defaults = $this->get_default_fields();
        $default_val = isset($defaults[$key]) ? $defaults[$key] : '';

        $val = isset($options['fields'][$key]) ? esc_attr($options['fields'][$key]) : $default_val;

        echo "<input type='text' name='{$this->option_name}[fields][$key]' value='$val' class='regular-text' placeholder='$default_val' />";
    }

    public function render_field_checkbox_input($args)
    {
        $options = get_option($this->option_name);
        $key = $args['key'];
        // stored as '1' if hidden
        $is_hidden = isset($options['visibility'][$key]) && $options['visibility'][$key] === '1';
        $checked = $is_hidden ? 'checked' : '';

        echo "<input type='checkbox' name='{$this->option_name}[visibility][$key]' value='1' $checked />";
        echo '<label> Hide</label>';
    }

    public function render_subject_label_input($args)
    {
        $options = get_option($this->option_name);
        $idx = $args['index'];
        $val = isset($options['subject_labels'][$idx]) ? esc_attr($options['subject_labels'][$idx]) : '';
        echo "<input type='text' name='{$this->option_name}[subject_labels][$idx]' value='$val' class='regular-text' placeholder='Subject $idx' />";
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
                            do_settings_sections($this->plugin_name . '_fields');
                            break;

                        case 'fields_hide_show':
                            do_settings_sections($this->plugin_name . '_fields_hide_show');
                            break;

                        case 'subjects':
                            do_settings_sections($this->plugin_name . '_subjects');
                            break;

                        case 'fields_validation':
                        case 'subject_validation':
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
