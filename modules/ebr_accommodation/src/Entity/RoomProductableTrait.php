<?php

declare(strict_types = 1);

namespace Drupal\ebr_accommodation\Entity;

use Drupal\ebr\Entity\ProductableTrait;

/**
 * Provides extra fields for "package" nodes.
 */
trait RoomProductableTrait {

  use ProductableTrait;

  /**
   * {@inheritDoc}
   */
  public function getProductFieldNames(): array {
    return ['field_price', 'field_occupancy_std', 'field_occupancy_max', 'field_roomsize'];
  }
}
