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

        foreach ($rows as $row) {
            $student_id = 0;

            // Try to find student_id
            if (!empty($row['student_id'])) {
                $student_id = intval($row['student_id']);
            } elseif (!empty($row['roll_number'])) {
                // Lookup by roll number
                $student = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}boniedu_students WHERE roll_number = %s", $row['roll_number']));
                if ($student) {
                    $student_id = $student->id;
                }
            }

            if (empty($student_id) || empty($row['exam_id']) || empty($row['subject_id'])) {
                $error_count++;
                continue;
            }

            $data = [
                'student_id' => $student_id,
                'exam_id' => intval($row['exam_id']),
                'subject_id' => intval($row['subject_id']),
                'marks_obtained' => floatval($row['marks_obtained']),
                'total_marks' => isset($row['total_marks']) ? floatval($row['total_marks']) : 100,
                'grade' => isset($row['grade']) ? sanitize_text_field($row['grade']) : '',
            ];

            // Check if exists
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_name WHERE student_id = %d AND exam_id = %d AND subject_id = %d",
                $data['student_id'],
                $data['exam_id'],
                $data['subject_id']
            ));

            if ($existing) {
                $entry_id = $existing->id;
                $data['updated_at'] = current_time('mysql');
                $updated = $wpdb->update($table_name, $data, ['id' => $entry_id]);
                if ($updated !== false)
                    $updated_count++;
            } else {
                $data['created_at'] = current_time('mysql');
                $inserted = $wpdb->insert($table_name, $data);
                if ($inserted)
                    $success_count++;
                else
                    $error_count++;
            }
        }

        add_settings_error('boniedu_import', 'import_success', "Imported $success_count new results. Updated: $updated_count. Failed: $error_count.", 'success');
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
            <h2>Import Data</h2>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('boniedu_import_action', 'boniedu_import_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="import_type">Import Type</label></th>
                        <td>
                            <select name="import_type" id="import_type">
                                <option value="students">Students</option>
                                <option value="results">Results</option>
                            </select>
                            <p class="description">Select the type of data you are importing.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="import_file">CSV File</label></th>
                        <td>
                            <input type="file" name="import_file" id="import_file" accept=".csv" required>
                            <p class="description">Upload a CSV file.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Import CSV', 'primary', 'submit_import'); ?>
            </form>

            <hr>
            <h3>CSV Guidelines</h3>
            <p><strong>Students CSV Headers:</strong> first_name, last_name, email, roll_number, class_id, section_id</p>
            <p><strong>Results CSV Headers:</strong> student_id (or roll_number), exam_id, subject_id, marks_obtained,
                total_marks, grade</p>
        </div>
        <?php
    }
}
