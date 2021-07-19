<?php

    class User implements UserInterface{
        private $firstName,
                $lastName,
                $email,
                $phone,
                $password,
                $id,
                $dob;

        public function __construct(){

        }

        /**
         * Loads a user from the database;
         * @param int $userId
         */
        public function loadUser($userId){

        }

        /**
         * Changes the user password when the user is logged in
         * @param string $oldPassword
         */
        public function changePassword($oldPassword){

        }

        /**
         * Save the user to the database
         */
        public function save(){

        }

        /**
         * Register a new user
         * the email and password must be set
         *  @return string 
         */
        public function register(){
            if($this->email == null){
                return "NEE"; //Null Email Error
            }
    
            if($this->password == null){
                return "NPE"; //Null Password Error
            }
    
            if(!Utility::checkEmail($this->email)){
                return "UEE"; //Unqualified Email Error
            }
            $dbManager = new DbManager();

            if(User::doesEmailExist($this->email, $dbManager)){
                return "EEE"; //Email Exist Error
            }
    
            if(Utility::isPasswordStrong($this->password) !== true){
                return Utility::isPasswordStrong($this->password); 
            }
    
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
     
            $tableName = "user";
            $column_specs = ["email","`password`","profile_image"];
            $values = [$this->email, $this->password, "assets/img/logo.svg"];

            try{
                $insertId = $dbManager->insertIntoTable($tableName, $column_specs, $values);
                if($insertId != -1){
                    $this->id = $insertId;
                    if($this->sendConfEmail()){
                        return "OK";
                    }
                    return "SVE"; //Send Verification Email Error
                }
           
            }
            catch(Exception $exception){
                
            }

            return "SQE";
        }

        public function login(){
            if(!isset($this->email) || empty($this->email)){
                return "EEE";//email empty error
            }
    
            if(!isset($this->password) || empty($this->password)){
                return "EPE";//empty password error
            }
    
            try{
                $tableName = "user";
                $columns = ["id","email","password"];
                $values = [$this->email];
                $condition = ["email"];
                
                $dbManager = new DbManager();
                $dbManager->setFetchAll(false);

                $details = $dbManager->selectFromTable($tableName, $columns, $condition, $values);
                
                if($details){
    
                    $hashed_password = $details['password'];
                    $userId = $details['id'];
    
                    if(!password_verify($this->password, $hashed_password)){
                        return "WPE";//wrong password error
                    }
    
                    $_SESSION['userId'] = $userId;
                    $this->id = $userId;

                    return "OK"; //We have logged in successfully
                }
                else
                {
                    return "WEE";//wrong email error
                } 
            }
    
            catch (Exception $e)
            {
                return "SQE"; //SQL Error
            }
        }

        public function logout(){
            
        }

        /**
         * Deletes a user account
         */
        public function deleteAccount(){

        }

        /**
         * Checks if an email exist already
        * @param string $email
        * @param DbManager $dbManager
        * @return bool
        */
        public static function doesEmailExist($email, $dbManager){
            $table = "user";
            $columns = ["email"];
            $condition = ["email"];
            $values = [$email];

            $fetchAll = $dbManager->getFetchAll();
            $dbManager->setFetchAll(false);
            $result = $dbManager->selectFromTable($table, $columns, $condition, $values);
            $dbManager->setFetchAll($fetchAll);

            if($result && count($result) > 0){
                return true;
            }

            return false;
        }

        /**
         * This method sends the confirmation email to the user.
         * The id and the email of the object should be set before this method is called.
         * include the phpmailer.inc.php file to make this function work
         */
        public function sendConfEmail(){
            if(empty($this->id) || empty($this->email)){
                return false;
            }
            $dateTimestamp = time();
            $hostPortion = "localhost";
            $encryptedId = Utility::letoBase29Decode($this->id);
            $encryptedEmail = base64_encode(base64_encode($this->email));
            $encryptedDate = Utility::letoBase29Encode($dateTimestamp);

            $encrypedLink = "ez=$encryptedEmail&r=$encryptedDate&m=$encryptedId";
            $link = "$hostPortion/confirmEmail.php?$encrypedLink";

            $sub = "Verify your email";
            $msg = "Please click the link to verify your email. A nice html will be written for this later. link is $link";

            return sendEmail($this->email, $this->email, $sub, $msg);
        }

        

        /**
         * Get the value of firstName
         */ 
        public function getFirstName()
        {
                return $this->firstName;
        }

        /**
         * Set the value of firstName
         *
         * @return  self
         */ 
        public function setFirstName($firstName)
        {
                $this->firstName = $firstName;

                return $this;
        }

        /**
         * Get the value of lastName
         */ 
        public function getLastName()
        {
                        return $this->lastName;
        }

        /**
         * Set the value of lastName
         *
         * @return  self
         */ 
        public function setLastName($lastName)
        {
                        $this->lastName = $lastName;

                        return $this;
        }

        /**
         * Get the value of email
         */ 
        public function getEmail()
        {
                        return $this->email;
        }

        /**
         * Set the value of email
         *
         * @return  self
         */ 
        public function setEmail($email)
        {
                        $this->email = $email;

                        return $this;
        }

        /**
         * Get the value of phone
         */ 
        public function getPhone()
        {
                        return $this->phone;
        }

        /**
         * Set the value of phone
         *
         * @return  self
         */ 
        public function setPhone($phone)
        {
                        $this->phone = $phone;

                        return $this;
        }

        /**
         * Get the value of password
         */ 
        public function getPassword()
        {
                        return $this->password;
        }

        /**
         * Set the value of password
         *
         * @return  self
         */ 
        public function setPassword($password)
        {
                        $this->password = $password;

                        return $this;
        }

        /**
         * Get the value of id
         */ 
        public function getId()
        {
                        return $this->id;
        }

        /**
         * Set the value of id
         *
         * @return  self
         */ 
        public function setId($id)
        {
                        $this->id = $id;

                        return $this;
        }

        /**
         * Get the value of dob
         */ 
        public function getDob()
        {
                        return $this->dob;
        }

        /**
         * Set the value of dob
         *
         * @return  self
         */ 
        public function setDob($dob)
        {
                        $this->dob = $dob;

                        return $this;
        }
    }

?>