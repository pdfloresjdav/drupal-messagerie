<?php

namespace Drupal\messagerie\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;
use Drupal\messagerie\MessagerieStorage;
use Drupal\Core\Url;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Link;
use Drupal\Core\Ajax\RedirectCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\Element\EntityAutocomplete;


/**
 * This is our hero controller.
 */
class MessagerieController extends ControllerBase {

  protected $account;

  public function __construct(){
    $this->account = \Drupal::currentUser();
  }

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
      $results = [];
      $resultsid = [];
      $input = $request->query->get('q');

      // Get the typed string from the URL, if it exists.
      if (!$input) {
        return new JsonResponse($results);
      }

      $input = Xss::filter($input);

      $users =MessagerieStorage::getAllUsersMessage($input);
      //error_log(print_r($users,true));
      foreach ($users as $user) {
        if( $user->roles !== 'administrator'){
          if(property_exists($user, 'share') || property_exists($user, 'sing') || property_exists($user, 'keo')){
            if( $user->share == 1 || $user->sing == 1 || $user->keo == 1){
              $term_data[$user->uid] = (!empty($user->firstname))?ucwords(strtolower($user->firstname.' '.$user->lastname)):ucwords(strtolower($user->name));
              $availability = '✅';
              $userl = user_load($user->uid);

              /*$label = [
                $user->name,
                '<small>(' . $user->uid . ')</small>',
                $availability,
              ];*/
              $label = [
                (!empty($user->firstname))?ucwords(strtolower($user->firstname.' '.$user->lastname)):ucwords(strtolower($user->name)),
              ];
              
              if(!in_array($user->uid, $resultsid))
              {
                $resultsid[] = $user->uid;
                $results[] = [
                  'value' => (!empty($user->firstname)?ucwords(strtolower($user->firstname.' '.$user->lastname)):ucwords(strtolower($user->name))).' (' . $user->uid . '),',
                  'label' => implode(' ', $label),
                ];
              }
            }
          }
        }
      }

