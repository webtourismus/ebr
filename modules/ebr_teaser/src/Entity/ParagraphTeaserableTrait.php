<?php

declare(strict_types=1);

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
   * {@inheritdoc}
   */
  public function isTeaserableViewmode(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): bool {
    // A paragraph teaserable'ness is defined on bundle level, not on view mode level.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldSuppressed(string $fieldName): bool {
    if ($this->hasField('field_suppress_fields')) {
      $suppressedFields = array_column($this->get('field_suppress_fields')->getValue(), 'target_id');
      return in_array("paragraph.{$this->bundle()}.{$fieldName}", $suppressedFields, TRUE);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferencingField(): ?FieldItemListInterface {
    if ($this->hasField('field_link')) {
      return $this->get('field_link');
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferencedEntity(): ?TeaserableInterface {
    if ($this->getReferencingField()?->isEmpty() ?? TRUE) {
      return NULL;
    }

    $fieldItem = $this->getReferencingField()->first();
    $referencedEntity = NULL;

    if ($fieldItem instanceof EntityReferenceItem) {
      // EntityReferenceItem::getEntity() returns the host entity; use ->entity for the target.
      $referencedEntity = $fieldItem->entity;
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
    if ($ownFieldDefinition->getName() === 'field_images' &&
      $displayOptions['type'] === 'entity_reference_entity_view' &&
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
      // @see \Drupal\designsystem\DesignHelper::getRealViewmode()
      $build['#view_mode'] = $imageViewMode ?? $viewMode;
      return $build;
    }

    if (!($referencedField instanceof FieldItemListInterface) || $referencedField->isEmpty()) {
      return NULL;
    }

    $referencedFieldDefinition = $referencedField->getFieldDefinition();
    if ($ownFieldDefinition->getFieldStorageDefinition()->getSetting('target_type') === 'media' &&
      $referencedFieldDefinition->getType() === 'image'
    ) {
      // If we need to render a foreign entity's plain image field, we assume there is a 1:1 mapping
      // between media entity view modes and responsive image styles
      $responsiveImageStyle = \Drupal::config('responsive_image.styles.' . ($imageViewMode ?? $viewMode))?->get('id');
      if ($responsiveImageStyle) {
        $displayOptions = [
          'type' => 'responsive_image',
          'label' => 'hidden',
          'settings' => [
            'responsive_image_style' => $responsiveImageStyle,
          ],
        ];
        $build = $this->entityTypeManager()
          ->getViewBuilder($this->getReferencedEntity()->getEntityTypeId())
          ->viewField($referencedField, $displayOptions);
        $build['#is_teaserable'] = TRUE;
        $build['#referencing_object'] = $this;
        // @see \Drupal\designsystem\DesignHelper::getRealViewmode()
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
    // @see \Drupal\designsystem\DesignHelper::getRealViewmode()
    $build['#view_mode'] = $imageViewMode ?? $viewMode;
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getTeaserTitleField(): ?FieldItemListInterface {
    if (!$this->hasField('field_title')) {
      return $this->getReferencedEntity()?->getTeaserTitleField();
    }
    if ($this->get('field_title')->isEmpty() && $this->getReferencedEntity() instanceof TeaserableInterface) {
      return $this->getReferencedEntity()->getTeaserTitleField();
    }
    return $this->get('field_title');
  }

  /**
   * {@inheritdoc}
   */
  public function getTeaserSubtitleField(): ?FieldItemListInterface {
    if (!$this->hasField('field_subtitle')) {
      return $this->getReferencedEntity()?->getTeaserSubtitleField();
    }
    if ($this->get('field_subtitle')->isEmpty() && $this->getReferencedEntity() instanceof TeaserableInterface) {
      return $this->getReferencedEntity()->getTeaserSubtitleField();
    }
    return $this->get('field_subtitle');
  }

  /**
   * {@inheritdoc}
   */
  public function getTeaserImagesField(): ?FieldItemListInterface {
    if (!$this->hasField('field_images')) {
      return $this->getReferencedEntity()?->getTeaserImagesField();
    }
    if ($this->get('field_images')->isEmpty() && $this->getReferencedEntity() instanceof TeaserableInterface) {
      return $this->getReferencedEntity()->getTeaserImagesField();
    }
    return $this->get('field_images');
  }

  /**
   * {@inheritdoc}
   */
  public function getTeaserTextField(): ?FieldItemListInterface {
    if (!$this->hasField('field_text')) {
      return $this->getReferencedEntity()?->getTeaserTextField();
    }
    if ($this->get('field_text')->isEmpty() && $this->getReferencedEntity() instanceof TeaserableInterface) {
      return $this->getReferencedEntity()->getTeaserTextField();
    }
    return $this->get('field_text');
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderedTeaserTitle(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $ownField = $this->hasField('field_title') ? $this->get('field_title') : NULL;
    $build = $this->renderFieldWithReferenceFallback($viewMode, $ownField, $this->getReferencedEntity()?->getTeaserTitleField());
    if ($build['#is_teaserable'] ?? FALSE) {
      $build['#teaser_fieldname'] = 'title';
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderedTeaserSubTitle(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $ownField = $this->hasField('field_subtitle') ? $this->get('field_subtitle') : NULL;
    $build = $this->renderFieldWithReferenceFallback($viewMode, $ownField, $this->getReferencedEntity()?->getTeaserSubtitleField());
    if ($build['#is_teaserable'] ?? FALSE) {
      $build['#teaser_fieldname'] = 'subtitle';
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderedTeaserImages(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $ownField = $this->hasField('field_images') ? $this->get('field_images') : NULL;
    $build = $this->renderFieldWithReferenceFallback($viewMode, $ownField, $this->getReferencedEntity()?->getTeaserImagesField());
    if ($build['#is_teaserable'] ?? FALSE) {
      $build['#teaser_fieldname'] = 'images';
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderedTeaserText(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $ownField = $this->hasField('field_text') ? $this->get('field_text') : NULL;
    $build = $this->renderFieldWithReferenceFallback($viewMode, $ownField, $this->getReferencedEntity()?->getTeaserTextField());
    if ($build['#is_teaserable'] ?? FALSE) {
      $build['#teaser_fieldname'] = 'text';
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultActions(): array {
    return [ReadmoreActionableInterface::ACTION_READMORE];
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultActionLabel($actionId): TranslatableMarkup|string|NULL {
    return match ($actionId) {
      ReadmoreActionableInterface::ACTION_READMORE => new TranslatableMarkup('Details'),
      default => NULL,
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getActionFieldnames(?string $viewMode = NULL): array {
    $result = [];
    $referencingField = $this->getReferencingField();
    if ($referencingField === NULL || $this->isFieldSuppressed($referencingField->getName())) {
      // Suppressing the link field should suppress all action links (also from a referenced entity).
      return [];
    }

    $actionEntity = $this;
    if ($this->getReferencedEntity() instanceof ActionableInterface) {
      $result = $this->getReferencedEntity()->getActionFieldnames($viewMode);
      $actionEntity = $this->getReferencedEntity();
    }
    // Filter and sort by view mode
    if (!empty($viewMode)) {
      // If this paragraph references a productable node, we must use the node's display mode settings for action links,
      // because paragraphs themselves can't be productable and therefore can't configure those action links.
      $viewModeObject = $this->entityTypeManager()
        ->getStorage('entity_view_display')
        ->load("{$actionEntity->getEntityTypeId()}.{$actionEntity->bundle()}.{$viewMode}");
      // We might have special display modes on paragraphs that might not be available on the referenced
      // node. To cover those cases, use the paragraph's own display mode settings as fallback.
      if (empty($viewModeObject)) {
        $viewModeObject = $this->entityTypeManager()
          ->getStorage('entity_view_display')
          ->load("{$this->getEntityTypeId()}.{$this->bundle()}.{$viewMode}");
      }
      $enabledComponents = $viewModeObject?->getComponents();
      if (empty($enabledComponents)) {
        return [];
      }
      $result = array_intersect($result, array_keys($enabledComponents));
      uasort($result, function ($a, $b) use ($enabledComponents) {
        return $enabledComponents[$a]['weight'] < $enabledComponents[$b]['weight'] ? -1 : 1;
      });
    }
    if (!empty($this->getActionUrl(ReadmoreActionableInterface::ACTION_READMORE))) {
      // Do not use the referenced read more action, because paragraphs have their own "field_link",
      // which might have extra query params.
      $result[ReadmoreActionableInterface::ACTION_READMORE] = $referencingField->getName();
    }
    else {
      unset($result[ReadmoreActionableInterface::ACTION_READMORE]);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getActionLabel(string $actionId): TranslatableMarkup|string|NULL {
    if ($actionId !== ReadmoreActionableInterface::ACTION_READMORE && $this->getReferencedEntity() instanceof ActionableInterface) {
      return $this->getReferencedEntity()->getActionLabel($actionId);
    }
    $referencingField = $this->getReferencingField();
    if ($actionId === ReadmoreActionableInterface::ACTION_READMORE && $referencingField !== NULL && !$referencingField->isEmpty()) {
      return $referencingField->first()->getTitle() ?? new TranslatableMarkup('Details');
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getActionUrl(string $actionId): ?Url {
    if ($actionId !== ReadmoreActionableInterface::ACTION_READMORE && $this->getReferencedEntity() instanceof ActionableInterface) {
      return $this->getReferencedEntity()->getActionUrl($actionId);
    }
    $referencingField = $this->getReferencingField();
    if ($actionId === ReadmoreActionableInterface::ACTION_READMORE && $referencingField !== NULL && !$referencingField->isEmpty()) {
      $url = $referencingField->first()->getUrl();
      $url->setOption('attributes', array_merge_recursive(
        [
          'data-action-link-entity' => $this->getReferencedEntity()?->getEntityTypeId() ?? $this->getEntityTypeId(),
          'data-action-link-bundle' => $this->getReferencedEntity()?->bundle() ?? $this->bundle(),
          'data-action-link-type' => $actionId,
          'class' => [
            "action-link-{$actionId}",
          ],
        ],
        $url->getOption('attributes') ?? [],
      ));
      return $url;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * Rule: Suppressing the primary action "readmore" == suppressing "field_link" will also suppress other action links.
   */
  public function getRenderedAction(string $actionId, string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $fieldName = $this->getActionFieldnames($viewMode)[$actionId] ?? NULL;
    $referencingField = $this->getReferencingField();
    if (empty($fieldName) || $referencingField === NULL || $this->isFieldSuppressed($referencingField->getName())) {
      return NULL;
    }
    if ($actionId === ReadmoreActionableInterface::ACTION_READMORE && !$referencingField->isEmpty()) {
      $displayOptions = $this->entityTypeManager()
        ->getStorage('entity_view_display')
        ->load("paragraph.{$this->bundle()}.{$viewMode}")
        ?->getComponent($fieldName);
      if (is_null($displayOptions)) {
        return NULL;
      }
      $build = $this->viewBuilder()->viewField(
        $this->get($fieldName),
        $displayOptions
      );
      $build[0]['#options']['attributes'] = array_merge_recursive(
        $build[0]['#options']['attributes'] ?? [],
        $this->get($fieldName)?->first()?->getUrl()?->getOption('attributes') ?? [],
        [
          'class' => ["action-link-{$actionId}"],
          'data-action-link-entity' => $this->getReferencedEntity()?->getEntityTypeId() ?? $this->getEntityTypeId(),
          'data-action-link-bundle' => $this->getReferencedEntity()?->bundle() ?? $this->bundle(),
          'data-action-link-type' => $actionId,
        ]
      );
      $build['#link_action_type'] = $actionId;
      // @see \Drupal\designsystem\DesignHelper::getRealViewmode()
      $build['#view_mode'] = $viewMode;
      return $build;
    }
    if ($this->getReferencedEntity() instanceof ActionableInterface) {
      return $this->getReferencedEntity()->getRenderedAction($actionId, $viewMode);
    }
    return NULL;
  }

}
