<?php

/**
* Easyview primary file to load
*
* @package easyview report
* @copyright 2014 UC Regents
* @license http://www.gnu.org/copyleft/gpl.html GNU GPL v2
*/

//////////////////////////////////////////////////
//gets all grade item info for a given course/////
//////////////////////////////////////////////////
//returns array

//require 'config_path.php';


function get_grade_items($courseid,$DB){
        //query, returns all grade items for a given course
        $sql="  SELECT
                gi.id,
		gi.categoryid,
		gi.gradetype,
 		case when gisrt.itemtype = 'course' then gi.sortorder else gisrt.sortorder end as gc_srt,
                gi.itemname,
		gi.itemtype,
		gi.sortorder,
                gi.itemmodule,
		gi.iteminstance,
                gi.itemnumber,
		gi.grademax,
                gc.id as cat_id,
		gc.parent as cat_parent,
                gc.depth as cat_depth,
		gc.path as cat_path,
                gc.fullname as cat_fullname
        FROM {grade_items} as gi
        left JOIN {grade_categories} as gc
        ON (gi.categoryid = gc.id)
        LEFT JOIN mdl_grade_items gisrt ON gisrt.courseid = gi.courseid AND gisrt.iteminstance = gi.categoryid 
        WHERE gi.courseid = ".$courseid." ORDER BY gc_srt, gi.sortorder; ";

        $grade_items = $DB->get_records_sql($sql);//runs sql query
        $array_grade_items = array();//will be populated with processed rows 
        $array_all_cats = array();
        //print("Steve - these dev prints are in index.php in the get_grade_items function if you need to comment them<br>"); 
        foreach ($grade_items as $grade_item){
                //later when we iterate through grade items for each student,
                //the gradeitem id will be used as the column header for each score
                //for that grade item for each student
                $row['id'] = (int)$grade_item->id;
                $row['gradetype'] = (int)$grade_item->gradetype;
                $row['gid'] = "g".(string)$grade_item->id;
                //the line below is key
                //we will store feedback for each grade item in a column identified by the 
                //grade item id concatenated with the string "feedback"
                $row['feedback'] = (string)$grade_item->id."feedback";
                $row['overridden'] = (string)$grade_item->id."overridden";
                $row['ggid'] = (string)$grade_item->id."ggid";
                /////code in next few lines used to get the category name
                //it will be saved in the grade item as well as in the $array_all_cats
                if ($grade_item->categoryid != ""){
                        $cat_name = $DB->get_record_sql("SELECT fullname FROM {grade_categories} as gc WHERE id = ".$grade_item->categoryid)->fullname;
                }else{
                        $cat_name="";
                }
                if($cat_name=="?"){$cat_name ="Top Level Grade Item";}
                $row['cat_name']=(str_replace("&","and",$cat_name));
                array_push($array_all_cats,(str_replace("&","and",$cat_name)));
                if ($grade_item->itemtype == "manual"){
                        // we will have the standard quickedit icon for these
                        $row['name'] = addslashes($grade_item->itemname);
                        $row['locked'] = false;//used to lock column, categories are locked 
                        $row['type']='manual';
                        $row['printicon']='manual';
                        array_push($array_grade_items, ($row));
                } else if ($grade_item->itemtype == "course"){
                        $row['name'] = 'Course Total';
                        $row['locked'] = true;//used to lock column, categories are locked 
                        $row['type']='category';
                        array_push($array_grade_items, ($row));
                } else if ($grade_item->itemtype == "blocks"){
                        // will want icons for these
                        //  if itemtype = 'quiz'  set icon =  pixmap(t/quiz); (or whatever it might be)
                        $row['name'] = addslashes($grade_item->itemname);
                        $row['locked'] = false;//used to lock column, categories are locked 
// iclicker
// although its really a block, but that doesn't make much sense to me right now
                        $row['type']='mod';
                        $row['printicon']=$grade_item->itemmodule;
                        array_push($array_grade_items, ($row));
                } else if ($grade_item->itemtype == "mod"){
                        // will want icons for these
                        //  if itemtype = 'quiz'  set icon =  pixmap(t/quiz); (or whatever it might be)
                        $row['name'] = addslashes($grade_item->itemname);
                        $row['locked'] = false;//used to lock column, categories are locked 
                        $row['type']='mod';
                        $row['printicon']=$grade_item->itemmodule;
                        array_push($array_grade_items, ($row));
                } else if ($grade_item->itemtype == "category"){
                        // NO icons for categories
                        $row['name'] = ($DB->get_record_sql("SELECT fullname FROM {grade_categories} as gc  WHERE id = ".$grade_item->iteminstance)->fullname);
                        //$row['name'] = preg_replace('(','[',$DB->get_record_sql("SELECT fullname FROM {grade_categories} as gc WHERE id = ".$grade_item->iteminstance)->fullname);
                        $row['locked'] = true;//used to lock column, categories are locked 
                        $row['type']='category';
                        array_push($array_grade_items, ($row));
                }
        }
        //all categories entry will be used in the drop down to wipe all category filters
        array_push($array_all_cats,'All grade items');
        $array_all_cats = array_unique($array_all_cats);
        //exit();
        return (array($array_grade_items,$array_all_cats));
}
////////////////////////////////////////////////
//gets all students info for a given course//////
////////////////////////////////////////////////
//returns an array structure
function get_students($courseid,$DB){

        //raw query to get basic student info without grade item scores
// TODO - maybe review this query, we just added a distinct  6.3.2014
        $sql = "SELECT
                DISTINCT u.id, u.idnumber, c.shortname, u.firstname, 
                u.lastname, u.email
        
                FROM {user_enrolments} as ue
        
                INNER JOIN {user} as u
                ON (ue.userid = u.id)
        
                INNER JOIN {enrol} as e
                ON (ue.enrolid = e.id)
        
                INNER JOIN {course} as c
                ON (e.courseid = c.id)

                WHERE c.id = ".$courseid." and u.id in (
                        SELECT tmpra.userid
                        FROM {context} as tmpcontext
                        INNER JOIN {course} as tmpcourse
                        ON (tmpcontext.instanceid = tmpcourse.id)
                        INNER JOIN {role_assignments} as tmpra
                        ON(tmpcontext.id = tmpra.contextid)
                        WHERE tmpcourse.id = ".$courseid." AND tmpra.roleid = 5
                )";

        //query to get groups given a userid and courseno
        //used on each student in the loop below
        $getGroups = <<<SQL
        SELECT gm.id as uniq, g.courseid, g.idnumber, g.name
        FROM {groups_members} gm
        INNER JOIN {groups} g
                ON ( gm.groupid = g.id)
        WHERE gm.userid = ?
                AND g.courseid = ? ;
SQL;

        //query for first,last,perm,id,email
        $students = $DB->get_records_sql($sql);
        $array_students = array();//array_students will eventually contain all students and their info

        //all_groups will be used to populate the dropdown filter
        $all_groups = array();

        //loop over resources
        foreach ($students as $student){
                $row['first']   = $student->firstname;
                $row['last']    = $student->lastname;
                $row['perm']    = (int)$student->idnumber;
                $row['id']      = (int)$student->id;//id is what is used in mdl as an identifier, different from perm
                $row['email']   = $student->email;

                //get user's groups, uses sql defined above
                $groups    =  $DB->get_records_sql($getGroups, array('userid'=>$student->id, 'courseid'=>$courseid));
                $user_groups     = array();
                //iterates through all groups returned
                foreach ($groups as $group) {
                        array_push($user_groups, addslashes($group->name));
                        array_push($all_groups, addslashes($group->name));
                }
                //turns array into comma separated string, 
                //pushes onto the row we've been constructing above
                $row['group'] = implode(', ', $user_groups);

                array_push($array_students, ($row));
        }

        $all_groups = array_unique($all_groups);
        //returns all the students plus all the groups that were found
        return (array($array_students,$all_groups));
}

?>
