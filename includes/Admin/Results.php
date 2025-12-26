<?php

namespace BoniEdu\Admin;

/**
 * Manage Results (Manual Entry).
 */
class Results
{

    private $plugin_name;
    private $version;
    private $table_results;
    private $table_students;
    private $table_classes;
    private $table_sections;
    private $table_subjects;

    public function __construct($plugin_name, $version)
    {
        global $wpdb;
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->table_results = $wpdb->prefix . 'boniedu_results';
        $this->table_students = $wpdb->prefix . 'boniedu_students';
        $this->table_classes = $wpdb->prefix . 'boniedu_classes';
        $this->table_sections = $wpdb->prefix . 'boniedu_sections';
        $this->table_subjects = $wpdb->prefix . 'boniedu_subjects';
    }

    public function add_submenu()
    {
        add_submenu_page(
            $this->plugin_name,
            'Results',
            'Results',
            'manage_options',
            'boniedu-results',
            array($this, 'display_page')
        );
    }

    public function process_form_data()
    {
        global $wpdb;

        if (isset($_POST['boniedu_save_results_nonce']) && wp_verify_nonce($_POST['boniedu_save_results_nonce'], 'boniedu_save_results')) {

            $class_id = intval($_POST['class_id']);
            $section_id = intval($_POST['section_id']);
            $subject_id = intval($_POST['subject_id']);
            $exam_type = sanitize_text_field($_POST['exam_type']);

            $marks_data = $_POST['marks']; // array( student_id => marks )

            if (!empty($marks_data) && is_array($marks_data)) {
                // Ensure Calculator class is available
                if (!class_exists('BoniEdu\Core\Calculator')) {
                    require_once BONIEDU_PLUGIN_DIR . 'includes/Core/Calculator.php';
                }

                foreach ($marks_data as $student_id => $marks) {
                    $student_id = intval($student_id);
                    $marks = floatval($marks);
                    $is_absent = isset($_POST['absent'][$student_id]) ? 1 : 0;

                    // Calculate Grade & GPA
                    $grade_letter = \BoniEdu\Core\Calculator::get_grade_letter($marks);
                    $grade_point = \BoniEdu\Core\Calculator::get_grade_point($marks);

                    if ($is_absent) {
                        $marks = 0;
                        $grade_letter = 'F';
                        $grade_point = 0.00;
                    }

                    // Check if result exists
                    $existing_id = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $this->table_results 
						WHERE student_id = %d AND subject_id = %d AND exam_type = %s",
                        $student_id,
                        $subject_id,
                        $exam_type
                    ));

                    $data = array(
                        'student_id' => $student_id,
                        'subject_id' => $subject_id,
                        'exam_type' => $exam_type,
                        'marks_obtained' => $marks,
                        'grade_letter' => $grade_letter,
                        'grade_point' => $grade_point,
                        'is_absent' => $is_absent,
                        'updated_at' => current_time('mysql')
                    );

                    if ($existing_id) {
                        $wpdb->update($this->table_results, $data, array('id' => $existing_id));
                    } else {
                        $data['created_at'] = current_time('mysql');
                        $wpdb->insert($this->table_results, $data);
                    }
                }
                add_settings_error('boniedu_results', 'results_saved', 'Results saved successfully.', 'success');
            }
        }
    }

    public function display_page()
    {
        $this->process_form_data();
        global $wpdb;

        $step = isset($_GET['step']) ? intval($_GET['step']) : 1;

        $classes = $wpdb->get_results("SELECT * FROM $this->table_classes ORDER BY numeric_value ASC");

        // Defaults
        $selected_class = isset($_REQUEST['class_id']) ? intval($_REQUEST['class_id']) : 0;
        $selected_section = isset($_REQUEST['section_id']) ? intval($_REQUEST['section_id']) : 0;
        $selected_subject = isset($_REQUEST['subject_id']) ? intval($_REQUEST['subject_id']) : 0;
        $selected_exam = isset($_REQUEST['exam_type']) ? sanitize_text_field($_REQUEST['exam_type']) : '';

        ?>
        <div class="wrap">
            <h1>Results Management</h1>
            <?php settings_errors('boniedu_results'); ?>

            <div class="card" style="padding: 20px; margin-bottom: 20px;">
                <form method="get">
                    <input type="hidden" name="page" value="boniedu-results">
                    <input type="hidden" name="step" value="2">

                    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                        <div>
                            <label>Class</label><br>
                            <select name="class_id" required onchange="this.form.submit()">
                                <option value="">-- Select Class --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?php echo $c->id; ?>" <?php selected($selected_class, $c->id); ?>>
                                        <?php echo esc_html($c->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if ($selected_class):
                            $sections = $wpdb->get_results($wpdb->prepare("SELECT * FROM $this->table_sections WHERE class_id = %d", $selected_class));
                            $subjects = $wpdb->get_results($wpdb->prepare("SELECT * FROM $this->table_subjects WHERE class_id = %d", $selected_class));
                            ?>
                            <div>
                                <label>Section</label><br>
                                <select name="section_id">
                                    <option value="0">All Sections</option>
                                    <?php foreach ($sections as $s): ?>
                                        <option value="<?php echo $s->id; ?>" <?php selected($selected_section, $s->id); ?>>
                                            <?php echo esc_html($s->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label>Subject</label><br>
                                <select name="subject_id" required>
                                    <option value="">-- Select Subject --</option>
                                    <?php foreach ($subjects as $sub): ?>
                                        <option value="<?php echo $sub->id; ?>" <?php selected($selected_subject, $sub->id); ?>>
                                            <?php echo esc_html($sub->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label>Exam Type</label><br>
                                <select name="exam_type" required>
                                    <option value="">-- Select Exam --</option>
                                    <option value="First Terminal" <?php selected($selected_exam, 'First Terminal'); ?>>First
                                        Terminal</option>
                                    <option value="Second Terminal" <?php selected($selected_exam, 'Second Terminal'); ?>>Second
                                        Terminal</option>
                                    <option value="Final" <?php selected($selected_exam, 'Final'); ?>>Final</option>
                                </select>
                            </div>

                            <div>
                                <button type="submit" class="button button-primary">Filter / Load Students</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php
            if ($step == 2 && $selected_class && $selected_subject && $selected_exam) {
                $this->render_marks_entry_form($selected_class, $selected_section, $selected_subject, $selected_exam);
            }
            ?>

        </div>
        <?php
    }

    private function render_marks_entry_form($class_id, $section_id, $subject_id, $exam_type)
    {
        global $wpdb;

        $query = "SELECT * FROM $this->table_students WHERE class_id = $class_id";
        if ($section_id) {
            $query .= " AND section_id = $section_id";
        }
        $query .= " ORDER BY roll_no ASC";

        $students = $wpdb->get_results($query);

        // Fetch existing results
        $existing_results_raw = $wpdb->get_results($wpdb->prepare(
            "SELECT student_id, marks_obtained, is_absent FROM $this->table_results 
			WHERE subject_id = %d AND exam_type = %s",
            $subject_id,
            $exam_type
        ));

        $results_map = array();
        foreach ($existing_results_raw as $res) {
            $results_map[$res->student_id] = $res;
        }

        if (empty($students)) {
            echo '<p>No students found for this selection.</p>';
            return;
        }

        ?>
        <form method="post" action="">
            <?php wp_nonce_field('boniedu_save_results', 'boniedu_save_results_nonce'); ?>
            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
            <input type="hidden" name="section_id" value="<?php echo $section_id; ?>">
            <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
            <input type="hidden" name="exam_type" value="<?php echo esc_attr($exam_type); ?>">

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 80px;">Roll No</th>
                        <th>Student Name</th>
                        <th style="width: 150px;">Marks Obtained</th>
                        <th style="width: 80px;">Absent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student):
                        $existing = isset($results_map[$student->id]) ? $results_map[$student->id] : null;
                        $marks = $existing ? $existing->marks_obtained : '';
                        $absent = $existing && $existing->is_absent ? 'checked' : '';
                        ?>
                        <tr>
                            <td><?php echo intval($student->roll_no); ?></td>
                            <td><strong><?php echo esc_html($student->name); ?></strong></td>
                            <td>
                                <input type="number" step="0.01" name="marks[<?php echo $student->id; ?>]"
                                    value="<?php echo esc_attr($marks); ?>" class="small-text" style="width: 100%;">
                            </td>
                            <td>
                                <input type="checkbox" name="absent[<?php echo $student->id; ?>]" value="1" <?php echo $absent; ?>>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary button-large">Save Results</button>
            </p>
        </form>
        <?php
    }

}
