<?php

if(!session_id()) {
    @session_start();
} else {

}

//@TODO change functions to use $options array instead of long constructors
//@TODO add the ability to change sorting
//@TODO getNext should be 1 object only, getNextList should handle pagination
//@TODO add new functions getPrevious, getPreviousList
//@TODO continue commenting
//@TODO modular file structure
//@TODO update should handle more robust datasets (thinks like an array of new data)

//Cool Stuff
//@TODO menu system overhaul - a more robust menu system with children and parents
//@TODO built into framework object chaining for deleting, MYSQL JOIN, and updating of objects
//@TODO creation of tables if they do not exist, automagically.
//@TODO backing up of data automagically
//@TODO page caching system
//@TODO data display should run on angular JS instead, perhaps using HTML5 storage to save selected object, then displaying it on page
//@TODO Email system for emailing datagrams and members
//@TODO re-encode line breaks in phpbb for the web application so that they display correctly on the forums
//@TODO angularJS data sorting using a dropdown

//PERMISSIONS
//@TODO Permissions System Overhaul
//@TODO Method Permissions Level
//@TODO permissions system overhaul+addition - The ability to embed permissions in code in a web page, and specify a name and a pLevel
//@TODO more robust permissions system that allows methods to have a permission set
//@TODO move permissions to the core instead of it being a regular database object
//@TODO all methods/pages should check to see if the there is a permission set, and if not, then ignore that permission
//@TODO guest permissions
//@TODO Groups and Group permissions
//@TODO negative permission levels


class Config{
	static $confArray;

	public static function read($name){
		return self::$confArray[$name];
	}

	public static function write($name, $value){
		self::$confArray[$name] = $value;
	}
}

abstract class DatabaseObject{

    public $loaded = false;
    public $id;

    //Force Extending class to define this method
    //abstract protected function classDataSetup();

    public function __construct($id = null){

        if(is_array($id)){

            if($this->updateObject($id) === false)
                throw new Exception("Object failed to set new variables");
            else
                $this->loaded = true;

        }else if(Core::isJson($id)){

            if($this->updateObject(json_decode($id)) === false)
                throw new Exception("Object Wasn't JSON or failed to set new variables");
            else
                $this->loaded = true;

        }else if(is_object($id)){
            $this->updateObject(get_object_vars($id));
        }else if($id !== null && is_numeric($id))
            $this->load($id);


    }

    // Common methods

    /**
     * Saves a Database Object
     *
     * @param Boolean $fillNull if this is set to true, it will fill all of the data elements that are null with a value
     * @return Boolean true/false based on success of DB insert
     * @throws PDO error if database is unreachable
     */
    public function save($fillNull = false){
        //a list of keys to be iterated though, generated by Object Attribute Names
        $keyChain = $this->getKeyChain();

        //beginning of building the prepared MYSQL insert statement
        $prepareStatement = "INSERT INTO ".get_class($this)." (";

        foreach($keyChain as $val){
            if($val !== "id" && $this->{$val} !== null) //since this is a new object, we don't want to save the ID, rather letting the DB generate an ID
                $prepareStatement .= "$val, ";
            else if($val !== "id" && $fillNull === true)
                $prepareStatement .= "$val, ";

        }

        $prepareStatement = rtrim($prepareStatement, ", ");
        $prepareStatement .= ") VALUES (";

        foreach($keyChain as $val){
            if($val !== "id" && $this->{$val} !== null) //since this is a new object, we don't want to save the ID, rather letting the DB generate an ID
                $prepareStatement .= ":$val, ";
            else if($val !== "id" && $fillNull === true)
                $prepareStatement .= ":$val, ";

        }

        $prepareStatement = rtrim($prepareStatement, ", ");
        $prepareStatement .= ")";

        //at this point, the array should be good to go
        //INSERT INTO fruit (color, count) VALUES (:color, :count)
        //we are going to generate the array of variables to be processed by PDO
        //in example, color and count will be overwritten by PDO safely
        //to learn more, refer to PDO manual for more information on specific PDO procedures

        $executeArray = array();

        //our data is gotten from the iterateVisible class
        $dataArray = $this->toArray();

        foreach ($keyChain as $val) {
            if($val !== "id"){
                if (strpos($val,'date') !== false && $this->{$val} !== null)
                    $executeArray[':'.$val] =  Core::unixToMySQL($dataArray[$val]);
                else if($this->{$val} !== null)
                    $executeArray[':'.$val] = $dataArray[$val];
                else if($fillNull === true)
                    $executeArray[':'.$val] = "";
            }
        }

        try {

            //getting PDO connection and preparing the statement
            $pdo = Core::getInstance();
            $query = $pdo->dbh->prepare($prepareStatement);
            //running the statement with object variables
            $query->execute($executeArray);

            if($query->rowCount() > 0){ //checks to see if there was an object that was inserted into the database
                $this->id = $pdo->dbh->lastInsertId();
                return true;
            }

            return false;

        }catch(PDOException $pe) {
            trigger_error('Could not connect to MySQL database. ' . $pe->getMessage() , E_USER_ERROR);
        }

        return false;
    }

