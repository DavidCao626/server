<?php
return array(
    '0x036001'                               => 'Request exception',
    '0x036002'                               => 'System abnormality',
    '0x036003'                               => 'Item type is occupied',
    '0x036004'                               => 'Project role is occupied',
    '0x036005'                               => 'Add task, specify item task or template task, data exception',
    '0x036006'                               => 'Task executor cannot be empty during project task',
    '0x036007'                               => 'Non-project status items are not editable',
    '0x036008'                               => 'Items in review cannot be deleted',
    '0x036009'                               => 'Data already exists in the project template and cannot be deleted',
    '0x036010'                               => 'Imported template does not have related tasks',
    '0x036011'                               => 'The status of this item is abnormal',
    '0x036012'                               => 'Reply question does not support editing',
    '0x036013'                               => 'The problem was seen and cannot be edited',
    '0x036014'                               => 'Abnormal data, you can\'t delete others\' discussions',
    '0x036015'                               => 'Data is abnormal. The status of the problem cannot be edited',
    '0x036016'                               => 'The data is abnormal. The current problem status does not support this operation',
    '0x036017'                               => 'Please fill in the solution',
    '0x036018'                               => 'You currently have no permission to operate',
    '0x036019'                               => 'The project has ended and no new task can be created',
    '0x036020'                               => 'This operation is not supported during project review',
    '0x036021'                               => 'The project has ended and does not support this operation',
    '0x036022'                               => 'The current status of the project does not support this operation',
    '0x036023'                               => 'Non-project leader cannot change project status',
    '0x036024'                               => 'The project data is abnormal. Please try again',
    '0x036025'                               => 'The project name is empty',
    '0x036026'                               => 'The project end time is empty',
    '0x036027'                               => 'The project start time is empty',
    '0x036028'                               => 'The project end time cannot be less than start time',
    '0x036030'                               => 'The task end time cannot be less than start time',
    '0x036029'                               => 'Permission configuration exception :flag',
    'secondary'                              => 'secondary',
    'commonly'                               => 'general',
    'important'                              => 'important',
    'very_important'                         => 'Very important',
    'very_low'                               => 'Extremely low',
    'low'                                    => 'low',
    'in'                                     => 'in',
    'high'                                   => 'high',
    'higher'                                 => 'Higher',
    'modify_task_id_schedule'                => 'The progress of modifying task id=:task_id is :task_persent%.',
    'modify_project_id_state'                => 'The status of modifying task id=:task_id is :task_status.',
    'unsubmitted'                            => 'unsubmitted',
    'submission'                             => 'submitted',
    'in_the_process_of_processing'           => 'Processing',
    'already_processed'                      => 'Processed',
    'unsolved'                               => 'unsolved',
    'resolved'                               => 'solved',
    'in_the_project'                         => 'Designing',
    'examination_and_approval'               => 'In approval',
    'retreated'                              => 'Returned',
    'have_in_hand'                           => 'processing',
    'finished'                               => 'over',
    'number'                                 => 'Quantity',
    'project_creator'                        => 'Project creator',
    'project_leader'                         => 'Project manager',
    'project_type'                           => 'project type',
    'emergency_degree'                       => 'emergency level',
    'priority_level'                         => 'Priority level',
    'project_status'                         => 'project status',
    'project_cycle'                          => 'Project Cycle',
    'project_completed_progress_percent'     => 'Project completion progress (%)',
    'other'                                  => 'Other',
    'priority'                               => 'Priority',
    "no_auditor_can_not_submission_of_audit" => "This project has no approver and cannot be submitted for review.",
    "task"                                   => "Task",
    "team"                                   => "Team",
    "discuss"                                => "Discuss",
    "problem"                                => "Problem",
    "question"                                => "Problem",
    "file"                                   => "Documents",
    "document"                                   => "Documents",
    "gantt_chart"                            => "Gantt chart",
    "assessment"                             => "Assessment",
    "detail"                                 => "Detail",
    "manager"                                => "manager",
    "monitor"                                => "monitor",
    "executor"                               => "executor",
    "project"                                => "Project",
    "log" => [
        'name' => 'Logs',
        "actions" => [
            'add' => 'Add',
            'modify' => 'Modify',
            'delete' => 'Delete',
            'proExamine' => 'Submit review',
            'proApprove' => 'Approved',
            'proRefuse' => 'Return',
            'proEnd' => 'End',
            'proRestart' => 'Restart',
        ],
        'editType' => [
            'person' => 'Personnel change',
            'date' => 'Date change',
            'percent' => 'Percent change',
            'other' => 'Other',
            'manager_state' => 'Manager state change',
        ],
        'add' => 'Add',
        'remove' => 'Remove',
    ],
    "fields" => [
        'task_name' => 'Task name',
        'task_persondo' => 'Task Principal',
        'task_begintime' => 'Task start date',
        'task_endtime' => 'Task end date',
        'task_persent' => 'Task progress',
        'manager_state' => 'Project state',
    ],
    'task_front_not_exists' => 'Predecessors not exists',
    'not_begin' => 'Not started',
    'have_in_hand_overdue'  => '[overdue] processing',
    'finished_overdue'  => '[overdue] over',
    "doc_name"  => "Document name",
    "public_dir_name"  => "Public dir",
    "complete"  => "Done",
    "not_complete"  => "Undone",
    "role" => [
        "team_person" => "Project team",
        "doc_creater" => "Document founder",
        "question_person" => "Question proposer",
        "question_doperson" => "Question dealer",
        "question_creater" => "Question founder",
        "p1_task_persondo" => "Direct parent task executor",
        "p2_task_persondo" => "All parent task executor",
    ],
    "this_field_role_exist" => "Permission has been set for this field, please return to the list to view",
    "name_of_the_problem" => "Question name",
    "processing_person" => "Dealer",
    "expiry_time" => "Expire date",
);
