<?php

namespace BoniEdu\Admin;

class Export
{
    use CSV_Handler;

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function init()
    {
        // Hook for handling export request before headers are sent (if needed via admin-post)
        // Or we can handle it in the render if we use OB clean, but admin-post is better.
        // For simplicity in this single page app flow, keeping checks here or in early hooks.
        if (isset($_POST['submit_export']) && check_admin_referer('boniedu_export_action', 'boniedu_export_nonce')) {
            $this->handle_export();
        }
    }

    public function handle_export()
    {
        $type = isset($_POST['export_type']) ? sanitize_text_field($_POST['export_type']) : '';

        switch ($type) {
            case 'students':
                $this->export_students();
                break;
            case 'results':
                $this->export_results();
                break;
            default:
                add_settings_error('boniedu_export', 'type_error', 'Invalid export type.', 'error');
        }
    }

    private function export_students()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'boniedu_students';
        $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

        if (empty($results)) {
            add_settings_error('boniedu_export', 'empty', 'No students found to export.', 'error');
            return;
        }

        $filename = 'students_export_' . date('Y-m-d_H-i-s') . '.csv';
        $this->send_csv_headers($filename);
        $this->output_csv($results);
    }

    private function export_results()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'boniedu_results';
        // Join with students to make it more readable? Or just raw dump.
        // Raw dump is better for update/re-import cycles.
        $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

        if (empty($results)) {
            add_settings_error('boniedu_export', 'empty', 'No results found to export.', 'error');
            return;
        }

        $filename = 'results_export_' . date('Y-m-d_H-i-s') . '.csv';
        $this->send_csv_headers($filename);
        $this->output_csv($results);
    }

    public function render_page()
    {
        settings_errors('boniedu_export');
        ?>
        <div class="wrap">
            <h2>Export Data</h2>
            <form method="post">
                <?php wp_nonce_field('boniedu_export_action', 'boniedu_export_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="export_type">Export Type</label></th>
                        <td>
                            <select name="export_type" id="export_type">
                                <option value="students">Students</option>
                                <option value="results">Results</option>
                            </select>
                            <p class="description">Select the data to export.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Export CSV', 'primary', 'submit_export'); ?>
            </form>
        </div>
        <?php
    }
}
