Yahoo! Browser-Based Authentication QuickStart Readme
Author: Jason Levitt
Date: September 18th, 2006

Testing BBauth using success_ybbauth.php

What you need:

* You need write access to a web server on the public Internet (including the root directory)
* PHP4 or PHP5 with the Curl extension installed
* A username and password to a Yahoo! Photos account

Directions:

1. Place the files success_ybbauth.php, ybrowserauth.class.php4, and 
   ybrowserauth.class.php5 into the same directory on your web server.

2. Register your application by going here:
   https://developer.yahoo.com/wsregapp/index.php

   When you fill out the registration form, put your application path, e.g.
   http://www.yourdomain.com/dir/success_ybbauth.php
   in the "Web Application URL" form field.
   Place the special text file, as directed, in the root of www.yourdomain.com

3. Run your app! http://www.yourdomain.com/dir/success_ybbauth.php

4. Click on the link and login using a Yahoo! username/password that has 
   a Yahoo! Photos account. Some key bbauth values will be displayed along
   with some raw XML that should list the photo albums in your Yahoo! Photos
   account.

================================================
