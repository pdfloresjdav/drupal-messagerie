<?php
/**
 * @file
 * Contains Drupal\messagerie\Form\AddCommentReplyAllForm
 */

namespace Drupal\messagerie\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\kozon\HeroStorage;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityBase;
use Drupal\file\Entity\File;
use Drupal\messagerie\MessagerieStorage;

/**
 * Class AddForm.
 *
 * @package Drupal\messagerie\Form\AddCommentReplyAllForm
 */
class AddCommentReplyAllForm extends FormBase {

  use StringTranslationTrait;

  protected $account;

  public function __construct(){
    $this->account = \Drupal::currentUser();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'messagerie_comment_reply_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $arg = NULL) {
    global $base_url;

    $fuser = MessagerieStorage::getMessage($arg);
    $guser = MessagerieStorage::getUserInfo($fuser->uid);

    $name = (!empty($guser->fname))?$guser->fname . ' ' . $guser->lname : $guser->username;

    $form = array(
        '#attributes' => array('enctype' => 'multipart/form-data'),
        'title' => array(
          '#markup' => '<a class="kmessage"><p>RÉPONDRE AU MESSAGE DE: '.$name.'</p></a>',
        ),
        'explanations' => array(
          '#markup' => '<p class="kexplanations"></p>',
        ),
        'destinatarie' => array(
          '#type' => 'hidden',
          '#value' => $arg,
        ),
        'subject' => array(
          '#type' => 'textfield',
          '#placeholder' => $this->t('Sujet: '),
          '#required' => TRUE,
        ),
        'body' => array(
          '#type' => 'text_format',
          '#size' => 20,
          '#format' => 'full_html',
          '#attributes' => array(
            'style' => 'width: 90%',
            'class' => array('body-mes-row'),
          ),
          '#required' => TRUE,
        ),
        'filemessage' => array(
          '#type' => 'managed_file',
          '#attributes' => array(
            'class' => 'kattach-upload-message',
          ),
          '#title' => t('Ajouter une pièce jointe'),
          '#upload_location' => 'public://message_files/',
          '#upload_validators' => array(
            'file_validate_extensions' => array('png gif jpg jpeg mov avi doc docx xls xlsx ppt pptx mp3 mp4 mpg mpeg pdf zip ico svg psd tiff gif txt json gz rar'),
            'file_validate_size' => array(256000000),
          ),
        ),
        'submit' => array(
          '#type' => 'submit',
          '#value' => $this->t('ENVOYER'),
          '#ajax'  => array(
            'url' => Url::fromRoute('messagerie.save.all.comment'),
          ),
        ),

        'annuler' => array(
          '#type' => 'button',
          '#value' => $this->t('ANNULER'),
          '#ajax'  => array(
            'url' => Url::fromRoute('messagerie.hide.comment'),
          ),
        ),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   * @todo obsolete?
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   * @todo obsolete?
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
