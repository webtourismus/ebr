Entity business rules
====================

Introduction
------------
Provides a set of features and base fields bases upon content entities.
This is an API module for site architects, developers and frontend coder.


Features
--------
This module provides three base fields:
* internal_id
* remote_datasource
* remote_id

These fields are used by various business rules:
* Entities having an non-empty "internal_id" 
  * are protected from deletion.
  * are listed in System site settings for information
* Sub modules and 3rd party modules might use internal_id to mark special entities.
  * Any module using this field should also implement hook_form_system_site_information_settings_alter().
  * See ebr_stable_mediahook_form_system_site_information_settings_alter() for an example.
* 3rd party modules might use remote_datasource and remote_id in conjunction with migrate module for external data synching.

Two interfaces are declaring common features based on those fields:
* Actionable Interface  
  For contextual call-to-action links when viewing a content entity.
* Widgetable Interface
  To render javascript web widgets.

EBR Teaser (sub module)
-----------------------
Provides an opinionated set of fields to be used in teaser-like Twig templates:
* title
* subtitle
* images
* text
By using getter functions for those fields, the same templates can be used by different entity types and bundles.

EBR Accommodatin (sub module)
----------------------------
Provides node bundles "room" and "package", base fields and contextual call-to-actions for accommodation content entites.

EBR Stable media (sub module)
----------------------------
Provides permalinks and admin UI helpers for frequently updated, downloadable media content entites like price lists or daily offers.
