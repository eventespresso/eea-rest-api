EE4 REST API
=========

An Event Espresso 4 addon for providing an RESTful interface for event espresso's data.

This plugin/addon needs to be uploaded to the "/wp-content/plugins/" directory on your server or installed using the WordPress plugins installer.

The general roadmap is:
-hook into the WP API for registering endpoints and other basic framework code
-give full access to the EE models' query params for filtering
-use versioning, in case we want to adjust our API's interface (should be independent of WP API's versioning)
-have generalized endpoints for each EE model, except simple join models (eg, if you register a model named
"EEM_Thingy", there should automatically be an endpoint called "ee4/thingies" for GETting all thingies, another
for creating thingies, another for updating thingies, etc)
-have endpoints for viewing and editing EE's config data
-add general EE data onto the WP API index
-read-only fields from the models should be prefixed with an underscore (fields which are
derived from other things, eg TXN_total, should be read-only, and the API shoudl handle setting those on creations or updates);
we should generally avoid having foreign keys in the model data,
-the API will need to respect the permissions of the user
-the EE API should allow API clients to specify what specific fields and related model objects they want in a request
-have SPCO-style endpoints for simplifying the reg process and handling more business logic (like sending emails, fetching the questions for registrations for specific events, etc.