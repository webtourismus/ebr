<?php

declare(strict_types=1);

namespace Drupal\ebr_teaser\Entity;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * An interface for entities acting as a facade for other entites.
 *
 * Typically this is a paragraph with a reference targeting a node.
 */
interface ParagraphTeaserableInterface extends TeaserableInterface {

  /**
   * The field targeting the decorated entity.
   */
  public function getReferencingField(): ?FieldItemListInterface;

  /**
   * Paragraph teaser entities can be a facade for other entites.
   */
  public function getReferencedEntity(): ?TeaserableInterface;

  /**
   * Paragraph teaser can suppress rendering of teaser fields, even if filled.
   */
  public function isFieldSuppressed(string $fieldName): bool;

}
