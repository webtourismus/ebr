<?php

declare(strict_types=1);

namespace Drupal\ebr_accommodation_seekda\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns the min price of a rate in standard occupancy from Seekda rates.
 */
#[MigrateProcess(id: 'seekda_minprice', handle_multiples: TRUE)]
class SeekdaMinprice extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a SeekdaMinprice plugin.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $minPrice = NULL;
    $minPriceOccupancy = NULL;
    $pricePerPerson = $this->configuration['price_per_person'];
    // Seekda package prices are given per day and may result in incorrect
    // prices when multiplied with min LOS.
    $roundCentsToFull = is_numeric($this->configuration['round_cents_to_full']) ? (float) $this->configuration['round_cents_to_full'] : 0.0;
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
              // This property is set in case of room rates.
              if ($row->getSourceProperty('std_occupancy')) {
                $minPriceOccupancy = $row->getSourceProperty('std_occupancy');
              }
              // Else we have a package rate.
              elseif ($row->getSourceProperty('rate_type') && ($rate['code'] ?? '') !== '') {
                $rooms = $this->entityTypeManager->getStorage('node')->loadByProperties([
                  'type' => 'room',
                  'remote_id' => $rate['code'],
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

    // For dayrate packages multiply the min price with min length of stay.
    if ($minPrice && $row->getSourceProperty('rate_type') === 'DayRate') {
      $los = $row->getSourceProperty('min_los');
      if (is_array($los) && !empty($los)) {
        $minLos = min($los);
      }
      else {
        $minLos = 1;
      }
      $minPrice = $minPrice * $minLos;
      $minPrice = $this->roundCentsIfNeeded($minPrice, $roundCentsToFull);
    }

    if ($minPrice && $pricePerPerson && $minPriceOccupancy) {
      $minPrice = $minPrice / $minPriceOccupancy;
      $minPrice = $this->roundCentsIfNeeded($minPrice, $roundCentsToFull);
    }
    return $minPrice;
  }

  /**
   * Rounds prices that are within the configured cent threshold of a full unit.
   */
  protected function roundCentsIfNeeded(float $price, float $roundCentsToFull): float {
    if (!$roundCentsToFull) {
      return $price;
    }
    $decimals = $price - floor($price);
    if ($decimals && ($decimals <= $roundCentsToFull || $decimals >= 1 - $roundCentsToFull)) {
      return round($price);
    }
    return $price;
  }

}
