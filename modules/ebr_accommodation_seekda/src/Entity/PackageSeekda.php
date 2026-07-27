<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_seekda\Entity;

use Drupal\ebr_accommodation\Entity\PackageProductableTrait;

/**
 * Seekda-backed package node bundle class.
 */
class PackageSeekda extends AccommodationSeekda {

  use PackageProductableTrait;

}
