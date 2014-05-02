This penn-gmail project provides command line tools and a web interface
to manage student gmail accounts at the University of Pennsylvania.

Roberto Mansfield
robertom@sas.upenn.edu
4/30/2014


1. Enable API access in the Google Admin panel. (Security -> bla bla)

#. Find consumer key / secret in Admin panel and update parameters.yml. 

#. Create a role / account that will be used for API access and assign the following permissions.

#. Create an "application" org (e.g., App Developers).
#. Create an application user (e.g., application).

#. Enable Google App Engine Admin console for your application org. 
    Google Dashboard -> More Controls (bottom of page) -> Other Google Services
    a. Override
    b. Switch on

#. Login to the Google Developers Console and create a new project. (e.g., penn-sas-provisioning)
    https://console.developers.google.com/

#. Enable Admin SDK

#. Create client id for web application (or service account with private key?)
    a. Update parameters.yml with client id and secret
    b. set oauth redirect url to match your app 
    c. Set path for token cache and refresh token storage    
