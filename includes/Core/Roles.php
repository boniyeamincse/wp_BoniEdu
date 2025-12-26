<?php

namespace BoniEdu\Core;

class Roles
{
    public static function add_roles()
    {
        // Add Teacher Role
        add_role(
            'boniedu_teacher',
            'BoniEdu Teacher',
            array(
                'read' => true,
                'manage_boniedu_academic' => true, // View/Edit Students, Results, Classes
                'upload_files' => true, // For photos
            )
        );

        // Add Student Role (Futureproofing)
        add_role(
            'boniedu_student',
            'BoniEdu Student',
            array(
                'read' => true,
                'read_boniedu_results' => true,
            )
        );

        // Add Capabilities to Admin
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_boniedu_academic');
            $role->add_cap('read_boniedu_results');
        }
    }

    public static function remove_roles()
    {
        remove_role('boniedu_teacher');
        remove_role('boniedu_student');
    }
}
