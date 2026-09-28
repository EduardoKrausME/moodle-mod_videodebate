<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English strings.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['accessibilityheader'] = 'Accessibility';
$string['addcurrentmoment'] = 'Add current moment';
$string['allowseek'] = 'Allow seeking into unwatched parts';
$string['argument'] = 'Argument';
$string['arguments'] = 'Arguments';
$string['assignmentautomatic'] = 'Automatically distribute positions';
$string['assignmentfree'] = 'Free choice';
$string['assignmentmode'] = 'Position assignment';
$string['backtodebate'] = 'Back to debate';
$string['blinduntilpost'] = 'Require own position before viewing classmates';
$string['cannotreplygroup'] = 'You cannot reply to a contribution outside your group.';
$string['cannotreplyself'] = 'You cannot reply to your own argument.';
$string['captionfile'] = 'WebVTT captions file';
$string['captionlang'] = 'Caption language';
$string['captions'] = 'Captions';
$string['completiondetail:percent'] = 'Watch at least {$a}% of the video';
$string['completiondetail:post'] = 'Publish an initial debate argument';
$string['completiondetail:replies'] = 'Publish at least {$a} replies to classmates';
$string['completionpercent'] = 'Required watched percentage';
$string['completionpost'] = 'Require an initial argument';
$string['completionreplies'] = 'Minimum replies required';
$string['completionrules'] = 'Completion rules';
$string['criterion:argumentation'] = 'Argumentation (0–100)';
$string['criterion:evidence'] = 'Use of evidence (0–100)';
$string['criterion:participation'] = 'Participation (0–100)';
$string['criterion:replies'] = 'Replies to classmates (0–100)';
$string['debate'] = 'Debate';
$string['debateheader'] = 'Debate';
$string['debatequestion'] = 'Debate question';
$string['defaultpositions'] = 'Agree
Disagree
Partially agree';
$string['deletepost'] = 'Delete';
$string['durationnotconfigured'] = 'The video duration has not been configured by the teacher.';
$string['durationseconds'] = 'Video duration in seconds';
$string['durationseconds_help'] = 'Authoritative duration used by the server to calculate watched percentage. This value prevents the browser from defining its own completion denominator.';
$string['editpost'] = 'Edit';
$string['errorcaptionlang'] = 'Enter a valid language code, for example en, pt-BR or es.';
$string['errordurationrequired'] = 'Enter the video duration in seconds.';
$string['errorgrade'] = 'The maximum grade must be between 0 and 100.';
$string['errorinvalidurl'] = 'Enter a valid HTTP or HTTPS video URL.';
$string['errorinvalidvimeo'] = 'Enter a valid Vimeo URL or numeric video ID.';
$string['errorinvalidyoutube'] = 'Enter a valid YouTube URL or video ID.';
$string['errormaxfiles'] = 'Only one file can be uploaded.';
$string['errornonnegative'] = 'Enter zero or a positive number.';
$string['errorpercent'] = 'The percentage must be between 0 and 100.';
$string['errorpositions'] = 'Enter at least two different positions.';
$string['errorresetvideodata'] = 'This activity already contains participant data. Confirm the reset before changing the video or its duration.';
$string['errorvideorequired'] = 'Select a video file for the uploaded video source.';
$string['errorweights'] = 'The four grading weights must total exactly 100%.';
$string['eventgradeupdated'] = 'Participant grade updated';
$string['eventpostcreated'] = 'Initial argument created';
$string['eventreplycreated'] = 'Debate reply created';
$string['eventreportviewed'] = 'Participation report viewed';
$string['evidence'] = 'Evidence';
$string['evidenceadded'] = 'Evidence added.';
$string['evidencedescription'] = 'Evidence description';
$string['evidenceempty'] = 'No evidence has been added yet.';
$string['evidencetimeline'] = 'Evidence timeline';
$string['evidencetitle'] = 'Video evidence';
$string['feedback'] = 'Feedback';
$string['finishinterval'] = 'Finish interval';
$string['grade'] = 'Grade';
$string['gradeparticipant'] = 'Grade {$a}';
$string['gradesaved'] = 'Grade saved.';
$string['gradingheader'] = 'Grading';
$string['gradingsummary'] = 'Evidence used: {$a->evidence}. Replies published: {$a->replies}.';
$string['hiddenpost'] = 'Hidden';
$string['hidepost'] = 'Hide';
$string['initialpostexists'] = 'You already published your initial argument.';
$string['invalidposition'] = 'The selected position is invalid.';
$string['lastaccess'] = 'Last video activity';
$string['maximumgrade'] = 'Maximum grade';
$string['messagebody:grade'] = 'Your activity {$a->activity} was graded: {$a->grade} / {$a->maximum}.';
$string['messagebody:reply'] = '{$a->author} replied to your argument in {$a->activity}.';
$string['messageprovider:gradenotification'] = 'Grade notifications';
$string['messageprovider:replynotification'] = 'Reply notifications';
$string['messagesubject:grade'] = 'Video Debate grade: {$a}';
$string['messagesubject:reply'] = 'New reply in Video Debate: {$a}';
$string['minevidence'] = 'Minimum evidence in initial argument';
$string['moderationupdated'] = 'The contribution was updated.';
$string['modulename'] = 'Video Debate';
$string['modulename_help'] = 'Create an academic debate where positions and replies must be grounded in evidence from specific moments of a video.';
$string['modulenameplural'] = 'Video Debates';
$string['nestedreplynotallowed'] = 'Replies can only be posted directly to an initial argument.';
$string['noposts'] = 'No arguments have been published yet.';
$string['nostudents'] = 'No participating students were found.';
$string['notenoughevidence'] = 'Add at least {$a} video evidence item(s) before publishing.';
$string['notpublished'] = 'Not published';
$string['orphanedreplies'] = 'Replies whose original argument was removed';
$string['parentremoved'] = 'The original argument for this reply is no longer available.';
$string['participation'] = 'Participation';
$string['pluginadministration'] = 'Video Debate Administration';
$string['pluginname'] = 'Video Debate';
$string['position'] = 'Position';
$string['positionpublished'] = 'Your published position:';
$string['positions'] = 'Positions';
$string['positions_help'] = 'Enter one position per line. At least two positions are required.';
$string['postmessage'] = 'Contribution text';
$string['postsaved'] = 'Your contribution was published.';
$string['postupdated'] = 'The contribution was updated.';
$string['privacy:metadata:evidence'] = 'Stores video moments and ranges cited as evidence.';
$string['privacy:metadata:evidence:endtime'] = 'Evidence end time in seconds.';
$string['privacy:metadata:evidence:label'] = 'Participant description of the evidence.';
$string['privacy:metadata:evidence:starttime'] = 'Evidence start time in seconds.';
$string['privacy:metadata:evidence:timecreated'] = 'When the evidence reference was created.';
$string['privacy:metadata:grades'] = 'Stores rubric scores and teacher feedback.';
$string['privacy:metadata:grades:argumentation'] = 'Argumentation rubric score.';
$string['privacy:metadata:grades:evidence'] = 'Evidence-use rubric score.';
$string['privacy:metadata:grades:feedback'] = 'Teacher feedback.';
$string['privacy:metadata:grades:finalgrade'] = 'Calculated final grade.';
$string['privacy:metadata:grades:graderid'] = 'The teacher who graded the user.';
$string['privacy:metadata:grades:participation'] = 'Participation rubric score.';
$string['privacy:metadata:grades:replies'] = 'Replies rubric score.';
$string['privacy:metadata:grades:timemodified'] = 'When the grade was last updated.';
$string['privacy:metadata:grades:userid'] = 'The graded user.';
$string['privacy:metadata:posts'] = 'Stores arguments and replies published by participants.';
$string['privacy:metadata:posts:groupid'] = 'The group associated with the contribution.';
$string['privacy:metadata:posts:hidden'] = 'Whether a moderator hid the contribution.';
$string['privacy:metadata:posts:message'] = 'The argument or reply text.';
$string['privacy:metadata:posts:parentid'] = 'The parent argument for a reply.';
$string['privacy:metadata:posts:position'] = 'The position selected or assigned for the initial argument.';
$string['privacy:metadata:posts:positionlabel'] = 'The historical label of the position at publication time.';
$string['privacy:metadata:posts:timecreated'] = 'When the contribution was published.';
$string['privacy:metadata:posts:timemodified'] = 'When the contribution was last modified.';
$string['privacy:metadata:posts:userid'] = 'The user who published the contribution.';
$string['privacy:metadata:progress'] = 'Stores watched video progress.';
$string['privacy:metadata:progress:completed'] = 'Whether the activity completion rules were met.';
$string['privacy:metadata:progress:duration'] = 'The authoritative video duration used for progress.';
$string['privacy:metadata:progress:lastposition'] = 'Last playback position.';
$string['privacy:metadata:progress:percent'] = 'Unique watched percentage.';
$string['privacy:metadata:progress:segments'] = 'Server-confirmed watched video segments.';
$string['privacy:metadata:progress:timemodified'] = 'When video progress was last updated.';
$string['privacy:metadata:progress:totalwatchtime'] = 'Total accepted watched seconds.';
$string['privacy:metadata:progress:uniquewatched'] = 'Unique watched seconds.';
$string['privacy:metadata:progress:userid'] = 'The user whose progress is stored.';
$string['publishargument'] = 'Publish argument';
$string['publishbeforeview'] = 'Publish your own position and argument before viewing classmates\' contributions.';
$string['publishreply'] = 'Publish reply';
$string['remove'] = 'Remove';
$string['replies'] = 'Replies';
$string['reply'] = 'Reply';
$string['replytargethidden'] = 'You cannot reply to a hidden contribution.';
$string['report'] = 'Participation report';
$string['requiredpercent'] = 'Required: {$a}% watched';
$string['resetgrades'] = 'Delete Video Debate grades and feedback';
$string['resetposts'] = 'Delete Video Debate arguments, replies and evidence';
$string['resetprogress'] = 'Delete Video Debate video progress';
$string['resetvideodata'] = 'Reset participant data because the video changed';
$string['resetvideodata_help'] = 'Changing the video or its duration invalidates watched progress and time-based evidence. When selected, progress, evidence and stored grades are cleared.';
$string['resumeplayback'] = 'Resume from the last watched position';
$string['savegrade'] = 'Save grade';
$string['scorebetween'] = 'Enter a score from 0 to 100.';
$string['seekblocked'] = 'Watch the preceding part before seeking to an unwatched position.';
$string['showpost'] = 'Show';
$string['sourceupload'] = 'Uploaded video';
$string['sourceurl'] = 'Direct video URL';
$string['sourcevimeo'] = 'Vimeo';
$string['sourceyoutube'] = 'YouTube';
$string['startinterval'] = 'Start interval';
$string['student'] = 'Student';
$string['trackingerror'] = 'Video progress could not be saved. Playback can continue and the plugin will retry on the next update.';
$string['transcript'] = 'Transcript';
$string['videodebate:addinstance'] = 'Add a new Video Debate activity';
$string['videodebate:grade'] = 'Grade Video Debate participants';
$string['videodebate:moderate'] = 'Edit, hide and delete Video Debate contributions';
$string['videodebate:participate'] = 'Publish an initial argument';
$string['videodebate:reply'] = 'Reply to classmates';
$string['videodebate:view'] = 'View Video Debate';
$string['videodebate:viewall'] = 'View all debate contributions';
$string['videodebate:viewreport'] = 'View Video Debate reports';
$string['videodebatename'] = 'Video Debate name';
$string['videofile'] = 'Video file';
$string['videoheader'] = 'Video';
$string['videoplayer'] = 'Video player';
$string['videoprogress'] = 'Video progress';
$string['videosource'] = 'Video source';
$string['videourl'] = 'Video URL or ID';
$string['weightargument'] = 'Argumentation weight (%)';
$string['weightevidence'] = 'Evidence use weight (%)';
$string['weightparticipation'] = 'Participation weight (%)';
$string['weightreplies'] = 'Replies weight (%)';
$string['yourgrade'] = 'Your grade';
$string['yourposition'] = 'Your position';
