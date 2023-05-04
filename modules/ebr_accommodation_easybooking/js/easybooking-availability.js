(function (Drupal, jQuery, once) {

  'use strict';

  Drupal.behaviors.easybooking_availability = {
    attach: function (context, settings) {
      const elements = once('easybooking_availability', '#ebAvailability', context)
      elements.forEach(ebAvailabilityLoadedCheck);
    }
  };

}(Drupal, jQuery, once));
