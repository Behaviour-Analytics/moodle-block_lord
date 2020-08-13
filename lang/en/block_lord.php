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
$string['custombutton'] = 'Use custom';
$string['systembutton'] = 'Use generated';
$string['resetbutton'] = 'Regenerate graph';
$string['graphsaved'] = 'Graph saved';
$string['systemgraph'] = 'System generated graph';
$string['usergraph'] = 'User generated graph';
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
$string['documentation'] = 'Documentation';
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
$string['docs:whatis'] = 'What is Learning Object Relation Discovery (LORD)?';
$string['docs:whatis:desc1'] = 'The Learning Object Relation Discovery (LORD) plugin is designed to discover the relationships between learning objects. This is done by first extracting the text content of each learning module in the course. The text content is then broken down into sentences so that each sentence can be compared with each other sentence. The comparison is done using a Web service (https://ws-nlp.vipresearch.ca/) built for this purpose. The Web service relies on WordNet (https://wordnet.princeton.edu/) to determine the similarity between words in the sentences, and as such, the plugin is currently limited to use with the English language. A final similarity value is assigned between each pair of learning objects, which is then used as a distance measure between the nodes in a network graph, where the graph nodes represent the learning modules. This process is designed to generate a graph configuration where similar nodes are grouped together. The generated graph can then be used with the Behaviour Analytics plugin, which uses the same learning object network graph for analyzing student interaction with the course material.';
$string['docs:howto'] = 'How to use the LORD plugin.';
$string['docs:howto:desc1'] = 'The plugin will not start the relation discovery process by default, so the plugin must be turned on after installation. This is done as Administrator from the plugin\'s global setting Site administration -> Plugins -> Plugins overview -> Settings (for LORD). But, this only turns the plugin on globally and for each course the plugin is installed in, the plugin must also be turned on there. The block itself has a link to its instance settings, the first of which is to start and stop the discovery process for that course.';
$string['docs:howto:desc2'] = 'With the discovery process activated both globally and locally, the extraction of learning content and the sentence by sentence comparison are done in the background through the Moodle schedule task interface. The comparison process can be quite lengthy and time consuming, so it is run in the background. The block shows a progress table listing the number of learning objects in the course, the number of connections between learning objects, and the number of connection analyzed so far.';
$string['docs:howto:desc3'] = 'Even before the relation discovery process is complete, it is possible to view the network graph of the learning objects. Until the discovery process completes, however, the similarity between nodes is not known, so the graph is not fully configured. When viewing the graph, there is a save button to save the current configuration and 3 slider controls that determine the minimum and maximum distances between nodes, as well as the scaling factor used in converting the similarity score to a distance. These 3 sliders, together with the graphing physics, control how the graph looks. It is also possible to rearrange the graph by dragging nodes around with the mouse.';
$string['docs:howto:desc4'] = 'Left-clicking on a graph node will show the data for the associated learning object. Right-clicking on another node will show the data for both nodes and the similarity between the 2 learning objects. The similarity is shown as a final value at the top of the data table with further data about the similarity following in the rest of the table. Each similarity calculation is shown for the words between each sentence, as well as the calculations between sentences and paragraphs. There can be quite a bit of data shown, but there are 2 basic types of tables shown. The first is a matrix of words from one sentence against the words in another sentence, where the intersection represents the similarity between words. The optimal assignment of values is used to determine the similarity between sentences. The second is a cost matrix that shows similarity values between sentences and paragraph instead of words. Again, the optimal assignment of values determines the similarity between sentences and between paragraphs.';
$string['docs:settings'] = 'How to configure the LORD plugin.';
$string['docs:settings:desc1'] = 'The LORD block has a "Settings" link, which shows the local settings for the plugin. The first is to start and stop the relation discovery process. The next three settings control the maximum number if paragraphs to compare between learning objects, the maximum number of sentences to compare from each paragraph, and the maximum number of words to allow in a sentence. The Web service used will not compare two sentences for more than 2 minutes, so excessively long sentences could cause a timeout and a 0 similarity value. There are also options for 3 weights, which are used in calculation the final similarity value. The learning object\'s content is broken down into names, introductions, and paragraphs (or all other content). Each of these can have a separate weight applied to it during the final calculation, which can give the names more significance than the rest of the content, or less significance.';
$string['docs:settings:desc2'] = 'There are 2 reset options available. The first will reset all the comparison values, which means that the relation discovery process will have to start all over again. This may be useful if the similarities were calculated using a large maximum words in a sentence value that produced too many 0 similarities. The other reset options will reset the comparisons and learning content data. This may be useful if the learning content changed after the discovery process was run. No reset is necessary when maximum sentence and paragraph values are changed, but increasing either value will require more sentences to be compared, which will take some time for the background task. Nor is a reset necessary if learning objects are added to or removed from the course, as the plugin is designed to find new learning objects and delete data for removed modules.';
$string['docs:settings:desc3'] = 'The last section of the settings page gives to option of adding and removing stop words from the dictionary. Stop words are removed from the sentences before using the Web server and are not considered in the similarity calculation. There is a default set of stop words, but these can be augmented with others or removed, depending on user preference. The dictionary is global and used in all courses where the plugin is used, so changing the dictionary in one course will affect all other courses.';
$string['docs:settings:desc4'] = 'The background task is set to run every 5 minutes by default, which helps the discovery process complete more quickly. Once completed, however, this schedule is no longer needed and can be changed by from Site administration -> Server -> Scheduled tasks. Alternatively, the discovery process can be turned off for the courses in which it is no longer needed, thereby allowing other courses to use the frequent schedule without introducing unnecessary computation to the courses that are complete.';
