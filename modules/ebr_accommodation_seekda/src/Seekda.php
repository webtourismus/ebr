<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_seekda;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;

/**
 * Seekda Channel Manager API helpers.
 */
class Seekda {

  /**
   * The access token required to access the API.
   *
   * (Yes, this really is a const!)
   */
  protected const ACCESSTOKEN = '42';

  /**
   * Constructs the Seekda service.
   */
  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Returns the access token for the Seekda JSON API.
   */
  public function getAccessToken(): string {
    return self::ACCESSTOKEN;
  }

  /**
   * Returns the property code for Seekda Channel Manager queries.
   */
  public function getPropertyCode(): string {
    return (string) $this->configFactory->get('ebr_accommodation_seekda.settings')->get('property_code');
  }

  /**
   * Returns the user ID used as owner for migrated entities.
   */
  public function getMigrationUid(): int {
    $uid = (int) $this->configFactory->get('ebr_accommodation_seekda.settings')->get('migration_uid');
    return $uid > 0 ? $uid : 1;
  }

  /**
   * Migrate process callback: returns the configured migration owner uid.
   *
   * @param mixed $value
   *   Ignored source value.
   */
  public static function migrationOwnerUid(mixed $value = NULL): int {
    return \Drupal::service('ebr_accommodation_seekda.seekda')->getMigrationUid();
  }

  /**
   * Whether a migration ID is a Seekda packages migration.
   */
  public function isPackageMigration(string $migrationId): bool {
    return str_contains($migrationId, '_seekda_packages_');
  }

  /**
   * Builds the calendar widget offersOverview endpoint URL.
   */
  public function getCalendarEndpoint(): string {
    $config = $this->configFactory->get('ebr_accommodation_seekda.settings');
    $days = (int) $config->get('jsonapi_months') * 30;
    return Url::fromUri('https://switch.seekda.com/switch/latest/json/offersOverview.json', [
      'query' => [
        'skd-property-code' => $this->getPropertyCode(),
        'token' => $this->getAccessToken(),
        'skd-overview-type' => 'rate',
        'skd-channel-id' => 'IBE',
        'skd-overview-length' => $days,
      ],
    ])->toString();
  }

  /**
   * Builds ratesAverage endpoint URL(s) for migration import.
   *
   * @return list<string>
   */
  public function getMigrationUrls(string $langcode, bool $packages = FALSE): array {
    $months = (int) $this->configFactory->get('ebr_accommodation_seekda.settings')->get('jsonapi_months');
    $url = Url::fromUri('https://switch.seekda.com/switch/latest/json/ratesAverage.json', [
      'query' => [
        'skd-property-code' => $this->getPropertyCode(),
        'token' => $this->getAccessToken(),
        'skd-language-code' => $langcode,
        'skd-packages' => $packages ? 'true' : 'false',
        'skd-months' => $months,
      ],
    ])->toString();
    return [$url];
  }

}
