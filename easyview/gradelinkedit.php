<?php

/*
 ** smart tool to figure out what edit screen to go to
* @package easyview report
* @copyright 2014 UC Regents
* @license http://www.gnu.org/copyleft/gpl.html GNU GPL v2
*/


require '../../../../config.php'; 
//require '../../config.php'; 

// var url = WROOT+"/grade/report/quick_edit/index.php?id="+COURSEIDPASSEDIN+"&item=grade&group=&itemid="+gradeid.substring(1);
if (session_id() == "") session_start('mysess');

require_login();
$courseid =  required_param('id', PARAM_INT); 
$itemid = required_param('itemid', PARAM_INT); // grade item  id
// $context = context_course::instance($courseid);
// if (! has_capability('moodle/course:manageactivities', $context, $USER->id)) {
//	print_error('Insufficient privilege');
// }

function get_item_url($courseid, $itemid, $DB){
    $sql = "
SELECT
    gi.id as giid,
    gi.courseid,
    -- gi.itemname,
    gi.itemtype,
    gi.itemmodule,
    gi.iteminstance,
    -- gi.itemnumber, cm.course, cm.module,
    cm.instance,
    -- cm.section, cm.idnumber,
    cm.id as cmid,
    mm.name
FROM {grade_items} gi
LEFT JOIN {course_modules} cm
ON ( cm.instance = gi.iteminstance  
AND  cm.course = gi.courseid)
LEFT JOIN {modules} mm ON mm.id = cm.module
WHERE
--    gi.courseid = $courseid AND 
    gi.id = $itemid 
    and ( gi.itemmodule = mm.name or mm.id is NULL)
   "; 
//    print "$sql<hr>";
    $uri='';
    if ( $grditem = $DB->get_records_sql($sql) ) { // there really should only be one.
        foreach ($grditem as $gri){
            $gritem = $gri;
        }    
//    print $gritem->itemtype."<hr>";
        if ($gritem->itemtype === 'mod') {
            switch($gritem->itemmodule) {
            case 'assign':
                //$uri = 'mod/assign/view.php?id=' . $gritem->cmid . '&action=grading';
                $uri = 'mod/assign/view.php?id=' . $gritem->cmid;
                break;
            case 'quiz':    
                // $uri = 'mod/quiz/view.php?id=' 
                //    . $gritem->cmid . '&action=grading';
                $uri = 'mod/quiz/report.php?id=' 
                    . $gritem->cmid . '&mode=overview';
                break;
//            case 'wwassignment':    
//                $uri = 'mod/wwassignment/view.php?id=' . $gritem->cmid . '&action=grading';
//                break;
            default:
                // print_error('itemedit module ' 
                error_log('easyview gradelinkedit: module ' 
                    . $gritem->itemmodule . ' not implemented');
                break;
            }        
//        print "is module $uri";
        } elseif ($gritem->itemtype === 'manual') {
            $uri= "/grade/report/quick_edit/index.php?id={$courseid}&item=grade&group=&itemid={$itemid}";
//                   /grade/report/quick_edit/index.php?id=578&item=grade&group=&itemid=6261
        }    
    }
    if ($uri === ''){
         checkout($courseid, "Not implemented for item $gritem->itemmodule");
         sleep(10);
         redirect("index.php?id=".$courseid);
    }     

//    print_r($gritem);
    return($uri);
} // end get_item_url

function checkout($courseid, $msg) { 
    // possible page error... no redirect
     
    global $DB, $PAGE, $OUTPUT;
    $course_params = array('id' => $courseid);
    
    $PAGE->set_url(new moodle_url('/grade/report/easyview/index.php', $course_params));
    $PAGE->set_pagelayout('standard');
    
    if (!$course = $DB->get_record('course', $course_params)) {
        print_error('nocourseid');
    }
    
    require_login($course);
    
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    
    require_capability('moodle/grade:viewall', $context);
    // require_capability('moodle/grade:edit', $context);
    // End permission
    
    
    $pluginname = get_string('pluginname', 'gradereport_easyview');
    
    $report_url = new moodle_url('/grade/report/grader/index.php', $course_params);
    $edit_url = new moodle_url('/grade/report/easyview/index.php', $course_params);
    // $view_url = new moodle_url('/local/easyview/index.php', $course_params);
    // $view_url = new moodle_url('/local/easygrade/wedge.php',$course_params);
    $view_url = new moodle_url('/grade/report/easyview/easyview/index.php', $course_params);
    
    $PAGE->navbar->ignore_active(true);
    
    $PAGE->navbar->add(get_string('courses'));
    $PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', $course_params));
    
    $PAGE->navbar->add(get_string('gradeadministration', 'grades'));
//    $PAGE->navbar->add(get_string('pluginname', 'gradereport_easyview'), $report_url);
    
    if ($reportname != $pluginname) {
        $PAGE->navbar->add($pluginname, $edit_url);
    //    $PAGE->navbar->add($reportname);
    } else {
        $PAGE->navbar->add($$reportname,$edit_url);
    }
    echo $OUTPUT->header();
    echo $msg;
    echo $OUTPUT->footer();

} // end function checkout

$url = get_item_url($courseid, $itemid, $DB);
//  print "<hr>url: $CFG->wwwroot/$url<hr>";
// quiz edit?
// /mod/quiz/report.php?id=801&mode=overview
// /mod/quiz/reviewquestion.php?attempt=25&slot=2
// /mod/quiz/view.php?id=801&action=grading
// quik_edit
// /grade/report/quick_edit/index.php?id=578&item=grade&group=&itemid=6261
// /grade/report/quick_edit/index.php?id=578&item=grade&group=&itemid=6261
    redirect("$CFG->wwwroot/$url");
  
 
