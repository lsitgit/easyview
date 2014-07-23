<?php
/*
 ** queries DB for list of other users who have used gradebook modules since the given timestamp
* @package easyview report
* @copyright 2014 UC Regents
* @license http://www.gnu.org/copyleft/gpl.html GNU GPL v2
*/
require 'config_path.php';
require_login();
$context = context_course::instance($_SESSION['COURSE_ID']);
if (! has_capability('gradereport/grader:view', $context, $USER->id)) {
        print_error('Insufficient privilege');
}

$sql = "
SELECT DISTINCT
    l.module,
    l.action,
    u.firstname,
    u.lastname
FROM
    {log} as l
INNER JOIN
    {user} as u
ON
    (
        l.userid = u.id) 
WHERE
    l.course = ".$_SESSION['COURSE_ID']."
AND l.module IN ('easyview', 'csvimport', 'quickedit', 'grader', 'grade') 
AND l.time >= unix_timestamp(now()) - 300 
AND l.userid != ".$USER->id;

$others = $DB->get_records_sql($sql);
$others_array = array();
foreach($others as $other){
	$row['module'] = $other->module;
	$row['action'] = $other->action;
	$row['firstname'] = $other->firstname;
	$row['lastname'] = $other->lastname;
	array_push($others_array,json_encode($row));
}
$final = implode(',',$others_array);

################################
### BEGIN OUTPUT TO BROWSER
###
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Fri, 01 Jul 2011 00:00:00 GMT"); // Date in the past
header("Content-type: application/JSON; charset=utf-8");

$status = empty($final) ? 'false' : 'true';
?>{"status": <?php echo $status; ?>, "others": [<?php echo $final; ?>]}
