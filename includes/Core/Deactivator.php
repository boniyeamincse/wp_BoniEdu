<?php

namespace BoniEdu\Core;

/**
 * Fired during plugin deactivation.
 */
class Deactivator
{

    /**
     * Run logic on deactivation.
     */
    public static function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

}
