<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_casablanca\Entity;

use Drupal\Core\Url;
use Drupal\ebr\EntityBusinessrules;
use Drupal\ebr_accommodation\Entity\AccommodationBase;

/**
 * Business rules for accommodation node bundles with Casablanca PMS.
 */
abstract class AccommodationCasablanca extends AccommodationBase {

  /**
   * The "remote_datasource" field value for entities from Casablanca PMS.
   */
  public const DATASOURCE = 'casablanca';

  /**
   * The human readable label of Casablanca PMS software.
   */
  public const NAME = 'Casablanca';

  /**
   * The URL prefix for Casablanca Web Widgets Info Page.
   *
   * The final url has the customer ID appended, like
   * 'https://booking.casablanca.at/Info/XXXXXXXXXX'.
   */
  public const INFOLINK_PREFIX = 'https://booking.casablanca.at/Info/';

  /**
   * {@inheritdoc}
   */
  public function getActionUrl(string $actionId): ?Url {
    $url = parent::getActionUrl($actionId);
    if ($url === NULL ||
      $this->get(EntityBusinessrules::FIELD_REMOTE_ID)->isEmpty() ||
      $this->get(EntityBusinessrules::FIELD_REMOTE_DATASOURCE)->value !== static::DATASOURCE
    ) {
      return $url;
    }

    $queryParams = $url->getOption('query') ?? [];
    if ($this->bundle() === static::BUNDLE_PACKAGE) {
      $queryParams['casapackage'] = $this->get(EntityBusinessrules::FIELD_REMOTE_ID)->value;
    }
    if ($this->bundle() === static::BUNDLE_ROOM) {
      $queryParams['casaroomtype'] = $this->get(EntityBusinessrules::FIELD_REMOTE_ID)->value;
    }
    $url->setOption('query', $queryParams);
    return $url;
  }

}
