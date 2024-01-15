<?php

namespace Drupal\messagerie\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;


/**
 * Provides a block called "Contest top block".
 *
 * @Block(
 *  id = "module_messagerie_top_form",
 *  admin_label = @Translation("Contest top block")
 * )
 */
class TopMessagerieBlock extends BlockBase {

   /**
   * {@inheritdoc}
   */
    public function build() {
        $taxonomies_type_content = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('type_of_content');
        $title = "";
        $autor = "";
        $desc = "";
        $image_id = "";
        foreach($taxonomies_type_content  as $taxonomy){
            if($taxonomy->name=="Messagerie"){
                //$variables['type_content'] = $taxonomy; 
                if(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($taxonomy->tid)->get('field_h_title_c')->getValue()){
                  $title = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($taxonomy->tid)->get('field_h_title_c')->getValue()[0]['value'];
                }
                else{
                  $title = '';
                }
                if(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($taxonomy->tid)->get('field_h_author_c')->getValue()){
                  $autor = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($taxonomy->tid)->get('field_h_author_c')->getValue()[0]['value'];
                }
                else{
                  $autor = '';
                }
                if(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($taxonomy->tid)->get('field_h_description_c')->getValue()){
                  $desc = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($taxonomy->tid)->get('field_h_description_c')->getValue()[0]['value'];
                }
                else{
                  $desc = '';
                }
                if(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($taxonomy->tid)->get('field_h_image_c')->getValue()){
                  $image_id = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($taxonomy->tid)->get('field_h_image_c')->getValue()[0]['target_id'];
                }
                else{
                  $image_id = 0;
                }
                
            }        
        }

        $file = File::load($image_id);
        $url_image = "";
        if($file){
          $url_image =  \Drupal\Core\Url::fromUri(file_create_url($file->getFileUri()))->toString();   
        }


        $html = "<div class='category_header_content'>
                  <img class='subcategory_img' src='$url_image'>
                  <div class='category_header_text '>
                      <div class='category_header_title text-center'>$title</div>
                      <div class='category_header_desc text-center'>$desc</div>
                      <div class='category_header_author text-center'>$autor</div>
                  </div>
              </div>";
      $data['#markup'] =t("$html");
      $data['#cache']['max-age'] = 0;

      return $data;

    }

  
  
}
