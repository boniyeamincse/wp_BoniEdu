<?php

namespace BoniEdu\Admin;

/**
 * Manage Students (Enrollment & Lists).
 */
class Students
{

    private $plugin_name;
    private $version;
    private $table_students;
    private $table_classes;
    private $table_sections;

    public function __construct($plugin_name, $version)
    {
        global $wpdb;
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->table_students = $wpdb->prefix . 'boniedu_students';
        $this->table_classes = $wpdb->prefix . 'boniedu_classes';
        $this->table_sections = $wpdb->prefix . 'boniedu_sections';
    }

    public function add_submenu()
    {
        add_submenu_page(
            $this->plugin_name,
            'Students',
            'Students',
            'manage_options',
            'boniedu-students',
            array($this, 'display_page')
        );
    }

    public function process_form_data()
    {
        global $wpdb;

        if (isset($_POST['boniedu_save_student_nonce']) && wp_verify_nonce($_POST['boniedu_save_student_nonce'], 'boniedu_save_student')) {

            $data = array(
                'roll_no' => intval($_POST['roll_no']),
                'name' => sanitize_text_field($_POST['student_name']),
                'father_name' => sanitize_text_field($_POST['father_name']),
                'mother_name' => sanitize_text_field($_POST['mother_name']),
                'dob' => sanitize_text_field($_POST['dob']),
                'gender' => sanitize_text_field($_POST['gender']),
                'address' => sanitize_textarea_field($_POST['address']),
                'class_id' => intval($_POST['class_id']),
                'section_id' => intval($_POST['section_id']),
                'session_year' => sanitize_text_field($_POST['session_year']),
                'registration_no' => sanitize_text_field($_POST['registration_no']),
                'created_at' => current_time('mysql')
            );

            $format = array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s');

            if (isset($_POST['student_id']) && !empty($_POST['student_id'])) {
                $id = intval($_POST['student_id']);
                $wpdb->update($this->table_students, $data, array('id' => $id), $format, array('%d'));
                add_settings_error('boniedu_students', 'student_updated', 'Student updated successfully.', 'success');
            } else {
                $wpdb->insert($this->table_students, $data, $format);
                add_settings_error('boniedu_students', 'student_added', 'Student enrolled successfully.', 'success');
            }
        }
    }

    public function display_page()
    {
        $this->process_form_data();

        $action = isset($_GET['action']) ? $_GET['action'] : 'list';

        if ($action == 'add' || $action == 'edit') {
            $this->render_form();
        } else {
            // Module 10 will implement the list view.
            echo '<div class="wrap"><h1>Students List (Module 10)</h1>';
            echo '<a href="' . admin_url('admin.php?page=boniedu-students&action=add') . '" class="page-title-action">Add New</a>';
            echo '<p>Student Directory will be implemented in Module 10.</p></div>';
        }
    }

    private function render_form()
    {
        global $wpdb;
        $student = null;
        $heading = 'Enroll New Student';
        $btn_text = 'Enroll Student';

        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_students WHERE id = %d", $id));
            if ($student) {
                $heading = 'Edit Student';
                $btn_text = 'Update Student';
            }
        }

        // Fetch Helpers
        $classes = $wpdb->get_results("SELECT * FROM $this->table_classes ORDER BY numeric_value ASC");
        $sessions = get_option('boniedu_sessions', array());
        // Fetch all sections (will separate by JS ideally, but fetching all for simple view)
        $sections = $wpdb->get_results("SELECT * FROM $this->table_sections");

        ?>
        <div class="wrap">
            <h1><?php echo $heading; ?></h1>
            <?php settings_errors('boniedu_students'); ?>

            <form method="post" action="" style="max-width: 800px;">
                <?php wp_nonce_field('boniedu_save_student', 'boniedu_save_student_nonce'); ?>
                <?php if ($student): ?><input type="hidden" name="student_id" value="<?php echo $student->id; ?>"><?php endif; ?>

                <div class="card" style="padding: 20px;">
                    <h3>Academic Info</h3>
                    <table class="form-table">
                        <tr>
                            <th><label>Session Year</label></th>
                            <td>
                                <select name="session_year" required>
                                    <option value="">-- Select Session --</option>
                                    <?php foreach ($sessions as $sess): ?>
                                        <option value="<?php echo esc_attr($sess); ?>" <?php selected($student ? $student->session_year : '', $sess); ?>><?php echo esc_html($sess); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Class</label></th>
                            <td>
                                <select name="class_id" required>
                                    <option value="">-- Select Class --</option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?php echo $c->id; ?>" <?php selected($student ? $student->class_id : '', $c->id); ?>><?php echo esc_html($c->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Section</label></th>
                            <td>
                                <select name="section_id">
                                    <option value="0">-- Select Section --</option>
                                    <?php foreach ($sections as $s): ?>
                                        <option value="<?php echo $s->id; ?>" <?php selected($student ? $student->section_id : '', $s->id); ?>><?php echo esc_html($s->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Roll No</label></th>
                            <td><input type="number" name="roll_no" value="<?php echo $student ? $student->roll_no : ''; ?>"
                                    required></td>
                        </tr>
                        <tr>
                            <th><label>Registration No</label></th>
                            <td><input type="text" name="registration_no"
                                    value="<?php echo $student ? $student->registration_no : ''; ?>"></td>
                        </tr>
                    </table>
                </div>

                <div class="card" style="padding: 20px; margin-top: 20px;">
                    <h3>Personal Info</h3>
                    <table class="form-table">
                        <tr>
                            <th><label>Student Name</label></th>
                            <td><input type="text" name="student_name" class="regular-text"
                                    value="<?php echo $student ? $student->name : ''; ?>" required></td>
                        </tr>
                        <tr>
                            <th><label>Father's Name</label></th>
                            <td><input type="text" name="father_name" class="regular-text"
                                    value="<?php echo $student ? $student->father_name : ''; ?>"></td>
                        </tr>
                        <tr>
                            <th><label>Mother's Name</label></th>
                            <td><input type="text" name="mother_name" class="regular-text"
                                    value="<?php echo $student ? $student->mother_name : ''; ?>"></td>
                        </tr>
                        <tr>
                            <th><label>Date of Birth</label></th>
                            <td><input type="date" name="dob" value="<?php echo $student ? $student->dob : ''; ?>"></td>
                        </tr>
                        <tr>
                            <th><label>Gender</label></th>
                            <td>
                                <select name="gender">
                                    <option value="Male" <?php selected($student ? $student->gender : '', 'Male'); ?>>Male
                                    </option>
                                    <option value="Female" <?php selected($student ? $student->gender : '', 'Female'); ?>>
                                        Female</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Address</label></th>
                            <td><textarea name="address" class="large-text"
                                    rows="3"><?php echo $student ? $student->address : ''; ?></textarea></td>
                        </tr>
                    </table>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary button-large"><?php echo $btn_text; ?></button>
                    <a href="<?php echo admin_url('admin.php?page=boniedu-students'); ?>"
                        class="button button-secondary">Cancel</a>
                </p>
            </form>
        </div>
        <?php
    }

}
