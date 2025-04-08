<?php

namespace Drupal\fielditem_renderby_view\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for fielditem renderby view routes.
 */
class FielditemRenderbyViewController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
