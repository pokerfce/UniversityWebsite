<?php
    require_once("MySql.php");

    class UtilisateurDB extends MySql {

        /*
            VERIFY AND QUERY METHODS
        */

        public function getIState() { 
            // checks if inscription is OPEN or CLOSED (boolean)
            
            $result = MySql::request(
                "SELECT inscription FROM website_config", array(), 1
            );
            // var_dump($result);
            return $result["inscription"];
        }

        public function userExists($CIN) {
            $resultUser = MySql::request(
                "SELECT CIN FROM utilisateur WHERE CIN = :CIN",
                array(':CIN' => $CIN),
                0
            );
            // "Erreur! Cette CIN déja inscrit auparavant!"
            return $resultUser;
        }

        public function emailExists($email, $return = 0) {
            $resultUser = MySql::request(
                "SELECT * FROM utilisateur WHERE email = :email",
                array(':email' => $email),
                $return
            );
            // "Erreur! Cette CIN déja inscrit auparavant!"
            return $resultUser;
        }

        public function listeExists($CIN) {
            $resultList = MySql::request(
                "SELECT CIN, role FROM liste_inscription WHERE CIN = :CIN",
                array(':CIN' => $CIN),
                1
            );
            
            // "Erreur! Cette CIN n'existe pas dans la liste!"
            if ($resultList == -1) {
                return false;
            } else {
                return $resultList["role"];
            }
        }

        /*
            MAIN CRUD METHODS
        */
        public function insert(Utilisateur $user) {
            $query = "INSERT INTO utilisateur(CIN, nom, prenom, sexe, adresse, dateNaissance, email, role, activationCode) VALUES (:CIN, :nom, :prenom, :sexe, :adresse, :dateNaissance, :email, :role, :activationCode)"; 
            $secureArray = array( 
                ":CIN" => $user->getCIN(),
                ":nom" => $user->getNom(),
                ":prenom" => $user->getPrenom(),
                ":sexe" => $user->getSexe(),
                ":adresse" => $user->getAdresse(),
                ":dateNaissance" => $user->getDateNaissance(),
                ":email" => $user->getEmail(),  
                ":role" => $user->getRole(),
                ":activationCode" => $user->getActivationCode()
            );

            $user->setMatricule(MySql::request($query, $secureArray, 3));
        }

        public function update($user) {
            $query = "UPDATE utilisateur SET nom = ?, prenom = ?, sexe = ?, adresse = ?, dateNaissance = ? WHERE CIN = ?"; 
            $secureArray = array( 
                $user->nom,
                $user->prenom,
                $user->sexe,
                $user->adresse,
                $user->dateNaissance,
                $user->CIN
            );

            MySql::request($query, $secureArray);
        }

        public function delete($matricule) {
            $query = "DELETE FROM utilisateur WHERE matricule = :matricule"; 
            $secureArray = array( 
                ":matricule" => $matricule
            );

            MySql::request($query, $secureArray);
        }
        /*
            SECONDARY CRUD METHODS
        */

        public function verify_activation_code(string $activationCode, string $email) {
            $responseUser = MySql::request(
                'SELECT CIN, activationCode, DATEDIFF(activationExpiry, now()) as isExpired
                 FROM utilisateur
                 WHERE isActive = 0 AND email = :email',
                array( 
                    ":email" => $email
                ),
                1
            );

            if ($responseUser != -1) {
                // error: ALREADY EXPIRED
                if ((int)$responseUser["isExpired"] === 1) {
                    self::delete($responseUser['matricule']);
                    return 0;
                }
                
                // verify the password
                if ($activationCode == $responseUser['activationCode']) {
                    return $responseUser; // IF VALID, return user;
                }
            }
            
            return -1; // error: USER NOT FOUND
        }
        

        public function activate_user($CIN, $password) {
            MySql::request(
                "UPDATE utilisateur SET isActive = 1, password = :PASS, activationExpiry = NULL WHERE CIN = :CIN",
                array( 
                    ":CIN" => $CIN,
                    ":PASS" => $password
                )
            );

            MySql::request(
                "UPDATE liste_inscription SET isSubscribed = 1 WHERE CIN = :CIN",
                array( 
                    ":CIN" => $CIN
                )
            );
        }
    }