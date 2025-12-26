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
                'religion' => sanitize_text_field($_POST['religion']),
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

            $format = array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%s');

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
        $heading = 'Add New Student';
        $btn_text = 'Publish';

        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_students WHERE id = %d", $id));
            if ($student) {
                $heading = 'Edit Student';
                $btn_text = 'Update';
            }
        }

        // Fetch Helpers
        $classes = $wpdb->get_results("SELECT * FROM $this->table_classes ORDER BY numeric_value ASC");
        $sessions = get_option('boniedu_sessions', array());
        $sections = $wpdb->get_results("SELECT * FROM $this->table_sections");

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo $heading; ?></h1>
            <hr class="wp-header-end">
            <?php settings_errors('boniedu_students'); ?>

            <form method="post" action="" id="post">
                <?php wp_nonce_field('boniedu_save_student', 'boniedu_save_student_nonce'); ?>
                <?php if ($student): ?><input type="hidden" name="student_id"
                        value="<?php echo $student->id; ?>"><?php endif; ?>

                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">

                        <!-- Main Content (Left Column) -->
                        <div id="post-body-content">

                            <!-- Student Name Input (Title Style) -->
                            <div id="titlediv">
                                <div id="titlewrap">
                                    <label class="screen-reader-text" id="title-prompt-text" for="title">Enter student
                                        name</label>
                                    <input type="text" name="student_name" size="30"
                                        value="<?php echo $student ? esc_attr($student->name) : ''; ?>" id="title"
                                        spellcheck="true" autocomplete="off" placeholder="Enter student name" required>
                                </div>
                            </div>

                            <!-- Student Information Meta Box -->
                            <div class="postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle">Student Information</h2>
                                </div>
                                <div class="inside">
                                    <p>
                                        <label for="father_name"><strong>Father Name</strong></label>
                                        <input type="text" name="father_name" id="father_name" class="widefat"
                                            value="<?php echo $student ? esc_attr($student->father_name) : ''; ?>"
                                            placeholder="Father Name">
                                    </p>
                                    <p>
                                        <label for="mother_name"><strong>Mother Name</strong></label>
                                        <input type="text" name="mother_name" id="mother_name" class="widefat"
                                            value="<?php echo $student ? esc_attr($student->mother_name) : ''; ?>"
                                            placeholder="Mother Name">
                                    </p>

                                    <div style="display: flex; gap: 20px;">
                                        <div style="flex: 1;">
                                            <label for="dob"><strong>Date Of Birth</strong></label><br>
                                            <input type="date" name="dob" id="dob" class="widefat"
                                                value="<?php echo $student ? esc_attr($student->dob) : ''; ?>">
                                            <span class="description">Date Of Birth Format (DD/MM/YEAR)</span>
                                        </div>
                                        <div style="flex: 1;">
                                            <label for="gender"><strong>Gender</strong></label><br>
                                            <select name="gender" id="gender" class="widefat">
                                                <option value="">Select Gender</option>
                                                <option value="Male" <?php selected($student ? $student->gender : '', 'Male'); ?>>Male</option>
                                                <option value="Female" <?php selected($student ? $student->gender : '', 'Female'); ?>>Female</option>
                                                <option value="Other" <?php selected($student ? $student->gender : '', 'Other'); ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <p>
                                        <label for="religion"><strong>Religion</strong></label>
                                        <input type="text" name="religion" id="religion" class="widefat"
                                            value="<?php echo $student ? esc_attr($student->religion) : ''; ?>"
                                            placeholder="Religion">
                                    </p>

                                    <div style="display: flex; gap: 20px;">
                                        <div style="flex: 1;">
                                            <label for="roll_no"><strong>Roll</strong></label><br>
                                            <input type="number" name="roll_no" id="roll_no" class="widefat"
                                                value="<?php echo $student ? esc_attr($student->roll_no) : ''; ?>"
                                                placeholder="Roll" required>
                                        </div>
                                        <div style="flex: 1;">
                                            <label for="registration_no"><strong>Reg no</strong></label><br>
                                            <input type="text" name="registration_no" id="registration_no" class="widefat"
                                                value="<?php echo $student ? esc_attr($student->registration_no) : ''; ?>"
                                                placeholder="Reg no">
                                        </div>
                                    </div>

                                    <p>
                                        <label for="address"><strong>Address</strong></label>
                                        <textarea name="address" id="address" class="widefat" rows="2"
                                            placeholder="Address"><?php echo $student ? esc_textarea($student->address) : ''; ?></textarea>
                                    </p>

                                </div>
                            </div>
                        </div>

                        <!-- Sidebar (Right Column) -->
                        <div id="postbox-container-1" class="postbox-container">

                            <!-- Publish Meta Box -->
                            <div class="postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle">Publish</h2>
                                </div>
                                <div class="inside">
                                    <div class="submitbox" id="submitpost">
                                        <div id="minor-publishing">
                                            <div id="misc-publishing-actions">
                                                <div class="misc-pub-section">
                                                    Status: <strong><?php echo $student ? 'Enrolled' : 'New'; ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="major-publishing-actions">
                                            <div id="delete-action">
                                                <?php if ($student): ?>
                                                    <a class="submitdelete deletion"
                                                        href="<?php echo admin_url('admin.php?page=boniedu-students&action=delete&id=' . $student->id . '&_wpnonce=' . wp_create_nonce('boniedu_delete_student')); ?>"
                                                        onclick="return confirm('Are you sure?')">Trash</a>
                                                <?php endif; ?>
                                            </div>
                                            <div id="publishing-action">
                                                <input type="submit" name="publish" id="publish"
                                                    class="button button-primary button-large" value="<?php echo $btn_text; ?>">
                                            </div>
                                            <div class="clear"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Class Meta Box -->
                            <div class="postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle">Class</h2>
                                </div>
                                <div class="inside">
                                    <select name="class_id" class="widefat" required>
                                        <option value="">All Class</option>
                                        <?php foreach ($classes as $c): ?>
                                            <option value="<?php echo $c->id; ?>" <?php selected($student ? $student->class_id : '', $c->id); ?>><?php echo esc_html($c->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p><a href="<?php echo admin_url('admin.php?page=boniedu-classes'); ?>">+ Add New Class</a>
                                    </p>
                                </div>
                            </div>

                            <!-- Year Meta Box -->
                            <div class="postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle">Year</h2>
                                </div>
                                <div class="inside">
                                    <select name="session_year" class="widefat" required>
                                        <option value="">All Year</option>
                                        <?php foreach ($sessions as $sess): ?>
                                            <option value="<?php echo esc_attr($sess); ?>" <?php selected($student ? $student->session_year : '', $sess); ?>><?php echo esc_html($sess); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p><a href="<?php echo admin_url('admin.php?page=boniedu-academic-years'); ?>">+ Add New
                                            Year</a></p>
                                </div>
                            </div>

                            <!-- Section Meta Box -->
                            <div class="postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle">Section</h2>
                                </div>
                                <div class="inside">
                                    <select name="section_id" class="widefat">
                                        <option value="0">All Section</option>
                                        <?php foreach ($sections as $s): ?>
                                            <option value="<?php echo $s->id; ?>" <?php selected($student ? $student->section_id : '', $s->id); ?>><?php echo esc_html($s->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p><a
                                            href="<?php echo admin_url('admin.php?page=boniedu-classes'); // Reusing classes link as sections created there ?>">+
                                            Add New Section</a></p>
                                </div>
                            </div>

                            <!-- Featured Image (Photo) Meta Box -->
                            <div class="postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle">Student Photo</h2>
                                </div>
                                <div class="inside">
                                    <?php
                                    $photo_url = '';
                                    if ($student && $student->photo_id) {
                                        $img = wp_get_attachment_image_src($student->photo_id, 'thumbnail');
                                        $photo_url = $img ? $img[0] : '';
                                    }
                                    ?>
                                    <div id="boniedu-photo-preview" style="margin-bottom: 10px;">
                                        <?php if ($photo_url): ?>
                                            <img src="<?php echo esc_url($photo_url); ?>" style="width: 100%; height: auto;">
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" name="photo_id" id="boniedu-photo-id"
                                        value="<?php echo $student ? $student->photo_id : ''; ?>">
                                    <p class="hide-if-no-js">
                                        <a href="#"
                                            id="boniedu-upload-photo"><?php echo $photo_url ? 'Click the image to edit or update' : 'Set student photo'; ?></a>
                                    </p>
                                    <p class="hide-if-no-js">
                                        <a href="#" id="boniedu-remove-photo"
                                            style="<?php echo $photo_url ? '' : 'display:none;'; ?>">Remove student photo</a>
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

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
                            button: { text: 'Use this photo' },
                            multiple: false
                        });
                        mediaUploader.on('select', function () {
                            var attachment = mediaUploader.state().get('selection').first().toJSON();
                            $('#boniedu-photo-id').val(attachment.id);
                            $('#boniedu-photo-preview').html('<img src="' + attachment.url + '" style="width: 100%; height: auto;">');
                            $('#boniedu-upload-photo').text('Click the image to edit or update');
                            $('#boniedu-remove-photo').show();
                        });
                        mediaUploader.open();
                    });

                    $('#boniedu-remove-photo').click(function (e) {
                        e.preventDefault();
                        $('#boniedu-photo-id').val('');
                        $('#boniedu-photo-preview').html('');
                        $('#boniedu-upload-photo').text('Set student photo');
                        $(this).hide();
                    });
                });
            </script>
        </div>
        <?php
    }

}
