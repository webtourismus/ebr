<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_seekda;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class Seekda {

  /**
   * The access token required to access the API.
   *
   * (Yes, this really is a const!)
   */
  protected const ACCESSTOKEN = '42';

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a Seekda service object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }


  /**
   * Return the access token for Seekda JSON API.
   */
  public function getAccessToken(): string {
    return self::ACCESSTOKEN;
  }

  /**
   * Return the propery code for all Seekda Channel manager queries.
   */
  public function getPropertyCode(): string {
    return $this->configFactory->get('system.site')->get('seekda.property_code');
  }
}
