<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_easybooking\Entity;

use Drupal\ebr_accommodation\Entity\PackageProductableTrait;

/**
 * Easybooking-backed package node bundle class.
 */
class PackageEasybooking extends AccommodationEasybooking {

  use PackageProductableTrait;

}
