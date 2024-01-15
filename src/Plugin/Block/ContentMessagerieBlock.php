<?php

namespace Drupal\messagerie\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;


/**
 * Provides a block called "Contest top block".
 *
 * @Block(
 *  id = "module_messagerie_content_form",
 *  admin_label = @Translation("Contest top block")
 * )
 */
class ContentMessagerieBlock extends BlockBase {

   /**
   * {@inheritdoc}
   */
    public function build() {
        
        $form = \Drupal::formBuilder()->getForm('privatemessageactions_2');

        $html = $form;
      $data['#markup'] =t("$html");
      $data['#cache']['max-age'] = 0;

      return $data;

    }

  
  
}
