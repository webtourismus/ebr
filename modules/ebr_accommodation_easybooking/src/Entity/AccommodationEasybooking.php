<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_easybooking\Entity;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ebr\Entity\WidgetableInterface;
use Drupal\ebr\Entity\WidgetableTrait;
use Drupal\ebr\EntityBusinessrules;
use Drupal\ebr_accommodation\Entity\AccommodationBase;

/**
 * Summary of Room
 */
class AccommodationEasybooking extends AccommodationBase implements WidgetableInterface {
  use WidgetableTrait;

  /**
   * The "remote_datasource" field value for entites from EasyBooking PMS.
   */
  public const DATASOURCE = 'easybooking';

  /**
   * The human readable label of EasyBooking PMS software.
   */
  public const NAME = 'EasyBooking';

  /**
   * The rates web widget from EasyBooking PMS software.
   */
  public const WIDGET_RATES = 'rates';

  /**
   * The availability web widget from EasyBooking PMS software.
   */
  public const WIDGET_AVAILABILITY = 'availability';

  /**
   * The availability web widget from EasyBooking PMS software.
   */
  public const WIDGET_PRICECOMPARISON = 'pricecomparison';

  /**
   * The URL prefix for Casablanca Web Widgets Info Page.
   *
   * The final url has customer ID and serial nr. as query params appended, like
   * 'https://www.easy-booking.at/preview/?...&cid=1234&serialNo=1111-2222-3333-4444-5555-6666-7777...'.
   */
  public const INFOLINK_PREFIX = 'https://www.easy-booking.at/preview/';

  /**
   * Return the customer ID (cid) for EasyBooking Widgets.
   */
  public function getCustomerId(): string {
    return \Drupal::configFactory()->get('system.site')->get('easybooking.customer_id');
  }

  /**
   * Return the serial number (serialNr) for EasyBooking Widgets.
   */
  public function getSerialNr(): string {
    return \Drupal::configFactory()->get('system.site')->get('easybooking.serial_nr');
  }

  /**
   * Given a ISO2 langcode, return the numeric language ID for EasyBooking Widgets.
   */
  public static function getWidgetLangId(string $langcode): int {
    return match($langcode) {
      'en' => 1,
      'de' => 2,
      'nl' => 3,
      'it' => 4,
      'fr' => 5,
      'hu' => 6,
      'ru' => 10,
      default => 1,
    };
  }

  /**
   * @inheritDoc
   */
  public static function getDefaultWidgets(): array {
    return [
      self::WIDGET_RATES,
      self::WIDGET_AVAILABILITY,
      self::WIDGET_PRICECOMPARISON,
    ];
  }

  public static function getDefaultWidgetLabel(string $widgetId): ?TranslatableMarkup {
    return match ($widgetId) {
      self::WIDGET_RATES => new TranslatableMarkup('Prices'),
      self::WIDGET_AVAILABILITY => new TranslatableMarkup('Availability'),
      self::WIDGET_PRICECOMPARISON => new TranslatableMarkup('Comparison of prices'),
    };
  }

  public function getWidgetFieldnames(): array {
    $result = [];
    if ($this->get(EntityBusinessrules::FIELD_REMOTE_DATASOURCE)->value == AccommodationEasybooking::DATASOURCE &&
      !$this->get(EntityBusinessrules::FIELD_REMOTE_ID)->isEmpty()
    ) {
      foreach ($this->getDefaultWidgets() as $widgetId) {
        $result[$widgetId] = WidgetableInterface::WIDGET_FIELD_PREFIX . $widgetId;
      }
    }
    return $result;
  }

  public function getWidgetLabel(string $widgetId): ?TranslatableMarkup {
    if (array_key_exists($widgetId, $this->getWidgetFieldnames())) {
      return $this->getDefaultWidgetLabel($widgetId);
    }
    return NULL;
  }

  public function getWidgetVariables(string $widgetId): array {
    if (array_key_exists($widgetId, $this->getWidgetFieldnames())) {
      return [
        'customer_id' => $this->getCustomerId(),
        'serial_nr' => $this->getSerialNr(),
        'lang_id' => $this->getWidgetLangId($this->languageManager()->getCurrentLanguage()->getId()),
      ];
    }
    return [];
  }

  public function getRenderedWidget(string $widgetId, string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $field = $this->getWidgetFieldnames()[$widgetId] ?? NULL;
    if (empty($field)) {
      null;
    }
    $displayOptions = $this->entityTypeManager()
      ->getStorage('entity_view_display')
      ->load("node.{$this->bundle()}.{$viewMode}")
      ?->getComponent($field);
    if (is_null($displayOptions)) {
      null;
    }
    $build = $this->entityTypeManager()->getViewBuilder('node')->viewField(
      $field,
      $displayOptions
    );
    $build['#widget_type'] = $widgetId;
    return $build;
  }
}