    /**
     * Updates an Database Object
     * @param String $identifier, if an ID isn't being used to update an object, the WHERE clause would go here
     * @param String $reserve_id, if the identifier is an object, then we check for this ID
     * @return Boolean true/false based on success of DB insert
     * @throws PDO error if database is unreachable
     *
     * @TODO update should be more like load, taking in a WHERE clause instead of updating an object.
     */
    public function update($identifier = null, $reserve_id = null){

        if(is_array($identifier)){
            $this->updateObject($identifier);
            $identifier = $reserve_id;
        }

        $keyChain = $this->getKeyChain();

        $prepareStatement = "UPDATE ".get_class($this)." SET ";

        foreach($keyChain as $val){
            if($val !== "id"){
                if($this->{$val} !== null)
                    $prepareStatement .= "$val = :$val, ";
            }
        }

        $prepareStatement = rtrim($prepareStatement, ", ");

        if($identifier !== null){

            foreach($keyChain as $value){

                if(stristr($identifier, $value) !== false)
                    $prepareStatement .= " WHERE {$value} = :{$value}";


            }

        }else{
            $prepareStatement .= " WHERE id = :id";
        }

        $executeArray = array();

        $dataArray = $this->toArray();
        foreach ($keyChain as $val){

            if (strpos($val,'date') !== false && $this->{$val} !== null)
                $executeArray[':'.$val] =  Core::unixToMySQL($dataArray[$val]);
            else if($this->{$val} !== null)
                $executeArray[':'.$val] = $dataArray[$val];

        }

        //string should look like this:
        //UPDATE fruit SET color = :color, count = :count WHERE id = :id

        try {

            $pdo = Core::getInstance();
            $query = $pdo->dbh->prepare($prepareStatement);

            return $query->execute($executeArray);

        }catch(PDOException $pe) {

            trigger_error('Could not connect to MySQL database. ' . $pe->getMessage() , E_USER_ERROR);

        }

        return false;

    }

    /**
     * Erases an object from the database
     * @param String $identifier, the id by which to kill an object
     * @param String $paramName, the name of the thing to kill an object
     * @return Boolean true/false based on success of DB delete
     * @throws PDO error if database is unreachable
     * @TODO give this an options array OR allow for the arrays of each of the params and add to the delete clause
     * @TODO create a static version of this method
     */
    public function erase($identifier = null, $paramName = null){

        if($identifier === null)
            $identifier = $this->id;

        if($paramName === null)
            $paramName = "id";

        $prepareStatement = "DELETE FROM ".get_class($this)." WHERE $paramName = :id";
        $executeArray = array(':id' => $identifier);

        try {

            $pdo = Core::getInstance();
            $query = $pdo->dbh->prepare($prepareStatement);

            $query->execute($executeArray);

            if($query->rowCount() > 0)
                return true;


            return false;

        }catch(PDOException $pe) {

            trigger_error('Could not connect to MySQL database. ' . $pe->getMessage() , E_USER_ERROR);

        }

        return false;

    }

    /**
     * Loads an object from the database
     * @param String $data, is the thing to be checked, (1, pizza)
     * @param Array $options, the name of the thing to kill an object
        * type is the name of the thing in the DB, (id, food_name)
        * limit is the number of returned results
        * return_object is if the object should be loaded into the current object, or if it should return a new instance.
     * @return Boolean true/false based on success of DB delete, or an OBJECT if return object is true;
     * @throws PDO error if database is unreachable
     * @defaults
        * $options = array($type = null, $limit = 1, $return_object = true)
     *
     * @TODO one should be able to load with $data = null, using the data inside the current object
     * @TODO $data could be an array as well, using keys as type instead
     */

