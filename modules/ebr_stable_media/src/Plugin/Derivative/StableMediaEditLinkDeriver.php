<?php

namespace Drupal\ebr_stable_media\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derivative class that provides the edit links for stable media entities.
 */
class StableMediaEditLinkDeriver extends DeriverBase implements ContainerDeriverInterface {

  public const STABLE_MEDIA_LINK_WEIGHT = 2000;

  public const STABLE_MEDIA_PREFIX = 'download_';

  /**
   * @var EntityTypeManagerInterface $entityTypeManager.
   */
  protected $entityTypeManager;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];

    $query = $this->entityTypeManager->getStorage('media')->getQuery();
    $query->accessCheck(TRUE);
    $query->condition('internal_id', self::STABLE_MEDIA_PREFIX, 'STARTS_WITH');
    $mediaIds = $query->execute();

    // We assume we don't have too many...
    $mediaEntities = $this->entityTypeManager->getStorage('media')->loadMultiple($mediaIds);
    /** @var MediaInterface $media */
    foreach ($mediaEntities as $id => $media) {
      $linkId = 'ebr.stable_media:' . $media->get('internal_id')->value;
      $weight = $media->hasField('field_weight') ? (int) $media->get('field_weight')->value : 0;
      $links[$linkId] = [
        'id' => $linkId,
        'title' => $media->label(),
        'route_name' => 'entity.media.edit_form',
        'route_parameters' => ['media' => $media->id()],
        'weight' => self::STABLE_MEDIA_LINK_WEIGHT + $weight,
        'menu_name' => 'editor',
      ] + $base_plugin_definition;
    }

    return $links;
  }
}