      return new JsonResponse($results);
  }


  public function content() 
  {
    if(empty($this->account->id())){
      return;
    }
    else{
      $uid = $this->account->id();
    }
    $mobileDetector = \Drupal::service('mobile_detect');
    $is_mobile = $mobileDetector->isMobile();
    //$link_url = Url::fromRoute('messagerie.add.comment',['arg'=>$nodeId]);
    $link_url = Url::fromRoute('messagerie.add.comment');
    $link_url->setOptions([
      'attributes' => [
        'class' => ['use-ajax', 'button', 'button--small'],
      ]
    ]);

    $link_all = Url::fromRoute('messagerie.load.comment');
    $link_all->setOptions([
      'attributes' => [
        'class' => ['use-ajax', 'button', 'button--small'],
      ]
    ]);

    $link_send = Url::fromRoute('messagerie.load.comment_send');
    $link_send->setOptions([
      'attributes' => [
        'class' => ['use-ajax', 'button', 'button--small'],
      ]
    ]);

    $link_del = Url::fromRoute('messagerie.load.comment_del');
    $link_del->setOptions([
      'attributes' => [
        'class' => ['use-ajax', 'button', 'button--small'],
      ]
    ]);

    $html ='<div class="row display-messagerie">
              <div class="col-sm-3 col-12 display-inbox">
                  <div class="row">
                      <div class="col-12">
                          <h3>Messages</h3>
                          <div class="new-message"><span class="nmesg">'.Link::fromTextAndUrl(t('NOUVEAU MESSAGE </span><span class="new-message-span"></span>'), $link_url)->toString().'
                          </div>
                          <hr>
                      </div>
                  </div>
                  <div class="row">
                      <div class="col-12">
                          <div class="messages">
                              <div class="row">
                                  <div class="col-2 message-header">
                                      <span class="message-all message-active">'.Link::fromTextAndUrl(t('REÇUS'), $link_all)->toString().'</span>
                                  </div>
                                  <div class="col-2 message-header">
                                      <span class="message-send">'.Link::fromTextAndUrl(t('ENVOYÉS'), $link_send)->toString().'</span>
                                  </div>
                                  <div class="col-2 message-header">
                                      <span class="message-delete">'.Link::fromTextAndUrl(t('SUPPRIMÉS'), $link_del)->toString().'</span>
                                  </div>
                                  <hr>
                              </div>
                              <div class="all-messages">';
    $messages = MessagerieStorage::getAllMessage($uid);
    
    $arg = '';                
    foreach ($messages as $message) 
    {
      if(empty($arg)){
        $arg = $message->mid;
      }
      
      $user = MessagerieStorage::getUserInfo($message->uid);
      $imageuser = File::load( $user->fid );
      if($imageuser){
        $imageuser = file_url_transform_relative(\Drupal\Core\Url::fromUri(file_create_url($imageuser->getFileUri()))->toString());  
      }
      else{
        $imageuser = '/themes/keonoo/images/nouserimage7.png';
      }

      $datechange=date('j F Y \á H:i', $message->timestamp);

      $now = time(); // or your date as well
      $datediff = $now - $message->timestamp;
      $days = floor($datediff / (60 * 60 * 24));
      $timeMessage = date('H:i', $message->timestamp);

      if ($days == 1) {
        $timeMessage = t("HIER");
      } elseif ($days > 1) {
        $timeMessage = $days." ".t("JOURS");
      }

      $link_sup = Url::fromRoute('messagerie.load.comment_sup',['arg'=>$message->mid]);
      $link_sup->setOptions(['attributes' => [
                             'class' => ['use-ajax', 'button', 'button--small']]]);
      $link_info = Url::fromRoute('messagerie.load.comment_info',['arg'=>$message->mid]);
      $link_info->setOptions(['attributes' => [
                              'class' => ['use-ajax', 'button', 'button--small']]]);
      $read = '';

      if($message->read == '1'){
        $read = 'message-read';
      }

      if(empty($user->fname) && empty($user->lname)){
        $name = $user->username;
      }
      else{
        $name = $user->fname.' '.$user->lname;
      }

      $link_url = Url::fromRoute('messagerie.add.comment_reply',['arg'=>$message->mid]);
      $link_url->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);
        
      $html.='<div class="row mess-'.$message->mid.' '.$read.'">
                <div class="col-4 col-img">
                  <span class="message-image">
                    <img src="'.$imageuser.'" class="user-photop" width="32px" height="32px" >
                  </span>
                </div>
                <div class="col-4 col-message">
                  '.Link::fromTextAndUrl(t('<div class="message-user">'.$name.'</div>
                  <div class="message-subject">'.$message->subject.'</div>'), $link_info)->toString().'
                </div>
                <div class="col-4 col-data">
                  <div class="message-date">'.$timeMessage.'</div>
                    <div>
                      <span class="message-delete" mid="'.$message->mid.'">'.Link::fromTextAndUrl(t('<i class="fa fa-close" aria-hidden="true"></i>'), $link_sup)->toString().'</span>
                      <span class="message-view" mid="'.$message->mid.'">'.Link::fromTextAndUrl(t('<i class="fa fa-reply" aria-hidden="true"></i>'), $link_url)->toString().'</span>
                    </div>
                  </div>
                </div>';
    }

    $countMess = MessagerieStorage::getAllCMessage($uid);
    $pagination = floor($countMess->cmid/25);

    if ($pagination > 0) {
      $html.='<div class="users_page">';
      $pag = $pagination*25;

      if($countMess->cmid > $pag ){
        $pagination++;
      }

      for ($i=0; $i < $pagination; $i++) { 
        $j = $i+1;
        $link_info = Url::fromRoute('messagerie.load.comment.pag',['arg'=>$i]);
        $link_info->setOptions(['attributes' => [
                                'class' => ['use-ajax', 'button', 'button--small']]]);

        $html .= '<span class="page_change">'.Link::fromTextAndUrl(t(''.$j.''), $link_info)->toString().'</span>';

      }
      $html.='</div>';
    }

    $html .='</div>';
    $html .='<div class="to-contacts">
              <h3>Contacts</h3>';

    $messages = MessagerieStorage::getContactProfile($uid);

    foreach ($messages as $message) {
      $link_url = Url::fromRoute('messagerie.add.comment_reply_contact',['arg'=>$message->uid]);
      $link_url->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);
      $html.='<div class="contactElement">'.Link::fromTextAndUrl(t((!empty($message->firstname))?$message->firstname.' '.$message->lastname:$message->name), $link_url)->toString().'</div>';
    }
    
    if(!empty($arg) && !$is_mobile){
      $html .='</div></div></div></div></div><div class="col-sm-9 col-12 display-comment">'.self::addCommentFirstLoadJS($arg).'</div></div>';
    }
    else{
      $html .='</div></div></div></div></div><div class="col-sm-9 col-12 display-comment msg-mobile"></div></div>';
    }
    $data['#markup'] =t("$html");
    $data['#cache']['max-age'] = 0;
    $data['#attached']['library'] = ['messagerie/messagerie-js','messagerie/messagerie-css'];
    return $data;
  }

  /**
   * Add a comment.
   */
  public function addCommentFirstLoadJS($arg)
  {
    $response = new AjaxResponse();
    $message = MessagerieStorage::getMessage($arg);
    $user = MessagerieStorage::getUserInfo($message->uidctc);
    $sender = MessagerieStorage::getUserInfo($message->uid);
    $datechange=date('j F Y \á H:i', $message->timestamp);
    $ccmessage = MessagerieStorage::getMessage($message->mid);
    if($ccmessage->ccsend == 0){
      $parent = $message->mid;
    }
    else{
      $parent = $message->ccsend;
    }
    $ccusers = MessagerieStorage::getCCMessage($parent);
    $ccuserName = '';
    $i=0;

    foreach ($ccusers as $value) {
      $ccuser = MessagerieStorage::getUserInfo($value->uidctc);

      if($i==0){
        $ccuserName .= (empty($ccuser->fname) && empty($ccuser->lname))?$ccuser->username:$ccuser->fname.' '.$ccuser->lname;
      }
      else{
        $ccuserName .= ','.(empty($ccuser->fname) && empty($ccuser->lname))?$ccuser->username:$ccuser->fname.' '.$ccuser->lname;
      }
      
      $i++;


      
    }
    

    $now = time(); // or your date as well
    $datediff = $now - $message->timestamp;
    $days = floor($datediff / (60 * 60 * 24));
    $timeMessage = date('H:i', $message->timestamp);

    if ($days == 1) {
      $timeMessage = t("HIER");
    } elseif ($days > 1) {
      $timeMessage = $days." ".t("JOURS");
    }

    MessagerieStorage::readMessage($arg);
    $link_sup = Url::fromRoute('messagerie.load.comment_sup',['arg'=>$message->mid]);
    $link_sup->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);
    $link_url = Url::fromRoute('messagerie.add.comment_reply',['arg'=>$message->mid]);
    $link_url->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);
    $link_urlall = Url::fromRoute('messagerie.add.comment_reply_all',['arg'=>$message->mid]);
    $link_urlall->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);

    if(empty($sender->fname) && empty($sender->lname)){
      $name = $sender->username;
    }
    else{
      $name = $sender->fname.' '.$sender->lname;
    }
    $filemessage = File::load( $message->fid );
    $filemes = '';
    $filemime = '';
    if($filemessage){
      $filemime = $filemessage->getMimeType();
      $filemes = file_url_transform_relative(\Drupal\Core\Url::fromUri(file_create_url($filemessage->getFileUri()))->toString());  
    }
    $mobileDetector = \Drupal::service('mobile_detect');
    $is_mobile = $mobileDetector->isMobile();
    if(!empty($arg) && !$is_mobile){
    $html = '
      <div class="row">
        <div class="col-12 vm-subject">
          <h3>'.$message->subject.'</h3>
        </div>
      </div>
      <div class="row vm-links">
        <div class="col-2 vm-link">'.Link::fromTextAndUrl(t('RÉPONDRE <i class="fa fa-reply" aria-hidden="true"></i>'), $link_url)->toString().'</div>
        <div class="col-3 vm-link">'.Link::fromTextAndUrl(t('REPONDRE À TOUS<i class="fas fa-reply-all"></i>'), $link_urlall)->toString().'</div>
        <!--<div class="col-4 vm-link"></div>-->
        <div class="col-3 vm-link">'.Link::fromTextAndUrl(t('SUPPRIMER <i class="fa fa-close" aria-hidden="true"></i>'), $link_sup)->toString().'</div>
      </div>
      <div class="row vm-details">
        <div class="col-sm-4 vm-from msg-expe"><strong>Expéditeur :</strong> <span>'.$name.'</span></div>
        <div class="col-sm-4 vm-cc"><strong>CC :</strong> <span>'.$ccuserName.'</span></div>
        <div class="col-sm-4 vm-date">'.$timeMessage.'</div>
      </div>
      <div class="row vm-body">
        <div class="col-12">'.$message->description.'</div>
      </div>
      <div class="row vm-body">';
      if(strpos($filemime, 'image')!==false){
        $html .= '
            <div class="col-12"><a href="'.$filemes.'" target="_blank"><img src="'.$filemes.'" class="user-photop" width="32px" height="32px"></a></div>
          </div>';
      }
      else{
        $html .= '
          <div class="col-12">'.(empty($filemes)?'':'<a href="'.$filemes.'" target="_blank">Fichier</a>').'</div>
        </div>';
      }
      
    }else{
      $html = '
      <div class="row">
        <div class="col-12 vm-subject">
          <h3>'.$message->subject.'</h3>
        </div>
      </div>
      <div class="row vm-links">
        <div class="col-2 vm-link">'.Link::fromTextAndUrl(t('RÉPONDRE <i class="fa fa-reply" aria-hidden="true"></i>'), $link_url)->toString().'</div>
        <div class="col-3 vm-link">'.Link::fromTextAndUrl(t('RÉP. À TOUS<i class="fa fa-reply" aria-hidden="true"></i>'), $link_urlall)->toString().'</div>
        <!--<div class="col-4 vm-link"></div>-->
        <div class="col-3 vm-link">'.Link::fromTextAndUrl(t('SUPPR. <i class="fa fa-close" aria-hidden="true"></i>'), $link_sup)->toString().'</div>
      </div>
      <div class="row vm-details">
        <div class="col-sm-4 vm-from msg-expe"><strong>Expéditeur :</strong> <span>'.$name.'</span></div>
        <div class="col-sm-4 vm-cc"><strong>CC :</strong> <span>'.$ccuserName.'</span></div>
        <div class="col-sm-4 vm-date">'.$timeMessage.'</div>
      </div>
      <div class="row vm-body">
        <div class="col-12">'.$message->description.'</div>
      </div>
      <div class="row vm-body">';
      if(strpos($filemime, 'image')!==false){
        $html .= '
            <div class="col-12"><a href="'.$filemes.'" target="_blank"><img src="'.$filemes.'" class="user-photop" width="32px" height="32px"></a></div>
          </div>';
      }
      else{
        $html .= '
          <div class="col-12">'.(empty($filemes)?'':'<a href="'.$filemes.'" target="_blank">Fichier</a>').'</div>
        </div>';
      }
    }
    return $html; 
  }

  /**
   * Add a comment.
   */
  public function contentDelete() 
  {
    $response = new AjaxResponse();
    
    if(empty($this->account->id())){
      return;
    }
    else{
      $uid = $this->account->id();
    }

    $messages = MessagerieStorage::getAllMessageSupprime($uid);
    $html ='';

    foreach ($messages as $message) 
    {
      $user = MessagerieStorage::getUserInfo($message->uid);
      $imageuser = File::load( $user->fid );

      if($imageuser){
        $imageuser = file_url_transform_relative(\Drupal\Core\Url::fromUri(file_create_url($imageuser->getFileUri()))->toString());  
      }
      else{
        $imageuser = '/themes/keonoo/images/nouserimage7.png';
      }

      $datechange=date('j F Y \á H:i', $message->timestamp);

      $now = time(); // or your date as well
      $datediff = $now - $message->timestamp;
      $days = floor($datediff / (60 * 60 * 24));
      $timeMessage = date('H:i', $message->timestamp);
      if ($days == 1) {
        $timeMessage = t("HIER");
      } elseif ($days > 1) {
        $timeMessage = $days." ".t("JOURS");
      }

      $link_info = Url::fromRoute('messagerie.load.comment_info_send',['arg'=>$message->mid]);
      $link_info->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);

      if(empty($user->fname) && empty($user->lname)){
        $name = $user->username;
      }
      else{
        $name = $user->fname.' '.$user->lname;
      }

       $html .='<div class="row mess-'.$message->mid.' mess-del">
                  <div class="col-4 col-img">
                    <span class="message-image">
                      <img src="'.$imageuser.'" class="user-photop" width="32px" height="32px">
                    </span>
                  </div>
                  <div class="col-4 col-message">'.Link::fromTextAndUrl(t('<div class="message-user">'.$name.'</div>
                  <div class="message-subject">'.$message->subject.'</div>'), $link_info)->toString().'
                  </div>
                  <div class="col-4 col-data">
                    <div class="message-date">'.$timeMessage.'</div>
                    <div>
                      <span class="message-view-message" mid="'.$message->mid.'">'.Link::fromTextAndUrl(t('<i class="fa fa-reply" aria-hidden="true"></i>'), $link_info)->toString().'</span>
                    </div>
                  </div>
                </div>';
    }

    $countMess = MessagerieStorage::getAllCMessageSupprime($uid);
    $pagination = floor($countMess->cmid/25);

    if ($pagination > 0) {
      $html.='<div class="users_page">';
      $pag = $pagination*25;

      if($countMess->cmid > $pag ){
        $pagination++;
      }

      for ($i=0; $i < $pagination; $i++) { 
        $j = $i+1;
        $link_info = Url::fromRoute('messagerie.load.comment_del.pag',['arg'=>$i]);
        $link_info->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);
        $html .= '<span class="page_change">'.Link::fromTextAndUrl(t(''.$j.''), $link_info)->toString().'</span>';
      }
      $html.='</div>';
    }

    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.message-all', 'removeClass', array('message-active')));
    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.message-send', 'removeClass', array('message-active')));
    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.message-delete', 'addClass', array('message-active')));
    $response->addCommand(new HtmlCommand('.all-messages', $html));
    $return = $response;
    return $return; 
  }

  /**
   * Add a comment.
   */
  public function contentPagDelete($arg)
  {
    $response = new AjaxResponse();

    if(empty($this->account->id())){
      return;
    }else{
      $uid = $this->account->id();
    }

    $pag = $arg*25;
    $messages = MessagerieStorage::getAllMessageSupprime($uid,$pag);
    $html ='';

    foreach ($messages as $message) {
      $user = MessagerieStorage::getUserInfo($message->uid);
      $imageuser = File::load( $user->fid );

      if($imageuser){
        $imageuser = file_url_transform_relative(\Drupal\Core\Url::fromUri(file_create_url($imageuser->getFileUri()))->toString());  
      }
      else{
        $imageuser = '/themes/keonoo/images/nouserimage7.png';
      }

      $datechange=date('j F Y \á H:i', $message->timestamp);
      $now = time(); // or your date as well
      $datediff = $now - $message->timestamp;
      $days = floor($datediff / (60 * 60 * 24));
      $timeMessage = date('H:i', $message->timestamp);
      if ($days == 1) {
        $timeMessage = t("HIER");
      } elseif ($days > 1) {
        $timeMessage = $days." ".t("JOURS");
      }

      $link_info = Url::fromRoute('messagerie.load.comment_info_send',['arg'=>$message->mid]);
      $link_info->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);

      if(empty($user->fname) && empty($user->lname)){
        $name = $user->username;
      }
      else{
        $name = $user->fname.' '.$user->lname;
      }

      $html .='
        <div class="row mess-'.$message->mid.' mess-del">
          <div class="col-4 col-img">
            <span class="message-image">
              <img src="'.$imageuser.'" class="user-photop" width="32px" height="32px" >
            </span>
          </div>
          <div class="col-4 col-message">
          '.Link::fromTextAndUrl(t('<div class="message-user">'.$name.'</div>
                  <div class="message-subject">'.$message->subject.'</div>'), $link_info)->toString().'
          </div>
          <div class="col-4 col-data">
              <div class="message-date">'.$timeMessage.'</div>
              <div>
                <span class="message-view-message" mid="'.$message->mid.'">'.Link::fromTextAndUrl(t('<i class="fa fa-reply" aria-hidden="true"></i>'), $link_info)->toString().'</span>
              </div>
          </div>
        </div>';
    }

    $countMess = MessagerieStorage::getAllCMessageSupprime($uid);
    $pagination = floor($countMess->cmid/25);

    if ($pagination > 0) {
      $html.='<div class="users_page">';
      $pag = $pagination*25;
      
      if($countMess->cmid > $pag ){
        $pagination++;
      }

      for ($i=0; $i < $pagination; $i++) { 
        $j = $i+1;
        $link_info = Url::fromRoute('messagerie.load.comment_del.pag',['arg'=>$i]);
        $link_info->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);
        $html .= '<span class="page_change">'.Link::fromTextAndUrl(t(''.$j.''), $link_info)->toString().'</span>';
      }

      $html.='</div>';
    }

    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.message-all', 'removeClass', array('message-active')));
    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.message-send', 'removeClass', array('message-active')));
    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.message-delete', 'addClass', array('message-active')));
    $response->addCommand(new HtmlCommand('.all-messages', $html));
    $return = $response;
    return $return; 
  }

  /**
   * Add a comment.
   */
  public function contentSend()
  {
    $response = new AjaxResponse();

    if(empty($this->account->id())){
      return;
    }else{
      $uid = $this->account->id();
    }

    $messages = MessagerieStorage::getAllMessageSend($uid);
    $html ='';

    foreach ($messages as $message) {
      $user = MessagerieStorage::getUserInfo($message->uidctc);
      $imageuser = File::load( $user->fid );

      if($imageuser){
        $imageuser = file_url_transform_relative(\Drupal\Core\Url::fromUri(file_create_url($imageuser->getFileUri()))->toString());  
      }
      else{
        $imageuser = '/themes/keonoo/images/nouserimage7.png';
      }

      $datechange=date('j F Y \á H:i', $message->timestamp);
      $now = time(); // or your date as well
      $datediff = $now - $message->timestamp;
      $days = floor($datediff / (60 * 60 * 24));
      $timeMessage = date('H:i', $message->timestamp);
      if ($days == 1) {
        $timeMessage = t("HIER");
      } elseif ($days > 1) {
        $timeMessage = $days." ".t("JOURS");
      }

      $link_info = Url::fromRoute('messagerie.load.comment_info_send',['arg'=>$message->mid]);
      $link_info->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);

      if(empty($user->fname) && empty($user->lname)){
        $name = $user->username;
      }
      else{
        $name = $user->fname.' '.$user->lname;
      }

      $html .='
        <div class="row mess-'.$message->mid.' mess-send">
          <div class="col-4 col-img">
            <span class="message-image">
              <img src="'.$imageuser.'" class="user-photop" width="32px" height="32px" >
            </span>
          </div>
          <div class="col-4 col-message">'.Link::fromTextAndUrl(t('<div class="message-user">'.$name.'</div>
                  <div class="message-subject">'.$message->subject.'</div>'), $link_info)->toString().'
          </div>
          <div class="col-4 col-data">
            <div class="message-date">'.$timeMessage.'</div>
            <div>
              <span class="message-view-message" mid="'.$message->mid.'">'.Link::fromTextAndUrl(t('<i class="fa fa-reply" aria-hidden="true"></i>'), $link_info)->toString().'</span>
            </div>
          </div>
        </div>';
    }

    $countMess = MessagerieStorage::getAllCMessageSend($uid);
    $pagination = floor($countMess->cmid/25);

    if ($pagination > 0) {
      $html.='<div class="users_page">';
      $pag = $pagination*25;
      
      if($countMess->cmid > $pag ){
        $pagination++;
      }

      for ($i=0; $i < $pagination; $i++) { 
        $j = $i+1;
        $link_info = Url::fromRoute('messagerie.load.comment_send.pag',['arg'=>$i]);
        $link_info->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);

        $html .= '<span class="page_change">'.Link::fromTextAndUrl(t(''.$j.''), $link_info)->toString().'</span>';
      }
      $html.='</div>';
    }

    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.message-all', 'removeClass', array('message-active')));
    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.message-send', 'addClass', array('message-active')));
    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.message-delete', 'removeClass', array('message-active')));

    $response->addCommand(new HtmlCommand('.all-messages', $html));
    $return = $response;
    return $return; 
  }

  /**
   * Add a comment.
   */
  public function contentPagSend($arg)
  {
    $response = new AjaxResponse();

    if(empty($this->account->id())){
      return;
    }else{
      $uid = $this->account->id();
    }

    $pag = $arg*25;
    $messages = MessagerieStorage::getAllMessageSend($uid,$pag);
    $html ='';

    foreach ($messages as $message) {
      $user = MessagerieStorage::getUserInfo($message->uidctc);
      $imageuser = File::load( $user->fid );

      if($imageuser){
        $imageuser = file_url_transform_relative(\Drupal\Core\Url::fromUri(file_create_url($imageuser->getFileUri()))->toString());  
      }
      else{
        $imageuser = '/themes/keonoo/images/nouserimage7.png';
      }

      $datechange=date('j F Y \á H:i', $message->timestamp);

      $now = time(); // or your date as well
      $datediff = $now - $message->timestamp;
      $days = floor($datediff / (60 * 60 * 24));
      $timeMessage = date('H:i', $message->timestamp);
      if ($days == 1) {
        $timeMessage = t("HIER");
      } elseif ($days > 1) {
        $timeMessage = $days." ".t("JOURS");
      }
      
      $link_info = Url::fromRoute('messagerie.load.comment_info_send',['arg'=>$message->mid]);
      $link_info->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);

      if(empty($user->fname) && empty($user->lname)){
        $name = $user->username;
      }
      else{
        $name = $user->fname.' '.$user->lname;
      }

      $html .='
        <div class="row mess-'.$message->mid.' mess-send">
          <div class="col-4 col-img">
            <span class="message-image">
              <img src="'.$imageuser.'" class="user-photop" width="32px" height="32px" >
            </span>
          </div>
          <div class="col-4 col-message">'.Link::fromTextAndUrl(t('<div class="message-user">'.$name.'</div>
                  <div class="message-subject">'.$message->subject.'</div>'), $link_info)->toString().'
          </div>
          <div class="col-4 col-data">
            <div class="message-date">'.$timeMessage.'</div>
            <div>
              <span class="message-view-message" mid="'.$message->mid.'">'.Link::fromTextAndUrl(t('<i class="fa fa-reply" aria-hidden="true"></i>'), $link_info)->toString().'</span>
            </div>                
          </div>
        </div>';
    }

    $countMess = MessagerieStorage::getAllCMessageSend($uid);
    $pagination = floor($countMess->cmid/25);

    if ($pagination > 0) {
      $html.='<div class="users_page">';
      $pag = $pagination*25;

      if($countMess->cmid > $pag ){
        $pagination++;
      }

      for ($i=0; $i < $pagination; $i++) { 
        $j = $i+1;
        $link_info = Url::fromRoute('messagerie.load.comment_send.pag',['arg'=>$i]);
        $link_info->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);
        $html .= '<span class="page_change">'.Link::fromTextAndUrl(t(''.$j.''), $link_info)->toString().'</span>';
      }

      $html.='</div>';
    }

    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.message-all', 'removeClass', array('message-active')));
    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.message-send', 'addClass', array('message-active')));
    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.message-delete', 'removeClass', array('message-active')));
    $response->addCommand(new HtmlCommand('.all-messages', $html));

    $return = $response;
    return $return; 
  }

  /**
   * Add a comment.
   */
  public function contentAll()
  {
    $response = new AjaxResponse();

    if(empty($this->account->id())){
      return;
    }else{
      $uid = $this->account->id();
    }

    $messages = MessagerieStorage::getAllMessage($uid);
    $html ='';

    foreach ($messages as $message) {
      $user = MessagerieStorage::getUserInfo($message->uid);
      $imageuser = File::load( $user->fid );

      if($imageuser){
        $imageuser = file_url_transform_relative(\Drupal\Core\Url::fromUri(file_create_url($imageuser->getFileUri()))->toString());  
      }
      else{
        $imageuser = '/themes/keonoo/images/nouserimage7.png';
      } 

      $datechange=date('j F Y \á H:i', $message->timestamp);

      $now = time(); // or your date as well
      $datediff = $now - $message->timestamp;
      $days = floor($datediff / (60 * 60 * 24));
      $timeMessage = date('H:i', $message->timestamp);
      if ($days == 1) {
        $timeMessage = t("HIER");
      } elseif ($days > 1) {
        $timeMessage = $days." ".t("JOURS");
      }

      $link_sup = Url::fromRoute('messagerie.load.comment_sup',['arg'=>$message->mid]);
      $link_sup->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small'],]]);
      $link_info = Url::fromRoute('messagerie.load.comment_info',['arg'=>$message->mid]);
      $link_info->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);

      $read = '';
      if($message->read == '1'){
        $read = 'message-read';
      }

      if(empty($user->fname) && empty($user->lname)){
        $name = $user->username;
      }
      else{
        $name = $user->fname.' '.$user->lname;
      }

      $link_url = Url::fromRoute('messagerie.add.comment_reply',['arg'=>$message->mid]);
      $link_url->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);

      $html .='
        <div class="row mess-'.$message->mid.' '.$read.'">
          <div class="col-4 col-img">
            <span class="message-image">
              <img src="'.$imageuser.'" class="user-photop" width="32px" height="32px" >
            </span>
          </div>
          <div class="col-4 col-message">'.Link::fromTextAndUrl(t('<div class="message-user">'.$name.'</div>
                  <div class="message-subject">'.$message->subject.'</div>'), $link_info)->toString().'
          </div>
          <div class="col-4 col-data">
            <div class="message-date">'.$timeMessage.'</div>
            <div>
              <span class="message-delete" mid="'.$message->mid.'">'.Link::fromTextAndUrl(t('<i class="fa fa-close" aria-hidden="true"></i>'), $link_sup)->toString().'</span>
              <span class="message-view" mid="'.$message->mid.'">'.Link::fromTextAndUrl(t('<i class="fa fa-reply" aria-hidden="true"></i>'), $link_url)->toString().'</span>
            </div>
          </div>
        </div>';
    }

    $countMess = MessagerieStorage::getAllCMessage($uid);
    $pagination = floor($countMess->cmid/25);

    if ($pagination > 0) {
      $html.='<div class="users_page">';
      $pag = $pagination*25;

      if($countMess->cmid > $pag ){
        $pagination++;
      }

      for ($i=0; $i < $pagination; $i++) { 
        $j = $i+1;
        $link_info = Url::fromRoute('messagerie.load.comment.pag',['arg'=>$i]);
        $link_info->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);
        $html .= '<span class="page_change">'.Link::fromTextAndUrl(t(''.$j.''), $link_info)->toString().'</span>';
      }

      $html.='</div>';
    }

    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.message-all', 'addClass', array('message-active')));
    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.message-send', 'removeClass', array('message-active')));
    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.message-delete', 'removeClass', array('message-active')));
    $response->addCommand(new HtmlCommand('.all-messages', $html));

    $return = $response;
    return $return; 
  }

  /**
   * Add a comment
   */
  public function contentPagAll($arg) 
  {
    $response = new AjaxResponse();

    if(empty($this->account->id())){
      return;
    }else{
      $uid = $this->account->id();
    }

    $pag = $arg*25;
    $messages = MessagerieStorage::getAllMessage($uid,$pag);
    $html ='';

    foreach ($messages as $message) {
      $user = MessagerieStorage::getUserInfo($message->uid);
      $imageuser = File::load( $user->fid );

      if($imageuser){
        $imageuser = file_url_transform_relative(\Drupal\Core\Url::fromUri(file_create_url($imageuser->getFileUri()))->toString());  
      }
      else{
        $imageuser = '/themes/keonoo/images/nouserimage7.png';
      }

      $datechange=date('j F Y \á H:i', $message->timestamp);

      $now = time(); // or your date as well
      $datediff = $now - $message->timestamp;
      $days = floor($datediff / (60 * 60 * 24));
      $timeMessage = date('H:i', $message->timestamp);
      if ($days == 1) {
        $timeMessage = t("HIER");
      } elseif ($days > 1) {
        $timeMessage = $days." ".t("JOURS");
      }

      $link_sup = Url::fromRoute('messagerie.load.comment_sup',['arg'=>$message->mid]);
      $link_sup->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);
      $link_info = Url::fromRoute('messagerie.load.comment_info',['arg'=>$message->mid]);
      $link_info->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);
      $read = '';

      if ($message->read == '1') {
        $read = 'message-read';
      }

      if(empty($user->fname) && empty($user->lname)){
        $name = $user->username;
      }
      else{
        $name = $user->fname.' '.$user->lname;
      }

      $html .='
        <div class="row mess-'.$message->mid.' '.$read.'">
          <div class="col-4 col-img">
            <span class="message-image">
              <img src="'.$imageuser.'" class="user-photop" width="32px" height="32px" >
            </span>
          </div>
          <div class="col-4 col-message">'.Link::fromTextAndUrl(t('<div class="message-user">'.$name.'</div>
                  <div class="message-subject">'.$message->subject.'</div>'), $link_info)->toString().'
          </div>
          <div class="col-4 col-data">
            <div class="message-date">'.$timeMessage.'</div>
            <div>
              <span class="message-delete" mid="'.$message->mid.'">'.Link::fromTextAndUrl(t('<i class="fa fa-close" aria-hidden="true"></i>'), $link_sup)->toString().'</span>
              <span class="message-view" mid="'.$message->mid.'">'.Link::fromTextAndUrl(t('<i class="fa fa-reply" aria-hidden="true"></i>'), $link_info)->toString().'</span>
            </div>
          </div>
        </div>';
    }

    $countMess = MessagerieStorage::getAllCMessage($uid);
    $pagination = floor($countMess->cmid/25);

    if ($pagination > 0) {
      $html.='<div class="users_page">';
      $pag = $pagination*25;

      if($countMess->cmid > $pag ){
        $pagination++;
      }

      for ($i=0; $i < $pagination; $i++) { 
        $j = $i+1;
        $link_info = Url::fromRoute('messagerie.load.comment.pag',['arg'=>$i]);
        $link_info->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);
        $html .= '<span class="page_change">'.Link::fromTextAndUrl(t(''.$j.''), $link_info)->toString().'</span>';
      }

      $html.='</div>';
    }

    $response->addCommand(new HtmlCommand('.all-messages', $html));
    $return = $response;
    return $return; 
  }
    
  /**
   * Add a comment.
   */
  public function contentSup($arg)
  {
    $response = new AjaxResponse();
    MessagerieStorage::deleteMessage($arg);
    $response->addCommand(new RemoveCommand('.mess-'.$arg));
    return $response; 
  }

  /**
   * Add a comment.
   */
  public function addCommentLoadJS($arg)
  {
    $response = new AjaxResponse();
    $message = MessagerieStorage::getMessage($arg);
    $user = MessagerieStorage::getUserInfo($message->uidctc);
    $sender = MessagerieStorage::getUserInfo($message->uid);
    $datechange=date('j F Y \á H:i', $message->timestamp);
    $ccmessage = MessagerieStorage::getMessage($message->mid);
    if($ccmessage->ccsend == 0){
      $parent = $message->mid;
    }
    else{
      $parent = $message->ccsend;
    }
    $ccusers = MessagerieStorage::getCCMessage($parent);
    $ccuserName = '';
    $i=0;
    foreach ($ccusers as $value) {
      $ccuser = MessagerieStorage::getUserInfo($value->uidctc);


        if($i==0){
          $ccuserName .= (empty($ccuser->fname) && empty($ccuser->lname))?$ccuser->username:$ccuser->fname.' '.$ccuser->lname;
        }
        else{
          $ccuserName .= ','.(empty($ccuser->fname) && empty($ccuser->lname))?$ccuser->username:$ccuser->fname.' '.$ccuser->lname;
        }
        
        $i++;


      
    }
    

    $now = time(); // or your date as well
    $datediff = $now - $message->timestamp;
    $days = floor($datediff / (60 * 60 * 24));
    $timeMessage = date('H:i', $message->timestamp);

    if ($days == 1) {
      $timeMessage = t("HIER");
    } elseif ($days > 1) {
      $timeMessage = $days." ".t("JOURS");
    }

    MessagerieStorage::readMessage($arg);
    $link_sup = Url::fromRoute('messagerie.load.comment_sup',['arg'=>$message->mid]);
    $link_sup->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);
    $link_url = Url::fromRoute('messagerie.add.comment_reply',['arg'=>$message->mid]);
    $link_url->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);
    $link_urlall = Url::fromRoute('messagerie.add.comment_reply_all',['arg'=>$message->mid]);
    $link_urlall->setOptions(['attributes' => ['class' => ['use-ajax', 'button', 'button--small']]]);

    if(empty($sender->fname) && empty($sender->lname)){
      $name = $sender->username;
    }
    else{
      $name = $sender->fname.' '.$sender->lname;
    }

    $filemessage = File::load( $message->fid );
    $filemes = '';
    $filemime = '';
    if($filemessage){
      $filemime = $filemessage->getMimeType();
      $filemes = file_url_transform_relative(\Drupal\Core\Url::fromUri(file_create_url($filemessage->getFileUri()))->toString());  
    }
    $mobileDetector = \Drupal::service('mobile_detect');
    $is_mobile = $mobileDetector->isMobile();
    if(!empty($arg) && !$is_mobile){
    $html = '
      <div class="row">
        <div class="col-12 vm-subject">
          <h3>'.$message->subject.'</h3>
        </div>
      </div>
      <div class="row vm-links">
        <div class="col-2 vm-link">'.Link::fromTextAndUrl(t('RÉPONDRE <i class="fa fa-reply" aria-hidden="true"></i>'), $link_url)->toString().'</div>
        <div class="col-3 vm-link">'.Link::fromTextAndUrl(t('REPONDRE À TOUS<i class="fas fa-reply-all"></i>'), $link_urlall)->toString().'</div>
        <!--<div class="col-4 vm-link"></div>-->
        <div class="col-3 vm-link">'.Link::fromTextAndUrl(t('SUPPRIMER <i class="fa fa-close" aria-hidden="true"></i>'), $link_sup)->toString().'</div>
      </div>
      <div class="row vm-details">
        <div class="col-sm-4 vm-from msg-expe"><strong>Expéditeur :</strong> <span>'.$name.'</span></div>
        <div class="col-sm-4 vm-cc"><strong>CC :</strong> <span>'.$ccuserName.'</span></div>
        <div class="col-sm-4 vm-date">'.$timeMessage.'</div>
      </div>
      <div class="row vm-body">
        <div class="col-12">'.$message->description.'</div>
      </div>
      <div class="row vm-body">';
      if(strpos($filemime, 'image')!==false){
        $html .= '
            <div class="col-12"><div class="loader"></div> <a href="'.$filemes.'" target="_blank"><img src="'.$filemes.'" class="user-photop forum-dis-non" width="32px" height="32px"></a></div>
          </div>';
      }
      else{
        $html .= '
          <div class="col-12">'.(empty($filemes)?'':'<a href="'.$filemes.'" target="_blank">Fichier</a>').'</div>
        </div>';
      }
    }else{
      $html = '
      <div class="row">
        <div class="col-12 vm-subject">
          <h3>'.$message->subject.'</h3>
        </div>
      </div>
      <div class="row vm-links">
        <div class="col-2 vm-link">'.Link::fromTextAndUrl(t('RÉPONDRE <i class="fa fa-reply" aria-hidden="true"></i>'), $link_url)->toString().'</div>
        <div class="col-3 vm-link">'.Link::fromTextAndUrl(t('RÉP. À TOUS<i class="fa fa-reply" aria-hidden="true"></i>'), $link_urlall)->toString().'</div>
        <!--<div class="col-4 vm-link"></div>-->
        <div class="col-3 vm-link">'.Link::fromTextAndUrl(t('SUPPR. <i class="fa fa-close" aria-hidden="true"></i>'), $link_sup)->toString().'</div>
      </div>
      <div class="row vm-details">
        <div class="col-sm-4 vm-from msg-expe"><strong>Expéditeur :</strong> <span>'.$name.'</span></div>
        <div class="col-sm-4 vm-cc"><strong>CC :</strong> <span>'.$ccuserName.'</span></div>
        <div class="col-sm-4 vm-date">'.$timeMessage.'</div>
      </div>
      <div class="row vm-body">
        <div class="col-12">'.$message->description.'</div>
      </div>
      <div class="row vm-body">';
      if(strpos($filemime, 'image')!==false){
        $html .= '
            <div class="col-12"><div class="loader"></div> <a href="'.$filemes.'" target="_blank"><img src="'.$filemes.'" class="user-photop forum-dis-non" width="32px" height="32px"></a></div>
          </div>';
      }
      else{
        $html .= '
          <div class="col-12">'.(empty($filemes)?'':'<a href="'.$filemes.'" target="_blank">Fichier</a>').'</div>
        </div>';
      }
    }
    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand(NULL, 'myTest', ['arg'=>'1']));
    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.mess-'.$arg, 'addClass', array('message-read')));
    $response->addCommand(new HtmlCommand('.display-comment', $html));
    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand(NULL, 'removeChooseCl', ['arg'=>'1']));
    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.mess-'.$arg, 'addClass', array('messagerie-choose')));
    
    if($is_mobile){
      $response->addCommand(new HtmlCommand('.display-inbox', ''));
    }
    
    $return = $response;
    return $return; 
  }

  /**
   * Add a comment.
   */
  public function addCommentReplyJS($arg)
  {
    $response = new AjaxResponse();
    $form = \Drupal::formBuilder()->getForm('Drupal\messagerie\Form\AddCommentReplyForm',$arg);
    $response->addCommand(
        new HtmlCommand('.display-comment', $form)
      );
    $mobileDetector = \Drupal::service('mobile_detect');
    $is_mobile = $mobileDetector->isMobile();
    if($is_mobile){
      $response->addCommand(new HtmlCommand('.display-inbox', ''));
    }
    
    $return = $response;
    return $return; 
  }

  /**
   * Add a comment.
   */
  public function addCommentReplyContactJS($arg)
  {
    $response = new AjaxResponse();
    $form = \Drupal::formBuilder()->getForm('Drupal\messagerie\Form\AddCommentReplyContactForm',$arg);
    $response->addCommand(
        new HtmlCommand('.display-comment', $form)
      );
    $mobileDetector = \Drupal::service('mobile_detect');
    $is_mobile = $mobileDetector->isMobile();
    if($is_mobile){
      $response->addCommand(new HtmlCommand('.display-inbox', ''));
    }
    
    $return = $response;
    return $return; 
  }

  /**
   * Add a comment.
   */
  public function addCommentReplyAllJS($arg)
  {
    $response = new AjaxResponse();
    $form = \Drupal::formBuilder()->getForm('Drupal\messagerie\Form\AddCommentReplyAllForm',$arg);
    $response->addCommand(
        new HtmlCommand('.display-comment', $form)
      );
    $mobileDetector = \Drupal::service('mobile_detect');
    $is_mobile = $mobileDetector->isMobile();
    if($is_mobile){
      $response->addCommand(new HtmlCommand('.display-inbox', ''));
    }
    
    $return = $response;
    return $return; 
  }

  /**
   * Add a comment.
   */
  public function addCommentJS()
  {
    $response = new AjaxResponse();
    $form = \Drupal::formBuilder()->getForm('Drupal\messagerie\Form\AddCommentForm');
    $response->addCommand(
        new HtmlCommand('.display-comment', $form)
      );
    $mobileDetector = \Drupal::service('mobile_detect');
    $is_mobile = $mobileDetector->isMobile();
    if($is_mobile){
      $response->addCommand(new HtmlCommand('.display-inbox', ''));
    }
    
    $return = $response;
    return $return; 
  }

  /**
   * Save a comment.
   */
  public function saveCommentJS()
  {
    if(empty(\Drupal::currentUser()->id())){
      return;
    }else{
      $uid = \Drupal::currentUser()->id();
    }
    
    $destina = $_POST['destinatarie'];
    $subject = $_POST['subject'];
    $body = $_POST['body'];
    $filemessage = empty($_POST['filemessage']['fids'])? 0 : $_POST['filemessage'];
    $response = new AjaxResponse();
    if(!empty($destina) && !empty($body['value'])){
      $destinataries = explode(',', $destina);
      $i=0;
      foreach ($destinataries as $destinatarie) {
        if(!empty($destinatarie)){
          $start_p = strrpos(trim($destinatarie), '(');
          if($start_p!==false){
            $end_p = strrpos(trim($destinatarie), ')');
            $long = $end_p-($start_p+1);
            $destinatarie_p = substr(trim($destinatarie), $start_p+1,$long);
          }
          else{
            $destinatarie_p = trim($destinatarie);
          }
          if($i==0){
            $mid = MessagerieStorage::addMessage($uid, $destinatarie_p, $body['value'], $subject, 0, $filemessage);
          }
          else{
            MessagerieStorage::addMessage($uid, $destinatarie_p, $body['value'], $subject, $mid, $filemessage); 
          }
        }
        
        $i++;
      }

      $return = $response->addCommand(
         new RedirectCommand('/messagerie')
        );
    }
    elseif(empty($destina)){
      $response->addCommand(new HtmlCommand('.kexplanations', '<span class="kozon-mandatory">* Destinataire veuillez renseigner ce champ</span>'));
      $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.destin-row', 'addClass', array('destin-row-red')));
      $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.cke_reset', 'removeClass', array('destin-row-red')));
      $return = $response;
    }
    elseif(empty($body['value'])){
      $response->addCommand(new HtmlCommand('.kexplanations', '<span class="kozon-mandatory">* Corps veuillez renseigner ce champ</span>'));
      $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.destin-row', 'removeClass', array('destin-row-red')));
      $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.cke_reset', 'addClass', array('destin-row-red')));
      
      $return = $response;
    }
    else{
      $return = $response;
    }
    return $return; 
  }

  /**
   * Save a comment.
   */
  public function saveCommentReplyJS()
  {
    if(empty(\Drupal::currentUser()->id())){
      return;
    }else{
      $uid = \Drupal::currentUser()->id();
    }
    
    $destina = $_POST['destinatarie'];
    $subject = $_POST['subject'];
    $mid = $_POST['mid'];
    $body = $_POST['body'];
    $filemessage = empty($_POST['filemessage']['fids'])? 0 : $_POST['filemessage'];
    $response = new AjaxResponse();
    $fuser = MessagerieStorage::getMessage($mid);
    if(empty($subject)){
      $subject = 'Re: '.$fuser->subject;
    }
    if(!empty($destina) && !empty($body['value'])){
      $destinataries = explode(',', $destina);
      $i=0;
      foreach ($destinataries as $destinatarie) {
        $start_p = strrpos(trim($destinatarie), '(');
        if($start_p!==false){
          $end_p = strrpos(trim($destinatarie), ')');
          $long = $end_p-($start_p+1);
          $destinatarie_p = substr(trim($destinatarie), $start_p+1,$long);
        }
        else{
          $destinatarie_p = trim($destinatarie);
        }
        if($i==0){
          $mid = MessagerieStorage::addMessage($uid, $destinatarie_p, 'Re:'.$fuser->subject.'<br/>'.$fuser->description.'<br/><hr>'.$body['value'], $subject, 0, $filemessage);
        }
        else{
          MessagerieStorage::addMessage($uid, $destinatarie_p, 'Re:'.$fuser->subject.'<br/>'.$fuser->description.'<br/><hr>'.$body['value'], $subject, $mid, $filemessage); 
        }
        
        $i++;
      }

      $return = $response->addCommand(
         new RedirectCommand('/messagerie')
        );
    }
    elseif(empty($destina)){
      $response->addCommand(new HtmlCommand('.kexplanations', '<span class="kozon-mandatory">* Destinataire veuillez renseigner ce champ</span>'));
      $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.destin-row', 'addClass', array('destin-row-red')));
      $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.cke_reset', 'removeClass', array('destin-row-red')));
      $return = $response;
    }
    elseif(empty($body['value'])){
      $response->addCommand(new HtmlCommand('.kexplanations', '<span class="kozon-mandatory">* Corps veuillez renseigner ce champ</span>'));
      $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.destin-row', 'removeClass', array('destin-row-red')));
      $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.cke_reset', 'addClass', array('destin-row-red')));
      
      $return = $response;
    }
    else{
      $return = $response;
    }
    return $return; 
  }

  /**
   * Save a comment.
   */
  public function saveCommentContactJS()
  {
    if(empty(\Drupal::currentUser()->id())){
      return;
    }else{
      $uid = \Drupal::currentUser()->id();
    }
    
    $destina = $_POST['destinatarie'];
    $subject = $_POST['subject'];
    $body = $_POST['body'];
    $filemessage = empty($_POST['filemessage']['fids'])? 0 : $_POST['filemessage'];
    $response = new AjaxResponse();
    if(!empty($destina) && !empty($body['value'])){
      $destinataries = explode(',', $destina);
      $i=0;
      foreach ($destinataries as $destinatarie) {
        $start_p = strrpos(trim($destinatarie), '(');
        if($start_p!==false){
          $end_p = strrpos(trim($destinatarie), ')');
          $long = $end_p-($start_p+1);
          $destinatarie_p = substr(trim($destinatarie), $start_p+1,$long);
        }
        else{
          $destinatarie_p = trim($destinatarie);
        }
        if($i==0){
          $mid = MessagerieStorage::addMessage($uid, $destinatarie_p, $body['value'], $subject, 0, $filemessage);
        }
        else{
          MessagerieStorage::addMessage($uid, $destinatarie_p, $body['value'], $subject, $mid, $filemessage); 
        }
        
        $i++;
      }

      $return = $response->addCommand(
         new RedirectCommand('/messagerie')
        );
    }
    elseif(empty($destina)){
      $response->addCommand(new HtmlCommand('.kexplanations', '<span class="kozon-mandatory">* Destinataire veuillez renseigner ce champ</span>'));
      $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.destin-row', 'addClass', array('destin-row-red')));
      $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.cke_reset', 'removeClass', array('destin-row-red')));
      $return = $response;
    }
    elseif(empty($body['value'])){
      $response->addCommand(new HtmlCommand('.kexplanations', '<span class="kozon-mandatory">* Corps veuillez renseigner ce champ</span>'));
      $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.destin-row', 'removeClass', array('destin-row-red')));
      $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.cke_reset', 'addClass', array('destin-row-red')));
      
      $return = $response;
    }
    else{
      $return = $response;
    }
    return $return; 
  }

  /**
   * Hide a comment form.
   */
  public function hideCommentJS()
  {
    $response = new AjaxResponse();
    $return = $response->addCommand(
        new HtmlCommand('.display-comment', '')
      );
    return $return; 
  }

  /**
   * Add a comment.
   */
  public function addCommentLoadSendJS($arg)
  {
    $response = new AjaxResponse();
    $message = MessagerieStorage::getMessage($arg);

    $user = MessagerieStorage::getUserInfo($message->uidctc);
    $sender = MessagerieStorage::getUserInfo($message->uid);
    setlocale(LC_TIME, "fr_FR");
    if(!empty($GLOBALS['language_content']->language)){
      $time = format_date($message->timestamp, 'custom', "j F Y \á H:i", NULL, $GLOBALS['language_content']->language);
    }
    else{
      $time = format_date($message->timestamp, 'custom', "j F Y \á H:i");
    }
    //$datechange=date('j F Y \á H:i', $message->timestamp);
    
    if(empty($sender->fname) && empty($sender->lname)){
      $name = $sender->username;
    }
    else{
      $name = $sender->fname.' '.$sender->lname;
    }
    $ccUser = (empty($user->fname) && empty($user->lname))?$user->username:$user->fname.' '.$user->lname;
    $filemessage = File::load( $message->fid );
    $filemes = '';
    $filemime = '';
    if($filemessage){
      $filemime = $filemessage->getMimeType();
      $filemes = file_url_transform_relative(\Drupal\Core\Url::fromUri(file_create_url($filemessage->getFileUri()))->toString());  
    }
    $html = '
      <div class="row">
        <div class="col-12 vm-subject"><h3>'.$message->subject.'</h3></div>
      </div>
      <div class="row vm-links">
      </div>
      <div class="row vm-details">
        <hr>
        <div class="col-4 vm-cc msg-expe"><strong>Expéditeur:</strong> '.$name.'</div>
        <div class="col-4 vm-from msg-expe"><strong>Destinataire:</strong> '.$ccUser.'</div>
        <div class="col-4 vm-date">'.$time.'</div>
        <hr>
      </div>
      <div class="row vm-body">
        <div class="col-12">'.$message->description.'</div>
      </div>
      <div class="row vm-body">';
      if(strpos($filemime, 'image')!==false){
        $html .= '
            <div class="col-12"><div class="loader"></div> <a href="'.$filemes.'" target="_blank"><img src="'.$filemes.'" class="user-photop forum-dis-non" width="32px" height="32px"></a></div>
          </div>';
      }
      else{
        $html .= '
          <div class="col-12">'.(empty($filemes)?'':'<a href="'.$filemes.'" target="_blank">Fichier</a>').'</div>
        </div>';
      }


    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand(NULL, 'myTest', ['arg'=>'1']));
    $response->addCommand(
        new HtmlCommand('.display-comment', $html)
      );
    $mobileDetector = \Drupal::service('mobile_detect');
    $is_mobile = $mobileDetector->isMobile();
    if($is_mobile){
      $response->addCommand(new HtmlCommand('.display-inbox', ''));
    }
    $return = $response;
    return $return; 
  }

  /**
   * Save a comment.
   */
  public function saveAllCommentJS()
  {
    if(empty(\Drupal::currentUser()->id())){
      return;
    }else{
      $uid = \Drupal::currentUser()->id();
    }
    
    $destina = $_POST['destinatarie'];
    $subject = $_POST['subject'];
    $body = $_POST['body'];
    $filemessage = empty($_POST['filemessage']['fids'])? 0 : $_POST['filemessage'];
    $response = new AjaxResponse();
    if(!empty($destina) && !empty($body['value'])){
      $destinataries = explode(',', $destina);
      $fuser = MessagerieStorage::getMessage($destina);
      if(empty($subject)){
        $subject = 'Re: '.$fuser->subject;
      }
      $mid = MessagerieStorage::addMessage($uid, $fuser->uid, $body['value'], $subject, 0, $filemessage);
      $destinataries = MessagerieStorage::getCCMessage($destina);
      foreach ($destinataries as $destinatarie) {
        if($destinatarie->uidctc != $uid){
          MessagerieStorage::addMessage($uid, $destinatarie->uidctc, 'Re:'.$fuser->subject.'<br/>'.$fuser->description.'<br/><hr>'.$body['value'], $subject, $mid, $filemessage); 
        }
      }
      $return = $response->addCommand(
         new RedirectCommand('/messagerie')
        );
    }elseif(empty($destina)){
      $response->addCommand(new HtmlCommand('.kexplanations', '<span class="kozon-mandatory">* Destinataire veuillez renseigner ce champ</span>'));
      $return = $response;
    }
    elseif(empty($body['value'])){
      $response->addCommand(new HtmlCommand('.kexplanations', '<span class="kozon-mandatory">* Corps veuillez renseigner ce champ</span>'));
      $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('.cke_reset', 'addClass', array('destin-row-red')));
      
      $return = $response;
    }
    else{
      $return = $response;
    }
    return $return; 
  }

  public function checNewJS(){
    if(empty($this->account->id())){
      return;
    }
    else{
      $uid = $this->account->id();
    }
    
     $mid = MessagerieStorage::getMessageRead($uid);
     if($mid->count > 0){
      $options = array('done' => 'new');
     }else{
       $options = array('done' => 'no');
     }
      
    return new JsonResponse($options); 
  }

}