    public function load($data, $options = array('type' => null, 'limit' => 1, 'return_object' => false, 'selector' => "AND")){

        if(!is_array($options)){
            $options = array('type' => $options);
        }

        if(!isset($options['type']))
            $options['type'] = null;
        if(!isset($options['cost']))
            $options['limit'] = 1;
        if(!isset($options['return_object']))
            $options['return_object'] = false;
        if(!isset($options['selector']))
            $options['selector'] = "AND";

        if($options['selector'] !== "AND" || $options['selector'] !== "OR")
            $options['selector'] = "AND";

        //a list of keys to be iterated though, generated by Object Attribute Names
        $keyChain = $this->getKeyChain();
        $condition = null;
        $prepareStatement = "SELECT * FROM ".get_class($this)." WHERE ";
        $execArray = null;

        if(is_array($data)){

            $condition = array();
            $execArray = array();

            if(is_array($options['type'])){

                foreach($options['type'] as $value){
                    foreach($keyChain as $val){

                        if($value === $val && stristr($value, 'password') === false){
                           array_push($condition, $val);
                        }
                    }
                }

            }else if($options['type'] !== null){

                for($i = 1; $i < count ($data); $i++){
                    array_push($condition, $options['type']);
                }

            }else{
                for($i = 1; $i < count ($data); $i++){
                    array_push($condition, 'id');
                }
            }

            foreach($condition as $key => $value){

                foreach($keyChain as $val){

                    if(stristr($value, 'password') !== false)
                        return false;

                    if($val == 'id'){

                        if($value == $val){
                            $prepareStatement .= "{$val} = :var$key AND ";
                            $execArray[":var$key"] = "$data[$key]";
                        }

                    }else if(stristr($value, $val) !== false){
                        $prepareStatement .= "{$val} = :var$key AND ";
                        $execArray[":var$key"] = "$data[$key]";
                    }

                }

            }

            $prepareStatement = rtrim($prepareStatement, " AND ");

            $prepareStatement .= " LIMIT {$options['limit']}";

        }else{ //if data is not an array
            if($options['type'] !== null){
                foreach($keyChain as $val){

                    if(stristr($options['type'], $val) !== false){
                        $condition = $val;
                        break;
                    }

                    if(stristr($options['type'], 'password') !== false){
                        $condition = null;
                        break;
                    }

                }

            }else{
                $condition = 'id';
            }

            $prepareStatement .= " {$condition} = :var";

            $execArray = array(':var' => $data);
        }

        if($condition === null || $execArray === null)
            return false;

        try {

            $pdo = Core::getInstance();
            $query = $pdo->dbh->prepare($prepareStatement);

            $query->execute($execArray);

            if($query->rowCount() === 0)
                return false;

            if($options['return_object']){

                $newInstance = $query->fetchObject(get_class($this));

                if(!is_object($newInstance) && !is_array($newInstance))
                    return false;

                $newInstance->loaded = true;

                return $newInstance;

            }else if($options['return_object'] === false){

                $query->setFetchMode(PDO::FETCH_INTO, $this);
                $query->execute();
                $query->fetch();
                $query->closeCursor();

                $this->loaded = true;

                return true;

            }


        }catch(PDOException $pe) {

            trigger_error('Could not connect to MySQL database. ' . $pe->getMessage() , E_USER_ERROR);

        }

        return false;
    }

    /**
     * Loads an object with a given ID
     * returns the object loaded, instead of setting ($this) to the loaded object
     * I'm fine with this method, since it cuts back on a lot of other options if we want an instance of a class
     */
    public static function returnInstance($data, $options = array('return_object' => true)){

        $options['return_object'] = true;

        $objectType = get_called_class();
        $object = new $objectType();

        return $object->load($data, $options);

    }

    /**
     * returns a new instance of the next object from an id sorted list
     * @param String $current_id, is the ID of the object to start the Comparison
     * @param String $mode, is what mode to get the objects from, next or prev
     * @return Boolean false based on success, an OBJECT
     * @throws PDO error if database is unreachable
     * @defaults
        * $current_id = null
        * $mode = "next"
     *
     * @TODO better configuration and more customizability in regards to generating the list via sorting and more where clauses
     */

