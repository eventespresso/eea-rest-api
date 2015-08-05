=== Event Espresso 4 - Events REST API ===
Contributors: sethshoultes,eventespresso
Tags: event registration, events planner, events calendar, wordpress events, event ticketing, class registration, conference registration, online registration, event management, buddypress, tickets, ticketing, ticket, registration, wordcamp, event manager, training, sports, booking
Requires at least: 4.2
Tested up to: 4.2.3
Stable tag: Version: 3.0.0.beta.001
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

An Event Espresso 4 plugin for providing an RESTful interface for Event Espresso's events data.

== Description ==

An Event Espresso 4 plugin for providing an RESTful interface for Event Espresso's events data.

We've reached the first milestone which provides read access to all EE4 data. That means you can build client-side javascript code, mobile apps, and programs  in any language (and on any server) that can read data used in Event Espresso 4.

So what's great about the EE4 REST API and what can you do with it? Here's a start:

* Unlike its predecessor, the [EE3 JSON API](http://eventespresso.com/product/espresso-json-api/), this add-on is compatible with Event Espresso 4 (not Event Espresso 3)
* It's built using the [WordPress REST API (aka WP API)](https://wordpress.org/plugins/json-rest-api/). That means many plugins that work with the WP API work with it too. WP API gives the EE4 REST API a solid foundation by handling authentication, providing endpoint discovery, and supplying lots of the "behind-the-scenes" code.
* It provides read access to all Event Espresso 4 data: events, tickets, datetimes, registrations, custom questions and answers, payment methods, and configuration data. Even Event Espresso 4 add-ons' data, like from the [Mailchimp Add-on](http://eventespresso.com/product/eea-mailchimp/) or the [People Add-on](http://eventespresso.com/product/eea-people-addon/), is available by default. If we've missed something [tell us in the Github issue tracker](https://github.com/eventespresso/eea-rest-api/issues/new)!
* It uses Event Espresso's [models system](http://developer.eventespresso.com/docs/model-querying/) for querying the database. This gives API clients nearly as much querying abilities as server-side plugins. 

Documentation, installation and usages guides are located on [developer.eventespresso.com/ee-plugin/ee4-json-rest-api/](http://developer.eventespresso.com/ee-plugin/ee4-json-rest-api/)

This plugin also requires you have the master branch of the WP API active on your site.

== Installation ==

* [Install WordPress on a server](https://codex.wordpress.org/Installing_WordPress)
* [Install Event Espresso 4 on that server](https://eventespresso.com/wiki/installing-event-espresso/)
* [Install the WordPress API 1.2](https://wordpress.org/plugins/json-rest-api/) [(master branch on github when this was written)](https://github.com/WP-API/WP-API/tree/master)
* Install this Event Espresso REST API Addon by either:

 - By checking out the github eea-rest-api project contents into  wp-content/plugins/eea-rest-api directory and activate it, or 
 - download the latest zip of master branch, and uploading it to WordPress using the wordpress plugin uploader


== Frequently Asked Questions ==

Want to learn more? Checkout our documentation and tutorials at [developer.eventespresso.com/ee-plugin/ee4-json-rest-api/](http://developer.eventespresso.com/ee-plugin/ee4-json-rest-api/), including:

* [Introduction and Setup](http://developer.eventespresso.com/docs/ee4-json-rest-api-documentation/)
* [Reading Data over the API](http://developer.eventespresso.com/docs/ee4-json-rest-api-reading/)
* [Code Samples](http://developer.eventespresso.com/tutorial/ee4-json-rest-api-code-samples/) (including a portable javascript calendar, and a standalone PHP script)
* [Example EE4 Add-on using the API and Angular.js](http://developer.eventespresso.com/tutorial/building-an-ee4-addon-that-uses-angular-js-and-the-ee4-json-rest-api/)
* [Extending the EE4 REST API](http://developer.eventespresso.com/docs/ee4-json-rest-api-extending/)

If you still have questions about how to build your killer app that works with the EE4 REST API, [open a ticket on our Github repo](https://github.com/eventespresso/eea-rest-api/issues). We'll try our best to answer it, and improve our documentation too.

Also you should stay tuned to our developer-specific blog at [developer.eventespresso.com](http://developer.eventespresso.com/) so you'll know about any important changes regarding backwards compatibility or other developments.

And what if you're wanting to create/edit EE4 data over the API? Or have some other feature request? We want to focus on what matters most to you, so let us know! [Chime in on our Github repo's issue tracker](https://github.com/eventespresso/eea-rest-api/issues) to let us know it matters to you!

And if you do build something great with the EE4 REST API, consider listing it on our [3rd party Add-ons page](http://eventespresso.com/product/third-party-add-ons/).


== Upgrade Notice ==

N/A

== Other Notes ==

= Powering the EventSmart.com API =

In addition to being made available for all Event Espresso users, the Event Espresso REST API Add-on will also power the [Event Smart](https://eventsmart.com/) API. That means that Event Smart users will be able to do the same things with their event data on as they can with Event Espresso.

Event application developers who build applications for the Event Espresso API will also have the advantage of accessing the Event Smart users and event data. We invite application developers who use or consume event data to integrate their applications with the Event Espresso/Event Smart API. As has been mentioned before, if you create a great third-party extension/app for Event Espresso/Smart data we will do our best to introduce it to our event organizing and attendee audiences.

== Changelog ==

Initial release.

= License =

This plugin is provided "as is" and without any warranty or expectation of function. I'll probably try to help you if you ask nicely, but I can't promise anything. You are welcome to use this plugin and modify it however you want, as long as you give credit where it is due.

== Screenshots ==

N/A