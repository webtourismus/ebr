Entity business rules
====================

This module provides three base fields:
- internal_id
- remote_datasource
- remote_id

These fields are used by various business rules:
- Entities having an non-empty "internal_id" 
  - are protected from deletion.
  - are listed in System site settings for information
- Sub modules and 3rd party modules might use internal_id to mark special entities.
  - Any module using this field should also implement hook_form_system_site_information_settings_alter().
  - See ebr_stable_mediahook_form_system_site_information_settings_alter() for an example.
- 3rd party modules might use remote_datasource and remote_id in conjunction with migrate module for external data synching.
