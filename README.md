# Simple Registration/Login code in PHP

Read more:[PHP registration form](http://www.html-form-guide.com/php-form/php-registration-form.html) [PHP login form](http://www.html-form-guide.com/php-form/php-login-form.html)

## Installation

1. Edit the file `membersite_config.php` in the includes folder and update the configuration information (like your email address, Database login etc)
    **Note**
    The script will create the table in the database when you submit the registration form the first time. 

2. Upload the entire 'source' folder  to your web site. 
    
3. You can customize the forms and scripts as required.

## Files

* register.php 

    This script displays the registration form. When the user submits the form,
the script sends a confirmation email to the user. The registration is complete only when
the user clicks the confirmation link that they received in the email

* confirmreg.php

    Confirms a user's email address. The user clicks the confirmation link that they receive at their email address and is send to this script. This script verifies the user and  marks the user as confirmed. The user can login only after he has confirmed himself.

* login.php

    The user can login through this login page. After successful login, the user is sent to the page login-home.php
    
* access-controlled.php

    This is a sample accesscontrolled page. If the user is logged in, he can view this page. Else the user is 
sent to login.php
    
* includes/membersite_config.php
    Update your confirguration information in this file
    
* includes/fg_membersite.php

    This file contains the main class that controls all the operations (validations, database updation, emailing etc)
If you want to edit the email message or make changes to the logic, edit this file
    
* includes/class.phpmailer.php

    This script uses PHPMailer to send emails. See:http://sourceforge.net/projects/phpmailer/ 
    
* includes/formvalidator.php    

    For form validations on the server side, the PHP form validator from HTML form guide is used See: [PHP form validation] (http://www.html-form-guide.com/php-form/php-form-validation.html)
    
 
## License
This program is free software published under the terms of the GNU [Lesser General Public License](http://www.gnu.org/copyleft/lesser.html).
You can freely use it on commercial or non-commercial websites. 
