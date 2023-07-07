<?php

namespace Drupal\ebr_accommodation_seekda\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns the min price of a rate in standard occupancy from a Seekda rates array.
 *
 * @MigrateProcessPlugin(
 *   id = "seekda_minprice",
 *   handle_multiples = TRUE
 * )
 */
class SeekdaMinprice extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $minPrice = NULL;
    $minPriceOccupancy = NULL;
    $pricePerPerson = $this->configuration['price_per_person'];
    // seekda package prices are given per day may and result in incorrect prices when multiplied with min los
    $roundCentsToFull = is_numeric($this->configuration['round_cents_to_full']) ? (float) $this->configuration['round_cents_to_full'] : 0;
    if ($roundCentsToFull > 0.5) {
      throw new MigrateException(sprintf('field_price config "round_cents_to_full" is %s but must be lower or equal than 0.5', var_export($roundCentsToFull, TRUE)));
    }
    if (empty($value)) {
      return NULL;
    }
    if (!is_array($value) && !($value instanceof \Traversable)) {
      throw new MigrateException(sprintf('%s is not traversable', var_export($value, TRUE)));
    }
    foreach ($value as $rate) {
      foreach ($rate['prices'] ?? [] as $month) {
        if (is_array($month) && array_key_exists(0, $month) && is_numeric($month[0])) {
          $currentPrice = (float) $month[0];
          if (!isset($minPrice) || $currentPrice < $minPrice) {
            $minPrice = $currentPrice;
            if ($pricePerPerson) {
              // this property is set in case of room rates
              if ($row->getSourceProperty('std_occupancy')) {
                $minPriceOccupancy = $row->getSourceProperty('std_occupancy');
              }
              // else we have a package rate
              elseif ($row->getSourceProperty('rate_type') && $rate['code'] ?? '') {
                $rooms = $this->entityTypeManager->getStorage('node')->loadByProperties([
                  'type' => 'room',
                  'remote_id' =>$rate['code']
                ]);
                $room = reset($rooms);
                if ($room) {
                  $minPriceOccupancy = $room->get('field_occupancy_std')->value;
                }
              }
            }
          }
        }
      }
    }

    // for dayrate packages multiply the min price with min length of stay
    if ($minPrice && $row->getSourceProperty('rate_type') == 'DayRate') {
      $los = $row->getSourceProperty('min_los');
      if (is_array($los) && !empty($los)) {
        $minLos = min($los);
      }
      else {
        $minLos = 1;
      }
      $minPrice = $minPrice * $minLos;
      if ($roundCentsToFull) {
        $decimals = $minPrice - floor($minPrice);
        if ($decimals && ($decimals <= $roundCentsToFull || $decimals >= 1 - $roundCentsToFull)) {
          $minPrice = round($minPrice);
        }
        }
    }

    if ($minPrice && $pricePerPerson && $minPriceOccupancy) {
      $minPrice = $minPrice / $minPriceOccupancy;
      if ($roundCentsToFull) {
        $decimals = $minPrice - floor($minPrice);
        if ($decimals && ($decimals <= $roundCentsToFull || $decimals >= 1 - $roundCentsToFull)) {
          $minPrice = round($minPrice);
        }
        }
    }
    return $minPrice;
  }
}
