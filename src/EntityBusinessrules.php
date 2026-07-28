<?php

declare(strict_types=1);

namespace Drupal\ebr;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The EntityBusinessrules service.
 */
class EntityBusinessrules {
  use StringTranslationTrait;

  /**
   * Entity types having fields allowing custom business rules.
   *
   * @internal This is intentionally protected to potentially allow other
   * modules to decorate this service and the corresponding getter.
   */
  protected const ENTITY_TYPES = ['node', 'media', 'taxonomy_term', 'block_content'];

  /**
   * The internal_id field name.
   */
  public const FIELD_INTERNAL_ID = 'internal_id';

  /**
   * The remote_id field name.
   */
  public const FIELD_REMOTE_ID = 'remote_id';

  /**
   * The remote_datasource field name.
   */
  public const FIELD_REMOTE_DATASOURCE = 'remote_datasource';

  /**
   * The internal_notes field name.
   *
   * Constant name kept for API compatibility with existing callers.
   *
   * @see ebr_internal_notes.module
   */
  public const FIELD_INTERAL_NOTES = 'internal_notes';

  /**
   * The constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityRepositoryInterface $entityRepository,
  ) { }

  /**
   * Returns entity types having fields for business rules.
   */
  public function getEntityTypes(): array {
    return self::ENTITY_TYPES;
  }

  /**
   * Returns the (machine) field names provided by EBR.
   */
  public function getFieldNames(): array {
    return [self::FIELD_INTERNAL_ID, self::FIELD_REMOTE_ID, self::FIELD_REMOTE_DATASOURCE];
  }

  /**
   * Adds the entity business rules information container to the site settings form.
   *
   * @internal To be called only once only by the entity_businessrules itself.
   */
  public function renderBusinessRulesContainer(array &$systemSiteInformationSettingsForm) {
    $systemSiteInformationSettingsForm['entity_businessrules'] = [
      '#type' => 'details',
      '#title' => $this->t('Content with special rules'),
      '#open' => FALSE,
    ];
    // @see \Drupal\ebr\EntityBusinessrules::renderRule()
    $systemSiteInformationSettingsForm['entity_businessrules']['rules'] = [
      '#type' => 'container',
    ];

    $internalContent = [];
    foreach ($this->getEntityTypes() as $entityType) {
      $query = $this->entityTypeManager->getStorage($entityType)->getQuery();
      $query->accessCheck(FALSE);
      $query->exists(self::FIELD_INTERNAL_ID);
      $entityIds = $query->execute();
      if (!empty($entityIds)) {
        ksort($entityIds);
        $internalContent[$entityType] = $entityIds;
      }
    }
    if (empty($internalContent)) {
      return;
    }

    $systemSiteInformationSettingsForm['entity_businessrules']['table_description'] = [
      '#type' => 'inline_template',
      '#template' => '<p><strong>{{ "List of special content entities"|t }}</strong></p>',
    ];

    $header = [
      $this->t('type'),
      $this->t('ID'),
      $this->t('Title'),
      $this->t('Internal ID'),
    ];
    $rows = [];
    foreach ($internalContent as $entityTypeId => $entityIds) {
      $entities = $this->entityTypeManager->getStorage($entityTypeId)->loadMultiple($entityIds);
      foreach ($entities as $entity) {
        $rows[] = [
          $entity->getEntityType()->getLabel(),
          $entity->id(),
          $entity->toLink($entity->label(), 'edit-form'),
          $entity->get(self::FIELD_INTERNAL_ID)->value,
        ];
      }
    }
    $systemSiteInformationSettingsForm['entity_businessrules']['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

  /**
   * Renders information on entity business rules in the site settings form.
   *
   * @param array $systemSiteInformationSettingsForm
   *   The site settings form render array.
   * @param string $ruleId
   *   An ID used to group multiple $ruleDescriptions.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string $ruleTitle
   *   The title for a rule.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string $ruleDescription
   *   The text description for a rule.
   */
  public function renderRule(array &$systemSiteInformationSettingsForm,
    string $ruleId,
    TranslatableMarkup|string $ruleTitle,
    TranslatableMarkup|string $ruleDescription):void {
    if (!array_key_exists('rules', $systemSiteInformationSettingsForm['entity_businessrules'] ?? [])) {
      return;
    }

    $systemSiteInformationSettingsForm['entity_businessrules']['rules'][$ruleId]['title'] = [
      '#type' => 'inline_template',
      '#template' => '<p><strong>{{ rule_title }}</strong></p>',
      '#context' => [
        'rule_title' => $ruleTitle,
      ],
    ];
    if (!array_key_exists('item_list', $systemSiteInformationSettingsForm['entity_businessrules']['rules'][$ruleId])) {
      $systemSiteInformationSettingsForm['entity_businessrules']['rules'][$ruleId]['item_list'] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => [],
      ];
    }
    $systemSiteInformationSettingsForm['entity_businessrules']['rules'][$ruleId]['item_list']['#items'][] = $ruleDescription;
  }

  /**
   * Returns the entity for the given interal_id.
   *
   * @param string $entityTypeId
   *   The entity type.
   * @param string $internalId
   *   The internal_id.
   * @param string $language
   *   The language code for the entity. Defaults to current content language.
   * @return EntityInterface|null
   */
  public function getEntity(string $entityTypeId, string $internalId, ?string $langCode = NULL): ?EntityInterface {
    if (!in_array($entityTypeId, $this->getEntityTypes(), TRUE) || $entityTypeId === '' || $internalId === '') {
      return NULL;
    }
    $entities = $this->entityTypeManager->getStorage($entityTypeId)->loadByProperties([
      'internal_id' => $internalId,
    ]);
    ksort($entities, SORT_NUMERIC);
    $entity = reset($entities);
    if (!($entity instanceof EntityInterface)) {
      return NULL;
    }
    return $this->entityRepository->getTranslationFromContext($entity, $langCode);
  }
}
