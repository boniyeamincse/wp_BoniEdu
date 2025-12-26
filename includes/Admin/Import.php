<?php

namespace BoniEdu\Admin;

class Import
{
    use CSV_Handler;

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function handle_import()
    {
        if (!isset($_POST['boniedu_import_nonce']) || !wp_verify_nonce($_POST['boniedu_import_nonce'], 'boniedu_import_action')) {
            return;
        }

        if (empty($_FILES['import_file']['tmp_name'])) {
            add_settings_error('boniedu_import', 'file_error', 'Please upload a valid CSV file.', 'error');
            return;
        }

        $import_type = isset($_POST['import_type']) ? sanitize_text_field($_POST['import_type']) : '';
        $rows = $this->read_csv($_FILES['import_file']['tmp_name']);

        if ($rows === false) {
            add_settings_error('boniedu_import', 'csv_error', 'Failed to parse CSV.', 'error');
            return;
        }

        switch ($import_type) {
            case 'students':
                $this->process_students_import($rows);
                break;
            case 'results':
                $this->process_results_import($rows);
                break;
            default:
                add_settings_error('boniedu_import', 'type_error', 'Invalid import type.', 'error');
        }
    }

    private function process_students_import($rows)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'boniedu_students';
        $success_count = 0;
        $error_count = 0;

        foreach ($rows as $row) {
            // Basic validation
            if (empty($row['first_name']) || empty($row['last_name'])) {
                $error_count++;
                continue;
            }

            // Map CSV columns to DB columns
            $data = [
                'first_name' => sanitize_text_field($row['first_name']),
                'last_name' => sanitize_text_field($row['last_name']),
                'email' => isset($row['email']) ? sanitize_email($row['email']) : '',
                'roll_number' => isset($row['roll_number']) ? sanitize_text_field($row['roll_number']) : '',
                'class_id' => isset($row['class_id']) ? intval($row['class_id']) : 0,
                'section_id' => isset($row['section_id']) ? intval($row['section_id']) : 0,
                // Add more fields as needed
                'created_at' => current_time('mysql'),
            ];

            // Check duplicate by email or roll_number if needed, for now insert ignore or simple insert
            // Assuming email unique
            $inserted = $wpdb->insert($table_name, $data);

            if ($inserted) {
                $success_count++;
            } else {
                $error_count++;
            }
        }

