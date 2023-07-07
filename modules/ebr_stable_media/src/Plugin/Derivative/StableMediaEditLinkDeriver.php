<?php

declare(strict_types = 1);

namespace Drupal\ebr_stable_media\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derivative class that provides the edit links for stable media entities.
 */
class StableMediaEditLinkDeriver extends DeriverBase implements ContainerDeriverInterface {

  public const STABLE_MEDIA_LINK_WEIGHT = 2100;

  public const STABLE_MEDIA_PREFIX = 'download_';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];

    $query = $this->entityTypeManager->getStorage('media')->getQuery();
    $query->accessCheck(TRUE);
    $query->condition('internal_id', self::STABLE_MEDIA_PREFIX, 'STARTS_WITH');
    $mediaIds = $query->execute();

    // We assume we don't have too many...
    $mediaEntities = $this->entityTypeManager->getStorage('media')->loadMultiple($mediaIds);
    /** @var \Drupal\media\MediaInterface $media */
    foreach ($mediaEntities as $media) {
      $linkId = 'ebr.stable_media:' . $media->get('internal_id')->value;
      $weight = $media->hasField('field_weight') ? (int) $media->get('field_weight')->value : 0;
      $links[$linkId] = [
        'id' => $linkId,
        'title' => $media->label(),
        'route_name' => 'entity.media.edit_form',
        'route_parameters' => ['media' => $media->id()],
        'weight' => self::STABLE_MEDIA_LINK_WEIGHT + $weight,
        'menu_name' => 'editor',
        'description' => '/libraries/fa6/svgs/regular/file-pdf.svg'
      ] + $base_plugin_definition;
    }

    return $links;
  }

}
