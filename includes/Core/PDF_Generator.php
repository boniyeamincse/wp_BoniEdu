<?php

namespace BoniEdu\Core;

class PDF_Generator
{
    private $tcpdf_path;

    public function __construct()
    {
        // Define path to TCPDF. Adjust as necessary if using Composer or manual install.
        if (defined('BONIEDU_PLUGIN_DIR')) {
            $this->tcpdf_path = BONIEDU_PLUGIN_DIR . 'includes/lib/tcpdf/tcpdf.php';
        }
    }

    private function load_tcpdf()
    {
        if (file_exists($this->tcpdf_path)) {
            require_once $this->tcpdf_path;
            return true;
        }
        // Fallback or check for standard include
        if (class_exists('TCPDF')) {
            return true;
        }
        return false;
    }

    public function generate_certificate($student_id)
    {
        if (!$this->load_tcpdf()) {
            wp_die('TCPDF library not found. Please install it in includes/lib/tcpdf/.');
        }

        global $wpdb;
        $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}boniedu_students WHERE id = %d", $student_id));

        if (!$student) {
            wp_die('Student not found.');
        }

        // Get Settings
        $settings = get_option('boniedu_certificate_settings');
        $bg_image = isset($settings['bg_image']) ? $settings['bg_image'] : '';
        $heading = isset($settings['heading']) ? $settings['heading'] : 'Certificate of Achievement';
        $body = isset($settings['body']) ? $settings['body'] : 'This is to certify that {student_name} has successfully completed the course.';
        $sig_left = isset($settings['signature_left']) ? $settings['signature_left'] : '';
        $sig_left_text = isset($settings['signature_left_text']) ? $settings['signature_left_text'] : '';
        $sig_right = isset($settings['signature_right']) ? $settings['signature_right'] : '';
        $sig_right_text = isset($settings['signature_right_text']) ? $settings['signature_right_text'] : '';

        // Replace Placeholders
        $placeholders = [
            '{student_name}' => $student->first_name . ' ' . $student->last_name,
            '{class}' => $student->class_id, // Should map to name ideally
            '{roll}' => $student->roll_number,
            '{year}' => date('Y'),
            '{gpa}' => 'N/A' // Calculate if available
        ];

        $body_text = str_replace(array_keys($placeholders), array_values($placeholders), $body);

        // Initialize TCPDF
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('BoniEdu');
        $pdf->SetAuthor('School Admin');
        $pdf->SetTitle('Certificate - ' . $student->first_name);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->AddPage();

        // Background
        if ($bg_image) {
            // Get local path from URL if possible, otherwise TCPDF handles URL if allow_url_fopen is on
            // For better performance/security, convert URL to path if local
            $upload_dir = wp_upload_dir();
            $bg_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $bg_image);

