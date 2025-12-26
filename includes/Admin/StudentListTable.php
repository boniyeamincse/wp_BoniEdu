<?php

namespace BoniEdu\Admin;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class StudentListTable extends \WP_List_Table
{

    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'student',
            'plural' => 'students',
            'ajax' => false
        ));
    }

    public function get_columns()
    {
        return array(
            'cb' => '<input type="checkbox" />',
            'photo' => 'Photo',
            'name' => 'Name',
            'roll_no' => 'Roll No',
            'class_id' => 'Class',
            'section_id' => 'Section',
            'session' => 'Session',
            'actions' => 'Actions'
        );
    }

    public function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'boniedu_students';
        $table_classes = $wpdb->prefix . 'boniedu_classes';
        $table_sections = $wpdb->prefix . 'boniedu_sections';

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array(
            'roll_no' => array('roll_no', false),
            'name' => array('name', false)
        );

        $this->_column_headers = array($columns, $hidden, $sortable);

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $orderby = (isset($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'class_id, roll_no';
        $order = (isset($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'ASC';

        // Filters
        $where = "1=1";
        if (isset($_REQUEST['s']) && !empty($_REQUEST['s'])) {
            $search = sanitize_text_field($_REQUEST['s']);
            $where .= " AND (name LIKE '%$search%' OR roll_no LIKE '%$search%' OR registration_no LIKE '%$search%')";
        }
        if (isset($_REQUEST['filter_class']) && !empty($_REQUEST['filter_class'])) {
            $where .= " AND class_id = " . intval($_REQUEST['filter_class']);
        }

        // Query
        $sql = "SELECT s.*, c.name as class_name, sec.name as section_name 
				FROM $table_name s 
				LEFT JOIN $table_classes c ON s.class_id = c.id
				LEFT JOIN $table_sections sec ON s.section_id = sec.id
				WHERE $where 
				ORDER BY $orderby $order 
				LIMIT $per_page OFFSET $offset";

        $this->items = $wpdb->get_results($sql, ARRAY_A);

        // Pagination
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $where");
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'roll_no':
            case 'name':
            case 'session_year': // mapped manually below
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />',
            $item['id']
        );
    }

    public function column_photo($item)
    {
        // Placeholder for Module 11
        return '<div style="width: 40px; height: 40px; background: #ddd; border-radius: 50%;"></div>';
    }

    public function column_name($item)
    {
        $delete_nonce = wp_create_nonce('boniedu_delete_student');
        $title = '<strong>' . $item['name'] . '</strong>';

        $actions = array(
            'edit' => sprintf('<a href="?page=%s&action=%s&id=%s">Edit</a>', $_REQUEST['page'], 'edit', $item['id']),
            'delete' => sprintf('<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['id'], $delete_nonce),
        );

        return $title . $this->row_actions($actions);
    }

    public function column_class_id($item)
    {
        return $item['class_name'];
    }

    public function column_section_id($item)
    {
        return $item['section_name'];
    }

    public function column_session($item)
    {
        return $item['session_year'];
    }

    public function column_actions($item)
    {
        return sprintf('<a href="?page=%s&action=%s&id=%s" class="button button-small">Result</a>', 'boniedu-results', 'add', $item['id']);
    }

}
