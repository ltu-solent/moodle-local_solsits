# Assignment Workflow diagrams

## SITS assignment change

```mermaid
stateDiagram-v2
    SITS --> assemble_payload : change
    SITS --> assemble_payload
    assemble_payload --> SITS : WS
    assemble_payload --> moodle_WS : WS
    moodle_WS --> assemble_payload
    assemble_payload --> Moodle_WS_create
    Moodle_WS_create --> Store_in_MDB
    Store_in_MDB --> [*] : create

    assemble_payload --> Moodle_WS_update
    Moodle_WS_update --> Store_in_MDB
    Store_in_MDB --> if_not_link_exists : update
    if_not_link_exists --> throw_error
    throw_error -->  [*]

    Store_in_MDB --> if_link_exists : update
    if_link_exists --> recalculate_link
    recalculate_link --> update_assignment_link : update
    update_assignment_link --> [*]

    create_list --> recalculate_link
    recalculate_link --> create_assignment_link : create
    create_assignment_link --> [*]

    create_assignment_task --> create_list
    create_list --> nothing_to_do
    nothing_to_do --> [*]


    state "AIS
    * Fetch data from SITS
    * Fetch data from Moodle
    * Determine if create or update" as assemble_payload
    state "Store in Moodle sitsassign table
    * Set scale based on Grademarkexempt flag
    and default settings
    " as Store_in_MDB
    state "Calculate: dates, scales" as recalculate_link
    state "Create assignment link" as create_assignment_link
    state "Fetch data from Moodle
    - category
    - course
    - existing assignment" as moodle_WS
```

## Set scale based on Grademarkexempt flag and default scale settings

Note: If the assignments exists, and already has grades, the scale is not changed, otherwise the scale is changed on update, depending on the settings.

```mermaid
stateDiagram
    Set_Scale --> assign.has_grades
    assign.has_grades --> no_change : yes

    assign.has_grades --> config.defaultPoints_set? : no
    config.defaultPoints_set? --> data.reattempt : yes
    data.reattempt --> find_first_attempt : yes
    data.reattempt --> use_config.defaultPoints : no

    find_first_attempt --> use_first_attempt_scale : yes
    find_first_attempt --> use_config.defaultPoints : no

    config.defaultPoints_set? --> data.grademarkexempt : no

    data.grademarkexempt --> use_grademarkexempt_scale : yes
    data.grademarkexempt --> use_grademark_scale : no

    no_change --> [*]
    use_first_attempt_scale --> [*]
    use_config.defaultPoints --> [*]
    use_grademarkexempt_scale --> [*]
    use_grademark_scale --> [*]

```
