=== Plugin Name ===
Contributors: elementlms, pmenard, jtsternberg
Donate link: http://www.elementlms.com
Tags: sensei, lms, elearning, elementlms, online courses
Requires at least: 5.3
Tested up to: 5.8.1
Stable tag: 2.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Lesson Timer for Sensei - a Sensei LMS plugin that adds a countdown timer to the lesson, forcing the learner to stay in the lesson until time expires.

== Description ==

Need a way to ensure that a student spends the requisite time on a lesson? Element LMS's Lesson Timer <i>for Sensei</i> solves this problem by providing a visual countdown timer and disabling the "Complete Lesson" button until the countdown has hit zero.

FEATURES:
* Set a required time for each lesson in increments of one minute.
* Optional warning message if learner leaves the lesson.
* Auto-complete the lesson when the timer reaches zero.
* Pause the lesson time when the browser is not in view.
* Disable lesson timer by role.
* Customize timer look and feel through css.

Lesson Timer <i>for Sensei</i> has been tested with WooTheme-Sensei and WooThemes Sensei-module, and the latest versions of Chrome, IE, Safari, Firefox and Opera.

== Installation ==

1. Unzip sensei_lesson_timer.zip.
2. Upload the sensei_lesson_timer folder (the folder itself, NOT it's contents) to the /wp-content/plugins directory.
3. Activate the plugin through the Admin Controls under Plugins > Installed Plugins.

== Frequently Asked Questions ==

= How do I set the time for the countdown timer within a lesson? =

When you edit a lesson, set the lesson time in Lesson Information.

= Can I set a value of less then one minute?  =

The current version supports 1 minute increments.

== Screenshots ==

1. Lesson Timer embedded in lesson page. Timer sits below the Complete Lesson button.
2. Popup message within a lesson page. When a student learner tries to leave the lesson page, there is a popup message. The Timer can be customized through css.
3. Admin Timer message. Under Sensei->Settings, at the center of the page, you will see a Warning message field. If provided, a warning message will display when a student learner tries to click away from the lesson page prior to timer reaching zero. 

== Changelog ==

= 2.0.2 =
 * Update Tested version up to Wordpress 5.8.1

= 2.0.1 =
 * Allow configuration of the timer display size

= 2.0.0 =
 * Added support to Sensei 3.x - Fix broken Timer Settings

= 1.2.1 =
 * Updated Readme.txt description and features list.
 * Fixed the screenshots not appearing in Wordpress.org site.
 * Added message about Element LMS.

= 1.2.0 =
 * Fix timer for lessons with quizzes. Now, timer will not allow quiz to be taken until time runs out.

= 1.1.3 =
 * Update to work with most recent version of Sensei, 1.9.5.
 * Fix missing post-type selector setting, which broke with new version of Sensei.
 * Fix missing user-role selector setting.
 * Fix form button element javascript selector, which also broke with new version of Sensei.
 * Fix a few bad variable names to clean up notices in the debug log.
 * Fix issue with plugin not working when installing plugin from Github (https://github.com/Automattic/sensei).

= 1.1.2 =
 * Improved logic to disable click on 'Complete Lesson' and 'View the lesson quiz' button while the timer is active.
 * Removed previous logic to imply Quizzes were supported. This is being rewritten and will be added in a later release.
 * Added / Improved logic to prevent timer from showing when users has already completed Lesson.
 * Added / Improved logic to prevent timer from showing when users has not registered for Course.
 * Rewrote logic to determine post types to use with timer. Previous logic loading all registered post_types and really only needed to check if the Sensei lesson post type is registered.
 * Add check to ensure Sensei plugin is installed and active before Sensei Lesson Timer plugin initialization.
 * Reduced the timer placement options via Settings. This can still be managed via a filter.
 * Removed styling previously applied by default to the timer. Styling can now be done within the theme.
 * Removed the many individual settings filters with single filter when the front-end page loads. The filter 'sensei_lesson_timer_settings' passes the complete array of settings which will be used for the timer display. This will make it easier to override the option by knowing all the settings at once. As a second argument to the filter is the current queried object. Included in this array is a single item 'show_timer' set to true. If returned value of false the timer will not be shown.
 * Also as part of the settings filter array are three new items 'form_element_cursor' CSS cursor value added to the lesson submit button. Default is 'not-allowed'.  'form_element_title' if not empty will add a title element to the button. 'form_element_class' if not empty well add this custom class to the button. Not the timer already adds the class 'slt-active-timer' to the button.
 * Cleanup unused and commented out code
 * Added proper documentation block on all functions
 * Rework plugin initialization to not use global variable.

= 1.1.1 =
* Restructured plugin code to be more in line with WordPress plugin coding standards. Moved inline JavaScript and CSS to external files loaded properly through wp_enqueue_script and wp_enqueue_style.
* Added support for i18n translations. see /languages directory content.
* Moved plugin settings to be within Sensei > Sensei Settings. Look for new tab 'Lesson Timer'.
* Correct timer displays to include leading zero digits.
* Added settings option to auto-submit Lesson form when timer reaches zero.
* Added settings option to control where the timer digits are displayed in relation to lesson complete button. Options are Outside Right (default), Outside Left, Hide Button, Add timer to button text, replace button text wth timer digits.
* Added WPML Translation configuration file wpml-config.xml to support translation of text and values via WPML

= 1.1 =
* Added setting (Settings > Reading) to show warning message to user if they attempt to leave page with an active time. (paul@codehooligans.com)

= 1.0 =
* First release version.

== About ElementLMS ==
Element LMS is a Silver WooExpert with a focus on using Wordpress to deliver online learning. Using Sensei and the Element suite of plugins, we transform Wordpress into a SAAS online learning environment. We host and support our Learning Management System (LMS) for clients. For more information about Element LMS and our work, please goto <a href="http://www.elementlms.com" target="_blank">http://www.elementlms.com</a>
