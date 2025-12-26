<?php

namespace BoniEdu\Core;

/**
 * Fired during plugin activation.
 */
class Activator
{

    /**
     * Run logic on activation.
     */
    public static function activate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Run Database Migrations
        Migrator::migrate();

        // Add Custom Roles
        require_once BONIEDU_PLUGIN_DIR . 'includes/Core/Roles.php';
        Roles::add_roles();
    }

}
