<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_seekda;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_tools\MigrateExecutable;

/**
 * Runs Seekda migration imports on cron and warns on repeated failures.
 */
class SeekdaCron {

  use StringTranslationTrait;

  /**
   * Constructs the Seekda cron service.
   */
  public function __construct(
    protected readonly TimeInterface $time,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly MigrationPluginManagerInterface $migrationPluginManager,
    protected readonly KeyValueFactoryInterface $keyValueFactory,
    protected readonly StateInterface $state,
    protected readonly MailManagerInterface $mailManager,
    protected readonly LanguageManagerInterface $languageManager,
    protected readonly Seekda $seekda,
  ) {}

  /**
   * Executes due Seekda migrations for allowed environments.
   */
  public function run(): void {
    $requestTime = $this->time->getRequestTime();
    $config = $this->configFactory->get('ebr_accommodation_seekda.settings');
    $allowedEnvs = $config->get('cron_allowed_environments') ?? [];
    if (!in_array(getenv('ENV') ?: ($_ENV['ENV'] ?? ''), $allowedEnvs, TRUE)) {
      return;
    }

    $migrationIds = $this->entityTypeManager->getStorage('migration')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('migration_group', 'seekda')
      ->condition('status', TRUE)
      ->sort('id')
      ->execute();

    $lastImported = $this->keyValueFactory->get('migrate_last_imported');

    foreach ($migrationIds as $migrationId) {
      /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
      $migration = $this->migrationPluginManager->createInstance($migrationId);
      $lastSuccessfulImport = $lastImported->get($migrationId, 0) / 1000;
      if ($requestTime < $lastSuccessfulImport + $config->get('cron_interval')) {
        continue;
      }

      if ($migration->getStatus() !== MigrationInterface::STATUS_IDLE) {
        $lastCronStart = $this->state->get("ebr_accommodation_seekda.last_cron_start.{$migrationId}", 0);
        // Temporary errors or timeouts from the remote API might have
        // incorrectly left a migration in running state.
        if ($requestTime > $lastCronStart + $config->get('cron_max_runtime')) {
          $migration->setStatus(MigrationInterface::STATUS_IDLE);
        }
      }

      $result = MigrationInterface::RESULT_SKIPPED;
      if ($migration->getStatus() === MigrationInterface::STATUS_IDLE) {
        $this->state->set("ebr_accommodation_seekda.last_cron_start.{$migrationId}", $requestTime);
        // All packages are set unpublished after an import to disable (but not
        // delete) unavailable packages. To re-enable active packages, all
        // imported rows must get a new changed timestamp.
        if ($this->seekda->isPackageMigration($migration->id())) {
          $migration->getIdMap()->prepareUpdate();
        }
        $executable = new MigrateExecutable($migration, new MigrateMessage());
        $result = $executable->import();
      }

      if ($result === MigrationInterface::RESULT_COMPLETED) {
        continue;
      }

      $this->maybeSendWarningMail($migrationId, $requestTime, (int) $lastSuccessfulImport, $config);
    }
  }

  /**
   * Sends a warning mail when imports fail repeatedly.
   */
  protected function maybeSendWarningMail(string $migrationId, int $requestTime, int $lastSuccessfulImport, ImmutableConfig $config): void {
    $lastCronMail = $this->state->get("ebr_accommodation_seekda.last_cron_mail.{$migrationId}", 0);
    if ($requestTime < $lastSuccessfulImport + $config->get('cron_warningmail_after') ||
      $requestTime < $lastCronMail + $config->get('cron_warningmail_repeat') ||
      empty($config->get('cron_warningmail_to'))
    ) {
      return;
    }

    $siteConfig = $this->configFactory->get('system.site');
    $mailMessage = $this->t(
      'The import @migration_id on @site_name has failed multiple times. The last successful import was on @date.',
      [
        '@migration_id' => $migrationId,
        '@site_name' => $siteConfig->get('name'),
        '@date' => date('Y-m-d H:i:s', $lastSuccessfulImport),
      ]
    );
    $mailParams = [
      'from' => $siteConfig->get('mail'),
      'context' => [
        'subject' => $this->t('Warning: Migration @migration_id failed', [
          '@migration_id' => $migrationId,
        ]),
        'message' => $mailMessage,
      ],
    ];
    $this->mailManager->mail(
      'system',
      'mail',
      $config->get('cron_warningmail_to'),
      $this->languageManager->getDefaultLanguage()->getId(),
      $mailParams,
      NULL,
      TRUE,
    );
    $this->state->set("ebr_accommodation_seekda.last_cron_mail.{$migrationId}", $requestTime);
  }

}
