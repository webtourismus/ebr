<?php

declare(strict_types = 1);

namespace Drupal\ebr_accommodation_seekda\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
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
   * The constructor.
   */
  public function __construct(
    protected EntityTypeManager $entityTypeManager,
    protected KeyValueFactoryInterface $keyValue,
    protected ConfigFactoryInterface $config,
  ) { }

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
    $unpublishGracePeriod = $this->config->get('ebr_accommodation_seekda')->get('migrate_post_import_unpublish_package');

    foreach ($allSeekdaPackages as $package) {
      $remoteId = $package->get(EntityBusinessrules::FIELD_REMOTE_ID)->value;
      if (empty($remoteId)) {
        continue;
      }
      $packageUpdateTime = $package->get('changed')->value;
      if ($packageUpdateTime + $unpublishGracePeriod > $lastImportTime ) {
        continue;
      }
      // The package was not updated for "migrate_post_import_unpublish_package" seconds,
      // therefore we assume it is no longer available for booking.
      $package->set('status', 0);
      $package->save();
    }
  }
}
