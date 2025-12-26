<?php

namespace BoniEdu\Publics; // Using Publics to avoid reserved keyword 'Public' issues if any, or just sticking to convention. logic says 'Public' is fine as namespace segment but 'Publics' or 'Frontend' is safer. Let's check headers. The user has 'Core' and 'Admin'. Let's use 'Frontend'.

class Shortcode
{
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function register()
    {
        add_shortcode('boniedu_result_search', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts)
    {
        ob_start();
        $this->handle_search();
        return ob_get_clean();
    }

    private function handle_search()
    {
        $result_data = null;
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['boniedu_search_submit'])) {
            if (!isset($_POST['boniedu_search_nonce']) || !wp_verify_nonce($_POST['boniedu_search_nonce'], 'boniedu_search_action')) {
                $error = 'Invalid request.';
            } else {
                global $wpdb;
                $roll_no = isset($_POST['roll_no']) ? sanitize_text_field($_POST['roll_no']) : '';
                $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
                // $year = isset($_POST['year']) ? sanitize_text_field($_POST['year']) : ''; 

                if (empty($roll_no) || empty($class_id)) {
                    $error = 'Please enter Roll Number and Select Class.';
                } else {
                    // Fetch Student
                    $student = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}boniedu_students WHERE roll_no = %s AND class_id = %d",
                        $roll_no,
                        $class_id
                    ));

                    if ($student) {
                        // Fetch Results
                        $results = $wpdb->get_results($wpdb->prepare(
                            "SELECT r.*, s.name as subject_name, e.name as exam_name 
                             FROM {$wpdb->prefix}boniedu_results r
                             LEFT JOIN {$wpdb->prefix}boniedu_subjects s ON r.subject_id = s.id
                             LEFT JOIN {$wpdb->prefix}boniedu_exams e ON r.exam_id = e.id
                             WHERE r.student_id = %d",
                            $student->id
                        ));

                        if ($results) {
                            $result_data = [
                                'student' => $student,
                                'results' => $results
                            ];
                        } else {
                            $error = 'No results found for this student.';
                        }
                    } else {
                        $error = 'Student not found.';
                    }
                }
            }
        }

        $this->render_form($result_data, $error);
    }

    private function render_form($data, $error)
    {
        global $wpdb;
        $classes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}boniedu_classes ORDER BY numeric_value ASC");
        ?>
        <div class="boniedu-search-wrapper"
            style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <h3 style="text-align: center;">Check Your Result</h3>

            <?php if ($error): ?>
                <div style="background: #f8dfdf; color: #a00; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
                    <?php echo esc_html($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field('boniedu_search_action', 'boniedu_search_nonce'); ?>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Class</label>
                    <select name="class_id" required style="width: 100%; padding: 8px;">
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class->id; ?>" <?php selected(isset($_POST['class_id']) ? $_POST['class_id'] : '', $class->id); ?>>
                                <?php echo esc_html($class->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Roll Number</label>
                    <input type="text" name="roll_no"
                        value="<?php echo isset($_POST['roll_no']) ? esc_attr($_POST['roll_no']) : ''; ?>" required
                        style="width: 100%; padding: 8px;" placeholder="Enter Roll Number">
                </div>

                <div style="text-align: center;">
                    <button type="submit" name="boniedu_search_submit"
                        style="background: #0073aa; color: #fff; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; font-size: 16px;">Search
                        Result</button>
                </div>
            </form>
        </div>

        <?php if ($data): ?>
            <?php $student = $data['student']; ?>
            <div class="boniedu-result-card" style="max-width: 600px; margin: 30px auto; border: 2px solid #0073aa; padding: 0;">
                <div style="background: #0073aa; color: #fff; padding: 15px; text-align: center;">
                    <h2 style="margin: 0; color: #fff;">Result Sheet</h2>
                </div>
                <div style="padding: 20px;">
                    <div
                        style="display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                        <div>
                            <strong>Name:</strong> <?php echo esc_html($student->name); ?><br>
                            <strong>Roll No:</strong> <?php echo esc_html($student->roll_no); ?>
                        </div>
                        <div style="text-align: right;">
                            <!-- Class Name fetch would be ideal here if not already known from input -->
                            <strong>Session:</strong> <?php echo esc_html($student->session_year); ?>
                        </div>
                    </div>

                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                        <thead>
                            <tr style="background: #f9f9f9; border-bottom: 2px solid #eee;">
                                <th style="padding: 10px; text-align: left;">Subject</th>
                                <th style="padding: 10px; text-align: center;">Total</th>
                                <th style="padding: 10px; text-align: center;">Obtained</th>
                                <th style="padding: 10px; text-align: center;">Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $grand_total = 0;
                            $obtained_total = 0;
                            foreach ($data['results'] as $res):
                                $grand_total += $res->total_marks;
                                $obtained_total += $res->marks_obtained;
                                ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px;">
                                        <?php echo esc_html($res->subject_name ? $res->subject_name : 'Subject ' . $res->subject_id); ?>
                                    </td>
                                    <td style="padding: 10px; text-align: center;"><?php echo esc_html($res->total_marks); ?></td>
                                    <td style="padding: 10px; text-align: center;"><?php echo esc_html($res->marks_obtained); ?></td>
                                    <td style="padding: 10px; text-align: center; font-weight: bold;">
                                        <?php echo esc_html($res->grade); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background: #f0f0f0; font-weight: bold;">
                                <td style="padding: 10px;">Total</td>
                                <td style="padding: 10px; text-align: center;"><?php echo $grand_total; ?></td>
                                <td style="padding: 10px; text-align: center;"><?php echo $obtained_total; ?></td>
                                <td style="padding: 10px;"></td>
                            </tr>
                        </tbody>
                    </table>

                    <div style="text-align: center; margin-top: 20px;">
                        <a href="<?php echo admin_url('admin-post.php?action=boniedu_download_marksheet&student_id=' . $student->id); ?>"
                            target="_blank"
                            style="display: inline-block; background: #28a745; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Download
                            Marksheet (PDF)</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php
    }
}
