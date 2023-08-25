<?php

declare(strict_types = 1);

namespace Drupal\ebr_accommodation\PathProcessor;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\ebr\Entity\ActionableInterface;
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

  /**
   * The request stack.
   */
  protected RequestStack $requestStack;

  /**
   * The entity type manager.
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $requestStack, EntityTypeManager $entityTypeManager) {
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if (empty($request)) {
      $request = $this->requestStack->getCurrentRequest();
    }
    $contextNode = $request->attributes->get('node');

    if (is_numeric($contextNode)) {
      $contextNode = $this->entityTypeManager->getStorage('node')->load($contextNode);
    }

    if (!($contextNode instanceof ActionableInterface)) {
      return $path;
    }

    $actionId = NULL;
    $actionUrl = NULL;
    foreach ($contextNode->getActionFieldnames() as $actionId => $actionField) {
      $actionUrl = $contextNode->getActionUrl($actionId);
      if (!($actionUrl instanceof Url)) {
        return $path;
      }
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
