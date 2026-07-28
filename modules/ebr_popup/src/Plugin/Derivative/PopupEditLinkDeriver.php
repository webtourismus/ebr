<?php

declare(strict_types=1);

namespace Drupal\ebr_popup\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derivative class that provides the edit links for block_content entities used as popup window.
 */
class PopupEditLinkDeriver extends DeriverBase implements ContainerDeriverInterface {

  public const POPUP_LINK_WEIGHT = 2000;

  public const POPUP_PREFIX = 'popup_';

  /**
   * Constructs a PopupEditLinkDeriver.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id): static {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $links = [];

    $query = $this->entityTypeManager->getStorage('block_content')->getQuery();
    $query->accessCheck(TRUE);
    $query->condition('internal_id', self::POPUP_PREFIX, 'STARTS_WITH');
    $blockIds = $query->execute();

    // We assume we don't have too many...
    $blockEntities = $this->entityTypeManager->getStorage('block_content')->loadMultiple($blockIds);
    /** @var \Drupal\block_content\BlockContentInterface $block */
    foreach ($blockEntities as $block) {
      $linkId = 'ebr.popup:' . $block->get('internal_id')->value;
      $weight = $block->hasField('field_weight') ? (int) $block->get('field_weight')->value : 0;
      $links[$linkId] = [
        'id' => $linkId,
        'title' => $block->label(),
        'route_name' => 'entity.block_content.edit_form',
        'route_parameters' => ['block_content' => $block->id()],
        'weight' => self::POPUP_LINK_WEIGHT + $weight,
        'menu_name' => 'editor',
        'options' => [
          'attributes' => [
            'class' => ['before:![mask-image:url(/libraries/fa6/svgs/regular/bell.svg)]'],
          ],
        ],
      ] + $base_plugin_definition;
    }

    return $links;
  }

}
