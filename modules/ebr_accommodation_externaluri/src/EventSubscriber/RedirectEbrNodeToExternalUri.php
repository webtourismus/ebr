<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_externaluri\EventSubscriber;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\ebr\EntityBusinessrules;
use Drupal\ebr_accommodation\Entity\AccommodationBase;
use Drupal\node\NodeInterface;
use Drupal\redirect\RedirectChecker;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects book/enquiry nodes to a configured external URI.
 */
class RedirectEbrNodeToExternalUri implements EventSubscriberInterface {

  /**
   * Constructs the event subscriber.
   */
  public function __construct(
    protected readonly RedirectChecker $checker,
    protected readonly ConfigFactoryInterface $config,
    #[Autowire(service: 'ebr.service')]
    protected readonly EntityBusinessrules $ebr,
    protected readonly PathValidatorInterface $pathValidator,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LanguageManagerInterface $languageManager,
    protected readonly RendererInterface $renderer,
  ) {}

  /**
   * Handles the redirect if applicable.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onKernelRequestCheckRedirect(RequestEvent $event): void {
    // Get a clone of the request. During inbound processing the request
    // can be altered. Allowing this here can lead to unexpected behavior.
    // For example the path_processor.files inbound processor provided by
    // the system module alters both the path and the request; only the
    // changes to the request will be propagated, while the change to the
    // path will be lost.
    $request = clone $event->getRequest();

    if (!$this->checker->canRedirect($request)) {
      return;
    }

    $path = $request->getPathInfo();
    $url = $this->pathValidator->getUrlIfValid($path);
    if (!$url instanceof Url) {
      return;
    }

    $requestedNodeId = $url->getRouteParameters()['node'] ?? NULL;
    if ($url->getRouteName() !== 'entity.node.canonical' || empty($requestedNodeId)) {
      return;
    }

    $externalBookUri = $this->config->get('ebr_accommodation_externaluri.settings')->get('book');
    $externalEnquiryUri = $this->config->get('ebr_accommodation_externaluri.settings')->get('enquiry');

    if (empty($externalBookUri) && empty($externalEnquiryUri)) {
      return;
    }

    $bookingNode = $this->ebr->getEntity('node', AccommodationBase::ACTION_BOOK);
    $enquiryNode = $this->ebr->getEntity('node', AccommodationBase::ACTION_ENQUIRY);

    if (!in_array($requestedNodeId, [$bookingNode?->id(), $enquiryNode?->id()], TRUE)) {
      return;
    }

    $redirectUrl = NULL;
    $action = NULL;
    if ($externalBookUri && $bookingNode instanceof NodeInterface && $bookingNode->id() === $requestedNodeId) {
      $redirectUrl = $externalBookUri;
      $action = AccommodationBase::ACTION_BOOK;
    }
    elseif ($externalEnquiryUri && $enquiryNode instanceof NodeInterface && $enquiryNode->id() === $requestedNodeId) {
      $redirectUrl = $externalEnquiryUri;
      $action = AccommodationBase::ACTION_ENQUIRY;
    }

    if (!$redirectUrl) {
      return;
    }

    $sourceNodeId = $request->query->get('package') ?? $request->query->get('room');
    // Load via ternary to avoid assert() errors on a dev environment.
    $sourceNode = $sourceNodeId ? $this->entityTypeManager->getStorage('node')->load($sourceNodeId) : NULL;

    $queryString = $request->getQueryString() ?? '';
    if ($sourceNode) {
      // Remove our own entity parameter to avoid potential collisions with the external URI's parameter.
      $queryString = preg_replace("/&?{$sourceNode->bundle()}={$sourceNode->id()}/", '', $queryString);
    }
    if ($queryString) {
      $redirectUrl .= (!str_contains($redirectUrl, '?') ? '?' : '&') . $queryString;
    }

    $build = [
      '#type' => 'inline_template',
      '#template' => $redirectUrl,
      '#context' => [
        'node' => $sourceNode,
        'action' => $action,
        'language' => $this->languageManager->getCurrentLanguage(),
      ],
    ];
    try {
      $redirectUrl = DeprecationHelper::backwardsCompatibleCall(
        currentVersion: \Drupal::VERSION,
        deprecatedVersion: '10.3',
        currentCallable: fn() => trim($this->renderer->renderInIsolation($build)),
        deprecatedCallable: fn() => trim($this->renderer->renderPlain($build)),
      );
    }
    catch (\Exception $exception) {
      /* Fail silently, just pass the url as is. */
    }

    $response = new TrustedRedirectResponse($redirectUrl, 307);
    $event->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // This needs to run before Redirect module.
    return [
      KernelEvents::REQUEST => ['onKernelRequestCheckRedirect', 34],
    ];
  }

}
