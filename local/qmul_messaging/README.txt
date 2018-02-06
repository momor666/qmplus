Intallation notes and configs
=============================

This plugin sends messages or notifications to roles in category contexts or site wide.
Install code in the normal way.

Set up the webservices
----------------------
1. Enable web services if not already
2. Create a dedicated web serverice user if required
3. Add the user to the QM pre-defined web services role
4. Assign the user to the web services package for this module in order to generate the token
5. Add the capabilities to the webservices user.
5. Paste the token into the plugin settings
(Without this the ticker and the hide/delete functions on the manage messages page won't work)

Roles and permissions
---------------------
Set up the roles and permissions in the normal way. Admins and Courseadmins should be able to send messages as specified
in the requirements and no-one, although this may be subject to change.

Set the role relationships in the local plugin settings
-------------------------------------------------------
Self explanatory. Which roles can send messages to which roles.









