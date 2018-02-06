==== customscipts =====
<code>
	$CFG->customscripts = $CFG->wwwroot/local/landingpages/customscripts';
</code>
Enabling this will allow custom scripts to replace existing moodle scripts.
For example: if $CFG->customscripts/course/view.php exists then
it will be used instead of $CFG->wwwroot/course/view.php
At present this will only work for files that include config.php and are called
as part of the url (index.php is implied).
Some examples are:
      http://my.moodle.site/course/view.php
      http://my.moodle.site/index.php
     http://my.moodle.site/admin            (index.php implied)
 Custom scripts should not include config.php
Specify the full directory path to the custom scripts


