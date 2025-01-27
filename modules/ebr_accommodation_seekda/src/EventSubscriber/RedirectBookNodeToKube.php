<?php

namespace Drupal\ebr_accommodation_seekda\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\PathValidatorInterface;
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
class RedirectBookNodeToKube implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public function __construct(
    protected RedirectChecker $checker,
    protected ConfigFactoryInterface $config,
    protected EntityBusinessrules $ebr,
    protected PathValidatorInterface $pathValidator,
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

    $bookingNode = $this->ebr->getEntity('node', AccommodationBase::ACTION_BOOK);
    if (!$bookingNode instanceof NodeInterface || $bookingNode->id() != $requestedNodeId) {
      return;
    }

    $kubeDomain = $this->config->get('ebr_accommodation_seekda.settings')->get('kube_domain');
    if (empty($kubeDomain)) {
      return;
    }

    $redirectUrl = $kubeDomain . '?' . $request->getQueryString();

    preg_match('#^/([a-zA-Z]{2})(/.+)#', $path, $matches);
    $possiblyALangCode = strtolower($matches[1] ?? '');
    if (!empty($possiblyALangCode)) {
      $redirectUrl .= "&skd-language-code={$possiblyALangCode}";
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
