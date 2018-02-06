<?php
	require_once(__DIR__.'/../../config.php');
	class filter_replace extends moodle_text_filter {
		public function filter($text, array $options = array()) {
	        global $CFG;

# first simple search and replace for 
# INC0132141
$text = str_replace( $CFG->filter_string_search, $CFG->filter_string_replace, $text ) ;

$text = preg_replace( $CFG->filter_regexp_search, $CFG->filter_regexp_replace, $text ) ;

return $text ;

		}
	}
?>
