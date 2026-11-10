<?php

declare(strict_types=1);

namespace Drupal\ebr\Twig;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ebr\EntityBusinessrules;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig helpers for loading EBR entities by internal_id.
 */
class EbrTwigExtension extends AbstractExtension {

  /**
   * Constructs the Twig extension.
   */
  public function __construct(
    protected readonly EntityBusinessrules $ebr,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [new TwigFunction('ebr', [$this, 'getEntity'])];
  }

  /**
   * Returns the entity for the given internal_id.
   *
   * @see \Drupal\ebr\EntityBusinessrules::getEntity()
   */
  public function getEntity(
    string $entityTypeId,
    string $internalId,
    ?string $langCode = NULL,
    bool $checkAccess = TRUE,
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

  /**
   * Null-safe replacement of Twig Tweak's "entity_url" filter.
   *
   * @return \Drupal\Core\Url|string
   *   The URL of the entity, or an empty string without an entity.
   *
   * @see \Drupal\twig_tweak\TwigTweakExtension::entityUrl()
   * @see ebr_twig_tweak_filters_alter()
   */
  public static function entityUrl(?EntityInterface $entity, string $rel = 'canonical', array $options = []): Url|string {
    return $entity ? $entity->toUrl($rel, $options) : '';
  }

  /**
   * Null-safe replacement of Twig Tweak's "entity_link" filter.
   *
   * @return \Drupal\Core\Link|string
   *   The link to the entity, or an empty string without an entity.
   *
   * @see \Drupal\twig_tweak\TwigTweakExtension::entityLink()
   * @see ebr_twig_tweak_filters_alter()
   */
  public static function entityLink(?EntityInterface $entity, ?string $text = NULL, string $rel = 'canonical', array $options = []): Link|string {
    return $entity ? $entity->toLink($text, $rel, $options) : '';
  }

}
