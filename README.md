# Solent SITS integrations

[![SOLSITS Integration CI](https://github.com/ltu-solent/moodle-local_solsits/actions/workflows/moodle-ci.yml/badge.svg?branch=main)](https://github.com/ltu-solent/moodle-local_solsits/actions/workflows/moodle-ci.yml)

This plugin provides Moodle integrations between SOL and SITS (via AIS), focused on module templating, summative assignment lifecycle management, and grade export workflows.

It is bespoke to Southampton Solent University and is not intended as a general-purpose Moodle plugin.

## Feature summary

### 1) SITS assignment ingestion and update
- Web service endpoints to add and update SITS assignment records in Moodle (`local_solsits_add_assignments`, `local_solsits_update_assignments`).
- Assignment metadata handled includes SITS reference, title, sequence, weighting, attempt number, due date, and grading scale hints.
- Scale selection can be automated from configuration and assignment flags (for example grademark exempt).

### 2) Automated assignment creation in courses
- A scheduled task (`create_assignment_task`) creates Moodle assignment activities from queued SITS assignment records.
- Creation behaviour is configurable (for example assignment section, default submission settings, limits per run, year limits).
- Supports reattempt workflows, including optional hidden reattempt groups and group availability restrictions.

### 3) Template management for SITS-created pages
- Admin UI to create, edit, enable, and manage templates by page type and academic session.
- Queue UI shows courses/modules waiting for template application and why they are blocked.
- Scheduled template application (`applytemplate_task`) imports template course content into eligible target courses, with safety checks (visibility, enrolments, edited content).

### 4) Marks upload pipeline to SITS
- Detects newly released assignment grades and queues them for export (`get_new_grades_task`).
- Exports queued grades to AIS/SITS with batching and retry support (`export_grades_task`).
- Stores and updates per-student export responses for operational follow-up.
- Includes admin-configurable AIS endpoint/key settings and a test-connection action.

### 5) Assignment quality and process alerts
- Grading/view-page contextual alerts for summative assignments (for example hidden assignment, missing submission setup, due-date checks).
- Event-driven notifications when summative assignment settings are changed in ways that may affect students.
- Weekly digest tasks for:
	- upcoming assignment configuration issues
	- module configuration issues before module start

### 6) Due date change request workflow
- Tutor-facing flow to request due date changes to central teams.
- Sends templated email requests (including reason and context) through an ad-hoc task.

### 7) Course/assignment support utilities
- Course search endpoint used by admin filter forms.
- Shortcodes for dynamic assignment information blocks:
	- `summativeassignments`
	- `assignmentintro`
- Admin tools to inspect, filter, recreate, or clean up SITS assignment links where needed.

### 8) Security and permissions
- Dedicated capabilities for managing templates and assignments, registering SITS courses, releasing grades, and submission selection controls.

## Notes

- The plugin includes both scheduled and ad-hoc task processing; ensure Moodle cron is configured and running.
- Integration behaviour relies on site settings under the plugin admin pages (assignments, templates, contextual help, digest emails, and marks uploads).

