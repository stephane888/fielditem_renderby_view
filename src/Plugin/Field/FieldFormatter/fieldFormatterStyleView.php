<?php

namespace Drupal\fielditem_renderby_view\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\views\Views;

/**
 * Plugin implementation of the 'field_example_simple_text' formatter.
 *
 * @FieldFormatter(
 *   id = "fielditem_renderby_view_formatter",
 *   module = "fielditem_renderby_view",
 *   label = @Translation("Rendu via une view avec filtre contextuel"),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions"
 *   }
 * )
 */
class fieldFormatterStyleView extends FormatterBase {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view_name' => null,
      'display_view_id' => null,
      'view_arguments' => [],
      'view_filters' => [],
      'configure_view' => [
        'display_view_id' => null
      ]
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    if ($items->isEmpty())
      return $elements;
    $args = [];
    $viewId = $this->getSetting('view_name');
    $configure_view = $this->getSetting('configure_view');
    $display_view_id = !empty($configure_view['display_view_id']) ? $configure_view['display_view_id'] : $this->getSetting('display_view_id');
    foreach ($items->getValue() as $value) {
      if (!empty($value['target_id']))
        $args[] = $value['target_id'];
    }
    if (!$args)
      return $elements;
    $args = implode(",", $args);
    /**
     *
     * @var \Drupal\views\ViewExecutable $viewExecute
     */
    $viewExecute = Views::getView($viewId);
    // Si la vue existe et que l'utilisateur a acces.
    if (!$viewExecute || !$viewExecute->access($display_view_id))
      return $elements;
    $viewExecute->setDisplay($display_view_id);
    $viewExecute->setArguments([
      $args
    ]);
    $viewExecute->preExecute();
    $build = $viewExecute->render();
    
    // Recupere l'entité qui porte les references.
    $parent_entity = $items->getEntity();
    // On ajoute le cache en fonction de l'entité parente.
    $cacheability = CacheableMetadata::createFromRenderArray($build);
    $cacheability->addCacheableDependency($parent_entity);
    $cacheability->applyTo($build);
    
    // Ajout au rendu du champ
    $elements = $build;
    return $elements;
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter::settingsForm()
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    /**
     * Information de configuration du champs.
     *
     * @var array $settings
     */
    $settings = $this->getFieldSettings();
    // \Stephane888\Debug\debugLog::kintDebugDrupal($settings,
    // 'fieldFormatterStyleView__settingsForm', true);
    
    $elements['view_name'] = [
      '#title' => $this->t(' Select view'),
      '#type' => 'select',
      '#options' => $this->getViews(),
      '#required' => TRUE,
      '#default_value' => $this->getSetting('view_name'),
      '#ajax' => [
        'callback' => self::class . '::SelectViewAndConfigure',
        'wrapper' => 'creation_site_virtuel_view_name_display_id',
        'effect' => 'fade'
      ]
    ];
    $elements['configure_view'] = [
      '#type' => 'details',
      '#open' => true,
      '#title' => t('Select display and configure view'),
      '#attributes' => [
        'id' => 'creation_site_virtuel_view_name_display_id'
      ],
      '#tree' => true
    ];
    $view_name_display = $this->getSetting('view_name') ? $this->getSetting('view_name') : $form_state->getValue('view_name');
    if (!empty($view_name_display)) {
      $configure_view = $this->getSetting('configure_view');
      $elements['configure_view']['display_view_id'] = [
        '#title' => $this->t(' Select display '),
        '#type' => 'select',
        '#options' => $this->getViewDisplays($view_name_display),
        '#required' => TRUE,
        '#default_value' => $configure_view['display_view_id']
      ];
    }
    return $elements;
  }
  
  /**
   * Retourne la liste des vues actives ayant au moins un filtre contextuel.
   *
   * @return array Tableau [view_id => label]
   */
  protected function getViews() {
    $options = [];
    // Charger uniquement les vues actives
    $ids = \Drupal::entityQuery('view')->condition('status', TRUE)->accessCheck(TRUE)->execute();
    if (!$ids)
      return $options;
    
    /** @var \Drupal\views\Entity\View[] $views */
    $views = \Drupal::entityTypeManager()->getStorage('view')->loadMultiple($ids);
    foreach ($views as $view) {
      $displays = $view->get('display');
      if (empty($displays)) {
        continue;
      }
      // Vérifie chaque display
      foreach ($displays as $display) {
        if (!empty($display['display_options']['arguments']) && is_array($display['display_options']['arguments'])) {
          $options[$view->id()] = $view->label();
          break;
        }
      }
    }
    return $options;
  }
  
  /**
   * Retourne les displays d'une view qui ont des filtres contextuels.
   *
   * @param string $view_name
   *        ID de la view.
   *        
   * @return array Tableau [display_id => display_title]
   */
  protected function getViewDisplays($view_name) {
    $options = [];
    /**
     *
     * @var \Drupal\views\ViewExecutable $View
     */
    $view = Views::getView($view_name);
    if (!$view)
      return $options;
    /**
     *
     * @var \Drupal\views\Entity\View $viewdff
     */
    $viewdff = $view->storage;
    $displays = $view->storage->get('display');
    if (empty($displays))
      return $options;
    foreach ($displays as $display_id => $display) {
      if (!empty($display['disabled']))
        continue;
      /**
       * Pour bien gerer cette partie il faut distinguer le cas ou la vue est
       * surcharger ou pas.
       */
      // if (!empty($display['display_options']['arguments'])) {
      // $options[$display_id] = $display['display_title'];
      // }
      // @todo en attente, on met tous les affichages.
      $options[$display_id] = $display['display_title'];
    }
    return $options;
  }
  
  /**
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public static function SelectViewAndConfigure($form, FormStateInterface $form_state) {
    return $form['settings']['formatter']['settings_wrapper']['settings']['configure_view'];
  }
}

