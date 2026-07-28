<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_externaluri\Entity;

use Drupal\ebr_accommodation\Entity\AccommodationBase;

/**
* Business rules for accomodation node bundles "room" and "package" with generic booking link.
  */
abstract class AccommodationExternalUri extends AccommodationBase {

  /**
   * The "remote_datasource" field value for entites with an external call-to-action URI.
   */
  public const DATASOURCE = 'external_uri';

  /**
   * The human readable label of the external call-to-action method.
   */
  public const NAME = 'External URI';
}
