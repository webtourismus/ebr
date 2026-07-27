<?php

declare(strict_types = 1);

namespace Drupal\ebr_accommodation_seekda\Entity;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ebr_accommodation\Entity\AccommodationBase;
use Drupal\ebr\Entity\WidgetableInterface;
use Drupal\ebr\EntityBusinessrules;
use Drupal\Core\Url;

/**
 * Summary of Room.
 */
abstract class AccommodationSeekda extends AccommodationBase implements WidgetableInterface {

  /**
   * The "remote_datasource" field value for entites from Seekda CM.
   */
  public const DATASOURCE = 'seekda';

  /**
   * The human readable label of EasyBooking PMS software.
   */
  public const NAME = 'Seekda';

  /**
   * Calendar widget showing prices and availability in Seekda Channel manager.
   */
  public const WIDGET_CALENDAR = 'calendar';

  /**
   * {@inheritDoc}
   */
  public static function getDefaultWidgets(): array {
    return [
      self::WIDGET_CALENDAR,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function getDefaultWidgetLabel($widgetType): TranslatableMarkup {
    return match ($widgetType) {
      self::WIDGET_CALENDAR => new TranslatableMarkup('Prices & Availability'),
    };
  }

  /**
   * {@inheritDoc}
   */
  public function getWidgetFieldnames(): array {
    $result = [];
    if ($this->get(EntityBusinessrules::FIELD_REMOTE_DATASOURCE)->value == AccommodationSeekda::DATASOURCE &&
      !$this->get(EntityBusinessrules::FIELD_REMOTE_ID)->isEmpty()
    ) {
      foreach ($this->getDefaultWidgets() as $widgetId) {
        $result[$widgetId] = WidgetableInterface::WIDGET_FIELD_PREFIX . $widgetId;
      }
    }
    return $result;
  }

  /**
   * {@inheritDoc}
   */
  public function getWidgetLabel(string $widgetId): ?TranslatableMarkup {
    if (array_key_exists($widgetId, $this->getWidgetFieldnames())) {
      return $this->getDefaultWidgetLabel($widgetId);
    }
    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getWidgetVariables($widgetType): array {
    return [
      'property_code' => \Drupal::service('seekda.service')->getPropertyCode(),
      'token' => \Drupal::service('seekda.service')->getAccessToken(),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getRenderedWidget(string $widgetId, string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $field = $this->getWidgetFieldnames()[$widgetId] ?? NULL;
    if (empty($field)) {
      return NULL;
    }
    $displayOptions = $this->entityTypeManager()
      ->getStorage('entity_view_display')
      ->load("node.{$this->bundle()}.{$viewMode}")
      ?->getComponent($field);
    if (is_null($displayOptions)) {
      return NULL;
    }
    $build = $this->entityTypeManager()->getViewBuilder('node')->viewField(
      $field,
      $displayOptions
    );
    $build['#widget_type'] = $widgetId;
    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public function getActionUrl(string $actionId): ?Url {
    $url = parent::getActionUrl($actionId);
    if (is_null($url) ||
      $this->get(EntityBusinessrules::FIELD_REMOTE_ID)->isEmpty() ||
      $this->get(EntityBusinessrules::FIELD_REMOTE_DATASOURCE)->value != static::DATASOURCE
    ) {
      return $url;
    }

    $queryParams = $url->getOption('query');
    if ($this->bundle() == static::BUNDLE_PACKAGE) {
      $queryParams['skd-package-view'] = $this->get(EntityBusinessrules::FIELD_REMOTE_ID)->value;
    }
    if ($this->bundle() == static::BUNDLE_ROOM) {
      $queryParams['skd-room-view'] = $this->get(EntityBusinessrules::FIELD_REMOTE_ID)->value;
    }
    $url->setOption('query', $queryParams);
    return $url;
  }
}
