<?php

namespace Drupal\ebr\Twig;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\ebr\EntityBusinessrules;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EbrTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function __construct(protected EntityBusinessrules $ebr) { }

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [new TwigFunction('ebr', [$this, 'getEntity'])];
  }

  /**
   * Returns the entity for the given interal_id.
   *
   * @see Drupal\ebr\EntityBusinessrules::getEntity()
   */
  public function getEntity(
    string $entityTypeId,
    string $internalId,
    ?string $langCode = NULL,
    bool $checkAccess = FALSE
  ): ?EntityInterface {
    $entity = $this->ebr->getEntity($entityTypeId, $internalId, $langCode);

    if (!$entity) {
      return NULL;
    }

    $access = $checkAccess ? $entity->access('view', NULL, TRUE) : AccessResult::allowed();

    if (!$access->isAllowed()) {
      return NULL;
    }

    return $entity;
  }
}
