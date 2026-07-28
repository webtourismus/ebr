<?php

declare(strict_types=1);

namespace Drupal\ebr\Entity;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ebr_teaser\Entity\TeaserableInterface;

/**
 * A content entity potentially extra fields for product-specific data.
 *
 * This interface provides a common API to inject additional fields
 * into shared teaserable templates.
 * E.g. a node of type "room" can might have a teaser view mode showing
 * extra fields like "price" and "roomsize". Using this interface,
 * a shared template for node teaser and paragraph linkblock
 * can dynamically show extra fields in both templates.
 */
interface ProductableInterface extends TeaserableInterface {

  /**
   * Returns all potentially available product specific fields.
   *
   * Optionally sort and filter fields as specified in the given view mode.
   */
  public function getProductFieldnames(?string $viewMode = NULL): array;

  /**
   * Returns the human readable label of a call-to-action.
   */
  public function getProductFieldLabel(string $fieldName): TranslatableMarkup|string|NULL;

  /**
   * Returns a product field
   */
  public function getProductField(string $fieldName): ?FieldItemListInterface;

  /**
   * Returns a product field as render array.
   */
  public function getRenderedProductField(string $fieldName, string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array;

}
