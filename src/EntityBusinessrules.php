<?php

declare(strict_types = 1);

namespace Drupal\ebr;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
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
   * modules to decorate this serivce and the corresponding getter.
   */
  protected const ENTITY_TYPES = ['node', 'media', 'taxonomy_term'];

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
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

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

}
