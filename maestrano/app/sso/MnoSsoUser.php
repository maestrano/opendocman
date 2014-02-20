<?php

/**
 * Configure App specific behavior for 
 * Maestrano SSO
 */
class MnoSsoUser extends MnoSsoBaseUser
{
  /**
   * Database connection
   * @var PDO
   */
  public $connection = null;
  
  
  /**
   * Extend constructor to inialize app specific objects
   *
   * @param OneLogin_Saml_Response $saml_response
   *   A SamlResponse object from Maestrano containing details
   *   about the user being authenticated
   */
  public function __construct(OneLogin_Saml_Response $saml_response, &$session = array(), $opts = array())
  {
    // Call Parent
    parent::__construct($saml_response,$session);
    
    // Assign new attributes
    $this->connection = $opts['db_connection'];
  }
  
  
  /**
   * Sign the user in the application. 
   * Parent method deals with putting the mno_uid, 
   * mno_session and mno_session_recheck in session.
   *
   * @return boolean whether the user was successfully set in session or not
   */
  protected function setInSession()
  {
    if ($this->local_id) {
        // initiate a session
        $_SESSION['uid'] = $this->local_id;
        
        return true;
    } else {
        return false;
    }
  }
  
  
  /**
   * Used by createLocalUserOrDenyAccess to create a local user 
   * based on the sso user.
   * If the method returns null then access is denied
   *
   * @return the ID of the user created, null otherwise
   */
  protected function createLocalUser()
  {
    $lid = null;
    
    if ($this->accessScope() == 'private') {
      // Build the user
      $user = $this->buildLocalUser();
      
      // Build query and run it
      $query = "INSERT INTO odm_user (username, password, department, Email,last_name, first_name) VALUES(
        '". mysql_real_escape_string($user['username'])."', 
        md5('". mysql_real_escape_string($user['password']) ."'), 
        '" . mysql_real_escape_string($user['department'])."',
        '" . mysql_real_escape_string($user['email'])."',
        '" . mysql_real_escape_string($user['last_name']) . "',
        '" . mysql_real_escape_string($user['first_name']) . "' )";
      
      $result = mysql_query($query, $this->connection);
      
      // Get user id
      $lid = mysql_insert_id($this->connection);
      
      // Set the admin status for this user
      if ($lid > 0) {
        $query = "INSERT INTO odm_admin (id, admin) VALUES(
          '" . $lid . "', 
          '" . $this->isAdmin() . "')";
        
        $result = mysql_query($query, $this->connection);
      }
    }
    
    return $lid;
  }
  
  /**
   * Build a local user for creation
   *
   * @return associative array
   */
  protected function buildLocalUser()
  {
    $user_data = Array(
      'first_name' => $this->name,
      'last_name'  => $this->surname,
      'username'   => $this->uid,
      'password'   => $this->generatePassword(),
      'email'      => $this->email,
      'department' => $this->getDefaultDepartmentId()
    );
    
    return $user_data;
  }
  
  /**
   * Return the department ID that should be assigned
   * to a user by default
   * (Opendocman requires one department at least so
   * the return value should never be null)
   *
   * @return a department ID if found, null otherwise
   */
  protected function getDefaultDepartmentId()
  {
    $query = "SELECT id FROM odm_department LIMIT 1";
    $result = mysql_query($query, $this->connection);
    $result = mysql_fetch_assoc($result);
    
    if ($result && $result['id']) {
      return $result['id'];
    }
    
    return null;
  }
  
  /**
   * Return wether the user is admin or not
   * If the user is the owner of the app or at least Admin
   * for each organization, then it is given the role of 'Admin'.
   * Return 'User' role otherwise
   *
   * @return 1 if admin, 0 otherwise
   */
  protected function isAdmin() {
    $admin = 0; // User
    
    if ($this->app_owner) {
      $admin = 1; // Admin
    } else {
      foreach ($this->organizations as $organization) {
        if ($organization['role'] == 'Admin' || $organization['role'] == 'Super Admin') {
          $admin = 1;
        } else {
          $admin = 0;
        }
      }
    }
    
    return $admin;
  }
  
  /**
   * Get the ID of a local user via Maestrano UID lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByUid()
  {
    $query = "SELECT id FROM odm_user WHERE mno_uid = '" . mysql_real_escape_string($this->uid) . "'";
    $result = mysql_query($query, $this->connection);
    $result = mysql_fetch_assoc($result);
    
    if ($result && $result['id']) {
      return $result['id'];
    }
    
    return null;
  }
  
  /**
   * Get the ID of a local user via email lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByEmail()
  {
    $query = "SELECT id FROM odm_user WHERE Email = '" . mysql_real_escape_string($this->email) . "'";
    $result = mysql_query($query, $this->connection);
    $result = mysql_fetch_assoc($result);
    
    if ($result && $result['id']) {
      return $result['id'];
    }
    
    return null;
  }
  
  /**
   * Set all 'soft' details on the user (like name, surname, email)
   * Implementing this method is optional.
   *
   * @return boolean whether the user was synced or not
   */
   protected function syncLocalDetails()
   {
     if($this->local_id) {
       
       $query = "UPDATE odm_user 
         SET Email = '" . mysql_real_escape_string($this->email) . "',
         username = '" . mysql_real_escape_string($this->uid) . "',
         last_name = '" . mysql_real_escape_string($this->surname) . "',
         first_name = '" . mysql_real_escape_string($this->name) . "',
         WHERE id = '" . mysql_real_escape_string($this->local_id) . "'";
       
       $upd = mysql_query($query, $this->connection);
       return $upd;
     }
     
     return false;
   }
  
  /**
   * Set the Maestrano UID on a local user via id lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function setLocalUid()
  {
    if($this->local_id) {
      $query = "UPDATE odm_user 
        SET mno_uid = '" . mysql_real_escape_string($this->uid) . "',
        WHERE id = '" . mysql_real_escape_string($this->local_id) . "'";
      
      $upd = mysql_query($query, $this->connection);
      return $upd;
    }
    
    return false;
  }
}