<?php

declare(strict_types=1);

namespace Drupal\ebr;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\ebr\EventSubscriber\EbrEntityCloneEventSubscriber;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Conditionally registers services that depend on optional modules.
 */
class EbrServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    $modules = $container->getParameter('container.modules');
    if (!isset($modules['entity_clone'])) {
      return;
    }

    $container->register('ebr.entity_clone_event_subscriber', EbrEntityCloneEventSubscriber::class)
      ->addArgument(new Reference('messenger'))
      ->addTag('event_subscriber');
  }

}
