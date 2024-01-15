<?php

namespace Drupal\messagerie;	

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Description of ActualiteStorage
 *
 * @author developer
 */
class MessagerieStorage {

    /**
    * {@inheritdoc}
    */
    static function getMessages($user_id){
        return db_query("
        SELECT m.message__value,m.owner as sender, c.id as chat_id, m.id,users_chat.members_target_id as receiver
FROM 
private_message_thread__private_messages r
inner join   private_messages m  on  r.private_messages_target_id=m.id
inner join   private_message_threads c  on  c.id=r.entity_id
inner join private_message_thread__members users_chat on r.entity_id= users_chat.entity_id 
where  
users_chat.members_target_id= 130
group by c.id " , array(':user_id'=>$user_id)
        )->fetchAll();
        
    }

    /**
    * {@inheritdoc}
    */
    static function addMessage($user_id, $contact_id, $description, $subject, $insertId=0, $filemessage=0){
        return db_insert('messagerie')
            ->fields(array(
              'uid' => $user_id,
              'uidctc' => $contact_id,
              'subject' => $subject,
              'description' => $description,
              'read' => '0',
              'timestamp' => REQUEST_TIME,
              'ccsend' => $insertId,
              'fid' => (($filemessage!=0)?$filemessage['fids']:0)
            ))
            ->execute();
    }

    /**
    * {@inheritdoc}
    */
    static function deleteMessage($mid){
        db_update('messagerie')
			->fields(array(
			  'delete' => '1',
              'timestampdel' => REQUEST_TIME,
			))
			->condition('mid',$mid)
			->execute();
    }

    /**
    * {@inheritdoc}
    */
    static function readMessage($mid){
        db_update('messagerie')
			->fields(array(
			  'read' => '1',
			))
			->condition('mid',$mid)
			->execute();
    }

