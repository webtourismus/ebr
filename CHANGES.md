# CHANGES

##### 2026-11-10 1.7.16

- BUGFIX: The Twig Tweak filters `entity_url` and `entity_link` render an empty
  string instead of failing when the entity is NULL, e.g. when `ebr()` returns
  nothing for a missing or unpublished entity. Since 1.7.10 `ebr()` checks view
  access by default.

##### 2026-11-04 1.7.15

- BUGFIX: ebr_accommodation_externaluri: TypeError in trim() when redirecting
  to the external URI, the rendered URL is cast to a string.

##### 2026-11-03 1.7.14

- BUGFIX: ebr_accommodation_seekda: update 10002 returns a plain string instead of
  `t()`, matching its `string` return type.

##### 2026-11-02 1.7.13

- BUGFIX: Version in ebr.info.yml matches the release.

##### 2026-09-25 1.7.12

- BUGFIX: ebr_teaser: node preprocessing without a node (null) no longer fails.

##### 2026-09-15 1.7.11

- BUGFIX: Service deprecation messages of ebr_accommodation_externaluri and
  ebr_accommodation_seekda for the current Drupal version.

##### 2026-09-01 1.7.10

- Refactoring of the whole repository to current Drupal guidelines, fixed
  dependencies, bugfixes and styles.

##### 2026-07-27 1.7.9

- BUGFIX: ebr_accommodation_seekda: date of the last successful import.

##### 2026-07-14 1.7.8

- BUGFIX: Typo in 1.7.7.

##### 2026-07-14 1.7.7

- FEATURE: The Twig `ebr` filter loads entities in the current language.

##### 2026-01-13 1.7.6

- BUGFIX: ebr_accommodation_seekda: bundle of the room and package imports.

##### 2025-07-01 1.7.5

- FEATURE: Alt texts for images.

##### 2025-04-29 1.7.4

- BUGFIX: ebr_accommodation_externaluri: prevent assert() errors on dev.

##### 2025-02-24 1.7.3

- BUGFIX: Check for fieldable entities before purging EBR fields on
  entity_clone.

##### 2025-02-03 1.7.2

- BUGFIX: Empty external URI is stored as null.

##### 2025-02-03 1.7.1

- BUGFIX: Config schema of the external URI.

##### 2025-01-27 1.7.0

- CHANGE: All settings moved from system.site to the module's own settings.

##### 2024-11-14 1.6.4

- ebr_accommodation_externaluri: keep existing query parameters (except room
  and package) when redirecting.

##### 2024-11-13 1.6.3

- Minor improvements of ebr_accommodation_externaluri.

##### 2024-10-29 1.6.2

- BUGFIX: Linkblock image fallback.
- Reworked the install logic of the EBR base fields.

##### 2024-10-01 1.6.1

- BUGFIX: Fallback from the paragraph linkblock media image to the plain
  image of the referenced entity.

##### 2024-08-20 1.6.0

- FEATURE: Drupal 11 compatibility.

##### 2024-07-08 1.5.0

- FEATURE: New submodule ebr_accommodation_externaluri: redirects the book
  node to external booking sites.

##### 2024-06-05 1.4.10

- BUGFIX: Async-safe loading of flatpickr for the Seekda availability
  calendars.

##### 2024-05-06 1.4.9

- BUGFIX: Action link attributes.

##### 2024-02-14 1.4.8

- BUGFIX: Undefined action link options variable.

##### 2024-02-07 1.4.7

- FEATURE: Route `/ebr/{entity}/{id}` redirects to EBR entities.

##### 2024-01-16 1.4.6

- Refactored the action link attributes to prevent problems with field
  serializers and exports.

##### 2024-01-15 1.4.5

- BUGFIX: Setting the bundle class, module dependencies.

##### 2024-01-09 1.4.4

- CHANGE: All node types with teaser fields use NodeTeaserBase as bundle
  class.

##### 2023-11-29 1.4.3

- BUGFIX: Seekda packages: force the timestamp update on import.

##### 2023-11-22 1.4.2

- BUGFIX: Incorrect update flag of the Seekda packages cron.

##### 2023-11-15 1.4.1

- BUGFIX: Unpublish grace period of Seekda packages.

##### 2023-11-14 1.4.0

- FEATURE: All product and action fields in the render array, filtered and
  sorted by view mode.
- FEATURE: Unpublishing of Seekda packages.

##### 2023-11-02 1.3.10

- BUGFIX: Unpublish status of Seekda packages set by cron.

##### 2023-10-18 1.3.9

- Default button translation "Anfragen", action link only for actionable
  nodes.

##### 2023-10-12 1.3.8

- Seekda calendar widget loads asynchronously.

##### 2023-10-06 1.3.7

- BUGFIX: Seekda fixes.

##### 2023-09-26 1.3.6

- BUGFIX: Paragraph action link "readmore".

##### 2023-09-25 1.3.5

- BUGFIX: Paragraph teaser: removed isFieldSuppressed() from the teaser field
  getters.

##### 2023-09-20 1.3.4

- ebr_accommodation_seekda: code cleanup.

##### 2023-09-20 1.3.3

- ebr_accommodation_seekda: redirect the book node to Kube.

##### 2023-09-13 1.3.2

- Better implementation of the toolbar icons.

##### 2023-09-12 1.3.1

- Translations, Seekda price per day/night.

##### 2023-09-01 1.3.0

- FEATURE: Productable interface for teaser entities.

##### 2023-08-31 1.2.2

- Translations.

##### 2023-08-25 1.2.1

- BUGFIX: Action URLs; ParagraphTeaserBase implements Actionable.

##### 2023-07-28 1.2.0

- FEATURE: Block UUIDs, Twig `ebr` filter, translations.

##### 2023-07-28 1.1.3

- BUGFIX: Dependency of the internal notes.

##### 2023-07-26 1.1.2

- Reworked the internal notes, action link info on the site settings form.

##### 2023-07-21 1.1.1

- Translations.

##### 2023-07-07 1.1.0

- FEATURE: Seekda integration, popups.

##### 2023-05-22 1.0.3

- Coding standard fixes.

##### 2023-05-15 1.0.2

- FEATURE: Internal notes field.
- BUGFIX: Teaserable view mode variable not passed to Twig.

##### 2023-05-04 1.0.1

- Documentation improvements.

##### 2023-05-04 1.0.0

- Initial release.
