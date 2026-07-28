<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_casablanca\Entity;

use Drupal\ebr_accommodation\Entity\RoomProductableTrait;

/**
 * Casablanca-backed room node bundle class.
 */
class RoomCasablanca extends AccommodationCasablanca {

  use RoomProductableTrait;

}
