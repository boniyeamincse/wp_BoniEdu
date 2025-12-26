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
                'photo_id' => intval($_POST['photo_id']),
                'updated_at' => current_time('mysql')
            );

            // Handle created_at for insert
            if (!isset($_POST['student_id']) || empty($_POST['student_id'])) {
                $data['created_at'] = current_time('mysql');
            }

            $format = array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%s');

            if (isset($_POST['student_id']) && !empty($_POST['student_id'])) {
                $id = intval($_POST['student_id']);
                $wpdb->update($this->table_students, $data, array('id' => $id), $format, array('%d'));
                add_settings_error('boniedu_students', 'student_updated', 'Student updated successfully.', 'success');
            } else {
                $wpdb->insert($this->table_students, $data, $format);
                add_settings_error('boniedu_students', 'student_added', 'Student enrolled successfully.', 'success');
            }
        }

        // Delete Logic
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'boniedu_delete_student')) {
                $wpdb->delete($this->table_students, array('id' => intval($_GET['id'])));
                add_settings_error('boniedu_students', 'student_deleted', 'Student deleted.', 'success');
            }
        }

        // Bulk Delete Logic
        if (isset($_POST['bulk-delete']) && is_array($_POST['bulk-delete'])) {
            // In real production, would check bulk nonce from the table
            foreach ($_POST['bulk-delete'] as $id) {
                $wpdb->delete($this->table_students, array('id' => intval($id)));
            }
            add_settings_error('boniedu_students', 'students_bulk_deleted', 'Students deleted.', 'success');
        }
    }

    public function display_page()
    {
        require_once BONIEDU_PLUGIN_DIR . 'includes/Admin/StudentListTable.php';
        $this->process_form_data();

        $action = isset($_GET['action']) ? $_GET['action'] : 'list';

        if ($action == 'add' || $action == 'edit') {
            $this->render_form();
        } else {
            $student_table = new StudentListTable();
            $student_table->prepare_items();
            ?>
            <div class="wrap">
                <h1 class="wp-heading-inline">Students</h1>
                <a href="<?php echo admin_url('admin.php?page=boniedu-students&action=add'); ?>" class="page-title-action">Add
                    New</a>
                <hr class="wp-header-end">

                <?php settings_errors('boniedu_students'); ?>

                <form id="student-filter" method="get">
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                    <?php $student_table->search_box('Search', 'student_search'); ?>
                    <?php $student_table->display(); ?>
                </form>
            </div>
            <?php
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
                <?php if ($student): ?><input type="hidden" name="student_id"
                        value="<?php echo $student->id; ?>"><?php endif; ?>

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
                    <h3>Student Photo</h3>
                    <table class="form-table">
                        <tr>
                            <th><label>Photo</label></th>
                            <td>
                                <?php
                                $photo_url = '';
                                if ($student && $student->photo_id) {
                                    $img = wp_get_attachment_image_src($student->photo_id, 'thumbnail');
                                    $photo_url = $img ? $img[0] : '';
                                }
                                ?>
                                <div id="boniedu-photo-preview" style="margin-bottom: 10px;">
                                    <?php if ($photo_url): ?>
                                        <img src="<?php echo esc_url($photo_url); ?>" style="max-width: 150px; height: auto;">
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="photo_id" id="boniedu-photo-id"
                                    value="<?php echo $student ? $student->photo_id : ''; ?>">
                                <button type="button" class="button" id="boniedu-upload-photo">Upload Photo</button>
                                <button type="button" class="button" id="boniedu-remove-photo"
                                    style="<?php echo $photo_url ? '' : 'display:none;'; ?>">Remove</button>
                                <p class="description">Upload or select student photo.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="card" style="padding: 20px; margin-top: 20px;">
                    <h3>Personal Info</h3>
                    <!-- ... existing personal info fields ... -->
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

            <script>
                jQuery(document).ready(function ($) {
                    var mediaUploader;
                    $('#boniedu-upload-photo').click(function (e) {
                        e.preventDefault();
                        if (mediaUploader) {
                            mediaUploader.open();
                            return;
                        }
                        mediaUploader = wp.media.frames.file_frame = wp.media({
                            title: 'Select Student Photo',
                            button: {
                                text: 'Use this photo'
                            },
                            multiple: false
                        });
                        mediaUploader.on('select', function () {
                            var attachment = mediaUploader.state().get('selection').first().toJSON();
                            $('#boniedu-photo-id').val(attachment.id);
                            $('#boniedu-photo-preview').html('<img src="' + attachment.url + '" style="max-width: 150px; height: auto;">');
                            $('#boniedu-remove-photo').show();
                        });
                        mediaUploader.open();
                    });
                    $('#boniedu-remove-photo').click(function (e) {
                        $('#boniedu-photo-id').val('');
                        $('#boniedu-photo-preview').html('');
                        $(this).hide();
                    });
                });
            </script>
        </div>
        <?php
    }

}
