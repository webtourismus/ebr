<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation\Entity;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ebr\Entity\ActionableInterface;
use Drupal\ebr\Entity\ProductableInterface;
use Drupal\ebr\EntityBusinessrules;
use Drupal\ebr_teaser\Entity\ReadmoreActionTrait;
use Drupal\ebr_teaser\Entity\NodeTeaserableTrait;
use Drupal\ebr_teaser\Entity\TeaserableInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Business rules for accomodation node bundles "room" and "package".
 */
abstract class AccommodationBase extends Node implements ActionableInterface, TeaserableInterface, ProductableInterface {
  use NodeTeaserableTrait;
  use ReadmoreActionTrait {
    getDefaultActions as protected getReadmoreActions;
    getDefaultActionLabel as protected getReadmoreActionLabel;
    getActionUrl as protected getReadmoreActionUrl;
  }

  /**
   * The "remote_datasource" field value for actionable accommodation entites.
   *
   * This datasource value has no special rules tied to external PMS,
   * and typically uses internal webforms for action links.
   */
  public const DATASOURCE = '_dummy';

  /**
   * Accomodation nodes are rentable entities and can be booked.
   */
  public const ACTION_BOOK = 'book';

  /**
   * Accomodation nodes are product-like entities and can be enquired.
   */
  public const ACTION_ENQUIRY = 'enquiry';

  /**
   * Accomodation node type "room".
   */
  public const BUNDLE_ROOM = 'room';

  /**
   * Accomodation node type "package".
   */
  public const BUNDLE_PACKAGE = 'package';

  /**
   * {@inheritdoc}
   */
  public static function getDefaultActions(): array {
    return array_merge(static::getReadmoreActions(), [static::ACTION_BOOK, static::ACTION_ENQUIRY]);
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultActionLabel(string $actionId): TranslatableMarkup|string|NULL {
    return match ($actionId) {
      static::ACTION_BOOK => new TranslatableMarkup('Book', [], ['context' => 'accommodation']),
      static::ACTION_ENQUIRY => new TranslatableMarkup('Enquiry', [], ['context' => 'anfragen']),
      default => self::getReadmoreActionLabel($actionId),
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getActionUrl(string $actionId): ?Url {
    if (in_array($actionId, $this->getReadmoreActions(), TRUE)) {
      return $this->getReadmoreActionUrl($actionId);
    }
    if ($this->get(EntityBusinessrules::FIELD_REMOTE_DATASOURCE)->isEmpty()) {
      return NULL;
    }
    if ($actionId === static::ACTION_BOOK && $this->get(EntityBusinessrules::FIELD_REMOTE_ID)->isEmpty()) {
      return NULL;
    }
    $query = $this->entityTypeManager()->getStorage('node')->getQuery();
    $query->accessCheck(TRUE);
    $query->condition(EntityBusinessrules::FIELD_INTERNAL_ID, $actionId);
    $query->condition('status', 1);
    $query->sort('nid');
    $nodeIds = $query->execute() ?? [];
    $nodeId = reset($nodeIds);
    if (empty($nodeId)) {
      return NULL;
    }
    $node = $this->entityTypeManager()->getStorage('node')->load($nodeId);
    if (!($node instanceof NodeInterface)) {
      return NULL;
    }
    $url = Url::fromRoute(
      'entity.node.canonical',
      [
        'node' => $node->id(),
      ],
      [
        'query' => [
          "{$this->bundle()}" => $this->id(),
        ],
        'attributes' => [
          'data-action-link-entity' => $this->getEntityTypeId(),
          'data-action-link-bundle' => $this->bundle(),
          'data-action-link-type' => $actionId,
          'class' => [
            "action-link-{$actionId}",
          ],
        ],
      ],
    );
    return $url;
  }

}
