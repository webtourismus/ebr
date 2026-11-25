<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_seekda\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\ebr\EntityBusinessrules;
use Drupal\ebr_accommodation_seekda\Entity\AccommodationSeekda;
use Drupal\ebr_accommodation_seekda\Seekda;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Unpublish Seekda packages no longer in the JSON API source result.
 */
class MigrationPackagePublishStatus implements EventSubscriberInterface {

  /**
   * Number of rows saved by the running import, keyed by migration ID.
   *
   * @var array<string, int>
   */
  protected array $savedRows = [];

  /**
   * Constructs the event subscriber.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly KeyValueFactoryInterface $keyValue,
    protected readonly ConfigFactoryInterface $config,
    protected readonly Seekda $seekda,
    #[Autowire(service: 'logger.channel.ebr_accommodation_seekda')]
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MigrateEvents::PRE_IMPORT => 'resetSavedRows',
      MigrateEvents::POST_ROW_SAVE => 'countSavedRow',
      MigrateEvents::POST_IMPORT => 'unpublish',
    ];
  }

  /**
   * Starts counting the saved rows of an import.
   */
  public function resetSavedRows(MigrateImportEvent $event): void {
    $this->savedRows[$event->getMigration()->id()] = 0;
  }

  /**
   * Counts a saved row of the running import.
   */
  public function countSavedRow(MigratePostRowSaveEvent $event): void {
    $migrationId = $event->getMigration()->id();
    $this->savedRows[$migrationId] = ($this->savedRows[$migrationId] ?? 0) + 1;
  }

  /**
   * Unpublishes outdated packages after a packages migration import.
   *
   * The migration _must_ run with the "update" flag for this to work.
   * Otherwise active packages will become unpublished too.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The migration import event.
   */
  public function unpublish(MigrateImportEvent $event): void {
    $migration = $event->getMigration();
    if (!$this->seekda->isPackageMigration($migration->id())) {
      return;
    }
    // An empty result usually means a problem at seekda (outage, wrong
    // configuration), not that every package has ended. Keep the packages
    // published; they are checked again after the next import.
    if (($this->savedRows[$migration->id()] ?? 0) === 0) {
      $this->logger->warning('The import @migration returned no packages. Packages were not unpublished.', ['@migration' => $migration->id()]);
      return;
    }
    $lastImportTime = round($this->keyValue->get('migrate_last_imported')->get($migration->id(), 0) / 1000);

    $allSeekdaPackages = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => 'package',
      EntityBusinessrules::FIELD_REMOTE_DATASOURCE => AccommodationSeekda::DATASOURCE,
      'default_langcode' => 1,
    ]);
    $unpublishGracePeriod = $this->config->get('ebr_accommodation_seekda.settings')->get('migrate_post_import_unpublish_package');

    foreach ($allSeekdaPackages as $package) {
      $remoteId = $package->get(EntityBusinessrules::FIELD_REMOTE_ID)->value;
      if (empty($remoteId)) {
        continue;
      }
      $packageUpdateTime = $package->get('changed')->value;
      if ($packageUpdateTime + $unpublishGracePeriod > $lastImportTime) {
        continue;
      }
      // The package was not updated for migrate_post_import_unpublish_package
      // seconds, therefore we assume it is no longer available for booking.
      $package->set('status', 0);
      $package->save();
    }
  }

}
