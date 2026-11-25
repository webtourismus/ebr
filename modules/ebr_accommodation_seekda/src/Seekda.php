<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_seekda;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Seekda API helpers.
 */
class Seekda {

  /**
   * The access token documented by seekda for its JSON APIs.
   */
  public const DEFAULT_ACCESSTOKEN = '42';

  /**
   * Seconds to cache a failed calendar request, to not repeat it on every view.
   */
  protected const CALENDAR_FAILURE_MAX_AGE = 300;

  /**
   * Constructs the Seekda service.
   */
  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly ClientInterface $httpClient,
    #[Autowire(service: 'cache.default')]
    protected readonly CacheBackendInterface $cache,
    protected readonly TimeInterface $time,
    protected readonly LanguageManagerInterface $languageManager,
    #[Autowire(service: 'logger.channel.ebr_accommodation_seekda')]
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Returns the access token for the Seekda JSON API.
   */
  public function getAccessToken(): string {
    $token = (string) $this->configFactory->get('ebr_accommodation_seekda.settings')->get('api_token');
    return $token !== '' ? $token : self::DEFAULT_ACCESSTOKEN;
  }

  /**
   * Returns the property code for Seekda queries.
   */
  public function getPropertyCode(): string {
    return (string) $this->configFactory->get('ebr_accommodation_seekda.settings')->get('property_code');
  }

  /**
   * Returns the booking source passed to KUBE as "skd-search-id".
   *
   * @return string
   *   The source, or an empty string if disabled.
   */
  public function getSearchId(): string {
    return trim((string) $this->configFactory->get('ebr_accommodation_seekda.settings')->get('search_id'));
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
   * Returns the URL the calendar widget loads its data from.
   *
   * The data is fetched and cached by Drupal, so the visitor's browser does
   * not connect to seekda. The query string keeps overridden calendar
   * templates working that append "&skd-..." parameters to the endpoint.
   *
   * @see \Drupal\ebr_accommodation_seekda\Controller\OffersOverviewController
   */
  public function getCalendarEndpoint(string $langcode): string {
    $options = ['query' => ['format' => 'json']];
    if ($language = $this->languageManager->getLanguage($langcode)) {
      $options['language'] = $language;
    }
    return Url::fromRoute('ebr_accommodation_seekda.offers_overview', ['langcode' => $langcode], $options)->toString();
  }

  /**
   * Returns the seekda offersOverview data for the calendar, cached.
   *
   * @param string $langcode
   *   The language of the texts in the response.
   *
   * @return array|null
   *   The decoded response, or NULL if seekda could not be reached.
   */
  public function getOffersOverview(string $langcode): ?array {
    $config = $this->configFactory->get('ebr_accommodation_seekda.settings');
    $requestTime = $this->time->getRequestTime();
    $startDate = date('Y-m-d', $requestTime);
    $cid = "ebr_accommodation_seekda:offers_overview:{$this->getPropertyCode()}:{$langcode}:{$startDate}";
    if ($cached = $this->cache->get($cid)) {
      return $cached->data;
    }

    $url = Url::fromUri('https://switch.seekda.com/switch/latest/json/offersOverview.json', [
      'query' => [
        'skd-property-code' => $this->getPropertyCode(),
        'token' => $this->getAccessToken(),
        'skd-overview-type' => 'rate',
        'skd-channel-id' => 'IBE',
        'skd-overview-length' => (int) $config->get('jsonapi_months') * 30,
        'skd-language-code' => $langcode,
        'skd-start-date' => $startDate,
      ],
    ])->toString();

    $data = NULL;
    try {
      $response = $this->httpClient->request('GET', $url, [
        'timeout' => 15,
        'headers' => ['Accept' => 'application/json'],
      ]);
      $decoded = json_decode((string) $response->getBody(), TRUE);
      if (is_array($decoded) && isset($decoded['overview']['entities'])) {
        $data = $decoded;
      }
      else {
        $this->logger->warning('Unexpected response from the seekda offersOverview API for language @langcode.', ['@langcode' => $langcode]);
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Could not load the seekda offersOverview API for language @langcode: @message', [
        '@langcode' => $langcode,
        '@message' => $e->getMessage(),
      ]);
    }

    $maxAge = $data === NULL ? self::CALENDAR_FAILURE_MAX_AGE : max(60, (int) $config->get('calendar_cache_max_age') ?: 3600);
    $this->cache->set($cid, $data, $requestTime + $maxAge, ['config:ebr_accommodation_seekda.settings']);
    return $data;
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
