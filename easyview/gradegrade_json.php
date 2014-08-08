<?php
/*
 ** queries DB for list of other users who have used gradebook modules since the given timestamp
* @package easyview report
* @copyright 2014 UC Regents
* @license http://www.gnu.org/copyleft/gpl.html GNU GPL v2
*/
//define('CLI_SCRIPT', true);
error_log("this is grade grade json");
require 'config_path.php';
require_login();
$context = context_course::instance($_SESSION['COURSE_ID']);
if (! has_capability('gradereport/grader:view', $context, $USER->id)) {
        print_error('Insufficient privilege');
}
$ggid = $_GET['ggid'];

$sql = "
SELECT
    gg.id,
    gg.itemid,
    gg.userid,
    gg.finalgrade,
    gg.hidden,
    gg.locked,
    gg.overridden,
    gg.excluded,
    gg.feedback,
    gi.itemname,
    gi.itemtype,
    gi.itemmodule,
    gi.grademax as itemgrademax,
    gi.locked as itemlocked,
    gi.scaleid as itemscaleid,
    u.firstname,
    u.lastname,
    u.idnumber
FROM
    {grade_grades} as gg
INNER JOIN
    {grade_items} as gi
ON
    (
        gg.itemid = gi.id)
INNER JOIN
    {user} as u
ON
    (
        gg.userid = u.id)
WHERE
    gg.id = ".$ggid;

$others = $DB->get_records_sql($sql);
$others_array = array();
foreach($others as $other){
	$row['id'] = $other->id;
	$row['itemname'] = $other->itemname;
	$row['finalgrade'] = $other->finalgrade;
	$row['feedback'] = $other->feedback;
	$row['firstname'] = $other->firstname;
	$row['lastname'] = $other->lastname;
	$row['idnumber'] = $other->idnumber;
	$row['locked'] = $other->lastname;
	$row['overridden'] = $other->overridden;
	$row['excluded'] = $other->excluded;
	$row['itemname'] = $other->itemname;
	$row['itemtype'] = $other->itemtype;
	$row['itemmodule'] = $other->itemmodule;
	$row['itemgrademax'] = $other->itemgrademax;
	$row['itemlocked'] = $other->itemlocked;
	$row['itemscaleid'] = $other->itemscaleid;
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
?>{"status": <?php echo $status; ?>, "gradegrade": [<?php echo $final; ?>]}
