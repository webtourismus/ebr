(function (Drupal, jQuery, once) {

  'use strict';

  Drupal.behaviors.easybooking_rates = {
    attach: function (context, settings) {
      const elements = once('easybooking_rates', '#ebPricesFrame', context)
      elements.forEach(ebPricesLoadedCheck);
    }
  };

}(Drupal, jQuery, once));
