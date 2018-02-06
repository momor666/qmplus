
QMUL Moodle Theme
=======

This guide will take you through the features of your new theme and how to configure it.


Getting started
---------------

All the configurable options of this theme are available through Moodle's theme settings menus.
<br />You can access these in the administration block of moodle:

	* Site Administration
		* Appearance
  			* Themes
  				* Your Theme Name
  					* General Settings
  					* Frontpage Content
  					* User Alerts
  					* Google Analytics
  					* News Settings

Settings
---------------

Here, we'll now go through each settings page and explain in detail each setting and it's effect on your Moodle site.

### General Settings

This is where most of the configuration that will affect your site on a global level will happen.

###### Logo

```italic
theme_qmul | logo
```

You have the option to upload a new logo here.
Any image you upload will replace the default logo for your theme at a site leve

	Any standard image format (jpg, gif, png) will work correctly.
	Dimensions are limited to 350 x 64 - but any size will work correctly and be resized

###### Login Page Background

```italic
theme_qmul | loginbackground
```

You have the option to upload an image here that will be used as a background for your sites login pages.

	Any standard image format (jpg, gif, png) will work correctly.
	The image will be resized to fit the window, but ideally this should be a high resolution image.

###### Drawer Menus

```italic
theme_qmul | drawermenus
```

This option changes **most** _(but not all)_ of the modal windows in moodle into drawer-like menus, which function better on mobile devices and provide a better user experience overall.
<br />This can provide a good experience for users who visit your site on a variety of devices

	Enabled by default

###### Dashboard Thumbnails

```italic
theme_qmul | coursebox
```

This option will enable the display of courses and stats on the standard /my/index.php page.

	Enabled by default

###### Sticky Table Headers

```italic
theme_qmul | stickytables
```

This option will stick the header of **most** _(but not all)_ tables in moodle to the top of the window once the user has begun to scroll.
<br />It is particularly helpful when scrolling through very long data tables in moodle.

	Enabled by default

###### User Avatars

```italic
theme_qmul | avatars
```

Another new feature for this theme is the ability to enable a selection of semi-random user avatars to replace the moodle default icon.
<br />These will only affect users who haven't uploaded their own avatar image.

	This feature is set to Abstract Patterns, but can be changed or turned off through the generic theme settings.

The four different options available are:

* Same image for every user (Moodle Default)
* Abstract Patterns
* People
* Animals
* Robots & Aliens

```warning
WARNING: The 'People' option uses a language algorithm to guess a users gender based on their first name.  If you feel this may negatively affect some of your users, please do not select this option.```

###### Show course contacts images

```italic
theme_qmul | showteacherimages
```

This option will enable the display of teacher images for courses at certain points throughout the site.

	Disabled by default

###### Full Screen Scorm Page

```italic
theme_qmul | fullscreenscorm
```

This option will remove all headers, sideblocks and footers from the SCORM player page, allowing the SCORM content to be viewed on the largest area of the screen possible

	Disabled by default

###### Copyright

```italic
theme_qmul | copyright
```

This allows you to add a copyright message to the footer of your site.

	The theme will automatically include the copyright symbol and this year, anything you enter will be appended to this

###### Footnote

```italic
theme_qmul | footnote
```

This allows you to add additional content to the footer of your site.
<br />The footnote setting is an HTML area and appears at the very bottom of the footer.
<br />Any HTML you enter here will be output at the bottom of every page

###### Custom CSS

```italic
theme_qmul | customcss
```

This allows you to add additional CSS to all pages of your site.
<br />The CSS setting is a plain text area and anything entered will be added on to the end of the standard theme CSS.
<br />This can allow you to make changes to colours, sizes and layouts of your site without having to make any changes to the theme code on your server

### Frontpage Content

This is where settings that affect the frontpage of your site are located.

###### Login Box Background

```italic
theme_qmul | loginbg
```

The image uploaded here will appear in the login box on the frontpage

###### Browse all Modules Background

```italic
theme_qmul | browsemodulesbg
```

The image uploaded here will appear in the browse all modules box on the frontpage

###### Help & Support Background

```italic
theme_qmul | helpsupportbg
```

