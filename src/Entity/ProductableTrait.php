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
    if (!in_array($fieldName, $this->getProductEntity()?->getProductFieldnames())) {
      return NULL;
    }
    return $this->getProductEntity()?->get($fieldName);
  }

  /**
   * {@inheritDoc}
   */
  public function getRenderedProductField(string $fieldName, string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    if (!in_array($fieldName, $this->getProductEntity()?->getProductFieldnames())) {
      return NULL;
    }
    $build = $this->getProductEntity()?->renderField($viewMode, $this->getProductField($fieldName));
    // @see \Drupal\designsystem\DesignHelper::getRealViewmode()
    $build['#view_mode'] = $viewMode;
    return $build;
  }

  protected function getFilteredAndSortedProductFields(array $fields, ?string $viewMode = NULL): array {
    if (empty($viewMode)) {
      return $fields;
    }
    $enabledComponents = $this->entityTypeManager()
      ->getStorage('entity_view_display')
      ->load("{$this->getProductEntity()?->getEntityTypeId()}.{$this->getProductEntity()?->bundle()}.{$viewMode}")
      ?->getComponents();
    if (empty($enabledComponents)) {
      return [];
    }
    $fields = array_intersect($fields, array_keys($enabledComponents));
    uasort($fields, function($a, $b) use ($enabledComponents) {
      return $enabledComponents[$a]['weight'] < $enabledComponents[$b]['weight'] ? -1 : 1;
    });
    return $fields;
  }
}
