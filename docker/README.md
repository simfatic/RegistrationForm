## Docker test environment
This docker environment is for testing only. 
The docker environment has nginx webserver, PHP, MySql database and MailHog to capture emails sent by the script.

## Installation instructions
You should have Docker setup on your developer machine. 

If you have `make` run 
```
make build
```
and then 
```
make up
```
Open localhost:3100 in the browser

Register a test account. 

Open localhost:8025 in your browser. 
This opens [MailHog](https://github.com/mailhog/MailHog) page that captures the emails sent by the script

Click the link in the confirmation email to complete the registration.

