<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation\PathProcessor;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\ebr\Entity\ActionableInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds contextual query parameters and fragments to actionable outbound links.
 *
 * Within the scope of an entity, the outbound context is naturally available.
 * This path processor applies the same context to global action links
 * outside the entity scope (menus, header blocks, etc...).
 */
class OutboundPathProcessor implements OutboundPathProcessorInterface {

  protected RequestStack $requestStack;

  /**
   * The constructor.
   *
   * @param RequestStack $request_stack
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if (empty($request)) {
      $request = $this->requestStack->getCurrentRequest();
    }
    $contextNode = $request->attributes->get('node');

    if (is_numeric($contextNode)) {
      $contextNode = Node::load($contextNode);
    }

    if (!($contextNode instanceof ActionableInterface)) {
      return $path;
    }

    $actionId = NULL;
    $actionUrl = NULL;
    foreach ($contextNode->getActionFieldnames() as $actionId => $actionField) {
      $actionUrl = $contextNode->getActionUrl($actionId);
      if (ltrim($path, '/') == $actionUrl->getInternalPath()) {
        if ($bubbleable_metadata) {
          $bubbleable_metadata->addCacheableDependency($contextNode);
        }
        if (!empty($actionUrl->getOption('query'))) {
          $options['query'] = array_merge($options['query'] ?? [], $actionUrl->getOption('query'));
        }
        if (!empty($actionUrl->getOption('fragment'))) {
          $options['fragment'] = $actionUrl->getOption('fragment');
        }
      }
    }
    return $path;
  }
}
