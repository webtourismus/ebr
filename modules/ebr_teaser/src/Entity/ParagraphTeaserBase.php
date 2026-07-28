<?php

declare(strict_types=1);

namespace Drupal\ebr_teaser\Entity;

use Drupal\ebr\Entity\ActionableInterface;
use Drupal\ebr\Entity\ProductableInterface;
use Drupal\ebr\Entity\ProductableTrait;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * The entity bundle class used by linkblocks and icon paragraphs.
 */
class ParagraphTeaserBase extends Paragraph implements ParagraphTeaserableInterface, ActionableInterface, ProductableInterface {
  use ParagraphTeaserableTrait;
  use ProductableTrait;

  /**
   * {@inheritdoc}
   */
  public function getProductFieldnames(?string $viewMode = NULL): array {
    if ($this->getReferencedEntity() instanceof ProductableInterface) {
      return $this->getReferencedEntity()->getProductFieldnames($viewMode);
    }
    // By convention a Paragraph itself should not be a product.
    return [];
  }
}
