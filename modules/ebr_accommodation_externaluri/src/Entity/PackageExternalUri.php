<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_externaluri\Entity;

use Drupal\ebr_accommodation\Entity\PackageProductableTrait;

/**
 * External-URI-backed package node bundle class.
 */
class PackageExternalUri extends AccommodationExternalUri {

  use PackageProductableTrait;

}
