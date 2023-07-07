<?php

declare(strict_types = 1);

namespace Drupal\ebr_teaser\Entity;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ebr\Entity\ActionableInterface;
use Drupal\link\LinkItemInterface;

/**
 * Methods for ParagraphTeaserableInterface.
 */
trait ParagraphTeaserableTrait {
  use StringTranslationTrait;

  /**
   * Shortcut to the viewBuilder service.
   */
  protected function viewBuilder(): EntityViewBuilderInterface {
    return $this->entityTypeManager()->getViewBuilder('paragraph');
  }

  /**
   * {@inheritDoc}
   */
  public function isTeaserableViewmode(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): bool {
    // A paragraph teaserable'ness is defined on bunble level, not on view mode level.
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function isFieldSuppressed(string $fieldName): bool {
    if ($this->hasField('field_suppress_fields')) {
      $suppressedFields = array_column($this->get('field_suppress_fields')->getValue(), 'target_id');
      return in_array("paragraph.{$this->bundle()}.{$fieldName}", $suppressedFields);
    }
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function getReferencingField(): ?FieldItemListInterface {
    if ($this->hasField('field_link')) {
      return $this->get('field_link');
    }
    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getReferencedEntity(): ?TeaserableInterface {
    if ($this?->getReferencingField()?->isEmpty() ?? TRUE) {
      return NULL;
    }

    $fieldItem = $this->getReferencingField()->first();
    $referencedEntity = NULL;

    if ($fieldItem instanceof EntityReferenceItem) {
      $referencedEntity = $fieldItem->getEntity();
    }
    elseif ($fieldItem instanceof LinkItemInterface) {
      /**
       * @var \Drupal\Core\Url $url
       */
      $url = $fieldItem->getUrl();
      if (!$url->isRouted()) {
        return NULL;
      }

      $routeParameters = $url->getRouteParameters();
      $possiblyAnEntityType = key($routeParameters);
      $possiblyAnEntityId = current($routeParameters);
      try {
        $referencedEntity = $this->entityTypeManager()
          ->getStorage($possiblyAnEntityType)
          ->load($possiblyAnEntityId);
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
        return NULL;
      }
    }

    if (!($referencedEntity instanceof TeaserableInterface)) {
      return NULL;
    }
    if ($referencedEntity->isTranslatable()) {
      $referencedEntity = \Drupal::service('entity.repository')->getTranslationFromContext($referencedEntity);
    }
    return $referencedEntity;
  }

  /**
   * Shared render function for all fields.
   *
   * Teaserable fields should always be rendered with the view mode of the host entity, even if the field values
   * are sourced from the referenced entity. This also means that - in case of referenced field - they must be of
   * interchangeable types.
   */
  protected function renderFieldWithReferenceFallback(
    string $viewMode,
    ?FieldItemListInterface $ownField,
    ?FieldItemListInterface $referencedField
  ): ?array {
    if (!($ownField instanceof FieldItemListInterface)) {
      return NULL;
    }
    if ($this->isFieldSuppressed($ownField->getName())) {
      return NULL;
    }
    $ownFieldDefinition = $ownField->getFieldDefinition();

    $displayOptions = $this->entityTypeManager()
      ->getStorage('entity_view_display')
      ->load("paragraph.{$this->bundle()}.{$viewMode}")
        ?->getComponent($ownFieldDefinition->getName());
    if (is_null($displayOptions)) {
      return NULL;
    }
    $imageViewMode = NULL;
    // Teaserable paragraphs have an image ratio field, which overrides the default display settings
    // for the image field, and maps to an media entity view mode.
    if ($ownFieldDefinition->getName() == 'field_images' &&
      $displayOptions['type'] == 'entity_reference_entity_view' &&
      $ownField->getEntity()->hasField('field_image_ratio') &&
      !$ownField->getEntity()->get('field_image_ratio')->isEmpty()
    ) {
      $imageViewMode = $ownField->getEntity()->get('field_image_ratio')->target_id;
      $displayOptions['settings']['view_mode'] = $imageViewMode;
    }


    if (!$ownField->isEmpty()) {
      // Case 1: render own field.
      $build = $this->viewBuilder()->viewField(
        $ownField,
        $displayOptions
      );
      $build['#is_teaserable'] = TRUE;
      // When fields are rendered in isolation, Drupal sets the '#view_mode' to '_custom'.
      // Therefore we need to re-provide a proper value.
      $build['#view_mode'] = $imageViewMode ?? $viewMode;
      return $build;
    }

    if (!($referencedField instanceof FieldItemListInterface) || $referencedField->isEmpty()) {
      return NULL;
    }

    $referencedFieldDefinition = $referencedField->getFieldDefinition();
    if ( $ownFieldDefinition->getFieldStorageDefinition()->getSetting('target_type') == 'media' &&
      $referencedFieldDefinition->getType() == 'image'
    ) {
      // if we need to render a foreign entitie's image field, we assume there is a 1:1 mapping
      // between media entity view modes and responsive image styles
      $responsiveImageStyle = \Drupal::config('responsive_image.style')->get($imageViewMode ?? $viewMode)?->get('id');
      if ($responsiveImageStyle) {
        $displayOptions = [
          'label' => 'hidden',
          'type' => 'responsive_image',
          'settings' => [
            'repsonsive_image_style' => $responsiveImageStyle,
          ],
        ];
        $build = $this->entityTypeManager()
          ->getViewBuilder($this->getReferencedEntity()->getEntityTypeId())
          ->viewField($referencedField, $displayOptions);
        $build['#is_teaserable'] = TRUE;
        $build['#referencing_object'] = $this;
        $build['#view_mode'] = $imageViewMode ?? $viewMode;
        return $build;
      }
      \Drupal::logger('ebr_teaser')->error("Paragraph host field paragraph.{$this->bundle()}.{$ownFieldDefinition->getName()} has no matching responsive image style '{$viewMode}' for {$this->getReferencedEntity()->getEntityTypeId()}.{$this->getReferencedEntity()->bundle()}.{$referencedFieldDefinition->getName()}");
      return NULL;
    }

    // Fallback variant: Render the field from the referenced entity.
    // This blindly assumes that the referenced field is similar enough to the own field
    // to allow rendering the referenced field with the same field formatter options as the own field.
    $build = $this->viewBuilder()->viewField(
      $referencedField,
      $displayOptions
    );
    $build['#is_teaserable'] = TRUE;
    $build['#referencing_object'] = $this;
    $build['#view_mode'] = $imageViewMode ?? $viewMode;
    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public function getTeaserTitleField(): ?FieldItemListInterface {
    if ($this->isFieldSuppressed('field_title')) {
      return NULL;
    }
    if ($this->get('field_title')->isEmpty() && $this->getReferencedEntity() instanceof TeaserableInterface) {
      return $this->getReferencedEntity()->getTeaserTitleField();
    }
    return $this->get('field_title');
  }

  /**
   * {@inheritDoc}
   */
  public function getTeaserSubtitleField(): ?FieldItemListInterface {
    if ($this->isFieldSuppressed('field_subtitle')) {
      return NULL;
    }
    if ($this->get('field_subtitle')->isEmpty() && $this->getReferencedEntity() instanceof TeaserableInterface) {
      return $this->getReferencedEntity()->getTeaserSubtitleField();
    }
    return $this->get('field_subtitle');
  }

  /**
   * {@inheritDoc}
   */
  public function getTeaserImagesField(): ?FieldItemListInterface {
    if ($this->isFieldSuppressed('field_images')) {
      return NULL;
    }
    if ($this->get('field_images')->isEmpty() && $this->getReferencedEntity() instanceof TeaserableInterface) {
      return $this->getReferencedEntity()->getTeaserImagesField();
    }
    return $this->get('field_images');
  }

  /**
   * {@inheritDoc}
   */
  public function getTeaserTextField(): ?FieldItemListInterface {
    if ($this->isFieldSuppressed('field_text')) {
      return NULL;
    }
    if ($this->get('field_text')->isEmpty() && $this->getReferencedEntity() instanceof TeaserableInterface) {
      return $this->getReferencedEntity()->getTeaserTextField();
    }
    return $this->get('field_text');
  }

  /**
   * {@inheritDoc}
   */
  public function getRenderedTeaserTitle(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $build = $this->renderFieldWithReferenceFallback($viewMode, $this->get('field_title'), $this->getReferencedEntity()?->getTeaserTitleField());
    if ($build['#is_teaserable'] ?? FALSE) {
      $build['#teaser_fieldname'] = 'title';
    }
    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public function getRenderedTeaserSubTitle(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $build = $this->renderFieldWithReferenceFallback($viewMode, $this->get('field_subtitle'), $this->getReferencedEntity()?->getTeaserSubtitleField());
    if ($build['#is_teaserable'] ?? FALSE) {
      $build['#teaser_fieldname'] = 'subtitle';
    }
    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public function getRenderedTeaserImages(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $build = $this->renderFieldWithReferenceFallback($viewMode, $this->get('field_images'), $this->getReferencedEntity()?->getTeaserImagesField());
    if ($build['#is_teaserable'] ?? FALSE) {
      $build['#teaser_fieldname'] = 'images';
    }
    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public function getRenderedTeaserText(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $build = $this->renderFieldWithReferenceFallback($viewMode, $this->get('field_text'), $this->getReferencedEntity()?->getTeaserTextField());
    if ($build['#is_teaserable'] ?? FALSE) {
      $build['#teaser_fieldname'] = 'text';
    }
    return $build;
  }

  /**
   * Shim for \Drupal\ebr\Entity\ActionableInterface::getActionFieldnames().
   *
   * Paragraphs do not implement ActionableInterface, but their referenced
   * entities might do so. Therefore we provide passthrough functions for
   * some actionable methods for usage in Twig templates.
   */
  public function getActionFieldnames(): array {
    $result = [];
    if ($this->getReferencedEntity() instanceof ActionableInterface) {
      $result = $this->getReferencedEntity()->getActionFieldnames();
      // Do not use the read more action, because paragraphs have their own "field_link".
      if (array_key_exists(ReadmoreActionableInterface::ACTION_READMORE, $result)) {
        unset($result[ReadmoreActionableInterface::ACTION_READMORE]);
      }
    }
    return $result;
  }

  /**
   * Shim for \Drupal\ebr\Entity\ActionableInterface::getActionLabel().
   *
   * Paragraphs do not implement ActionableInterface, but their referenced
   * entities might do so. Therefore we provide passthrough functions for
   * some actionable methods for usage in Twig templates.
   */
  public function getActionLabel(string $actionId): TranslatableMarkup|string|NULL {
    if ($actionId != ReadmoreActionableInterface::ACTION_READMORE && $this->getReferencedEntity() instanceof ActionableInterface) {
      return $this->getReferencedEntity()->getActionLabel($actionId);
    }
    return NULL;
  }

  /**
   * Shim for \Drupal\ebr\Entity\ActionableInterface::getActionUrl().
   *
   * Paragraphs do not implement ActionableInterface, but their referenced
   * entities might do so. Therefore we provide passthrough functions for
   * some actionable methods for usage in Twig templates.
   */
  public function getActionUrl(string $actionId): ?Url {
    if ($actionId != ReadmoreActionableInterface::ACTION_READMORE && $this->getReferencedEntity() instanceof ActionableInterface) {
      return $this->getReferencedEntity()->getActionUrl($actionId);
    }
    return NULL;
  }

  /**
   * Returns an call-to-action as render array.
   */
  public function getRenderedAction(string $actionId, string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    if ($actionId != ReadmoreActionableInterface::ACTION_READMORE && $this->getReferencedEntity() instanceof ActionableInterface) {
      return $this->getReferencedEntity()->getRenderedAction($actionId, $viewMode);
    }
    return NULL;
  }

}
