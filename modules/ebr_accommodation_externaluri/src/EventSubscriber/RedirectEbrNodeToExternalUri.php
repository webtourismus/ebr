<?php

namespace Drupal\ebr_accommodation_externaluri\EventSubscriber;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\ebr\EntityBusinessrules;
use Drupal\ebr_accommodation\Entity\AccommodationBase;
use Drupal\node\NodeInterface;
use Drupal\redirect\RedirectChecker;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Redirects the booking node to the Kube domain.
 */
class RedirectEbrNodeToExternalUri implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public function __construct(
    protected RedirectChecker $checker,
    protected ConfigFactoryInterface $config,
    protected EntityBusinessrules $ebr,
    protected PathValidatorInterface $pathValidator,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LanguageManagerInterface $languageManager,
    protected Renderer $renderer,
  ) { }

  /**
   * Handles the redirect if applicable.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onKernelRequestCheckRedirect(RequestEvent $event) {
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
    if ($url->getRouteName() != 'entity.node.canonical' || empty($requestedNodeId)) {
      return;
    }

    $externalBookUri = $this->config->get('system.site')->get('ebr_external_uri.book');
    $externalEnquiryUri = $this->config->get('system.site')->get('ebr_external_uri.enquiry');

    if (empty($externalBookUri) && empty($externalEnquiryUri)) {
      return;
    }

    $bookingNode = $this->ebr->getEntity('node', AccommodationBase::ACTION_BOOK);
    $enquiryNode = $this->ebr->getEntity('node', AccommodationBase::ACTION_ENQUIRY);

    if (!in_array($requestedNodeId, [$bookingNode?->id(), $enquiryNode?->id()])) {
      return;
    }

    $redirectUrl = NULL;
    if ($externalBookUri && $bookingNode instanceof NodeInterface && $bookingNode->id() == $requestedNodeId) {
      $redirectUrl = $externalBookUri;
      $action = AccommodationBase::ACTION_BOOK;
    }
    elseif ($externalEnquiryUri && $enquiryNode instanceof NodeInterface && $enquiryNode->id() == $requestedNodeId) {
      $redirectUrl = $externalEnquiryUri;
      $action = AccommodationBase::ACTION_ENQUIRY;
    }

    if (!$redirectUrl) {
      return;
    }

    $sourceNode = $this->entityTypeManager->getStorage('node')->load(
      $request->query->get('package') ?? $request->query->get('room')
    );
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
      /* fail silently, just pass the url as is */
    }


    $response = new TrustedRedirectResponse($redirectUrl, 307);
    $event->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This needs to run before Redirect module
    $events[KernelEvents::REQUEST][] = ['onKernelRequestCheckRedirect', 34];
    return $events;
  }

}
