<?php

namespace BoniEdu\Admin;

/**
 * Manage Classes and Sections.
 */
class ClassesSections
{

    private $plugin_name;
    private $version;
    private $table_classes;
    private $table_sections;

    public function __construct($plugin_name, $version)
    {
        global $wpdb;
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->table_classes = $wpdb->prefix . 'boniedu_classes';
        $this->table_sections = $wpdb->prefix . 'boniedu_sections';
    }

    public function add_submenu()
    {
        add_submenu_page(
            $this->plugin_name,
            'Classes & Sections',
            'Classes & Sections',
            'manage_options',
            'boniedu-classes-sections',
            array($this, 'display_page')
        );
    }

    public function process_form_data()
    {
        global $wpdb;

        // Add Class
        if (isset($_POST['boniedu_add_class_nonce']) && wp_verify_nonce($_POST['boniedu_add_class_nonce'], 'boniedu_add_class')) {
            $name = sanitize_text_field($_POST['class_name']);
            $numeric = intval($_POST['class_numeric']);

            $wpdb->insert(
                $this->table_classes,
                array(
                    'name' => $name,
                    'numeric_value' => $numeric,
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%d', '%s')
            );
            add_settings_error('boniedu_classes', 'class_added', 'Class added successfully.', 'success');
        }

        // Add Section
        if (isset($_POST['boniedu_add_section_nonce']) && wp_verify_nonce($_POST['boniedu_add_section_nonce'], 'boniedu_add_section')) {
            $class_id = intval($_POST['section_class_id']);
            $name = sanitize_text_field($_POST['section_name']);
            $capacity = intval($_POST['section_capacity']);

            $wpdb->insert(
                $this->table_sections,
                array(
                    'class_id' => $class_id,
                    'name' => $name,
                    'capacity' => $capacity,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%d', '%s')
            );
            add_settings_error('boniedu_sections', 'section_added', 'Section added successfully.', 'success');
        }

        // Delete Actions
        if (isset($_GET['action']) && $_GET['action'] == 'delete_class' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            // Check if sections exist
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $this->table_sections WHERE class_id = %d", $id));
            if ($count > 0) {
                add_settings_error('boniedu_classes', 'delete_error', 'Cannot delete class with existing sections.', 'error');
            } else {
                $wpdb->delete($this->table_classes, array('id' => $id), array('%d'));
                add_settings_error('boniedu_classes', 'class_deleted', 'Class deleted.', 'success');
            }
        }

        if (isset($_GET['action']) && $_GET['action'] == 'delete_section' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $wpdb->delete($this->table_sections, array('id' => $id), array('%d'));
            add_settings_error('boniedu_sections', 'section_deleted', 'Section deleted.', 'success');
        }
    }

    public function display_page()
    {
        global $wpdb;
        $this->process_form_data();

        $classes = $wpdb->get_results("SELECT * FROM $this->table_classes ORDER BY numeric_value ASC");
        $sections = $wpdb->get_results("SELECT s.*, c.name as class_name FROM $this->table_sections s JOIN $this->table_classes c ON s.class_id = c.id ORDER BY c.numeric_value ASC, s.name ASC");
        ?>
        <div class="wrap">
            <h1>Classes & Sections Management</h1>
            <?php settings_errors('boniedu_classes'); ?>
            <?php settings_errors('boniedu_sections'); ?>

            <div style="display: flex; gap: 20px; flex-wrap: wrap;">

                <!-- Classes Management -->
                <div style="flex: 1; min-width: 300px;">
                    <div class="card" style="padding: 20px;">
                        <h2>Manage Classes</h2>
                        <form method="post" action="">
                            <?php wp_nonce_field('boniedu_add_class', 'boniedu_add_class_nonce'); ?>
                            <p>
                                <label>Class Name (e.g. Class One)</label><br>
                                <input type="text" name="class_name" class="widefat" required>
                            </p>
                            <p>
                                <label>Numeric Value (Ordering, e.g. 1)</label><br>
                                <input type="number" name="class_numeric" class="widefat" required>
                            </p>
                            <button type="submit" class="button button-primary">Add Class</button>
                        </form>

                        <br>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($classes)): ?>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td><?php echo esc_html($class->name); ?></td>
                                            <td><?php echo intval($class->numeric_value); ?></td>
                                            <td>
                                                <a href="<?php echo esc_url(admin_url('admin.php?page=boniedu-classes-sections&action=delete_class&id=' . $class->id)); ?>"
                                                    onclick="return confirm('Are you sure?')" style="color:red;">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3">No classes found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sections Management -->
                <div style="flex: 1; min-width: 300px;">
                    <div class="card" style="padding: 20px;">
                        <h2>Manage Sections</h2>
                        <form method="post" action="">
                            <?php wp_nonce_field('boniedu_add_section', 'boniedu_add_section_nonce'); ?>
                            <p>
                                <label>Select Class</label><br>
                                <select name="section_class_id" class="widefat" required>
                                    <option value="">-- Select Class --</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class->id; ?>"><?php echo esc_html($class->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </p>
                            <p>
                                <label>Section Name (e.g. Section A)</label><br>
                                <input type="text" name="section_name" class="widefat" required>
                            </p>
                            <p>
                                <label>Student Capacity</label><br>
                                <input type="number" name="section_capacity" class="widefat" value="50">
                            </p>
                            <button type="submit" class="button button-primary">Add Section</button>
                        </form>

                        <br>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Capacity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($sections)): ?>
                                    <?php foreach ($sections as $section): ?>
                                        <tr>
                                            <td><?php echo esc_html($section->class_name); ?></td>
                                            <td><?php echo esc_html($section->name); ?></td>
                                            <td><?php echo intval($section->capacity); ?></td>
                                            <td>
                                                <a href="<?php echo esc_url(admin_url('admin.php?page=boniedu-classes-sections&action=delete_section&id=' . $section->id)); ?>"
                                                    onclick="return confirm('Are you sure?')" style="color:red;">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">No sections found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
        <?php
    }

}