            // Allow full page bg
            $pdf->Image($bg_path, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0);
        }

        // Content
        $pdf->SetTextColor(0, 0, 0);

        // Heading
        $pdf->SetFont('helvetica', 'B', 30);
        $pdf->SetXY(0, 50);
        $pdf->Cell(297, 20, $heading, 0, 1, 'C');

        // Body
        $pdf->SetFont('helvetica', '', 16);
        $pdf->SetXY(40, 90);
        $pdf->MultiCell(217, 40, $body_text, 0, 'C');

        // Signatures
        $pdf->SetFont('helvetica', 'B', 12);

        if ($sig_left) {
            $sig_left_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $sig_left);
            $pdf->Image($sig_left_path, 40, 150, 40, 0);
        }
        $pdf->SetXY(40, 170);
        $pdf->Cell(40, 10, $sig_left_text, 'T', 0, 'C');

        if ($sig_right) {
            $sig_right_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $sig_right);
            $pdf->Image($sig_right_path, 217, 150, 40, 0);
        }
        $pdf->SetXY(217, 170);
        $pdf->Cell(40, 10, $sig_right_text, 'T', 0, 'C');

        $pdf->Output('certificate_' . $student_id . '.pdf', 'D');
        exit;
    }

    public function generate_marksheet($student_id)
    {
        if (!$this->load_tcpdf()) {
            wp_die('TCPDF library not found.');
        }

        // Basic Marksheet Implementation
        global $wpdb;
        $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}boniedu_students WHERE id = %d", $student_id));
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}boniedu_results WHERE student_id = %d", $student_id));

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('BoniEdu');
        $pdf->SetTitle('Mark Sheet - ' . $student->first_name);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 10, 'Mark Sheet', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Name: ' . $student->first_name . ' ' . $student->last_name, 0, 1);
        $pdf->Cell(0, 10, 'Roll: ' . $student->roll_number, 0, 1);
        $pdf->Ln(10);

        // Table Header
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(80, 10, 'Subject', 1);
        $pdf->Cell(40, 10, 'Marks', 1);
        $pdf->Cell(40, 10, 'Total', 1);
        $pdf->Cell(20, 10, 'Grade', 1);
        $pdf->Ln();

        // Rows
        $pdf->SetFont('helvetica', '', 12);
        foreach ($results as $result) {
            // Fetch subject name (mocking or query)
            // $subject_name = ...
            $subject_name = 'Subject ' . $result->subject_id;

            $pdf->Cell(80, 10, $subject_name, 1);
            $pdf->Cell(40, 10, $result->marks_obtained, 1);
            $pdf->Cell(40, 10, $result->total_marks, 1);
            $pdf->Cell(20, 10, $result->grade, 1);
            $pdf->Ln();
        }

        $pdf->Output('marksheet_' . $student_id . '.pdf', 'D');
        exit;
    }

    public function generate_admit_card($student_id)
    {
        if (!$this->load_tcpdf()) {
            wp_die('TCPDF library not found.');
        }

        global $wpdb;
        $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}boniedu_students WHERE id = %d", $student_id));

        if (!$student) {
            wp_die('Student not found.');
        }

        // Get Settings
        $settings = get_option('boniedu_admit_card_settings');
        $bg_image = isset($settings['bg_image']) ? $settings['bg_image'] : '';
        $heading = isset($settings['heading']) ? $settings['heading'] : 'ADMIT CARD';
        $details = isset($settings['exam_details']) ? $settings['exam_details'] : 'Exam: Test Exam\nTime: 10:00 AM';
        $body = isset($settings['body']) ? $settings['body'] : 'Instructions...';

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('BoniEdu');
        $pdf->SetTitle('Admit Card - ' . $student->first_name);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();

        // Background
        if ($bg_image) {
            $upload_dir = wp_upload_dir();
            $bg_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $bg_image);
            // A4 Page is approx 210x297. Let's make admit card half page sized or just center it.
            // We'll do a simple full page or half page design. Let's do a box.
            //$pdf->Image($bg_path, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0); 
        }

        // --- Design: Box with Border ---
        $pdf->SetRect(20, 20, 170, 120); // x, y, w, h

        // Header
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetXY(20, 25);
        $pdf->Cell(170, 10, $heading, 0, 1, 'C');

        // Details
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(20, 40);
        $pdf->MultiCell(170, 15, $details, 0, 'C');

        // Student Info Box
        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetXY(30, 60);
        $html = "
        <table cellspacing=\"5\">
            <tr>
                <td width=\"80\"><strong>Name:</strong></td>
                <td>{$student->first_name} {$student->last_name}</td>
            </tr>
             <tr>
                <td><strong>Roll No:</strong></td>
                <td>{$student->roll_number}</td>
            </tr>
             <tr>
                <td><strong>Class:</strong></td>
                <td>{$student->class_id}</td>
            </tr>
        </table>";
        $pdf->writeHTML($html, true, false, true, false, '');

        // Photo (if exists)
        if ($student->photo_id) {
            $img_url = wp_get_attachment_image_url($student->photo_id, 'medium');
            if ($img_url) {
                $upload_dir = wp_upload_dir();
                $img_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $img_url);
                $pdf->Image($img_path, 150, 60, 30, 30, '', '', '', true, 300, '', false, false, 1, false, false, false);
            }
        }

        // Instructions
        $pdf->SetXY(30, 100);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, 'Instructions:', 0, 1);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(150, 20, $body, 0, 'L');

        $pdf->Output('admit_card_' . $student_id . '.pdf', 'D');
        exit;
    }
}
