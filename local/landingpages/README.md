moodle-local_landingpages
========================

A Moodle plugin for redirecting users to landing pages

Capabilities
===============




Installation
=================


git remote add -f local_landingpages git@github.com:ULCC-QMUL/moodle-local_landingpages.git
git merge -s ours --no-commit --squash local_landingpages/<BRANCH_OR_TAG_>
git read-tree --prefix=local/landingpages/ -u local_landingpages/<BRANCH_OR_TAG>
git commit -m "Install landingpages version <version_number>"


Point your browser at Moodle, and login as admin.  This should kick off
the upgrade so that Moodle can now recognise the new plugin.

Copyright (C) Gerry G Hall 2013 and beyond, All rights reserved

moodle-local_landingpages free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.