    /**
    * {@inheritdoc}
    */
    static function getMessage($mid){
        return db_query('SELECT *
            FROM {messagerie} ms 
            WHERE ms.mid = :mid' , array(':mid' => $mid))->fetchObject();
    }

    /**
    * {@inheritdoc}
    */
    static function getCCMessage($parent){
        return db_query('SELECT ms.uidctc uidctc
            FROM {messagerie} ms 
            WHERE ms.mid = :parent' , array(':parent' => $parent))->fetchAll();
    }
 	
 	/**
    * {@inheritdoc}
    */
    static function getAllMessage($uidctc, $pagination=''){
    	$deleted = '0';
        return db_query('SELECT *
            FROM {messagerie} ms 
            WHERE ms.uidctc = :uidctc and ms.delete = :deleted
            ORDER BY ms.mid DESC LIMIT 25 offset '.(empty($pagination)?'0':$pagination) , array(':uidctc' => $uidctc, ':deleted' => $deleted))->fetchAll();
    }

    /**
    * {@inheritdoc}
    */
    static function getAllCMessage($uidctc){
        $deleted = '0';
        return db_query('SELECT COUNT(ms.mid) as cmid
            FROM {messagerie} ms 
            WHERE ms.uidctc = :uidctc and ms.delete = :deleted' , array(':uidctc' => $uidctc, ':deleted' => $deleted))->fetchObject();
    }

    /**
    * {@inheritdoc}
    */
    static function getAllMessageSupprime($uidctc, $pagination=''){
        return db_query('SELECT *
            FROM {messagerie} ms 
            WHERE ms.uidctc = :uidctc and ms.delete = 1
            ORDER BY ms.mid DESC LIMIT 25 offset '.(empty($pagination)?'0':$pagination) , array(':uidctc' => $uidctc))->fetchAll();
    }

    /**
    * {@inheritdoc}
    */
    static function getAllCMessageSupprime($uidctc){
        return db_query('SELECT COUNT(ms.mid) as cmid
            FROM {messagerie} ms 
            WHERE ms.uidctc = :uidctc and ms.delete = 1' , array(':uidctc' => $uidctc))->fetchObject();
    }

    /**
    * {@inheritdoc}
    */
    static function getAllMessageSend($uid, $pagination=''){
        return db_query('SELECT *
            FROM {messagerie} ms 
            WHERE ms.uid = :uid
            ORDER BY ms.mid DESC LIMIT 25 offset '.(empty($pagination)?'0':$pagination) , array(':uid' => $uid))->fetchAll();
    }

    /**
    * {@inheritdoc}
    */
    static function getAllCMessageSend($uid){
        return db_query('SELECT COUNT(ms.mid) as cmid
            FROM {messagerie} ms 
            WHERE ms.uid = :uid' , array(':uid' => $uid))->fetchObject();
    }

    /**
	* {@inheritdoc}
	*/
    static function getUsers(){
        return db_query('SELECT name, uid FROM {users_field_data} ORDER BY name', array())->fetchAll();
    }

    /**
    * {@inheritdoc}
    */
    static function getAllUsersShared(){
        return db_query('SELECT ud.name name, ud.uid uid, fn.field_first_name_value firstname, ln.field_last_name_value lastname, ud.uid uid, ur.roles_target_id roles, sp.field_sharepoint_value share
            from {users_field_data} ud 
            LEFT JOIN {user__field_first_name} fn ON fn.entity_id = ud.uid
            LEFT JOIN {user__field_last_name} ln ON ln.entity_id = ud.uid
            LEFT JOIN {user__field_sharepoint} sp ON ud.uid = sp.entity_id
            LEFT JOIN {user__roles} ur ON ur.entity_id = ud.uid
         where ud.status = :status AND ur.roles_target_id != :roles order by ud.name', array(':status'=>'1', ':roles'=>'administrator'))->fetchAll();
        
    }

    /**
    * {@inheritdoc}
    */
    static function getAllUsersMessage($index_name=''){
        if(empty($index_name)){
            return db_query('SELECT ud.name name, ud.uid uid, udiv.field_division_target_id division, ud.mail mail, tn.field_fixed_telephone_number_value telephone, mb.field_mobile_phone_number_value mobile, pt.user_picture_target_id fid, ct.field_city_value city, st.field_status_value status, fn.field_first_name_value firstname, ln.field_last_name_value lastname, ud.uid uid, sg.field_sing_and_go_value sing, ko.field_keolife_value keo, ur.roles_target_id roles, sp.field_sharepoint_value share
                from {users_field_data} ud 
                LEFT JOIN {user__field_division} udiv ON udiv.entity_id = ud.uid
                LEFT JOIN {user__field_fixed_telephone_number} tn ON tn.entity_id = ud.uid
                LEFT JOIN {user__field_mobile_phone_number} mb ON mb.entity_id = ud.uid
                LEFT JOIN {user__user_picture} pt ON pt.entity_id = ud.uid
                LEFT JOIN {user__field_city} ct ON ct.entity_id = ud.uid
                LEFT JOIN {user__field_status} st ON st.entity_id = ud.uid
                LEFT JOIN {user__field_first_name} fn ON fn.entity_id = ud.uid
                LEFT JOIN {user__field_last_name} ln ON ln.entity_id = ud.uid
                LEFT JOIN {user__field_sharepoint} sp ON ud.uid = sp.entity_id
                LEFT JOIN {user__field_sing_and_go} sg ON ud.uid = sg.entity_id
                LEFT JOIN {user__field_keolife} ko ON ud.uid = ko.entity_id
                LEFT JOIN {user__roles} ur ON ur.entity_id = ud.uid
             where ud.status = :status AND ur.roles_target_id != :roles order by ud.name limit 200', array(':status'=>'1', ':roles'=>'administrator'))->fetchAll();
        }
        else{
            if(strpos($index_name, ' ')!==false){
                $piece = explode(" ", $index_name);
                return db_query('SELECT ud.name name, ud.uid uid, udiv.field_division_target_id division, ud.mail mail, tn.field_fixed_telephone_number_value telephone, mb.field_mobile_phone_number_value mobile, pt.user_picture_target_id fid, ct.field_city_value city, st.field_status_value status, fn.field_first_name_value firstname, ln.field_last_name_value lastname, ud.uid uid, sg.field_sing_and_go_value sing, ko.field_keolife_value keo, ur.roles_target_id roles, sp.field_sharepoint_value share
                    from {users_field_data} ud 
                    LEFT JOIN {user__field_division} udiv ON udiv.entity_id = ud.uid
                    LEFT JOIN {user__field_fixed_telephone_number} tn ON tn.entity_id = ud.uid
                    LEFT JOIN {user__field_mobile_phone_number} mb ON mb.entity_id = ud.uid
                    LEFT JOIN {user__user_picture} pt ON pt.entity_id = ud.uid
                    LEFT JOIN {user__field_city} ct ON ct.entity_id = ud.uid
                    LEFT JOIN {user__field_status} st ON st.entity_id = ud.uid
                    LEFT JOIN {user__field_first_name} fn ON fn.entity_id = ud.uid
                    LEFT JOIN {user__field_last_name} ln ON ln.entity_id = ud.uid
                    LEFT JOIN {user__field_sharepoint} sp ON ud.uid = sp.entity_id
                    LEFT JOIN {user__field_sing_and_go} sg ON ud.uid = sg.entity_id
                    LEFT JOIN {user__field_keolife} ko ON ud.uid = ko.entity_id
                    LEFT JOIN {user__roles} ur ON ur.entity_id = ud.uid
                 where fn.field_first_name_value LIKE :pattern AND ln.field_last_name_value LIKE :pattern1 and ud.status = :status AND ur.roles_target_id != :roles order by ud.name limit 200', array(':pattern' => \Drupal::database()->escapeLike($piece[0]) . '%', ':pattern1' => \Drupal::database()->escapeLike($piece[1]) . '%', ':status'=>'1', ':roles'=>'administrator'))->fetchAll();
            }
            else{
                return db_query('SELECT DISTINCT ud.name name, ud.uid uid, udiv.field_division_target_id division, ud.mail mail, tn.field_fixed_telephone_number_value telephone, mb.field_mobile_phone_number_value mobile, pt.user_picture_target_id fid, ct.field_city_value city, st.field_status_value status, fn.field_first_name_value firstname, ln.field_last_name_value lastname, ud.uid uid, sp.field_sharepoint_value share, sg.field_sing_and_go_value sing, ko.field_keolife_value keo, ur.roles_target_id roles, sp.field_sharepoint_value share
                    from {users_field_data} ud 
                    LEFT JOIN {user__field_division} udiv ON udiv.entity_id = ud.uid
                    LEFT JOIN {user__field_fixed_telephone_number} tn ON tn.entity_id = ud.uid
                    LEFT JOIN {user__field_mobile_phone_number} mb ON mb.entity_id = ud.uid
                    LEFT JOIN {user__user_picture} pt ON pt.entity_id = ud.uid
                    LEFT JOIN {user__field_city} ct ON ct.entity_id = ud.uid
                    LEFT JOIN {user__field_status} st ON st.entity_id = ud.uid
                    LEFT JOIN {user__field_first_name} fn ON fn.entity_id = ud.uid
                    LEFT JOIN {user__field_last_name} ln ON ln.entity_id = ud.uid
                    LEFT JOIN {user__field_sharepoint} sp ON ud.uid = sp.entity_id
                    LEFT JOIN {user__field_sing_and_go} sg ON ud.uid = sg.entity_id
                    LEFT JOIN {user__field_keolife} ko ON ud.uid = ko.entity_id
                    LEFT JOIN {user__roles} ur ON ur.entity_id = ud.uid
                 where ud.status = :status AND ur.roles_target_id != :roles AND ud.name LIKE :pattern OR fn.field_first_name_value LIKE :pattern OR ln.field_last_name_value LIKE :pattern order by ud.name limit 200', array(':pattern' => \Drupal::database()->escapeLike($index_name) . '%', ':status'=>'1', ':roles'=>'administrator'))->fetchAll();
            }
        }
    }

    /**
    * {@inheritdoc}
    */
    static function getAllUsers($index_name=''){
        if(empty($index_name)){
            return db_query('select ud.name name, ud.uid uid, udiv.field_division_target_id division, ud.mail mail, tn.field_fixed_telephone_number_value telephone, mb.field_mobile_phone_number_value mobile, pt.user_picture_target_id fid, ct.field_city_value city, st.field_status_value status, fn.field_first_name_value firstname, ln.field_last_name_value lastname, ud.uid uid
                from {users_field_data} ud 
                LEFT JOIN {user__field_division} udiv ON udiv.entity_id = ud.uid
                LEFT JOIN {user__field_fixed_telephone_number} tn ON tn.entity_id = ud.uid
                LEFT JOIN {user__field_mobile_phone_number} mb ON mb.entity_id = ud.uid
                LEFT JOIN {user__user_picture} pt ON pt.entity_id = ud.uid
                LEFT JOIN {user__field_city} ct ON ct.entity_id = ud.uid
                LEFT JOIN {user__field_status} st ON st.entity_id = ud.uid
                LEFT JOIN {user__field_first_name} fn ON fn.entity_id = ud.uid
                LEFT JOIN {user__field_last_name} ln ON ln.entity_id = ud.uid
             where ud.status = :status order by ud.name', array(':status'=>'1'))->fetchAll();
        }
        else{
            if(strpos($index_name, ' ')!==false){
                $piece = explode(" ", $index_name);
                return db_query('select ud.name name, ud.uid uid, udiv.field_division_target_id division, ud.mail mail, tn.field_fixed_telephone_number_value telephone, mb.field_mobile_phone_number_value mobile, pt.user_picture_target_id fid, ct.field_city_value city, st.field_status_value status, fn.field_first_name_value firstname, ln.field_last_name_value lastname, ud.uid uid
                    from {users_field_data} ud 
                    LEFT JOIN {user__field_division} udiv ON udiv.entity_id = ud.uid
                    LEFT JOIN {user__field_fixed_telephone_number} tn ON tn.entity_id = ud.uid
                    LEFT JOIN {user__field_mobile_phone_number} mb ON mb.entity_id = ud.uid
                    LEFT JOIN {user__user_picture} pt ON pt.entity_id = ud.uid
                    LEFT JOIN {user__field_city} ct ON ct.entity_id = ud.uid
                    LEFT JOIN {user__field_status} st ON st.entity_id = ud.uid
                    LEFT JOIN {user__field_first_name} fn ON fn.entity_id = ud.uid
                    LEFT JOIN {user__field_last_name} ln ON ln.entity_id = ud.uid
                 where fn.field_first_name_value LIKE :pattern AND ln.field_last_name_value LIKE :pattern1 and ud.status = :status order by ud.name', array(':pattern' => \Drupal::database()->escapeLike($piece[0]) . '%', ':pattern1' => \Drupal::database()->escapeLike($piece[1]) . '%', ':status'=>'1'))->fetchAll();
            }
            else{
                return db_query('select ud.name name, ud.uid uid, udiv.field_division_target_id division, ud.mail mail, tn.field_fixed_telephone_number_value telephone, mb.field_mobile_phone_number_value mobile, pt.user_picture_target_id fid, ct.field_city_value city, st.field_status_value status, fn.field_first_name_value firstname, ln.field_last_name_value lastname, ud.uid uid
                    from {users_field_data} ud 
                    LEFT JOIN {user__field_division} udiv ON udiv.entity_id = ud.uid
                    LEFT JOIN {user__field_fixed_telephone_number} tn ON tn.entity_id = ud.uid
                    LEFT JOIN {user__field_mobile_phone_number} mb ON mb.entity_id = ud.uid
                    LEFT JOIN {user__user_picture} pt ON pt.entity_id = ud.uid
                    LEFT JOIN {user__field_city} ct ON ct.entity_id = ud.uid
                    LEFT JOIN {user__field_status} st ON st.entity_id = ud.uid
                    LEFT JOIN {user__field_first_name} fn ON fn.entity_id = ud.uid
                    LEFT JOIN {user__field_last_name} ln ON ln.entity_id = ud.uid
                 where ud.name LIKE :pattern OR fn.field_first_name_value LIKE :pattern OR ln.field_last_name_value LIKE :pattern and ud.status = :status order by ud.name', array(':pattern' => \Drupal::database()->escapeLike($index_name) . '%', ':status'=>'1'))->fetchAll();
            }
        }
    }

    /**
    * {@inheritdoc}
    */
    static function getUserInfo($uid){
        return db_query('SELECT ud.name username, pt.user_picture_target_id fid, fn.field_first_name_value fname, ln.field_last_name_value lname
            FROM {users_field_data} ud
            LEFT JOIN {user__user_picture} pt ON pt.entity_id = ud.uid 
            LEFT JOIN {user__field_first_name} fn ON fn.entity_id = ud.uid 
            LEFT JOIN {user__field_last_name} ln ON ln.entity_id = ud.uid 
            WHERE ud.uid=:uid', array(':uid'=>$uid))->fetchObject();
    }

    /**
    * {@inheritdoc}
    */
    static function getContactProfile($user_id){
        return db_query('SELECT ud.name name, ud.uid uid, udiv.field_division_target_id division, pt.user_picture_target_id fid, fn.field_first_name_value firstname, ln.field_last_name_value lastname, tn.field_fixed_telephone_number_value telephone, mb.field_mobile_phone_number_value mobile, pc.uidctc contact, ud.mail mail, ct.field_city_value city, st.field_status_value status
            FROM {profile_contacts} pc 
            LEFT JOIN {users_field_data} ud ON ud.uid = pc.uidctc
            LEFT JOIN {user__field_division} udiv ON udiv.entity_id = ud.uid
            LEFT JOIN {user__user_picture} pt ON pt.entity_id = ud.uid
            LEFT JOIN {user__field_first_name} fn ON fn.entity_id = ud.uid
            LEFT JOIN {user__field_last_name} ln ON ln.entity_id = ud.uid
            LEFT JOIN {user__field_fixed_telephone_number} tn ON tn.entity_id = ud.uid
            LEFT JOIN {user__field_mobile_phone_number} mb ON mb.entity_id = ud.uid
            LEFT JOIN {user__field_city} ct ON ct.entity_id = ud.uid
            LEFT JOIN {user__field_status} st ON st.entity_id = ud.uid
            WHERE pc.uid = :userid ' , array(':userid' => $user_id))->fetchAll();
    }

    /**
    * {@inheritdoc}
    */
    static function getMessageRead($uid){
        return db_query('SELECT count(ud.mid) count
            FROM {messagerie} ud
            WHERE ud.uidctc=:uid AND ud.read=:read', array(':uid'=>$uid,':read'=>0))->fetchObject();
    }
   
}
