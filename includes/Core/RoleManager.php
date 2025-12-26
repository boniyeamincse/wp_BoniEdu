<?php

namespace BoniEdu\Core;

/**
 * Manage User Roles and Capabilities.
 */
class RoleManager
{

    /**
     * Add custom roles.
     */
    public static function add_roles()
    {
        // Teacher Role
        add_role(
            'boniedu_teacher',
            'BoniEdu Teacher',
            array(
                'read' => true,
                'boniedu_manage_results' => true,
                'boniedu_manage_students' => true,
                'upload_files' => true,
            )
        );

        // Component/Parent Role (View Only)
        add_role(
            'boniedu_parent',
            'BoniEdu Parent',
            array(
                'read' => true,
                'boniedu_view_results' => true,
            )
        );

        // Add caps to Administrator
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('boniedu_manage_results');
            $role->add_cap('boniedu_manage_students');
            $role->add_cap('boniedu_view_results');
            $role->add_cap('boniedu_manage_settings');
        }
    }

    /**
     * Remove custom roles.
     */
    public static function remove_roles()
    {
        remove_role('boniedu_teacher');
        remove_role('boniedu_parent');

        // Remove caps from Administrator
        $role = get_role('administrator');
        if ($role) {
            $role->remove_cap('boniedu_manage_results');
            $role->remove_cap('boniedu_manage_students');
            $role->remove_cap('boniedu_view_results');
            $role->remove_cap('boniedu_manage_settings');
        }
    }

}
