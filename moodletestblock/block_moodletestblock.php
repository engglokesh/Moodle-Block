<?php

/**
 * Block for displayed All activities for course along with status.
 *
 * @package    block_moodletestblock
 * @copyright  2021-2022 Lingel Learning
 * @author     Lokesh Malpani <engg.lokeshmalpani@gmail.com>

 */
class block_moodletestblock extends block_list {

     /**
     * List of submissions.
     * @var array of arrays: userid => [cmid => obj]
     */
    protected $submissions = null;

    /**
     * List of computed completions.
     * @var array of arrays: userid => [cmid => state]
     */
    protected $completions = null;
    
    /**
     * Whether completions have been loaded for all course users already.
     * @var boolean
     */
    protected $completionsforall = false;

    public function init() {
        global $CFG;

        require_once("{$CFG->libdir}/completionlib.php");

        $this->title = get_string('pluginname', 'block_moodletestblock');
    }

    public function applicable_formats() {
        return array('course' => true);
    }

    public function get_content() {
        
        global $CFG, $DB, $OUTPUT,$USER;



        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $course = $this->page->course;
        $this->for_user($USER);
        require_once($CFG->dirroot.'/course/lib.php');

        $modinfo = get_fast_modinfo($course);
        $modfullnames = array();

        $archetypes = array();
        $activity_details = array();
        foreach($modinfo->cms as $cm) {
           
            if (!$cm->uservisible || (!$cm->has_view() && strcmp($cm->modname, 'folder') !== 0)) {
                continue;
            }

            $activity_details[$cm->id]['id'] =  $cm->id;
            $activity_details[$cm->id]['modname'] =  $cm->modname;

           
            $this->load_completions();
            if($this->completions[$course->id][$cm->id]==1){
                $activity_completion_status  = 'Completed';
            }else if($this->completions[$course->id][$cm->id]==0){
                $activity_completion_status  = 'Not Started';
            }else{
                $activity_completion_status = $this->completions[$course->id][$cm->id];
            }
            $activity_details[$cm->id]['completion_status'] =  $activity_completion_status;
            $activity_details[$cm->id]['created_on'] =  date('d-M-Y',$cm->added);
            
        }
        
        

        foreach ($activity_details as $key => $single_activity) {
            
            if ($modname === 'resources') {
                $icon = $OUTPUT->pix_icon('monologo', '', 'mod_page', array('class' => 'icon'));
                $this->content->items[] = '<a href="'.$CFG->wwwroot.'/course/resources.php?id='.$course->id.'">'.$icon.$single_activity['id'].'-'.$single_activity['modname'].'-'.$single_activity['created_on'].'</a>-'.$single_activity['completion_status'];
            } else {
                $icon = $OUTPUT->image_icon('monologo', get_string('pluginname', $single_activity['modname']), $single_activity['modname']);
                $this->content->items[] = '<a href="'.$CFG->wwwroot.'/mod/'.$single_activity['modname'].'/index.php?id='.$course->id.'">'.$icon.$single_activity['id'].'-'.$single_activity['modname'].'-'.$single_activity['created_on'].'</a>-'.$single_activity['completion_status'];
            }
        }

        return $this->content;
    }

    /**
     * This block shouldn't be added to a page if the completion tracking advanced feature is disabled.
     *
     * @param moodle_page $page
     * @return bool
     */
    public function get_aria_role() {
        return 'navigation';
    }

    //Get course completion from DB
    protected function load_completions() {
        global $DB;
        
        $c = context_course::instance($this->page->course->id);
        $enrolsql = get_enrolled_join($c, 'u.id', false);
        $query = "SELECT DISTINCT " . $DB->sql_concat('cm.id', "'-'", 'u.id') . " AS id,
                        u.id AS userid, cm.id AS cmid,
                        COALESCE(cmc.completionstate, :incomplete) AS completionstate
                    FROM {user} u {$enrolsql->joins}
              CROSS JOIN {course_modules} cm
               LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
                   WHERE {$enrolsql->wheres}
                     AND cm.course = :courseid
                     AND cm.completion <> :none";
        $params = $enrolsql->params + [
            'courseid' => $this->page->course->id,
            'incomplete' => COMPLETION_INCOMPLETE,
            'none' => COMPLETION_TRACKING_NONE,
        ];
       
        if ($this->user) {
            $query .= " AND u.id = :userid";
            $params['userid'] = $this->user->id;
        } else {
            $this->completionsforall = true;
        }
        $rset = $DB->get_recordset_sql($query, $params);
       
        $this->completions = [];
        foreach ($rset as $compl) {
            $submission = $this->submissions[$compl->userid][$compl->cmid] ?? null;

            if ($compl->completionstate == COMPLETION_INCOMPLETE && $submission) {
                $this->completions[$compl->userid][$compl->cmid] = 'submitted';
            } else if ($compl->completionstate == COMPLETION_COMPLETE_FAIL && $submission
                    && !$submission->graded) {
                $this->completions[$compl->userid][$compl->cmid] = 'submitted';
            } else {
                $this->completions[$compl->userid][$compl->cmid] = $compl->completionstate;
            }
        }
        $rset->close();
    }

    //Find completed activities for user
    public function for_user(stdClass $user): self {
        $this->user = $user;
        $this->load_completions();
        return $this;
    }
}
