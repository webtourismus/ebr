<?php

declare(strict_types=1);

namespace Drupal\ebr\Entity;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ebr_teaser\Entity\ParagraphTeaserableInterface;

/**
 * Methods for ProductableInterface.
 */
trait ProductableTrait {

  /**
   * Returns the productable entity (self or referenced entity).
   */
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
   * {@inheritdoc}
   */
  public function getProductFieldLabel(string $fieldName): TranslatableMarkup|string|NULL {
    return $this->getProductField($fieldName)?->getFieldDefinition()?->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getProductField(string $fieldName): ?FieldItemListInterface {
    $productEntity = $this->getProductEntity();
    if ($productEntity === NULL || !in_array($fieldName, $productEntity->getProductFieldnames(), TRUE)) {
      return NULL;
    }
    return $productEntity->get($fieldName);
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderedProductField(string $fieldName, string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $productEntity = $this->getProductEntity();
    if ($productEntity === NULL || !in_array($fieldName, $productEntity->getProductFieldnames(), TRUE)) {
      return NULL;
    }
    if (!method_exists($productEntity, 'renderField')) {
      return NULL;
    }
    $build = $productEntity->renderField($viewMode, $this->getProductField($fieldName));
    if ($build === NULL) {
      return NULL;
    }
    // @see \Drupal\designsystem\DesignHelper::getRealViewmode()
    $build['#view_mode'] = $viewMode;
    return $build;
  }

  /**
   * Filters and sorts product fields by the given view mode components.
   */
  protected function getFilteredAndSortedProductFields(array $fields, ?string $viewMode = NULL): array {
    if (empty($viewMode)) {
      return $fields;
    }
    $productEntity = $this->getProductEntity();
    if ($productEntity === NULL) {
      return [];
    }
    $enabledComponents = $this->entityTypeManager()
      ->getStorage('entity_view_display')
      ->load("{$productEntity->getEntityTypeId()}.{$productEntity->bundle()}.{$viewMode}")
      ?->getComponents();
    if (empty($enabledComponents)) {
      return [];
    }
    $fields = array_intersect($fields, array_keys($enabledComponents));
    uasort($fields, function ($a, $b) use ($enabledComponents) {
      return $enabledComponents[$a]['weight'] < $enabledComponents[$b]['weight'] ? -1 : 1;
    });
    return $fields;
  }

}
