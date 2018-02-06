# Widgets block

This block displays widgets selected from the available list by each user.

The available widgets are found in local/widgets/type.

## Creating additional widgets

local/widgets/type/helloworld is a simple example of what a widget can contain.

Each widget must have a name consisting only of lowercase letters - I will refer to this as [widgetname]. The component name for the widget is 'widgettype_[widgetname]'.

Each widget must have the following files:
* local/widgets/type/[widgetname]/version.php - standard Moodle version file.
* local/widgets/type/[widgetname]/db/access.php - define the capabilities that your widget will use, at a minimum you should define 'widgettype/[widgetname]:use' (unless you want to override the 'can_use()' function to use a different check).
* local/widgets/type/[widgetname]/lang/en/widgettype_[widgetname].php - as a minimum, this should define 'pluginname' and '[widgetname]:use' strings (assuming you are using the recommended capability definition in access.php).
* local/widgets/type/[widgetname]/classes/[widgetname].php - this should contain the class definition for the main part of your widget (see below for more details).
* local/widgets/type/[widgetname]/styles.css - (optional) custom CSS for the widget, use the '.widgettype-[widgetname]' selector to target only this widget.
* local/widgets/type/[widgetname]/db - (optional) can contain standard install.xml, install.php, upgrade.php files, if needed (plus any of the other files that can go here in a standard Moodle plugin)
* local/widgets/type/[widgetname]/settings.php - this is **not** currently supported - support can be added if there turns out to be a use case for it

The main class for your widget must be named after the widget and stored in the classes directory (as detailed above), starting in the namespace 'widgettype_[widgetname]'.

The class must extend '\block_widgets\widgettype_base'.

The class will contain the following functions:
* get_title_internal() - return a string to display as the widget title
* get_items() - return an array of strings containing the HTML to output for each item in the widget
* get_footer() - (optional) return a string containing the HTML to output in the footer of the widget
* get_extra_css_classes() - (optional) return an array of CSS classes to add to the outer element of the widget
* can_use() - (optional) override the standard capability check to see if the current user can add/view the widget (defaults to checking for 'widgettype/[widgetname]:use' in the system context)
