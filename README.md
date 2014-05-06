This penn-gmail project provides command line tools and a web interface
to manage student gmail accounts at the University of Pennsylvania.

Roberto Mansfield
robertom@sas.upenn.edu
4/30/2014


1. Enable API access in the Google Admin panel. (Security -> bla bla)

2. Create a role / account that will be used for API access and assign the following permissions.
    a. (check what role permissions we need)

3. Create an "application" org (e.g., App Developers).

4. Create an application user (e.g., application@your.google.domain).

5. Enable Google App Engine Admin console for your application org defined above. 
    Google Dashboard -> More Controls (bottom of page) -> Other Google Services
    a. Override
    b. Switch on

6. Login to the Google Developers Console and create a new project. (e.g., penn-sas-provisioning)
    https://console.developers.google.com/

7. Enable Admin SDK in your project.

8. Create client id for web application
    a. Update parameters.yml with client id and secret
    b. set oauth redirect url to match your app 
    c. Set path for token cache and refresh token storage in parameters.yml    
