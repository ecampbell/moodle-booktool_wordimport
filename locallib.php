<?php
// This file is part of Moodle - http://moodle.org/
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Import/Export Microsoft Word files library.
 *
 * @package    booktool_wordimport
 * @copyright  2016 Eoin Campbell
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/mod/book/lib.php');
require_once($CFG->dirroot.'/mod/book/locallib.php');
require_once($CFG->dirroot.'/mod/book/tool/importhtml/locallib.php');

use \booktool_wordimport\wordconverter;
use core_files\archive_writer;

/**
 * Import HTML pages from a Word file
 *
 * @param string $wordfilename Word file to be imported
 * @param stdClass $book Book being imported into
 * @param context_module $context
 * @param bool $splitonsubheadings Split book into chapters and subchapters
 * @param bool $verbose Print extra progress messages
 * @param string $convertformat Bootstrap, Daylight (Brightspace), or none
 * @return void
 */
function booktool_wordimport_import(string $wordfilename, stdClass $book, context_module $context,
                bool $splitonsubheadings = false, bool $verbose = false, string $convertformat = 'convert2bootstrap') {
    global $CFG;

    // Convert the Word file content into XHTML and an array of images.
    $imagesforzipping = array();
    $word2xml = new wordconverter('booktool_wordimport', $convertformat);
    $htmlcontent = $word2xml->import($wordfilename, $imagesforzipping);

    // Store images in a Zip file and split the HTML file into sections.
    // Add the sections to the Zip file and store it in Moodles' file storage area.
    $zipfilename = tempnam($CFG->tempdir, "zip");
    $zipfile = $word2xml->zip_imported_images($zipfilename, $imagesforzipping);
    $word2xml->split_html($htmlcontent, $zipfile, $splitonsubheadings, $verbose);
    $zipfile = $word2xml->store_html($zipfilename, $zipfile, $context);
    unlink($zipfilename);

    // Call the core HTML import function to really import the content.
    // Argument 2, value 2 = Each HTML file represents 1 chapter.
    toolbook_importhtml_import_chapters($zipfile, 2, $book, $context);
}

/**
 * Export Book chapters to a Word file
 *
 * @param stdClass $book Book to export
 * @param context_module $context Current course context
 * @param int $chapterid The chapter to export (optional)
 * @return string
 */
function booktool_wordimport_export(stdClass $book, context_module $context, int $chapterid = 0) {
    global $DB;

    // Export a single chapter or the whole book into Word.
    $allchapters = array();
    $booktext = '';
    $word2xml = new wordconverter();
    if ($chapterid == 0) {
        $allchapters = $DB->get_records('book_chapters', array('bookid' => $book->id), 'pagenum');
        // Read the title and introduction into a string, embedding images.
        $booktext .= '<p class="MsoTitle">' . $book->name . "</p>\n";
        // Grab the images, convert any GIFs to PNG, and return the list of converted images.
        $giffilenames = array();
        $imagestring = $word2xml->base64_images($context->id, 'mod_book', 'intro', $giffilenames, null);

        $introcontent = $book->intro;
        if (count($giffilenames) > 0) {
            $introcontent = str_replace($giffilenames['gif'], $giffilenames['png'], $introcontent);
        }
        $booktext .= '<div class="chapter" id="intro">' . $introcontent . $imagestring . "</div>\n";
    } else {
        $allchapters[0] = $DB->get_record('book_chapters', array('bookid' => $book->id, 'id' => $chapterid), '*', MUST_EXIST);
    }

    // Append all the chapters to the end of the string, again embedding images.
    foreach ($allchapters as $chapter) {
        // Make sure the chapter is visible to the current user.
        if (!$chapter->hidden || has_capability('mod/book:viewhiddenchapters', $context)) {
            $booktext .= '<div class="chapter" id="' . $chapter->id . '">';
            // Check if the chapter title is duplicated inside the content, and include it if not.
            if (!$chapter->subchapter && !strpos($chapter->content, "<h1")) {
                $booktext .= "<h1>" . $chapter->title . "</h1>\n";
            } else if ($chapter->subchapter && !strpos($chapter->content, "<h2")) {
                $booktext .= "<h2>" . $chapter->title . "</h2>\n";
            }

            // Grab the images, convert any GIFs to PNG, and return the list of converted images.
            $giffilenames = array();
            $imagestring = $word2xml->base64_images($context->id, 'mod_book', 'chapter', $giffilenames, $chapter->id);

            // Grab the chapter text content, and update any GIF image names to the new PNG name.
            $chaptercontent = $chapter->content;
            if (count($giffilenames) > 0) {
                $chaptercontent = str_replace($giffilenames['gif'], $giffilenames['png'], $chaptercontent);
            }
            $booktext .= $chaptercontent . $imagestring . "</div>\n";
        }
    }
    $moodlelabels = "<moodlelabels></moodlelabels>\n";

    // Convert the XHTML string into a Word-compatible version, with image data embedded in Word 365-compatible way.
    $booktext = $word2xml->export($booktext, 'booktool_wordimport', $moodlelabels, 'embedded');
    return $booktext;
}

