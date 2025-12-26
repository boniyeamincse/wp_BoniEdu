<?php

namespace BoniEdu\Admin;

/**
 * Dashboard Widgets for BoniEdu.
 */
class DashboardData
{
    private $plugin_name;
    private $version;
    private $table_students;
    private $table_classes;
    private $table_results;

    public function __construct($plugin_name, $version)
    {
        global $wpdb;
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->table_students = $wpdb->prefix . 'boniedu_students';
        $this->table_classes = $wpdb->prefix . 'boniedu_classes';
        $this->table_results = $wpdb->prefix . 'boniedu_results';
    }

    public function register_widgets()
    {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
    }

    public function add_dashboard_widgets()
    {
        wp_add_dashboard_widget(
            'boniedu_at_a_glance',
            'BoniEdu: At a Glance',
            array($this, 'render_at_a_glance')
        );

        wp_add_dashboard_widget(
            'boniedu_recent_activity',
            'BoniEdu: Recent Activity',
            array($this, 'render_recent_activity')
        );
    }

    public function render_at_a_glance()
    {
        global $wpdb;
        $student_count = $wpdb->get_var("SELECT COUNT(*) FROM $this->table_students");
        $class_count = $wpdb->get_var("SELECT COUNT(*) FROM $this->table_classes");
        $result_count = $wpdb->get_var("SELECT COUNT(*) FROM $this->table_results");

        echo '<div class="main">';
        echo '<ul>';
        echo '<li class="post-count"><a href="' . admin_url('admin.php?page=boniedu-students') . '">' . $student_count . ' Students</a></li>';
        echo '<li class="page-count"><a href="' . admin_url('admin.php?page=boniedu-classes') . '">' . $class_count . ' Classes</a></li>';
        echo '<li class="comment-count"><a href="' . admin_url('admin.php?page=boniedu-results') . '">' . $result_count . ' Results</a></li>';
        echo '</ul>';
        echo '</div>';
    }

    public function render_recent_activity()
    {
        global $wpdb;
        $recent_students = $wpdb->get_results("SELECT * FROM $this->table_students ORDER BY id DESC LIMIT 5");

        if ($recent_students) {
            echo '<div id="published-posts" class="activity-block">';
            echo '<h3>Recent Students</h3>';
            echo '<ul>';
            foreach ($recent_students as $student) {
                // If we had a created_at field it would be better, using id for now as loose proxy for recent or just list them
                $time_string = '';
                if (isset($student->created_at)) {
                    $time = strtotime($student->created_at);
                    $time_string = '<span style="color:#777;"> ' . human_time_diff($time, current_time('timestamp')) . ' ago</span>';
                }

                echo '<li>';
                echo '<span>' . esc_html($student->name) . ' (' . esc_html($student->roll_no) . ')</span>';
                echo $time_string;
                echo '</li>';
            }
            echo '</ul>';
            echo '</div>';
        } else {
            echo '<p>No recent activity.</p>';
        }
    }
}
