(function (Drupal, jQuery, once) {

  'use strict';

  Drupal.behaviors.easybooking_pricecomparison = {
    attach: function (context, settings) {
      const elements = once('easybooking_pricecomparison', '#ebPriceOmeter', context)
      elements.forEach(ebPriceOmeterLoadedCheck);
    }
  };

}(Drupal, jQuery, once));
