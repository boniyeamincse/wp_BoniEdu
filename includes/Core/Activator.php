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
        RoleManager::add_roles();
    }

}
