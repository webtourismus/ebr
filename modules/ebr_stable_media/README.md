Entity business rules: Stable media links
========================================

Creates an UI to manage media entities with permalinks.

- Media entities having where field "internal_id" is starting with  "download_"
  are used for frequently changing files accessible under a stable link 
  (e.g. "Program of the week" as PDF download).
- Any matching entity will generate an media edit link in the "editor" menu.
- The media label will be used as link title.
- Stable media links have a base weight of 2000 plus the value of
  "field_weight" (if existing)
