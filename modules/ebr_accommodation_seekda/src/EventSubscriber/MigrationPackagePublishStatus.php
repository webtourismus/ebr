<?php

declare(strict_types = 1);

namespace Drupal\ebr_accommodation_seekda\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\ebr\EntityBusinessrules;
use Drupal\ebr_accommodation_seekda\Entity\AccommodationSeekda;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Unpublish seekda packages no longer in the JSON API source result.
 */
class MigrationPackagePublishStatus implements EventSubscriberInterface {

  /**
   * The EntityType Manager.
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * The constructor.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MigrateEvents::POST_IMPORT => 'unpublish'
    ];
  }

  /**
   * Event callback to unpublish packages missing in the API.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The migration import event.
   */
  public function unpublish(MigrateImportEvent $event): void {
    $migration = $event->getMigration();
    if ($migration->id() != '120_seekda_packages_de') {
      return;
    }

    // @todo remove the state dependant return once drupal.org/i/3130358 is _really_ fixed
    // BUGFIX_3130358_PART1 post_migration event is triggered once for every row instead once at the end of a complete migration
    // as a workaround we save timestamp to avoid mulitple runs of this trigger
    $lastUnpublishRun = \Drupal::state()->get("ebr_accommodation_seekda.last_postmigrationevent_unpulish.{$migration->id()}", 0);
    $requestTime = \Drupal::service('datetime.time')->getRequestTime();
    $maxCronRuntime = \Drupal::configFactory()->get('ebr_accommodation_seekda.settings')->get('cron_max_runtime');
    if ($requestTime < $lastUnpublishRun + $maxCronRuntime) {
      return;
    }
    // end BUGFIX_3130358_PART1

    $source = clone $migration->getSourcePlugin();
    $activePackages = [];
    // BUGFIX_3130358_PART2
    // because the event fired right after the first row due bug, do not rewind() because it also does a next()
    // $source->rewind();
    // end BUGFIX_3130358_PART2
    while ($source->valid()) {
      $activePackages[] = $source->current()->getSourceProperty('package_code');
      $source->next();
    }

    $allSeekdaPackages = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => 'package',
      EntityBusinessrules::FIELD_REMOTE_DATASOURCE => AccommodationSeekda::DATASOURCE,
      'langcode' => 'de',
    ]);

    foreach ($allSeekdaPackages as $package) {
      $remoteId = $package->get(EntityBusinessrules::FIELD_REMOTE_ID)->value;
      if (!$remoteId) {
        continue;
      }
      $package->set('status', (int) in_array($remoteId, $activePackages));
      $package->save();
    }

    // BUGFIX_3130358_PART3
    \Drupal::state()->set("ebr_accommodation_seekda.last_postmigrationevent_unpulish.{$migration->id()}", $requestTime);
    // end BUGFIX_3130358_PART3
  }
}