    public function getAdjacent($current_id = null, $mode = "next"){

        if($current_id === null)
            $current_id = $this->id;

        $prepare = "SELECT * FROM ".get_class($this)." WHERE id = ";

        if($mode === "next")
            $prepare .= "(SELECT MIN(id) FROM ".get_class($this)." WHERE id > :cur_id) LIMIT 1";
        else if($mode === "prev")
            $prepare .= "(SELECT MAX(id) FROM ".get_class($this)." WHERE id < :cur_id) LIMIT 1";

        try {

            $pdo = Core::getInstance();
            $query = $pdo->dbh->prepare($prepare);
            $query->execute(array(':cur_id' => $current_id));
            $newInstance = $query->fetchObject(get_class($this));

            return (is_object($newInstance)) ? $newInstance : false;

        }catch(PDOException $pe) {

            trigger_error('Could not connect to MySQL database. ' . $pe->getMessage() , E_USER_ERROR);

        }

        return false;
    }

    public function getPrev($current_id = null){
        return $this->getAdjacent($current_id, "prev");
    }

    public function getNext($current_id = null){
        return $this->getAdjacent($current_id, "next");
    }

    //@TODO condition statement should instead be an array, with a key used as the var name.
    //@todo function should be static

    //sorting is how the list is sorted.
    //condition is an array of values to add to there where clause

    public function getList($sorting = null, $condition = null){

        $prepare = "SELECT * FROM ".get_class($this)." WHERE ";

        if(is_array($condition)){
            foreach($condition as $key => $var){

                $prepare .= " {$key} = :{$key} AND";

            }

            $prepare = rtrim($prepare, " AND");

            foreach($condition as $key => $var){

                $execute[":$key"] = $var;

            }

        }else{
            $prepare = rtrim($prepare, " WHERE ");
        }

        if($sorting != null){

            $keyChain = $this->getKeyChain();

            foreach($keyChain as $value){

                if(stristr($sorting, $value) !== false){

                    if(stristr($sorting, 'asc') !== false)
                        $prepare .= " ORDER BY {$value} ASC";
                    else if(stristr($sorting, 'desc') !== false)
                        $prepare .= " ORDER BY {$value} DESC";
                    else
                        $prepare .= " ORDER BY {$value} DESC";

                    break;

                }
            }
        }

        try {

            $pdo = Core::getInstance();
            $query = $pdo->dbh->prepare($prepare);

            if(isset($execute))
                $query->execute($execute);
            else
                $query->execute();


            $objects = $query->fetchAll(PDO::FETCH_CLASS, get_class($this));

            if($objects === false)
                return false;


            return $objects;

        }catch(PDOException $pe) {

            trigger_error('Could not connect to MySQL database. ' . $pe->getMessage() , E_USER_ERROR);

        }

        return false;
    }

    public function getKeyChain(){

        $array = array();
        $count = 0;

        foreach($this as $key => $value) {

            if($key !== "loaded")
                $array[$count++] = $key;

        }

        unset($count);

        return $array;

    }

    public function toArray(){

        $array = array();

        foreach($this as $key => $value) {

            if($key !== "loaded")
                $array[$key] = $value;

        }

        return $array;
    }

    public function updateObject($array){
        $keyChain = $this->toArray();

        if(is_object($array))//converts json to an array
            $array = (array) $array;

        foreach($array as $key => $value){

            if(!array_key_exists($key, $keyChain))
                unset($array[$key]);
            else if($key !== "loaded")
                $this->{$key} = $value;

            if(strpos($key,'date') !== false && (Core::isValidDateTimeString($value) || $value instanceof DateTime))
                $this->{$key} = Core::unixToMySQL($value);
            else if(strpos($key,'date') !== false && !(Core::isValidDateTimeString($value) || $value instanceof DateTime))
                $this->{$key} = Core::unixToMySQL("now");

        }

        foreach($this as $key => $value) {

            if(isset($array[$key])){
                if(strpos($key,'date') !== false && !(Core::isValidDateTimeString($this->{$key}) || $this->{$key} instanceof DateTime))
                    return false;
                else if($this->{$key} !== $array[$key] && $key !== "loaded" && strpos($key,'date') === false)
                    return false;
            }

        }

        return true;
    }

    /**
     * Updates an object with an array of matched data
     *
     * @deprecated
     */
    public function toObject($array){
       return $this->updateObject($array);
    }

