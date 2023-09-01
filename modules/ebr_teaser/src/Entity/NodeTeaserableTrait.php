<?php

declare(strict_types = 1);

namespace Drupal\ebr_teaser\Entity;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Methods for TeaserableInterface.
 */
trait NodeTeaserableTrait {
  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  public function isTeaserableViewmode(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): bool {
    return strpos($viewMode, TeaserableInterface::TEASER_VIEWMODE_PREFIX) === 0;
  }

  /**
   * Shortcut to the viewBuilder service.
   */
  protected function viewBuilder(): EntityViewBuilderInterface {
    return $this->entityTypeManager()->getViewBuilder('node');
  }

  /**
   * {@inheritDoc}
   */
  public function getTeaserTitleField(): ?FieldItemListInterface {
    return $this->get('title');
  }

  /**
   * {@inheritDoc}
   */
  public function getTeaserSubtitleField(): ?FieldItemListInterface {
    if ($this->hasField('field_subtitle')) {
      return $this->get('field_subtitle');
    }
    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getTeaserImagesField(): ?FieldItemListInterface {
    if ($this->hasField('field_images')) {
      return $this->get('field_images');
    }
    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getTeaserTextField(): ?FieldItemListInterface {
    if ($this->hasField('body')) {
      return $this->get('body');
    }
    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function renderField(string $viewMode, ?FieldItemListInterface $field) {
    if (!$field instanceof FieldItemListInterface) {
      return NULL;
    }
    $displayOptions = $this->entityTypeManager()
      ->getStorage('entity_view_display')
      ->load("node.{$this->bundle()}.{$viewMode}")
      ?->getComponent($field->getFieldDefinition()->getName());
    if (is_null($displayOptions)) {
      if ($field->getName() === $this->getEntityType()->getKey('label')) {
        // Entity title fields often are not configurable in view modes (prime example: nodes).
        // If a teaser template requests that field, render it with default settings instead.
        $displayOptions = [];
      }
      else {
        // In any other case we assume there are no $displayOptions because that field is
        // intentionally hidden in view mode settings and should not be rendered.
        return NULL;
      }
    }
    $build = $this->viewBuilder()->viewField(
      $field,
      $displayOptions
    );
    $build['#is_teaserable'] = TRUE;
    $build['#teasered_entity_type'] = $this->getEntityTypeId();
    $build['#teasered_entity_bundle'] = $this->bundle();
    $build['#teasered_field_storage'] = $field->getName();
    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public function getRenderedTeaserTitle(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $build = $this->renderField($viewMode, $this->getTeaserTitleField());
    if ($build['#is_teaserable'] ?? FALSE) {
      $build['#teaser_fieldname'] = 'title';
    }
    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public function getRenderedTeaserSubTitle(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $build = $this->renderField($viewMode, $this->getTeaserSubtitleField());
    if ($build['#is_teaserable'] ?? FALSE) {
      $build['#teaser_fieldname'] = 'subtitle';
    }
    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public function getRenderedTeaserImages(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $build = $this->renderField($viewMode, $this->getTeaserImagesField());
    if ($build['#is_teaserable'] ?? FALSE) {
      $build['#teaser_fieldname'] = 'images';
    }
    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public function getRenderedTeaserText(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $build = $this->renderField($viewMode, $this->getTeaserTextField());
    if ($build['#is_teaserable'] ?? FALSE) {
      $build['#teaser_fieldname'] = 'text';
    }
    return $build;
  }

}
