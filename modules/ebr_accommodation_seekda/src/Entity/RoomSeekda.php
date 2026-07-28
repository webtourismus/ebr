<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_seekda\Entity;

use Drupal\ebr_accommodation\Entity\RoomProductableTrait;

/**
 * Seekda-backed room node bundle class.
 */
class RoomSeekda extends AccommodationSeekda {

  use RoomProductableTrait;

}