        add_settings_error('boniedu_import', 'import_success', "Imported $success_count students. Failed: $error_count.", 'success');
    }

    private function process_results_import($rows)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'boniedu_results';
        $success_count = 0;
        $updated_count = 0;
        $error_count = 0;

        // Get Configured Subjects to map Headers -> IDs
        $settings = get_option('boniedu_result_settings', array());
        $subject_labels = isset($settings['subject_labels']) ? $settings['subject_labels'] : array();
        // Create map: Label => ID (Index)
        // e.g., 'Bangla' => 1
        $subject_map = array();
        if (!empty($subject_labels)) {
            foreach ($subject_labels as $id => $label) {
                if (!empty($label)) {
                    $subject_map[trim(strtolower($label))] = $id;
                }
            }
        }

        foreach ($rows as $row) {
            $student_id = 0;

            // Try to find student_id
            if (!empty($row['student_id'])) {
                $student_id = intval($row['student_id']);
            } elseif (!empty($row['roll_number'])) {
                $student = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}boniedu_students WHERE roll_number = %s", $row['roll_number']));
                $student_id = $student ? $student->id : 0;
            } elseif (!empty($row['roll_no'])) { // Support roll_no alias
                $student = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}boniedu_students WHERE roll_number = %s", $row['roll_no']));
                $student_id = $student ? $student->id : 0;
            }

            if (empty($student_id) || empty($row['exam_id'])) {
                $error_count++;
                continue;
            }

            $exam_id = intval($row['exam_id']);

            // Detect if this is a Bulk Row (Subject Columns) or Single Row
            // Bulk strategy: Check if any CSV keys match our Subject Labels
            $csv_keys = array_change_key_case($row, CASE_LOWER); // normalized keys

            $found_subject_column = false;

            if (!empty($subject_map)) {
                foreach ($subject_map as $label_lower => $subj_id) {
                    if (isset($csv_keys[$label_lower])) {
                        // Found a mark for this subject!
                        $found_subject_column = true;
                        $mark_val = $csv_keys[$label_lower];

                        // Skip empty marks? Or treat as 0? Let's skip if empty string to allow partial updates
                        if ($mark_val === '')
                            continue;

                        $this->insert_or_update_result($table_name, $student_id, $exam_id, $subj_id, $mark_val, $success_count, $updated_count, $error_count);
                    }
                }
            }

            // Fallback: If no subject columns found, try standard single-row format
            if (!$found_subject_column && !empty($row['subject_id'])) {
                $this->insert_or_update_result($table_name, $student_id, $exam_id, intval($row['subject_id']), $row['marks_obtained'], $success_count, $updated_count, $error_count);
            } elseif (!$found_subject_column) {
                // No valid subject data found in this row
                $error_count++;
            }
        }

        add_settings_error('boniedu_import', 'import_success', "Processed Results. New: $success_count. Updated: $updated_count. Failed/Skipped: $error_count.", 'success');
    }

    private function insert_or_update_result($table_name, $student_id, $exam_id, $subject_id, $mark, &$success, &$updated, &$error)
    {
        global $wpdb;

        // Retrieve defaults for grade/fullMark if not provided in row (Bulk mode usually doesn't have grade per subject in same row easily unless complex pattern)
        // We will calculate simple Pass/Fail or Grade later? For now, we just save marks.
        // TODO: Auto-calculate Grade based on mark.

        $data = [
            'student_id' => $student_id,
            'exam_id' => $exam_id,
            'subject_id' => $subject_id,
            'marks_obtained' => floatval($mark),
            'total_marks' => 100, // Default, maybe fetch from settings
            'updated_at' => current_time('mysql')
        ];

        // Check exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE student_id = %d AND exam_id = %d AND subject_id = %d",
            $student_id,
            $exam_id,
            $subject_id
        ));

        if ($existing) {
            if ($wpdb->update($table_name, $data, ['id' => $existing->id]) !== false) {
                $updated++;
            } else {
                $error++;
            }
        } else {
            $data['created_at'] = current_time('mysql');
            if ($wpdb->insert($table_name, $data)) {
                $success++;
            } else {
                $error++;
            }
        }
    }

    public function render_page()
    {
        // Handle form submission
        if (isset($_POST['submit_import'])) {
            $this->handle_import();
        }

        settings_errors('boniedu_import');
        ?>
        <div class="wrap">
            <h1>Import Data</h1>

            <div class="boniedu-card">
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('boniedu_import_action', 'boniedu_import_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="import_type">Import Type</label></th>
                            <td>
                                <select name="import_type" id="import_type" class="regular-text">
                                    <option value="students">Students</option>
                                    <option value="results">Results (Bulk / Single)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="import_file">CSV File</label></th>
                            <td>
                                <input type="file" name="import_file" id="import_file" accept=".csv" required>
                                <p class="description">Upload a standard .csv file.</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('Import Data', 'primary', 'submit_import'); ?>
                </form>

                <hr>

                <h3>CSV Format Guidelines</h3>
                <div
                    style="background: var(--boni-bg); padding: 15px; border-radius: var(--boni-radius); border: 1px solid var(--boni-border);">
                    <h4 style="margin-top:0;">Students Import</h4>
                    <code>first_name, last_name, email, roll_number, class_id, section_id</code>

                    <h4 style="margin-top:15px;">Results Import (Bulk Subject-wise) - Recommended</h4>
                    <p>Use the <strong>exact Subject Label</strong> (from Settings) as the column header.</p>
                    <code>roll_number, exam_id, Bengali, English, Math, Science</code>

                    <h4 style="margin-top:15px;">Results Import (Single Row)</h4>
                    <code>roll_number, exam_id, subject_id, marks_obtained</code>
                </div>
            </div>
        </div>
        <?php
    }
}
