<?php

namespace BoniEdu\Admin;

/**
 * Manage Academic Years/Sessions.
 */
class AcademicYears
{

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function add_submenu()
    {
        add_submenu_page(
            $this->plugin_name,
            'Academic Years',
            'Academic Years',
            'manage_options',
            'boniedu-academic-years',
            array($this, 'display_page')
        );
    }

    public function process_form_data()
    {
        if (isset($_POST['boniedu_add_session_nonce']) && wp_verify_nonce($_POST['boniedu_add_session_nonce'], 'boniedu_add_session')) {
            if (isset($_POST['session_name']) && !empty($_POST['session_name'])) {
                $new_session = sanitize_text_field($_POST['session_name']);
                $sessions = get_option('boniedu_sessions', array());

                if (!in_array($new_session, $sessions)) {
                    $sessions[] = $new_session;
                    // Sort descending
                    rsort($sessions);
                    update_option('boniedu_sessions', $sessions);
                    add_settings_error('boniedu_sessions', 'session_added', 'Academic Session added successfully.', 'success');
                } else {
                    add_settings_error('boniedu_sessions', 'session_exists', 'Session already exists.', 'error');
                }
            }
        }

        if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['session'])) {
            // Simple delete (GET request for simplicity in Admin)
            // In production, should use nonce verification for delete links too.
            $session_to_delete = sanitize_text_field($_GET['session']);
            $sessions = get_option('boniedu_sessions', array());

            if (($key = array_search($session_to_delete, $sessions)) !== false) {
                unset($sessions[$key]);
                update_option('boniedu_sessions', array_values($sessions));
                add_settings_error('boniedu_sessions', 'session_deleted', 'Academic Session deleted.', 'success');
            }
        }
    }

    public function display_page()
    {
        $this->process_form_data();
        $sessions = get_option('boniedu_sessions', array());
        ?>
        <div class="wrap">
            <h1>Academic Years / Sessions</h1>
            <?php settings_errors('boniedu_sessions'); ?>

            <div style="display: flex; gap: 20px; align-items: start;">
                <!-- Add New Form -->
                <div class="card" style="max-width: 300px; padding: 20px;">
                    <h2>Add New Session</h2>
                    <form method="post" action="">
                        <?php wp_nonce_field('boniedu_add_session', 'boniedu_add_session_nonce'); ?>
                        <p>
                            <label for="session_name">Session Name (e.g. 2025)</label>
                            <input type="text" name="session_name" id="session_name" class="widefat" required>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary">Add Session</button>
                        </p>
                    </form>
                </div>

                <!-- List Table -->
                <div class="card" style="flex-grow: 1; padding: 0;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Session Name</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($sessions)): ?>
                                <?php foreach ($sessions as $session): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($session); ?></strong></td>
                                        <td>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=boniedu-academic-years&action=delete&session=' . $session)); ?>"
                                                class="button button-small button-link-delete"
                                                onclick="return confirm('Are you sure?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2">No academic sessions found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

}
