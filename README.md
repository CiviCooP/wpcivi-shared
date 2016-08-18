civicoop/wpcivi-shared
======================

Wordpress plugin that contains shared code for Wordpress <-> CiviCRM integration.  
It includes:

* A `WPCiviAPI` class that is used by all other functions and custom plugins to access the  
  CiviCRM API. It supports both local and remote installs. There are also some useful 
  utility functions and temporary caches in the `Util\...` classes.

* Base classes that make handling Gravity Forms submissions possible. It isn't possible to
  configure handlers dynamically in the WordPress admin (yet): every different form requires 
  creating a FormHandler class that extends `BaseFormHandler`. You can then select the handler
  you want to use for a form on the form's settings page. The actual custom handlers used are
  separated into a [per-site plugin](https://github.com/civicoop/wpcivi-jourcoop).
  
* Entity classes for all CiviCRM Entities that are currently accessed or changed by our custom
  modules. These classes represent a single CiviCRM Entity (*Contact*, *Email*, *Membership*)
  and wrap around the CiviCRM API (for example, calling `$contact->save()` in turn calls
  `Contact.Create({...})`).  
  A search should return a `EntityCollection` that contains an array of `Entity` classes.  
  See the examples in the [README_EXAMPLES](README_EXAMPLES.md) file.
  
* Basic plugin and theme functions that can be extended by other classes.  
  And a PSR-4 autoloader to try and introduce some sanity into WordPress.
  

---

*Note for those interested in WP+Civi integration:*  
The description about the CiviCRM Entity classes above may remind you of the
*civicrm_entity* module in Drupal. A similiar kind of deep integration between WordPress
and CiviCRM would open up a lot of possibilities for end-users who aren't developers,
and save a lot of development work in the long run.  
This module is not quite there yet: it's just a first, very provisional, attempt to allow 
easier access to CiviCRM data from WordPress. A next step might be trying to model the 
`Entity` and `EntityCollection` classes on `\WP_Post` and `\WP_Query`.
In the future, Entity types could be matched to custom post types; QueryWrangler could work
as a WP alternative for Views; and integration with the Advanced Custom Fields plugin and
form plugins like Gravity Forms would provide even more useful functionality.  
(However, all that may take more than a few hours of development work :-) - and there are
a lot of possible use cases and complications. The first one is that the WP_Query class that 
is central to WordPress only works with *wp_posts_** tables and is *very* difficult to extend.)