The image uploaded here will appear in the help & support box on the frontpage

###### Help & Support Link

```italic
theme_qmul | helpsupportlink
```

The link provided here will be applied to the help & support box

###### QMplus Media Background

```italic
theme_qmul | qmplusmediabg
```

The image uploaded here will appear in the QMplus Media box on the frontpage

###### QMplus Media Link

```italic
theme_qmul | qmplusmedialink
```

The link provided here will be applied to the QMplus Media box

###### QMplus Hub Background

```italic
theme_qmul | qmplushubbg
```

The image uploaded here will appear in the QMplus Hub box on the frontpage

###### QMplus Hub Link

```italic
theme_qmul | qmplushublink
```

The link provided here will be applied to the QMplus Hub box

###### QMplus Archive Background

```italic
theme_qmul | qmplusarchivebg
```

The image uploaded here will appear in the QMplus Archive box on the frontpage

###### QMplus Archive Link

```italic
theme_qmul | qmplusarchivelink
```

The link provided here will be applied to the QMplus Archive box

### User Alerts

Your theme has the capability to display Alerts at the top of each page.
<br />These can be useful for communicating important news that you need every user to see.
<br />These alerts are dismissable and once dismissed will not reappear until the user logs in again.
<br />For each of the 3 alerts there are 4 settings:

###### Enable Alert

```italic
theme_qmul | enable{number}alert
```

This option turns on the display of the alert.

	Disabled by default

###### Level

```italic
theme_qmul | alert{number}type
```

The 'Level' of the alert determines the colour of the alert box, based on your themes colour palette.

```
There are 3 levels
* Information (Blue)
* Warning (Orange)
* Annoucement (Green)
```

	Set to 'information' by default

###### Title

```italic
theme_qmul | alert{number}title
```

This title will appear at the top of the alert box, and should be used as a short subject for the alert.

	Empty by default

###### Alert Text

```italic
theme_qmul | alert{number}text
```

This is the main body text of the alert message

	Empty by default

### Google Analytics

This is where you can enable Google Analytics for your theme.

###### Enable Google Analytics

```italic
theme_qmul | useanalytics
```

This option enabled the output of the Google Analytics tracking code on your theme pages.

	Disabled by default

###### Enable Google Analytics

```italic
theme_qmul | analyticsid
```

Here you need to enter your organisations Google Analytics tracking ID.  This will be supplied in your Google Analytics account

	Empty by default

###### Send Clean URLs

```italic
theme_qmul | analyticsclean
```

