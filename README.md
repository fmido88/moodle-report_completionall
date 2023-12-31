# moodle-report_completionall
Same as activity completion and course completion reports but showing all students even suspended.

This is just the same as acitivity completion report and course completion report in the course level but with the option to show all students including the not active and suspended ones.
No credit for me.
Inspired from the ticket MDL-63164 so this is a soultion till it resolved.
Most of the code just copied from completionlib.php, report_progress, report_completion and core\course to override the process.
