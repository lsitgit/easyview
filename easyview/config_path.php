<?php

/**
* Easyview primary file to load
*
* @package easyview report
* @copyright 2014 UC Regents
* @license http://www.gnu.org/copyleft/gpl.html GNU GPL v2
*/

require '../../../../config.php'; // lots of library loaded, give $CFG and more useful variables, can uase all of moodle API from moodlelib.php dmllib.php, etc.""

$help_url = "/redirect/gradebook.html";
// Enable the link to the quickedit module
$quickedit_param = 1;

// Enable the link for the provided histogram functionality - please read about the stored procedures 
$histogram_param = 0;

// enable averages on the top 
$averages_param = 1;

// display the full grade item name when clicking on pull down
$text_param = 0;

//display feedback,name,score in hover tooltip
$show_feedback_tooltip = 0 ;

// By default, run background tasks to display who has been in grader tools or check if the grades have been updated
// this is a default setting for easyview, an instructor can easily enable it
$default_check_grades_and_others = 0;

//control whether the user has the abilty to see others usage and grade item updates
$enable_check_grades = 0;
$enable_check_others = 0;

// Interval in seconds to check to see if data has changed in gradebook
// query done by grade.grade timemodify change since last check or start up 
$check_grades_time = 15;

// How often to check mdl_log for gradebook access by peers
$check_others_time = 5;

?>

