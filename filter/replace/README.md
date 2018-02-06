# Moodle Filter Replace
Allows for searching and replacing of strings via an HTML filter on moodle output

## Notes
This filter is a simple way to replace strings on outpu from Moodle. Please note that this does have an impact on production as there is additional processing needed for the output to be parsed.

## Installation 
<ol>
<li>Add the following to the config.php file (moodle/confing.php) and replace the strings as needed
<pre>
//Filter filter_replace string search and replace
$CFG->filter_string_search = 'trumpets';
$CFG->filter_string_replace = 'bananas';
</pre>
</li>
<li>Go to Site administration ▶ Plugins ▶ Filters ▶ Common filter settings and set Text cache lifetime to 0 ("No") while you do development.</li>
<li>Place the filter_replace directory under the filter directory (i.e. moodle/filter/filter_replace)</li>
<li>Direct a browser to /admin/filters.php </li>
<li>Find the filter "Filter Replace" and enable this, and set it to Apply to 'Contenet and headings'</li>
<li>Test the site</li>
</ol>

## Bugs
<p>Often most problems can be dealt with by clearing the cache (i.e. purge all caches)</p>

## References
https://docs.moodle.org/dev/Filters
