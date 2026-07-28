<?php

declare(strict_types=1);

namespace Drupal\ebr\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * A content entity potentially providing outbound contextual action links.
 */
interface ActionableInterface extends ContentEntityInterface {

  /**
   * The prefix for all action field names.
   */
  public const ACTION_FIELD_PREFIX = 'link_';

  /**
   * Returns all default action IDs for this entity bundle type.
   *
   * This function is used to provide a default set of configurable fields
   * in field UI. For the real computed set of actually existing actions,
   * use getActionFieldnames() instead!
   */
  public static function getDefaultActions(): array;

  /**
   * Returns the human readable label of a default call-to-action.
   *
   * This function is used to provide a labels for default actions
   * in field UI.
   */
  public static function getDefaultActionLabel(string $actionId): TranslatableMarkup|string|NULL;

  /**
   * Returns names of available action fields for this entity.
   *
   * The array keys are the available action IDs, the array values are their
   * corresponding field (or render array) names injected into the entities
   * "content" render array for use in Twig.
   *
   * Optionally sort and filter fields as specified in the given view mode.
   */
  public function getActionFieldnames(?string $viewMode = NULL): array;

  /**
   * Returns the human readable label of a call-to-action.
   */
  public function getActionLabel(string $actionId): TranslatableMarkup|string|NULL;

  /**
   * Returns the target URL of a call-to-action.
   */
  public function getActionUrl(string $actionId): ?Url;

  /**
   * Returns an call-to-action as render array.
   */
  public function getRenderedAction(string $actionId, string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array;

}
