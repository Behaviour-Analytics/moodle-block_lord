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
 * Plugin strings are defined here.
 *
 * @package block_lord
 * @category string
 * @author Ted Krahn
 * @copyright 2020 Athabasca University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Learning Object Relation Discovery (LORD)';
$string['launch'] = 'View graph';
$string['compare'] = 'Compare learning objects';
$string['calculatedmodules'] = 'There are {$a->calculated} of {$a->total} ({$a->percent}%) similarity measures caclulated between learning objects with {$a->errors} error(s).';
$string['progresstitle'] = 'Learning activity similarity';
$string['percent'] = 'Completed';
$string['errors'] = 'Errors';
$string['error'] = '*** ERROR ***';
$string['complete'] = 'Connections analyzed';
$string['learningactivities'] = 'Learning activities';
$string['connections'] = 'Connections between activities';
$string['generalheader'] = 'General options';
$string['resetheader2'] = 'Reset options';
$string['stopwordsheader'] = 'Add or remove stop words (not used in similarity comparison)';
$string['addstopword'] = 'Add a stop word to the dictionary';
$string['removestopword'] = 'Remove a stop word from the dictionary. Defaults are: {$a}';
$string['reseterrors'] = 'Reset comparison errors?';
$string['resetcomparisons'] = 'Reset all comparisons. WARNING: Can\'t be undone!!';
$string['resetcontent'] = 'Reset all learning activity content and comparisons. WARNING: Can\'t be undone!!';
$string['numwords'] = 'The maximum number of words to allow in a sentence.';
$string['numparas'] = 'The maximum number of paragraphs to compare.';
$string['numsentence'] = 'The maximum number of sentences to compare.';
$string['formerror'] = 'Value must be a positive integer.';
$string['formerror2'] = 'Value must be a one, two, or three digit number (1, 2.5, 12, 1.75).';
$string['nameweight'] = 'The weight to use for name comparisons.';
$string['introweight'] = 'The weight to use for introduction comparisons.';
$string['sentenceweight'] = 'The weight to use for paragraph comparisons.';
$string['settingspage'] = 'Settings';
$string['savebutton'] = 'Save graph';
$string['similaritystr'] = 'Similarity:';
$string['comparisonerror'] = 'An error ocurred during comparison.';
$string['notcalculated'] = 'These activities have not been compared yet.';
$string['section'] = 'Section';
$string['mindistance'] = 'Min distance';
$string['maxdistance'] = 'Max distance';
$string['scalingfactor'] = 'Scaling factor';
$string['name'] = 'Name';
$string['intro'] = 'Intro';
$string['moduleid'] = 'Module ID';
$string['optimalassign'] = 'Optimal assignment';
$string['names'] = 'Names';
$string['introscost'] = 'Intros cost matrix';
$string['intros'] = 'Intros';
$string['parascost'] = 'Paragraphs cost matrix';
$string['sentscost'] = 'Sentences cost matrix';
$string['adminheader'] = 'Options for LORD block';
$string['showblocklabel'] = 'Show the LORD block?';
$string['showblockdesc'] = 'Show the Learning Object Relation Discovery (LORD) block to teachers?';
$string['startlabel'] = 'Start the relation discovery process?';
$string['startdesc'] = 'Start the learning object relation discovery process.';
$string['startdiscovery'] = 'Start or resume the learning object relation discovery process?';
$string['startdiscoverylabel'] = 'Checked box will start or resume the relation discovery process.';
$string['stopwords'] = 'a about an are as at be by com de en for from how i in is it la of on or that the this to und was what when where who will with www';
$string['privacy:metadata:block_lord_comparisons'] = 'Table to store learning object comparison data.';
$string['privacy:metadata:block_lord:courseid'] = 'Course id value.';
$string['privacy:metadata:block_lord:module1'] = 'Module id value for first module in comparison.';
$string['privacy:metadata:block_lord:module2'] = 'Module id value for second module in comparison.';
$string['privacy:metadata:block_lord:compared'] = 'An string identifying what was compared.';
$string['privacy:metadata:block_lord:value'] = 'The similarity value between learning objects.';
$string['privacy:metadata:block_lord:matrix'] = 'The similarity matrix for this comparison.';
$string['privacy:metadata:block_lord_modules'] = 'Table for storing the course module data.';
$string['privacy:metadata:block_lord:module'] = 'The module id value.';
$string['privacy:metadata:block_lord:name'] = 'The name of the module.';
$string['privacy:metadata:block_lord:intro'] = 'The introduction for the module.';
$string['privacy:metadata:block_lord_max_words'] = 'Table for storing plugin related options.';
$string['privacy:metadata:block_lord:dodiscovery'] = 'Flag for turning discovery process on and off.';
$string['privacy:metadata:block_lord:maxlength'] = 'Maximum number of words to allow in a sentence.';
$string['privacy:metadata:block_lord:maxsentence'] = 'Maximum number of sentences to compare.';
$string['privacy:metadata:block_lord:maxparas'] = 'Maximum number of paragraphs to compare.';
$string['privacy:metadata:block_lord:nameweight'] = 'Weight for name comparison.';
$string['privacy:metadata:block_lord:introweight'] = 'Weight for introduction comparisons.';
$string['privacy:metadata:block_lord:sentenceweight'] = 'Weight for paragraph comparisons.';
$string['privacy:metadata:block_lord_coords'] = 'Table for storing module coordinate data.';
$string['privacy:metadata:block_lord:userid'] = 'User id value.';
$string['privacy:metadata:block_lord:changed'] = 'The time the graph was changed and saved.';
$string['privacy:metadata:block_lord:moduleid'] = 'The module id value.';
$string['privacy:metadata:block_lord:xcoord'] = 'The x coordinate value.';
$string['privacy:metadata:block_lord:ycoord'] = 'The y coordinate value.';
$string['privacy:metadata:block_lord:visible'] = 'Flag to show or not the node.';
$string['privacy:metadata:block_lord_scales'] = 'Table for storing graph scaling data.';
$string['privacy:metadata:block_lord:coordsid'] = 'The graph configuration id value.';
$string['privacy:metadata:block_lord:scale'] = 'The graph scaling value.';
$string['privacy:metadata:block_lord_paragraph'] = 'Table for storing module paragraph data.';
$string['privacy:metadata:block_lord:paragraph'] = 'The paragraph id value.';
$string['privacy:metadata:block_lord:content'] = 'The paragraph text content.';
$string['privacy:metadata:block_lord_dictionary'] = 'Table for storing words and their Wordnet status.';
$string['privacy:metadata:block_lord:word'] = 'The word.';
$string['privacy:metadata:block_lord:status'] = 'The status of the word.';
