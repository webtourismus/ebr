<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_externaluri\Entity;

use Drupal\ebr_accommodation\Entity\RoomProductableTrait;

/**
 * External-URI-backed room node bundle class.
 */
class RoomExternalUri extends AccommodationExternalUri {

  use RoomProductableTrait;

}
