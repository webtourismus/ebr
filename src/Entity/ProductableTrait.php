<?php

declare(strict_types = 1);

namespace Drupal\ebr\Entity;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ebr_teaser\Entity\ParagraphTeaserableInterface;

/**
 * Methods for ProductableInterface
 */
trait ProductableTrait {

  protected function getProductEntity(): ?ProductableInterface {
    $productEntity = $this;
    if ($this instanceof ParagraphTeaserableInterface) {
      $productEntity = $this->getReferencedEntity();
    }
    if ($productEntity instanceof ProductableInterface) {
      return $productEntity;
    }
    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getProductFieldLabel(string $fieldName): TranslatableMarkup|string|NULL {
    return $this->getProductField($fieldName)?->getFieldDefinition()?->getLabel();
  }

  /**
   * {@inheritDoc}
   */
  public function getProductField(string $fieldName): ?FieldItemListInterface {
    if (!in_array($fieldName, $this->getProductEntity()?->getProductFieldNames())) {
      return NULL;
    }
    return $this->getProductEntity()?->get($fieldName);
  }

  /**
   * {@inheritDoc}
   */
  public function getRenderedProductField(string $fieldName, string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    if (!in_array($fieldName, $this->getProductEntity()?->getProductFieldNames())) {
      return NULL;
    }
    return $this->getProductEntity()?->renderField($viewMode, $this->getProductField($fieldName));
  }
}
