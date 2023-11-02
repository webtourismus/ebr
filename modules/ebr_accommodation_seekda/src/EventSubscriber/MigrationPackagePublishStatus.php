<?php

declare(strict_types = 1);

namespace Drupal\ebr_accommodation_seekda\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
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
   * The grace period in seconds for outdated packages between "package changed timestamp" and "last cron timestamp".
   */
  private const UNPUBLISH_GRACE_PERIOD = 300;

  /**
   * The EntityType Manager.
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * The KeyValue
   */
  protected KeyValueFactoryInterface $keyValue;

  /**
   * The constructor.
   */
  public function __construct(EntityTypeManager $entityTypeManager, KeyValueFactoryInterface $keyValue) {
    $this->entityTypeManager = $entityTypeManager;
    $this->keyValue = $keyValue;
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
   * Event callback to unpublish outdated packages.
   *
   * The migration _must_ run with "update" flag for this to work.
   * Otherwise active packages will become unpublished too.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The migration import event.
   */
  public function unpublish(MigrateImportEvent $event): void {
    $migration = $event->getMigration();
    if (strpos($migration->id(), '_seekda_packages_') !== 3) {
      return;
    }
    $lastImportTime = round($this->keyValue->get('migrate_last_imported')->get($migration->id(), 0) / 1000);

    $allSeekdaPackages = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => 'package',
      EntityBusinessrules::FIELD_REMOTE_DATASOURCE => AccommodationSeekda::DATASOURCE,
      'default_langcode' => 1,
    ]);

    foreach ($allSeekdaPackages as $package) {
      $remoteId = $package->get(EntityBusinessrules::FIELD_REMOTE_ID)->value;
      if (!$remoteId) {
        continue;
      }
      $packageUpdateTime = $package->get('changed')->value;
      if ($packageUpdateTime + self::UNPUBLISH_GRACE_PERIOD > $lastImportTime ) {
        continue;
      }
      $package->set('status', 0);
      $package->save();
    }
  }
}
