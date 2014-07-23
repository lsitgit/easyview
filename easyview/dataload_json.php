<?php
/*
 ** queries database for changes in the gradebook since the given timestamp, which is stored in $_SESSION['GRADEBOOK_DATALOAD']
* @package easyview report
* @copyright 2014 UC Regents
* @license http://www.gnu.org/copyleft/gpl.html GNU GPL v2
*/
require 'config_path.php';

require_once $CFG->dirroot.'/lib/gradelib.php';

require_login();
$context = context_course::instance($_SESSION['COURSE_ID']);
if (! has_capability('gradereport/grader:view', $context, $USER->id)) {
        print_error('Insufficient privilege');
}

$sql = "
SELECT
    gg.id,
    u.firstname,
    u.lastname,
    gg.finalgrade,
    gi.itemname,
    gi.id as gradeitemid
FROM
    {grade_grades} AS gg
INNER JOIN
    {user} AS u
ON
    (
        gg.userid = u.id)
INNER JOIN
    {grade_items} AS gi
ON
    (
        gg.itemid = gi.id)
WHERE
    gi.courseid = ".$_SESSION['COURSE_ID']."
AND gg.timemodified >= ".$_SESSION['GRADEBOOK_DATALOAD'];

//error_log("time on query: ".$_SESSION['GRADEBOOK_DATALOAD']);
$others = $DB->get_records_sql($sql);
error_log($sql);
$others_array = array();
foreach($others as $other){
	$row['firstname'] = $other->firstname;
	$row['lastname'] = $other->lastname;
           $giforscale=grade_item::fetch(array('id'=>$other->gradeitemid));
           $score = grade_format_gradevalue($other->finalgrade, $giforscale, true);
           $row['finalgrade'] = $score;
	//$row['finalgrade'] = $other->finalgrade;
	$row['itemname'] = $other->itemname;
	array_push($others_array,json_encode($row));
}
$final = implode(',',$others_array);
error_log($final);

################################
### BEGIN OUTPUT TO BROWSER
###
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Fri, 01 Jul 2011 00:00:00 GMT"); // Date in the past
header("Content-type: application/JSON; charset=utf-8");

$status = empty($final) ? 'false' : 'true';
?>{"status": <?php echo $status; ?>, "dataload": [<?php echo $final; ?>]}
