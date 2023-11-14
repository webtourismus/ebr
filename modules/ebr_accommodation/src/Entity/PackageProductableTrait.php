<?php

declare(strict_types = 1);

namespace Drupal\ebr_accommodation\Entity;

use Drupal\ebr\Entity\ProductableTrait;

/**
 * Provides extra fields for "room" nodes.
 */
trait PackageProductableTrait {

  use ProductableTrait;

  /**
   * {@inheritDoc}
   */
  public function getProductFieldnames(?string $viewMode = NULL): array {
    return $this->getFilteredAndSortedProductFields(
      ['field_price', 'field_minlos', 'field_mealplan'],
      $viewMode
    );
  }
}
