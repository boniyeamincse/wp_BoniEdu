<?php

namespace BoniEdu\Core;

/**
 * Result Calculation Engine.
 * Follows Bangladesh Education System Grading.
 */
class Calculator
{

    /**
     * Get Grade Letter from Marks.
     * 
     * @param float $marks
     * @return string
     */
    public static function get_grade_letter($marks)
    {
        if ($marks >= 80)
            return 'A+';
        if ($marks >= 70)
            return 'A';
        if ($marks >= 60)
            return 'A-';
        if ($marks >= 50)
            return 'B';
        if ($marks >= 40)
            return 'C';
        if ($marks >= 33)
            return 'D';
        return 'F';
    }

    /**
     * Get Grade Point (GPA) from Marks.
     * 
     * @param float $marks
     * @return float
     */
    public static function get_grade_point($marks)
    {
        if ($marks >= 80)
            return 5.00;
        if ($marks >= 70)
            return 4.00;
        if ($marks >= 60)
            return 3.50;
        if ($marks >= 50)
            return 3.00;
        if ($marks >= 40)
            return 2.00;
        if ($marks >= 33)
            return 1.00;
        return 0.00;
    }

    /**
     * Check if Pass or Fail.
     * 
     * @param float $marks
     * @return bool
     */
    public static function is_passed($marks)
    {
        return $marks >= 33;
    }

}
