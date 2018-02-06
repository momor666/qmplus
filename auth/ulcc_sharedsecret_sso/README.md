moodle-auth_ulcc_sharedsecret_sso
=================================

This plugin allows users to login to moodle  using a single sign on

Login page address: www.yourmoodle/login/index.php

we need three perameter to login.

  1. clientuname=(Username)
  2. sshash=(secret code md5 hash)
  3. page=(which page to redirect after successful login)

  optional expiration

  4. gen=(timestamp of sshash generation time - used for expiring token)
  5. ghash=(md5 of gen used to check hash)

So a good looking login url should be as below:

http://yourmoodle/login/index.php?clientuname=*****&sshash=********&page=course/view.php?id=3

with expiration:

http://yourmoodle/login/index.php?clientuname=*****&sshash=********&gen=********&ghash=******&page=course/view.php?id=3

This above link will redirect to the course page where course id=3 while parameter no 1 and 2 is correct!

