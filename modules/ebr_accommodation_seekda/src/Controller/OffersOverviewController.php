<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_seekda\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\ebr_accommodation_seekda\Seekda;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves the seekda prices and availability for the calendar widget.
 *
 * The data is fetched and cached on the server: visitors' browsers do not
 * connect to seekda, and the API quota (1,200 requests per day and property)
 * is not used up by page views.
 */
class OffersOverviewController implements ContainerInjectionInterface {

  use AutowireTrait;

  /**
   * Constructs the controller.
   */
  public function __construct(
    protected readonly Seekda $seekda,
    protected readonly LanguageManagerInterface $languageManager,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly KillSwitch $pageCacheKillSwitch,
  ) {}

  /**
   * Returns the offersOverview data of the property as JSON.
   */
  public function get(string $langcode): JsonResponse {
    if (!array_key_exists($langcode, $this->languageManager->getLanguages())) {
      throw new NotFoundHttpException();
    }
    // The internal page cache ignores max-age; the data is cached by the
    // Seekda service instead, so a request is still cheap.
    $this->pageCacheKillSwitch->trigger();

    $data = $this->seekda->getOffersOverview($langcode);
    if ($data === NULL) {
      return new JsonResponse(['error' => 'unavailable'], 503);
    }

    $response = new CacheableJsonResponse($data);
    $maxAge = (int) $this->configFactory->get('ebr_accommodation_seekda.settings')->get('calendar_cache_max_age') ?: 3600;
    $response->addCacheableDependency(
      (new CacheableMetadata())
        ->setCacheMaxAge($maxAge)
        ->setCacheTags(['config:ebr_accommodation_seekda.settings'])
        ->setCacheContexts(['url.path'])
    );
    return $response;
  }

}
