<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_easybooking\Entity;

use Drupal\ebr_accommodation\Entity\RoomProductableTrait;

/**
 * Easybooking-backed room node bundle class.
 */
class RoomEasybooking extends AccommodationEasybooking {

  use RoomProductableTrait;

}
