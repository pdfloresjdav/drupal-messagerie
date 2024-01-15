<?php

namespace Drupal\messagerie\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;
use Drupal\messagerie\MessagerieStorage;
use Drupal\private_message\Service\PrivateMessageServiceInterface;


/**
 * Provides a block called "Contest list block".
 *
 * @Block(
 *  id = "module_messagerie_left_form",
 *  admin_label = @Translation("Contest list block")
 * )
 */
class LeftMessagerieBlock extends BlockBase {

    protected $privateMessageService;
    public function __construct(){
    $this->privateMessageService = new PrivateMessageServiceInterface();
  }
   /**
   * {@inheritdoc}
   */
    public function build() {      

        $html = "";
        $html.= " <div id='contest_list_container' class='p-5'>";
        $html.='<div class="row">';
        $articles_data = [];
        foreach($articles_data as $j => $art){
            error_log("***** iterator $j ".$art['title']);
              $html.= '<div class="col">'
                        . '<div class="image"><img src="'.$art['image_url'].'"></img></div>'
                        . '<div class="title">'.$art['title'].'</div>'
                        . '<div class="desc">'.$art['body'].'</div>'
                        . '<div class="btn_form"><a href="'.$art['url'].'"> PARTICIPER</a></div>'
                    . '</div>';                        
        }
        $html .="</div>";
        $html .="</div>";


        $data['#markup'] =t("$html");
        $data['#cache']['max-age'] = 0;
        $data['#attached']['library'] = ['contest/contest-css'];

      return $data;

    }

  
   
}
