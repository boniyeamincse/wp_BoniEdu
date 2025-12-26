<?php

namespace BoniEdu\Core;

/**
 * Fired during plugin activation.
 * This class handles the creation and update of custom database tables.
 */
class Migrator
{

	/**
	 * Run the migrations.
	 */
	public static function migrate()
	{
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Table names
		$table_classes = $wpdb->prefix . 'boniedu_classes';
		$table_sections = $wpdb->prefix . 'boniedu_sections';
		$table_subjects = $wpdb->prefix . 'boniedu_subjects';
		$table_students = $wpdb->prefix . 'boniedu_students';
		$table_results = $wpdb->prefix . 'boniedu_results';

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		// 1. Classes Table
		$sql_classes = "CREATE TABLE $table_classes (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			numeric_value tinyint(3) NOT NULL,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// 2. Sections Table
		$sql_sections = "CREATE TABLE $table_sections (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			class_id mediumint(9) NOT NULL,
			name varchar(100) NOT NULL,
			capacity int(5) DEFAULT 0,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id),
			KEY class_id (class_id)
		) $charset_collate;";

		// 3. Subjects Table
		$sql_subjects = "CREATE TABLE $table_subjects (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			class_id mediumint(9) NOT NULL,
			name varchar(100) NOT NULL,
			code varchar(20) DEFAULT '',
			total_marks int(5) DEFAULT 100,
			pass_marks int(5) DEFAULT 33,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id),
			KEY class_id (class_id)
		) $charset_collate;";

		// 4. Students Table
		$sql_students = "CREATE TABLE $table_students (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			registration_no varchar(50) DEFAULT '',
			roll_no int(5) NOT NULL,
			name varchar(255) NOT NULL,
			father_name varchar(255) DEFAULT '',
			mother_name varchar(255) DEFAULT '',
			dob date DEFAULT '0000-00-00',
			gender varchar(20) DEFAULT '',
			religion varchar(50) DEFAULT '',
			address text DEFAULT '',
			photo_id bigint(20) DEFAULT 0,
			class_id mediumint(9) NOT NULL,
			section_id mediumint(9) DEFAULT 0,
			session_year varchar(20) DEFAULT '',
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			status varchar(20) DEFAULT 'active',
			PRIMARY KEY  (id),
			KEY class_section (class_id, section_id),
			KEY roll_no (roll_no)
		) $charset_collate;";

		// 5. Results Table (Marks)
		$sql_results = "CREATE TABLE $table_results (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			student_id mediumint(9) NOT NULL,
			subject_id mediumint(9) NOT NULL,
			exam_type varchar(50) NOT NULL,
			marks_obtained decimal(5,2) DEFAULT 0.00,
			grade_point decimal(3,2) DEFAULT 0.00,
			grade_letter varchar(5) DEFAULT '',
			is_absent tinyint(1) DEFAULT 0,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id),
			KEY student_id (student_id),
			KEY subject_id (subject_id)
		) $charset_collate;";

		dbDelta($sql_classes);
		dbDelta($sql_sections);
		dbDelta($sql_subjects);
		dbDelta($sql_students);
		dbDelta($sql_results);
	}
}
