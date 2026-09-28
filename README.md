# Video Debate (mod_videodebate)

Video Debate is a Moodle activity for academic debates grounded in evidence from a video. A teacher configures a debate
question and positions. Students choose or receive a position, publish an initial argument with one or more video
evidences, and then reply to classmates.

The activity includes server-authoritative watched-segment tracking with a teacher-defined canonical video duration,
resume, timeline markers, upload/direct URL/YouTube/Vimeo sources, Moodle groups, publish-before-viewing, weighted
grading with feedback, completion rules, scalable participation reports, moderation, Moodle notifications, event logging,
course reset, backup/restore, Privacy API integration and gradebook integration.

Accessibility support includes a plain-text transcript and an optional WebVTT captions file for HTML5 video sources.
YouTube and Vimeo continue to use the caption facilities provided by those platforms.

Changing the video or its canonical duration after participants have produced data requires explicit confirmation. The
plugin then clears video-dependent progress, evidence and stored grades so timestamps and completion percentages cannot
silently refer to a different video.

## Requirements

Moodle 4.5 or later (2024042200), with a PHP version supported by the selected Moodle release.

## Installation

Copy the `videodebate` directory to `mod/videodebate` and visit Site administration > Notifications.

## Testing

The GitHub Actions matrix covers Moodle 4.5, 5.1 and 5.2 with PostgreSQL and MariaDB. PHPUnit integration tests cover
server-side debate authorization, authoritative video tracking, custom completion, gradebook feedback, privacy deletion,
media reset and backup/restore. A Behat scenario exercises the student activity page.

## License

GNU GPL v3 or later.
