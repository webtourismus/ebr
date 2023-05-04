<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_seekda\Entity;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ebr\Entity\WidgetableInterface;
use Drupal\ebr_accommodation\Entity\AccommodationBase;

/**
 * Summary of Room
 */
class AccommodationSeekda extends AccommodationBase /* implements WidgetableInterface */ {

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
   * @inheritDoc
   */
  public static function getDefaultWidgets(): array {
    return [
      self::WIDGET_CALENDAR,
    ];
  }

  public static function getDefaultWidgetLabel($widgetType): TranslatableMarkup {
    return match ($widgetType) {
      self::WIDGET_CALENDAR => new TranslatableMarkup('Prices & Availability'),
    };
  }

  public function getWidgetVariables($widgetType): array {
    return [
      'property_code' => \Drupal::service('seekda.service')->getPropertyCode(),
      'token' => \Drupal::service('seekda.service')->getAccessToken(),
    ];
  }
}
