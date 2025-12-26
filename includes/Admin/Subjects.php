<?php

namespace BoniEdu\Admin;

/**
 * Manage Subjects and Groups.
 */
class Subjects
{

    private $plugin_name;
    private $version;
    private $table_subjects;
    private $table_classes;

    public function __construct($plugin_name, $version)
    {
        global $wpdb;
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->table_subjects = $wpdb->prefix . 'boniedu_subjects';
        $this->table_classes = $wpdb->prefix . 'boniedu_classes';
    }

    public function add_submenu()
    {
        add_submenu_page(
            $this->plugin_name,
            'Subjects',
            'Subjects',
            'manage_options',
            'boniedu-subjects',
            array($this, 'display_page')
        );
    }

    public function process_form_data()
    {
        global $wpdb;

        // Add Subject
        if (isset($_POST['boniedu_add_subject_nonce']) && wp_verify_nonce($_POST['boniedu_add_subject_nonce'], 'boniedu_add_subject')) {
            $class_id = intval($_POST['subject_class_id']);
            $name = sanitize_text_field($_POST['subject_name']);
            $code = sanitize_text_field($_POST['subject_code']);
            $total = intval($_POST['subject_total']);
            $pass = intval($_POST['subject_pass']);

            $wpdb->insert(
                $this->table_subjects,
                array(
                    'class_id' => $class_id,
                    'name' => $name,
                    'code' => $code,
                    'total_marks' => $total,
                    'pass_marks' => $pass,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%d', '%d', '%s')
            );
            add_settings_error('boniedu_subjects', 'subject_added', 'Subject added successfully.', 'success');
        }

        // Add Group
        if (isset($_POST['boniedu_add_group_nonce']) && wp_verify_nonce($_POST['boniedu_add_group_nonce'], 'boniedu_add_group')) {
            $new_group = sanitize_text_field($_POST['group_name']);
            $groups = get_option('boniedu_groups', array());

            if (!in_array($new_group, $groups) && !empty($new_group)) {
                $groups[] = $new_group;
                update_option('boniedu_groups', $groups);
                add_settings_error('boniedu_groups', 'group_added', 'Group added successfully.', 'success');
            }
        }

        // Delete Subject
        if (isset($_GET['action']) && $_GET['action'] == 'delete_subject' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $wpdb->delete($this->table_subjects, array('id' => $id), array('%d'));
            add_settings_error('boniedu_subjects', 'subject_deleted', 'Subject deleted.', 'success');
        }

        // Delete Group
        if (isset($_GET['action']) && $_GET['action'] == 'delete_group' && isset($_GET['group'])) {
            $group = sanitize_text_field($_GET['group']);
            $groups = get_option('boniedu_groups', array());
            if (($key = array_search($group, $groups)) !== false) {
                unset($groups[$key]);
                update_option('boniedu_groups', array_values($groups));
                add_settings_error('boniedu_groups', 'group_deleted', 'Group deleted.', 'success');
            }
        }
    }

    public function display_page()
    {
        global $wpdb;
        $this->process_form_data();

        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'subjects';
        $classes = $wpdb->get_results("SELECT * FROM $this->table_classes ORDER BY numeric_value ASC");
        ?>
        <div class="wrap">
            <h1>Subject & Group Management</h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=boniedu-subjects&tab=subjects"
                    class="nav-tab <?php echo $active_tab == 'subjects' ? 'nav-tab-active' : ''; ?>">Subjects</a>
                <a href="?page=boniedu-subjects&tab=groups"
                    class="nav-tab <?php echo $active_tab == 'groups' ? 'nav-tab-active' : ''; ?>">Groups</a>
            </h2>

            <?php
            if ($active_tab == 'subjects') {
                $this->render_subjects_tab($classes);
            } else {
                $this->render_groups_tab();
            }
            ?>
        </div>
        <?php
    }

    private function render_subjects_tab($classes)
    {
        global $wpdb;
        settings_errors('boniedu_subjects');
        $subjects = $wpdb->get_results("SELECT s.*, c.name as class_name FROM $this->table_subjects s JOIN $this->table_classes c ON s.class_id = c.id ORDER BY c.numeric_value ASC, s.name ASC");
        ?>
        <div class="card" style="margin-top: 20px; padding: 20px;">
            <h2>Add New Subject</h2>
            <form method="post" action="">
                <?php wp_nonce_field('boniedu_add_subject', 'boniedu_add_subject_nonce'); ?>

                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div style="flex: 1;">
                        <label>Class</label><br>
                        <select name="subject_class_id" class="widefat" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class->id; ?>"><?php echo esc_html($class->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label>Subject Name</label><br>
                        <input type="text" name="subject_name" class="widefat" required placeholder="e.g. Mathematics">
                    </div>
                    <div style="flex: 1;">
                        <label>Subject Code</label><br>
                        <input type="text" name="subject_code" class="widefat" placeholder="e.g. 101">
                    </div>
                </div>
                <div style="display: flex; gap: 20px; margin-top: 10px; flex-wrap: wrap;">
                    <div style="flex: 1;">
                        <label>Total Marks</label><br>
                        <input type="number" name="subject_total" class="widefat" value="100" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Pass Marks</label><br>
                        <input type="number" name="subject_pass" class="widefat" value="33" required>
                    </div>
                    <div style="flex: 1; display: flex; align-items: end;">
                        <button type="submit" class="button button-primary" style="width: 100%;">Add Subject</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card" style="margin-top: 20px; padding: 0;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Subject Name</th>
                        <th>Code</th>
                        <th>Total Marks</th>
                        <th>Pass Marks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($subjects)): ?>
                        <?php foreach ($subjects as $subject): ?>
                            <tr>
                                <td><?php echo esc_html($subject->class_name); ?></td>
                                <td><strong><?php echo esc_html($subject->name); ?></strong></td>
                                <td><?php echo esc_html($subject->code); ?></td>
                                <td><?php echo intval($subject->total_marks); ?></td>
                                <td><?php echo intval($subject->pass_marks); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=boniedu-subjects&action=delete_subject&id=' . $subject->id)); ?>"
                                        onclick="return confirm('Are you sure?')" style="color:red;">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No subjects found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function render_groups_tab()
    {
        settings_errors('boniedu_groups');
        $groups = get_option('boniedu_groups', array());
        ?>
        <div style="display: flex; gap: 20px; margin-top: 20px;">
            <div class="card" style="flex: 1; padding: 20px; height: fit-content;">
                <h2>Add New Group</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('boniedu_add_group', 'boniedu_add_group_nonce'); ?>
                    <p>
                        <label>Group Name (e.g. Science, Arts)</label><br>
                        <input type="text" name="group_name" class="widefat" required>
                    </p>
                    <button type="submit" class="button button-primary">Add Group</button>
                </form>
            </div>

            <div class="card" style="flex: 2; padding: 0;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Group Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($groups)): ?>
                            <?php foreach ($groups as $group): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($group); ?></strong></td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=boniedu-subjects&tab=groups&action=delete_group&group=' . urlencode($group))); ?>"
                                            onclick="return confirm('Are you sure?')" style="color:red;">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">No groups found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

}
