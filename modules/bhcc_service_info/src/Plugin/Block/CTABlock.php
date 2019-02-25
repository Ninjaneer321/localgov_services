<?php

namespace Drupal\bhcc_service_info\Plugin\Block;

use Drupal\bhcc_helper\CurrentPage;
use Drupal\bhcc_helper\Node\BHCCNodeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CTABlock
 *
 * @package Drupal\bhcc_service_info\Plugin\Block
 *
 * @Block(
 *  id = "cta_block",
 *  admin_label = @Translation("Call to actions"),
 *  category = @Translation("BHCC"),
 * )
 */
class CTABlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\bhcc_helper\CurrentPage
   */
  private $currentPage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('bhcc_helper.current_page')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentPage $currentPage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentPage = $currentPage;
  }

  /**
   * We only show this block if the current node contains some CTA actions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  protected function blockAccess(AccountInterface $account) {
    if ($this->currentPage->isNode()) {
      $node = $this->currentPage->getNode();

      if ($node instanceof BHCCNodeInterface) {
        return AccessResult::allowedIf(count($node->getCTAs()) >= 1);
      }
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      $this->getCTAButtons(),
      '#cache' => [
        'tags' => ['node:'.$this->currentPage->getNode()->id()],
        'contexts' => ['url.path']
      ]
    ];
  }

  /**
   * Returns the call to actions attached to the current page.
   *
   * We do not need to run any conditions as Drupal will only get this far if
   * all conditions in $this::blockAccess are met.
   *
   * @return array
   */
  private function getCTAButtons() {
    $buttons = [];
    foreach ($this->currentPage->getNode()->getCTAs() as $call_to_action) {

      $type = 'cta-blue';
      if (isset($call_to_action['options']['type']) && $call_to_action['options']['type'] === 'action') {
        $type = 'cta-green';
      }

      $buttons[] = [
        '#theme' => 'button',
        '#title' => $call_to_action['title'],
        '#url' => Url::fromUri($call_to_action['uri']),
        '#type' => $type,
        '#grid' => 'col-sm-4'
      ];
    }
    return $buttons;
  }
}
