## CommandCollection ##

(c) 2013 phpManufaktur by Ralf Hertsch

MIT License (MIT) - <http://www.opensource.org/licenses/MIT>

kitFramework - <https://kit2.phpmanufaktur.de>

**0.40** - 2014-09-14

* updated `@link` references
* added URL for the changelog in the CMS Tool

**0.39** - 2014-09-08

* Comments: corrected iFrame height if reCaptcha is active
* Comments: removed not needed `return false;` (causes problems in some browsers)
* Comments: missing translation command and assigned translation

**0.38** - 2014-09-03

* not SET DEFAULT for TEXT fields! (table collection_comments)

**0.37** - 2014-08-11

* improved translation handling, added support for i18nEditor
* generate extended debug information if the form submission is not valid 

**0.36** - 2014-07-16

* changed SQL query to count comments
* add administrative links to reject, remove and confirm already published comments

**0.35** - 2014-06-11

* changed `robots` directive for the kitCommand `rating`
* fixed problem if iframe is opened external and target is a flexContent permanent link
* changed formatting for Comments container
* fixed a problem creating the parameter PID - conflict with the new created parameter array (?!)

**0.34** - 2014-05-07

* all language files are now loaded by the BASIC extension

**0.33** - 2014-03-10

* changed import for the old FeedbackModule to usage of class `Alert`
* added missing translations for tag `COMMENTS`
* add template name as comment to support designers
* added missing german translations to multiple email templates
* For comments to EVENT redirect now to the permalink of the EVENT ID
* add table `collection_comments_passed` and function to pass comments to a mapped identifier

**0.32** - 2014-02-03

* added support for `FLEXCONTENT_CATEGORY` and `FLEXCONTET_FAQ`

**0.31** - 2014-01-24

* Comments support now also flexContent
* changed Comments templates to Bootstrap 3

**0.30** - 2014-01-13

* general disable the page tracking for the rating iFrames!
* changed the JSON handling for ExcelRead search integration

**0.29** - 2013-12-01

* add function `countComments()`
* simplified access to `Contact Control`
* changed description of CommandCollection

**0.28** - 2013-11-27

* bugfix: update does not initialize RAL table

**0.27** - 2013-11-27

* added kitCommand `~~ ral ~~` to the collection

**0.26** - 2013-11-08

* changed license information and handling in `extension.json`

**0.25** - 2013-11-07

* initializing of Comments was not every time complete
* fixed a typo in Rating hover

**0.24** - 2013-11-04

* added support for additional vendor information

**0.23** - 2013-10-30

* hide the iframe of *Comments* if the magic `EVENT_ID` is missing (possible if Event answers to response actions)

**0.22** - 2013-10-24

* the comment ID and the TYPE can now also submitted as CMS URL GET parameter, use ?comment=ID&type=EVENT (for example)

**0.21** - 2013-10-18

* fixed a typo which suppress the correct CSS classes generation for the table cells in *ExcelRead*
* increased the default editor height in *Comments* from `100px` to `150px`

**0.20** - 2013-10-14

* introduce "magic identifiers": `POST_ID`, `TOPIC_ID`, `EVENT_ID` for *Comments* (see help for more information) 
* Introduce the standard template 'white' with a white background, the 'default' template now uses a transparent background

**0.19** - 2013-10-08

* added import for the feedbacks of FeedbackModule into *Comments*, use `/kit2/admin/comments/import/feedbackmodule` to start the import

**0.18** - 2013-10-07

* fixed: [issue #1](https://github.com/phpManufaktur/kfCommandCollection/issues/1), added an extra day to dates ...
* added: parameter `format[date]` and `format[date()]`, extended default template

**0.17** - 2013-10-06

* added search function to the kitCommand `ExcelRead`

**0.16** - 2013-10-04

* extended `ExcelRead` with formatting parameter

**0.15** - 2013-09-25

* completed the kitCommand `Rating`

**0.14** - 2013-09-23

* prepared for beta test

**0.12** - 2013-09-16

* added kitCommand `Rating`

**0.11** - 2013-09-12

* added kitCommand `ExcelRead`

**0.10** - 2013-08-23

* first beta release