    //grabs a json string and converts it to the object

    //Magic Methods
    //serialize
    public function __sleep(){

        return $this->toArray();

    }

    public function __toString(){

        return json_encode($this->toArray());

    }

    public function __invoke($dataArray){

        $object = get_class($this);

        return new $object($dataArray);

    }

    /**
     * Loads a selected object from the database
     * @param Mixed $id is either the Id of an object in the database, or the object itself
     * @param Boolean $default is what the default, if there is no selected object, should be;
     * @return Object of selected class name;
     * @throws PDO error if database is unreachable
     */
    public static function selected($id = null, $default = false){

        $className = get_called_class();

        if($id === null){
            $selected = (isset($_SESSION['selected_'.$className])) ? new $className($_SESSION['selected_'.$className]) : (is_object($default) ? $default : new $className($default));

        }else{

            if(is_object($id)){
                $selected = $id;
            }else{
                $selected = new $className($id);
            }

            $_SESSION['selected_'.$className] = $selected->toArray();

        }

        return $selected;

    }



}


class Core{

	public $dbh;
	private static $instance;

	public function __construct(){

		$dsn = 'mysql:host=' . Config::read('db.host') .
			';dbname='    . Config::read('db.base') .
			';connect_timeout=15';

		$this->dbh = new PDO($dsn, Config::read('db.user'), Config::read('db.password'), array(PDO::ATTR_PERSISTENT => true));
		$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //PDO::ERRMODE_SILENT
		$this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
	}

	public static function getInstance(){
		if (!isset(self::$instance)){
			$object = __CLASS__;
			self::$instance = new $object;
		}

		return self::$instance;
	}

    public static function clean($data){
        return htmlspecialchars(mysql_real_escape_string($data));
    }

    public static function isValidDateTimeString($str_dt, $str_dateFormat = null, $str_timezone = null) {

        $result = false;

        if(!is_string($str_dt))
            return false;

        if($str_timezone === null)
            $str_timezone = self::getTimezone();
        else if(is_string($str_timezone))
            $str_timezone = new DateTimeZone($str_timezone);

        if($str_dateFormat === null){
            $str_dateFormat = 'Y-m-d H:i:s';
            $result = self::isValidDateTimeString($str_dt,'Y-m-d');
        }

        $date = DateTime::createFromFormat($str_dateFormat, $str_dt, $str_timezone);

        return (($date && DateTime::getLastErrors()["warning_count"] == 0 && DateTime::getLastErrors()["error_count"] == 0) || $result);

    }

    public static function unixToMySQL($timestamp){

        if($timestamp instanceof DateTime)
            $timestamp = $timestamp->format('Y-m-d H:i:s');

        if(strtotime($timestamp) === false)
            return date('Y-m-d H:i:s', $timestamp);

        return date('Y-m-d H:i:s', strtotime($timestamp));

    }

    public static function isJson($string){
        // make sure provided input is of type string
        if (!is_string($string))
            return false;

        // trim white spaces
        $string = trim($string);

        // get first character/last character
        $firstChar = substr($string, 0, 1);
        $lastChar = substr($string, -1);

        // check if there is a first and last character
        if (!$firstChar || !$lastChar)
            return false;

        // make sure first character is either { or [
        if ($firstChar !== '{' && $firstChar !== '[')
            return false;

        // make sure last character is either } or ]
        if ($lastChar !== '}' && $lastChar !== ']')
            return false;

        // let's leave the rest to PHP.
        // try to decode string
        json_decode($string);

        return (json_last_error() === JSON_ERROR_NONE);
    }

    public static function sendEmail($subject, $message, $recipient){

        $headers = "From: no-reply<noreply@whats-your-confidence.com>\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

        if(@mail($recipient, $subject, $message, $headers)){
            return true;
        }

        return false;

    }

    public static function getTimezone(){
        return new DateTimeZone('America/New_York');
    }

    public static function base64_url_encode($input){
        return strtr(base64_encode($input), '+/=', '-_,');
    }

    public static function formatNumber($number, $type = "ordinal"){//ordinal suffix

        $ends = array('th','st','nd','rd','th','th','th','th','th','th');

        if($number == "N/A")
            return $number;

        return (($number %100) >= 11 && ($number%100) <= 13) ? $number.'th' : $number.$ends[$number % 10];

    }



}

?>