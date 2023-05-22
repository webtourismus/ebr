<?php

declare(strict_types = 1);

namespace Drupal\ebr\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Item list for a generic computed field that renders a contextual field.
 *
 * This field type should always be single cardinality!
 * If multiple values are required, create multiple fields of this type.
 *
 * There is no assumption on the nature of the field item other than
 * its usage for highly opinionated business rules. Common examples are links
 * to external shops and webforms, or JS widgets provided by external systems.
 *
 * @see \Drupal\ebr\Plugin\Field\FieldType\ActionLinkItem
 */
class SingleComputedItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $this->ensurePopulated();
  }

  /**
   * Enforces caching of the computed link.
   */
  protected function ensurePopulated() {
    if (!isset($this->list[0])) {
      $this->list[0] = $this->createItem(0);
    }
  }

}
