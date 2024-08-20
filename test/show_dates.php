<?php

/**
 * Example URL : /local/obu_application/test/show_dates.php
 */
require('../../../config.php');

global $CFG;

require_once("../locallib.php");

foreach (range(1, 18) as $i) {
    $year = date("Y");
    $calcMonth = $i - 1;
    $start = strtotime("01/01/$year + $calcMonth months");
    $display = date("M,Y", $start);
    echo "Start: $display";
    $dates = local_obu_application_get_course_dates($start);
    var_dump($dates);
}