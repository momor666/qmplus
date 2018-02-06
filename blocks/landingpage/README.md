# Instructions for use

* The following code should be added to the site theme's lib.php:

```
function theme_THEMENAME_page_init(moodle_page $page) {
    global $USER;

    if ($page->pagelayout == 'mydashboard') {
        $redir = \block_landingpage\landingpage::instance()->get_landing_page($USER);
        if ($redir) {
            redirect($redir);
        }
    }
}
```
(replacing 'THEMENAME' with the name of the theme).

* The default page should be set to a sensible URL to go, if the user is not a member of any school (Site admin > Plugins > Blocks > Landing page). If the default is blank, then users will be directed to the site homepage instead (with ?redirect=0, to prevent looping back to the Dashboard page again).
* Each of the school landing page courses should have their 'ID number' set and that ID number should be added to the list in the user profile field 'landingpage'.
* To find which school a user is a member of, a list of all categories in which they have course enrolments is generated. For each of those categories, a search is made up the category tree until a course is found, that matches one of the ID numbers listed in the 'landingpage' user profile field values.
* If there is one school, the user will be redirected from the 'My dashboard' page to their school landing page.
* If there is no school, the user will be redirected to the default landing page.
* If there is more than one school, the user will be offered a choice of landing pages - this choice will be saved until it is updated (it will not change automatically if the user leaves the school).
* Adding this block anywhere on the site will allow users in more than one school to switch their landing page - users with one (or less) schools, will not see the block.