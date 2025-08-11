<?php

namespace Drupal\novaposhta\Commands;

use Drush\Commands\DrushCommands;

class NovaPoshtaCommands extends DrushCommands {
  
  /**
   * NovaPoshta update status (novaposhta:status_update)
   *
   * @command novaposhta:status_update
   * @usage novaposhta:status_update                     NovaPoshta update status
   */
  public function novaposhtaStatusUpdate() {
    \Drupal::service('NovaPoshta')->cronRun();
  }
  
   /**
   * NovaPoshta update status (novaposhta:status_update)
   *
   * @command novaposhta:list
   * @usage novaposhta:list                     NovaPoshta update Area/City
   */
  public function novaposhtaUpdate($type) {
    \Drupal::service('NovaPoshta')->runUpdate($type);
  }
}