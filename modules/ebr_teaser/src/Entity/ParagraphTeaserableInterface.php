<?php

declare(strict_types=1);

namespace Drupal\ebr_teaser\Entity;

use Drupal\Core\Field\FieldItemListInterface;

interface ParagraphTeaserableInterface extends TeaserableInterface {
  public function getReferencingField(): ?FieldItemListInterface;

  public function getReferencedEntity(): ?TeaserableInterface;

  public function isFieldSuppressed(string $fieldName): bool;
}
