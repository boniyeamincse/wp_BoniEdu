<?php

namespace BoniEdu\Admin;

trait CSV_Handler
{
    /**
     * Parse a CSV file into an associative array.
     *
     * @param string $file_path Path to the CSV file.
     * @return array|false Array of rows or false on failure.
     */
    public function read_csv($file_path)
    {
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return false;
        }

        $data = [];
        if (($handle = fopen($file_path, 'r')) !== false) {
            $header = fgetcsv($handle);
            if (!$header) {
                fclose($handle);
                return false;
            }

            // Remove BOM from the first key if present
            $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);

            while (($row = fgetcsv($handle)) !== false) {
                if (count($header) === count($row)) {
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * Send headers to force a CSV download.
     *
     * @param string $filename The name of the downloaded file.
     */
    public function send_csv_headers($filename)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    /**
     * Output data as CSV to the output buffer.
     *
     * @param array $data Array of associative arrays.
     */
    public function output_csv($data)
    {
        $output = fopen('php://output', 'w');

        if (!empty($data)) {
            // Output header
            fputcsv($output, array_keys(reset($data)));

            // Output rows
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }

        fclose($output);
        exit;
    }
}
