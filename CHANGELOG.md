## CommandCollection ##

(c) 2013 phpManufaktur by Ralf Hertsch

MIT License (MIT) - <http://www.opensource.org/licenses/MIT>

kitFramework - <https://kit2.phpmanufaktur.de>

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