/**
 * Export Book or chapter to a set of HTML files and images in a Zip file
 *
 * @param stdClass $book Book to export
 * @param context_module $context Current course context
 * @param int $chapterid The chapter to export (optional)
 * @return \ZipArchive Stored Zip file 
 */
function booktool_wordimport_exporthtml(stdClass $book, context_module $context, int $chapterid = 0) {
    global $CFG, $DB, $COURSE;
    $filetemplate = __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'xhtmfiletemplate.html';
    $xsltstylesheet = __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'export2xhtml.xsl';

    // Check the HTML template exists, and read it into a string.
    if (!file_exists($filetemplate)) {
        throw new \moodle_exception(get_string('templateunavailable', 'booktool_wordimport', $filetemplate));
        return null;
    } else {
        $htmltemplate = "<htmltemplate>\n" . file_get_contents($filetemplate) . "\n</htmltemplate>\n";
    }

    // Create a Zip file name to store the book content.
    $filenameparts = [
        $COURSE->shortname,
        $book->name,
        date("Ymd-His"),
    ];
    $zipfilename = $CFG->tempdir . clean_filename(implode('-', $filenameparts). '.zip');
    $zipfile = new \ZipArchive();
    unlink($zipfilename);
    if (!($zipfile->open($zipfilename, ZipArchive::CREATE))) {
        // Cannot open zip file.
        throw new \moodle_exception('cannotopenzip', 'error');
    }
    $zipfile->addEmptyDir('images');
    
    // Export a single chapter or the whole book into HTML.
    $allchapters = array();
    if ($chapterid == 0) {
        $allchapters = $DB->get_records('book_chapters', array('bookid' => $book->id), 'pagenum');
    } else {
        $allchapters[0] = $DB->get_record('book_chapters', array('bookid' => $book->id, 'id' => $chapterid), '*', MUST_EXIST);
    }

    $chaptertext = '';
    $word2xml = new wordconverter();

    // Export each chapter into a separate HTML file.
    foreach ($allchapters as $chapter) {
        // Make sure the chapter is visible to the current user.
        if (!$chapter->hidden || has_capability('mod/book:viewhiddenchapters', $context)) {
            $chaptertext .= '<div class="chapter" id="' . $chapter->id . '">\n';
            // Check if the chapter title is duplicated inside the content, and include it if not.
            if (!$chapter->subchapter && !strpos($chapter->content, "<h1")) {
                $chaptertext .= "<h1>" . $chapter->title . "</h1>\n";
            } else if ($chapter->subchapter && !strpos($chapter->content, "<h2")) {
                $chaptertext .= "<h2>" . $chapter->title . "</h2>\n";
            }
            $chaptertext .= $chapter->content . '\n</div>\n';
            
            // Add the HTML and images from this chapter into the Zip file.
            $zipfile->addFromString('chap' . $chapter->id . '.htm', $chaptertext);
            $word2xml->zip_chapter_images($context->id, 'mod_book', 'chapter', $zipfile, $chapter->id);

            // Assemble the chapter contents and the HTML template into a single XML file for easier XSLT processing.
            $chaptertext = "<container>\n<chaptertext><html xmlns='http://www.w3.org/1999/xhtml'><body>" .
                $chapter->content . "</body></html></chaptertext>\n" . $htmltemplate . "</container>";

            // Convert the XHTML string into standard output, with suitable links.
            // $chaptertext = $word2xml->xsltransform($chaptertext, $xsltstylesheet);
            // $zipfile->addFromString('chapter' . $chapter->id . '.htm', $chaptertext);
        }
    }
    return $zipfile;
}



/**
 * Delete previously unzipped Word file
 *
 * @param context_module $context
 */
function booktool_wordimport_delete_files($context) {
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_book', 'wordimporttemp', 0);
}