Rather than standard Moodle URLs the theme will send out clean URLs making it easier to identify the page and provide advanced reporting.
<br />More information on using this feature and its uses can be found [here](http://www.somerandomthoughts.com/blog/2012/04/18/ireland-uk-moodlemoot-analytics-to-the-front/).

	Empty by default

### News Settings

This is where you can set the rss feeds for the news ticker for various sections.

Courses
---------------

To make the most out of your theme, there are some configuration and content changes you can make to your courses.

### Course Images

Across your theme, there are pages that will make use of course imagery where it is available.
<br /> This can make parts of your Moodle more visually interesting and help to engage your users. It can also help to give each course it's own unique identity in a way.

To use course images, you need to upload an image to the 'Course overview Files' setting for each course.

	This image can be in any image format, as long as it is contained in Moodle's core 'courseoverviewfilesext' setting.
	Ideally this should be a high-resolution image.

### Landing Page Format

For the Landing page format, the course sections will automatically arrange themselves into a grid from left to right, but also filling up empty space depending on content.

Whilst editing a course, the space will not be filled in order to make it simpler to organise your content.

###### Section Options

For each section you have several additional settings on the edit screen.

<b>Style</b><br />
You can select here to have the option to choose between 'Standard Display', 'Background Fill' & 'Image Header' for the section style.  Background fill will mean the background of the section will be set to the main theme colour.  Image header will display an image at the top of the section, with optional content below.

<b>Section Image</b><br />
This is where you can upload an image for the section to be used in conjunction with the 'Image Header' style option.  The image will automatically resize to fit with the section width.

<b>Show on mobile?</b><br />
You can choose here to not show certain sections on mobile devices, and they will only appear on larger screens.

### Course Styling

There are several styles still available for use with content.

###### Bootstrap Components

The theme is built on Bootstrap 4, and so there are a large number of bootstrap components available for use within your content.<br />
<b><i>N.B.  - Some of the more advanced components will behave slightly differently within Moodle, and some may not function at all.</i></b>

You can find a detailed list of these components and how to use them here: <a target="_blank" href="https://v4-alpha.getbootstrap.com/components">Bootstrap Components</a>

###### Styling Classes

The following styles are available in the theme for your course content:

<b>Arrow List</b>
<div class="course-content">
<ul class="arrows">
<li>List item 1</li>
<li>List item 2</li>
<li>List item 3</li>
<li>Longer list item with a <a href="#">Link</a></li>
</ul>
</div>

	<ul class="arrows">
		<li>List item 1</li>
		<li>List item 2</li>
		<li>List item 3</li>
		<li>Longer list item with a <a href="#">Link</a></li>
	</ul>

<b>Block Links List</b>
<div class="course-content">
<a href="#" class="btn btn-secondary btn-block">First Link</a>
<a href="#" class="btn btn-secondary btn-block">Second Link</a>
<a href="#" class="btn btn-success btn-block">Green Link</a>
<a href="#" class="btn btn-secondary btn-block">A link with a much longer title</a>
<a href="#" class="btn btn-warning btn-block">Orange Link</a>
<a href="#" class="btn btn-danger btn-block">Red Link</a>
</div>

	<a href="#" class="btn btn-secondary btn-block">First Link</a>
	<a href="#" class="btn btn-secondary btn-block">Second Link</a>
	<a href="#" class="btn btn-success btn-block">Green Link</a>
	<a href="#" class="btn btn-secondary btn-block">A link with a much longer title</a>
	<a href="#" class="btn btn-warning btn-block">Orange Link</a>
	<a href="#" class="btn btn-danger btn-block">Red Link</a>

<br />
<b>Buttons</b><br />
<i>These use bootstrap classes for their styling</i>
<div class="course-content">
<a href="#" class="btn btn-primary">Primary</a>&nbsp; <a href="#" class="btn btn-outline-primary">Outline</a><br /><br />
<a href="#" class="btn btn-secondary">Secondary</a>&nbsp;<a href="#" class="btn btn-outline-secondary">Outline</a><br /><br />
<a href="#" class="btn btn-success">Success</a>&nbsp;<a href="#" class="btn btn-outline-success">Outline</a><br /><br />
<a href="#" class="btn btn-warning">Warning</a>&nbsp;<a href="#" class="btn btn-outline-warning">Outline</a><br /><br />
<a href="#" class="btn btn-danger">Danger</a>&nbsp;<a href="#" class="btn btn-outline-danger">Outline</a><br /><br />
<a href="#" class="btn btn-info">Info</a>&nbsp;<a href="#" class="btn btn-outline-info">Outline</a>
</div>

	<a href="#" class="btn btn-primary">Primary</a>
	<a href="#" class="btn btn-outline-primary">Outline</a>
	<a href="#" class="btn btn-secondary">Secondary</a>
	<a href="#" class="btn btn-outline-secondary">Outline</a>
	<a href="#" class="btn btn-success">Success</a>
	<a href="#" class="btn btn-outline-success">Outline</a>
	<a href="#" class="btn btn-warning">Warning</a>
	<a href="#" class="btn btn-outline-warning">Outline</a>
	<a href="#" class="btn btn-danger">Danger</a>
	<a href="#" class="btn btn-outline-danger">Outline</a>
	<a href="#" class="btn btn-info">Info</a>
	<a href="#" class="btn btn-outline-info">Outline</a>

On landing pages block with a background fill, the primary button styling will appear different:

<div class="course-content bg-primary format-landingpage">
<div class="sections" style="width: 100%;">
<div class="section backgroundfill">
<div class="sectioncontent">
<br />
	<a href="#" class="btn btn-primary">Primary</a>
<br /><br />
</div>
</div>
</div>
</div>