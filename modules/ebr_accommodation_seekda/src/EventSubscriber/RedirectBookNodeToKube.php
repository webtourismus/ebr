<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_seekda\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\PathValidatorInterface;
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
 * Redirects the booking node to the KUBE domain.
 */
class RedirectBookNodeToKube implements EventSubscriberInterface {

  /**
   * Constructs the event subscriber.
   */
  public function __construct(
    protected readonly RedirectChecker $checker,
    protected readonly ConfigFactoryInterface $config,
    #[Autowire(service: 'ebr.service')]
    protected readonly EntityBusinessrules $ebr,
    protected readonly PathValidatorInterface $pathValidator,
  ) {}

  /**
   * Handles the redirect if applicable.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onKernelRequestCheckRedirect(RequestEvent $event): void {
    // Get a clone of the request. During inbound processing the request can be
    // altered. Allowing this here can lead to unexpected behavior. For example
    // the path_processor.files inbound processor provided by the system module
    // alters both the path and the request; only the changes to the request
    // will be propagated, while the change to the path will be lost.
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

    $bookingNode = $this->ebr->getEntity('node', AccommodationBase::ACTION_BOOK);
    if (!$bookingNode instanceof NodeInterface || $bookingNode->id() !== $requestedNodeId) {
      return;
    }

    $kubeDomain = $this->config->get('ebr_accommodation_seekda.settings')->get('kube_domain');
    if (empty($kubeDomain)) {
      return;
    }

    $redirectUrl = $kubeDomain . '?' . $request->getQueryString();

    preg_match('#^/([a-zA-Z]{2})(/.+)#', $path, $matches);
    $possiblyALangCode = strtolower($matches[1] ?? '');
    if ($possiblyALangCode !== '') {
      $redirectUrl .= "&skd-language-code={$possiblyALangCode}";
    }

    $response = new TrustedRedirectResponse($redirectUrl, 307);
    $event->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // This needs to run before the Redirect module.
    return [
      KernelEvents::REQUEST => ['onKernelRequestCheckRedirect', 34],
    ];
  }

}
